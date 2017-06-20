<?php

include __DIR__ . "/vendor/autoload.php";

use FSth\Co\Call;

function async_dns_lookup($host, $ms = 100)
{
    return Call::race([
        Call::callCC(function ($k) use ($host) {
            swoole_async_dns_lookup($host, function ($host, $ip) use ($k) {
                $k($ip);
            });
        }),
        Call::timeout($ms)
    ]);
}

function async_dns_lookup_v2($host)
{
    return Call::callCC(function ($k) use ($host){
        swoole_async_dns_lookup($host, function ($host, $ip) use ($k) {
            $k($ip);
        });
    });
}

function timeout($ms)
{
    return Call::callCC(function ($k) use ($ms) {
        swoole_timer_after($ms, function () use ($k) {
            $k(null, new \Exception("timeout"));
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

    public function awaitGet($uri, $timeout = 1000)
    {
        return Call::race([
            Call::callCC(function ($k) use ($uri) {
                $this->get($uri, $k);
            }),
            timeout($timeout)
        ]);
    }
}

Call::spawn(function () {
    try {
//        $ip = (yield async_dns_lookup("www.baidu.com", 1));
//        echo $ip;

//        $r = (yield Tool::all([
//            async_dns_lookup_v2("www.baidu.com"),
//            async_dns_lookup_v2("www.weibo.com")
//        ]));

        $r = (yield Call::all([
            async_dns_lookup("www.baidu.com"),
            async_dns_lookup("www.weibo.com")
        ]));
        var_dump($r);
    } catch (\Exception $e) {
        echo $e->getMessage();
    }

});

//Tool::spawn(function () {
//    try {
//        $ip = (yield Tool::race([
//            async_dns_lookup("www.baidu.com"),
////            timeout(100)
//        ]));
//
////        $ip = (yield async_dns_lookup("www.baidu.com", 100));
//
////        $res = (yield (new HttpClient($ip, 80))->awaitGet("/"));
//
////        $res = (yield Tool::race([
////            (new HttpClient($ip, 80))->async_get("/"),
////            timeout(200)
////        ]));
////        var_dump($res->statusCode);
//    } catch (\Exception $e) {
//        echo $e->getMessage();
//    }
//
//    swoole_event_exit();
//});

//Tool::spawn(function () {
//    $e = null;
//    try {
//        $r = (yield Tool::all([
//            async_dns_lookup("www.bing.com", 100),
////            async_dns_lookup("www.so.com", 100),
////            async_dns_lookup("www.baidu.com", 100)
//        ]));
////        $r = (yield async_dns_lookup("www.bing.com", 100));
//        var_dump($r);
//    } catch (\Exception $e) {
//        echo $e;
//    }
//});