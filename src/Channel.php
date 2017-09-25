<?php

namespace FSth\Co;

class Channel
{
    // 因为同一个channel可能有多个接收者，使用队列实现，保证调度均衡
    // 队列内保存的是被阻塞的接收者协程的控制流，即call/cc的参数,我们模拟的continuation
    protected $recvQ;
    protected $sendQ;

    public function __construct()
    {
        $this->recvQ = new \SplQueue();
        $this->sendQ = new \SplQueue();
    }

    public function send($val)
    {
        return Call::callCC(function ($cc) use ($val) {
            if ($this->recvQ->isEmpty()) {
                // 当chan没有接收者，发送者协程挂起(将$cc入列,不调用$cc回送数据)
                $this->sendQ->enqueue([$cc, $val]);
            } else {
                // 当chan对端有接收者，将挂起接收者协程出列,
                // 调用接收者$recvCc发送数据，运行接收者协程后继代码
                // 执行完毕或者遇到Async挂起，$recvCc()调用返回,
                // 调用$cc()，控制流回到发送者协程
                $receiveCC = $this->recvQ->dequeue();
                $receiveCC($val, null);
                $cc(null, null);
            }
        });
    }

    public function recv()
    {
        return Call::callCC(function ($cc) {
            if ($this->sendQ->isEmpty()) {
                $this->recvQ->enqueue($cc);
            } else {
                // 当chan对端有发送者，将挂起发送者协程与待发送数据出列
                // 调用发送者$sendCc发送数据，运行发送者协程后继代码
                // 执行完毕或者遇到Async挂起，$sendCc()调用返回,
                // 调用$cc()，控制流回到接收者协程
                list($sendCC, $val) = $this->sendQ->dequeue();
                $sendCC(null, null);
                $cc($val, null);
            }
        });
    }
}