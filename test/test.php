<?php

include __DIR__ . "/../vendor/autoload.php";

use FSth\Co\Call;

//function newSubGen()
//{
//    yield 0;
//    throw new \Exception("e");
//    yield 1;
//}

//
//function newGen()
//{
//    $r1 = (yield newSubGen());
//    $r2 = (yield 2);
//    echo $r1, $r2;
//    yield 3;
//}
//
//$task = new \FSth\Co\AsyncTask(newGen());
//$trace = function ($r) {
//    echo $r;
//};
//$task->begin($trace);

//class AsyncSleep implements \FSth\Co\Async
//{
//    public function begin(callable $cc)
//    {
//        swoole_timer_after(1000, $cc);
//    }
//}
//
//class AsyncDns implements \FSth\Co\Async
//{
//    public function begin(callable $cc)
//    {
//        swoole_async_dns_lookup("www.baidu.com", function ($host, $ip) use ($cc) {
//            $cc($ip);
//        });
//    }
//}
//
//function newGen()
//{
////    $r1 = (yield 1);
////    throw new \Exception("e");
////    $r2 = (yield 2);
////    yield 3;
////    $r1 = (yield newSubGen());
////    $r2 = (yield 2);
////    $start = time();
////    yield new AsyncSleep();
////    echo time() - $start, "\n";
////    $ip = (yield new AsyncDns());
////    yield "Ip: $ip";
//    try {
//        $r1 = (yield newSubGen());
//        yield $r1;
//    } catch (\Exception $e) {
//        echo $e->getMessage();
//    }
//    $r2 = (yield 2);
//    yield 3;
//}
//
//$task = new \FSth\Co\AsyncTask(newGen());
//
//$trace = function ($r, $ex) {
//    if ($ex){
//        echo $ex->getMessage();
//    } else {
//        echo $r;
//    }
//};
//
//$task->begin($trace);

//try {
//    $task->begin($trace);
//} catch (\Exception $e) {
//    echo $e->getMessage();
//}


//function getCtx($key, $default = null)
//{
//    return new \FSth\Co\SysCall(function (\FSth\Co\AsyncTask $task) use ($key, $default) {
//        while ($task->parent && $task = $task->parent) ;
//        if (isset($task->gen->generator->$key)) {
//            return $task->gen->generator->$key;
//        }
//        return $default;
//    });
//}
//
//function setCtx($key, $val)
//{
//    return new \FSth\Co\SysCall(function(\FSth\Co\AsyncTask $task) use($key, $val) {
//        while($task->parent && $task = $task->parent);
//        $task->gen->generator->$key = $val;
//    });
//}
//
//function setTask()
//{
//    yield setCtx("foo", "bar");
//}
//
//function ctxTest()
//{
//    yield setTask();
//    $foo = (yield getCtx("foo"));
////    echo $foo;
//}
//
//$task = new \FSth\Co\AsyncTask(ctxTest());
//$task->begin($trace);

function async_sleep($ms)
{
    return Call::callCC(function ($k) use ($ms) {
        swoole_timer_after($ms, function () use ($k) {
            $k(null);
        });
    });
}

function async_dns_lookup($host)
{
    return Call::callCC(function ($k) use ($host) {
        swoole_async_dns_lookup($host, function ($host, $ip) use ($k) {
            $k($ip);
        });
    });
}

class HttpClient extends swoole_http_client
{
    public function async_get($uri)
    {
        return Call::callCC(function ($k) use ($uri) {
            $this->get($uri, $k);
        });
    }

    public function async_post($uri, $post)
    {
        return Call::callCC(function ($k) use ($uri, $post) {
            $this->post($uri, $post, $k);
        });
    }

    public function async_execute($uri)
    {
        return Call::callCC(function ($k) use ($uri) {
            $this->execute($uri, $k);
        });
    }
}

Call::spawn(function () {
    $ip = (yield async_dns_lookup("www.baidu.com"));
    $cli = new HttpClient($ip, 80);
    $cli->setHeaders(["foo" => "bar"]);
    $cli = (yield $cli->async_get("/"));
    echo $cli->body, "\n";
});