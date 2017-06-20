<?php

namespace FSth\Co;

class SysCall
{
    private $fun;

    public function __construct(callable $fun)
    {
        $this->fun = $fun;
    }

    public function __invoke(AsyncTask $task)
    {
        // TODO: Implement __invoke() method.
        $cb = $this->fun;
        return $cb($task);
    }
}