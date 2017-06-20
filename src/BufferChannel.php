<?php

namespace FSth\Co;

class BufferChannel
{
    // 缓存容量
    protected $capacity;

    // 缓存
    protected $queue;

    protected $receiveCC;

    protected $sendCC;

    public function __construct($capacity)
    {
        assert($capacity > 0);
        $this->capacity = $capacity;
        $this->queue = new \SplQueue();
        $this->sendCC = new \SplQueue();
        $this->receiveCC = new \SplQueue();
    }

    public function recv()
    {
        return Call::callCC(function ($cc) {
            if ($this->queue->isEmpty()) {
                $this->receiveCC->enqueue($cc);
            } else {
                $val = $this->queue->dequeue();
                $this->capacity++;
                $cc($val, null);
            }

            // 递归唤醒其他被阻塞的发送者与接收者收发数据，注意顺序
            $this->receivePingPong();
        });
    }

    public function send($val)
    {
        return Call::callCC(function ($cc) use ($val) {
            if ($this->capacity > 0) {
                $this->queue->enqueue($val);
                $this->capacity--;
                $cc(null, null);
            } else {
                $this->sendCC->enqueue([$cc, $val]);
            }

            // 递归唤醒其他被阻塞的发送者与接收者收发数据，注意顺序
            $this->sendPingPong();

        });
    }

    private function receivePingPong()
    {
        // 当有阻塞的发送者，唤醒其发送数据
        if (!$this->sendCC->isEmpty() && $this->capacity > 0) {
            list($sendCC, $val) = $this->sendCC->dequeue();
            $this->queue->enqueue($val);
            $this->capacity--;
            $sendCC(null, null);

            // 当有阻塞的接收者，唤醒其接收数据
            if (!$this->receiveCC->isEmpty() && !$this->queue->isEmpty()) {
                $receiveCc = $this->receiveCC->dequeue();
                $val = $this->queue->dequeue();
                $this->capacity++;
                $receiveCc($val);

                $this->receivePingPong();
            }
        }
    }

    private function sendPingPong()
    {
        // 当有阻塞的接收者，唤醒其接收数据
        if (!$this->receiveCC->isEmpty() && !$this->queue->isEmpty()) {
            $receiveCC = $this->receiveCC->dequeue();
            $val = $this->queue->dequeue();
            $this->capacity++;
            $receiveCC($val);
        }

        // 当有阻塞的发送者，唤醒其发送数据
        if (!$this->sendCC->isEmpty() && $this->capacity > 0) {
            list($sendCC, $val) = $this->sendCC->dequeue();
            $this->queue->enqueue($val);
            $this->capacity--;
            $sendCC(null, null);

            $this->sendPingPong();
        }
    }
}