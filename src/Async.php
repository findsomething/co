<?php

namespace FSth\Co;

interface Async
{
    /**
     * @param callable $continuation :: (mixed $r, \Exception $e) -> void
     * @return mixed
     */
    public function begin(callable $continuation);
}