<?php

namespace FSth\Co;

class Gen
{
    public $isFirst = true;
    public $generator;

    public function __construct(\Generator $generator)
    {
        $this->generator = $generator;
    }

    public function valid()
    {
        return $this->generator->valid();
    }

    public function send($value = null)
    {
        if ($this->isFirst) {
            $this->isFirst = false;
            return $this->generator->current();
        }
        return $this->generator->send($value);
    }

    public function throw_(\Exception $e)
    {
        return $this->generator->throw($e);
    }
}