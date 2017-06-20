<?php

namespace FSth\Co;

final class AsyncTask implements Async
{
    public $gen;
    public $continuation;
    public $parent;

    public function __construct(\Generator $gen, AsyncTask $parent = null)
    {
        $this->gen = new Gen($gen);
        $this->parent = $parent;
    }

    public function begin(callable $continuation)
    {
        $this->continuation = $continuation;
        $this->next();
    }

    public function next($result = null, \Exception $e = null)
    {
        try {
            if ($e) {
                $this->gen->throw_($e);
            }

            $value = $this->gen->send($result);

            if ($this->gen->valid()) {

                if ($value instanceof SysCall) {
                    $value = $value($this);
                }

                if ($value instanceof \Generator) {
                    $value = new self($value, $this);
                }

                if ($value instanceof Async) {
                    $cc = [$this, "next"];
                    $value->begin($cc);
                } else {
                    $this->next($value, null);
                }
            } else {
                $cc = $this->continuation;
                $cc($result, null);
            }
        } catch (\Exception $e) {
            if ($this->gen->valid()) {
                $this->next(null, $e);
            } else {
                $cc = $this->continuation;
                $cc(null, $e);
            }
        }
    }
}