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

                // 当无数据可接收时，$cc入列,让出控制流，挂起接收者协程
                $this->receiveCC->enqueue($cc);
            } else {

                // 当有数据可接收时，先接收数据，然后恢复控制流
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

            // 当缓存未满，发送数据直接加入缓存，然后恢复控制流
            if ($this->capacity > 0) {
                $this->queue->enqueue($val);
                $this->capacity--;
                $cc(null, null);
            } else {

                // 当缓存满，发送者控制流与发送数据入列，让出控制流,挂起发送者协程
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