<?php

namespace FSth\Co;

final class FutureTask
{
    const PENDING = 1;
    const DONE = 2;
    const TIMEOUT = 3;

    private $timerId;
    private $cc;

    private $state;
    private $result;
    private $ex;

    public function __construct(\Generator $gen, AsyncTask $parent = null)
    {
        $this->state = self::PENDING;

        if ($parent) {
            $asyncTask = new AsyncTask($gen, $parent);
        } else {
            $asyncTask = new AsyncTask($gen);
        }

        $asyncTask->begin(function ($r, $ex = null) {
            if ($this->state === self::TIMEOUT) {
                return;
            }
            $this->state = self::DONE;

            if ($cc = $this->cc) {
                if ($this->timerId) {
                    swoole_timer_clear($this->timerId);
                }
                $cc($r, $ex);
            } else {
                $this->result = $r;
                $this->ex = $ex;
            }
        });
    }

    public function get($timeout = 0)
    {
        return Call::callCC(function ($cc) use ($timeout) {
            if ($this->state == self::DONE) {
                // 获取结果时，任务已经完成，同步返回结果
                // 这里也可以考虑用defer实现，异步返回结果，首先先释放php栈，降低内存使用
                $cc($this->result, $this->ex);
            } else {
                $this->cc = $cc;
                $this->getResultTimeout($timeout);
            }
        });
    }

    private function getResultTimeout($timeout)
    {
        if (!$timeout) {
            return;
        }

        $this->timerId = swoole_timer_after($timeout, function () {
            assert($this->state == self::PENDING);
            $this->state = self::TIMEOUT;
            $cc = $this->cc;
            $cc(null, new \Exception("timeout"));
        });
    }
}