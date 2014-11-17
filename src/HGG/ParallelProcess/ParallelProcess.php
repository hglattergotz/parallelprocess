<?php

namespace HGG\ParallelProcess;

use Symfony\Component\Process\Process;

/**
 * Keep a configurable number of processes running at all times
 *
 * @author Henning Glatter-Götz <henning@glatter-gotz.com>
 */
class ParallelProcess
{
    /**
     * A callback that returns either true or false to indicate whether or not
     * spawning can commence
     *
     * @var mixed
     * @access protected
     */
    protected $condCallback;

    /**
     * cmd
     *
     * @var mixed
     * @access protected
     */
    protected $cmd;

    /**
     * maxCount
     *
     * @var mixed
     * @access protected
     */
    protected $maxCount;

    /**
     * sleepTime
     *
     * @var mixed
     * @access protected
     */
    protected $sleepTime;

    /**
     * spawns
     *
     * @var mixed
     * @access protected
     */
    protected $spawns;

    /**
     * shutdown
     *
     * @var mixed
     * @access protected
     */
    protected $shutdown;

    /**
     * __construct
     *
     * @param obj    $condition A callback that returns a boolean. If true then
     *                          this indicates spawning should start.
     * @param string $cmd
     * @param int    $maxCount
     * @param int    $sleepTime

     * @access public
     * @return void
     */
    public function __construct($condition, $cmd, $maxCount, $sleepTime)
    {
        $this->condCallback = $condition;
        $this->cmd          = $cmd;
        $this->maxCount     = $maxCount;
        $this->sleepTime    = $sleepTime;
        $this->spawns       = array();
        $this->shutdown     = false;

        // Setup signal handlers for shutting down the process
        declare(ticks = 1);
        pcntl_signal(SIGTERM, array($this, 'shutdown'));
        pcntl_signal(SIGINT, array($this, 'shutdown'));
    }

    /**
     * run
     *
     * @access public
     * @return void
     */
    public function run()
    {
        while (!$this->shutdown) {
            $spawnCount = count($this->spawns);

            if ($spawnCount < $this->maxCount) {
                $this->spawn($this->maxCount - $spawnCount);
            }

            $this->cleanup();

            sleep($this->sleepTime);
        }

        while (0 < count($this->spawns)) {
            $this->cleanup();
        }
    }

    /**
     * shutdown
     *
     * @access public
     * @return void
     */
    public function shutdown()
    {
        printf("\nSHUTDOWN\n");
        $this->shutdown = true;
    }

    /**
     * Delete processes (spawns) that are done
     *
     * @access protected
     * @return void
     */
    protected function cleanup()
    {
        foreach ($this->spawns as $key => $spawn) {
            if (!$spawn->isRunning()) {
                unset($this->spawns[$key]);
            }
        }
    }

    /**
     * Spawn a number of processes
     *
     * @param int $count The number of processes to spawn

     * @access protected
     * @return void
     */
    protected function spawn($count)
    {
        for ($i = 0; $i < $count; ++$i) {
            $proc = new Process($this->cmd);
            $proc->start();
            $this->spawns[] = $proc;
        }
    }
}
