<?php

namespace FSth\Co;

class Call
{
    /**
     * spawn one semicoroutine
     *
     *  param callable|\Generator|mixed $task
     *  param callable $continuation function($r = null, $ex = null) {}
     *  param AsyncTask $parent
     *  param array $ctx Context也可以附加在 \Generator 对象的属性上
     *
     *  第一个参数为task
     *  剩余参数(优先检查callable)
     *      如果参数类型 callable 则参数被设置为 Continuation
     *      如果参数类型 AsyncTask 则参数被设置为 ParentTask
     *      如果参数类型 array 则参数被设置为 Context
     */
    public static function spawn()
    {
        $n = func_num_args();
        if ($n === 0) {
            return;
        }

        $task = func_get_arg(0);
        $continuation = function () {
        };
        $parent = null;
        $ctx = [];

        for ($i = 1; $i < $n; $i++) {
            $arg = func_get_arg($i);
            if (is_callable($arg)) {
                $continuation = $arg;
            } else if ($arg instanceof AsyncTask) {
                $parent = $arg;
            } else if (is_array($arg)) {
                $ctx = $arg;
            }
        }

        if (is_callable($task)) {
            try {
                $task = $task();
            } catch (\Exception $e) {
                $continuation(null, $e);
                return;
            }
        }

        if ($task instanceof \Generator) {
            foreach ($ctx as $k => $v) {
                $task->$k = $v;
            }
            (new AsyncTask($task, $parent))->begin($continuation);
        } else {
            $continuation($task, null);
        }
    }

    public static function once(callable $fun)
    {
        $has = false;
        return function (...$args) use ($fun, &$has) {
            if ($has === false) {
                $fun(...$args);
                $has = true;
            }
        };
    }

    public static function timeoutWrapper(callable $fun, $timeout)
    {
        return function ($k) use ($fun, $timeout) {
            $k = self::once($k);
            $fun($k);
            swoole_timer_after($timeout, function () use ($k) {
                $k(null, new \Exception("timeout"));
            });
        };
    }

    public static function callCC(callable $fn)
    {
        return new CallCC($fn);
    }

    public static function timeout($ms)
    {
        return self::callCC(function ($k) use ($ms) {
            swoole_timer_after($ms, function () use ($k) {
                $k(null, new \Exception("timeout"));
            });
        });
    }

    public static function await($task, ...$args)
    {
        if ($task instanceof \Generator) {
            return $task;
        }

        if ($task instanceof SysCall) {
            $gen = function () use ($task) {
                yield $task;
            };
        } else if (is_callable($task)) {
            $gen = function () use ($task, $args) {
                yield $task(...$args);
            };
        } else {
            $gen = function () use ($task) {
                yield $task;
            };
        }

        return $gen();
    }

    public static function race(array $tasks)
    {
        $tasks = array_map('self::await', $tasks);

        return new SysCall(function (AsyncTask $parent) use ($tasks) {
            if (empty($tasks)) {
                return null;
            }
            return new Any($tasks, $parent);
        });
    }

    public static function all(array $tasks)
    {
        $tasks = array_map('self::await', $tasks);

        return new SysCall(function (AsyncTask $parent) use ($tasks) {
            if (empty($tasks)) {
                return null;
            }
            return new All($tasks, $parent);
        });
    }

    public static function go(...$args)
    {
        self::spawn(...$args);
    }

    public static function chan($n = 0)
    {
        if ($n === 0) {
            return new Channel();
        }
        return new BufferChannel($n);
    }

    public static function fork($task)
    {
        $task = self::await($task);
        return new SysCall(function (AsyncTask $parent) use ($task) {
            return new FutureTask($task, $parent);
        });
    }
}