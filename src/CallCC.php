<?php

namespace FSth\Co;

class CallCC implements Async
{
    public $fun;

    public function __construct(callable $fun)
    {
        $this->fun = $fun;
    }

    public function begin(callable $continuation)
    {
        // TODO: Implement begin() method.
        $fun = $this->fun;
        $fun($continuation);
    }
}