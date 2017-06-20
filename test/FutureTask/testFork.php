<?php

include __DIR__ . "/../../vendor/autoload.php";

//\FSth\Co\Call::go(function () {
//    $start = microtime(true);
//
//    $ch = \FSth\Co\Call::chan();
//
//    \FSth\Co\Call::spawn(function () use ($ch) {
//        yield \FSth\Co\Tool::asyncSleep(1000);
//        yield $ch->send(42);
//    });
//
//    yield \FSth\Co\Tool::asyncSleep(500);
//    $r = (yield $ch->recv());
//    echo $r."\n";
//
//    echo "cost ", microtime(true) - $start, "\n";
//});

\FSth\Co\Call::go(function () {
    $start = microtime(true);
    $future = (yield \FSth\Co\Call::fork(function () {
        yield \FSth\Co\Tool::asyncSleep(1000);
        yield 42;
    }));

    try {
        $r = (yield $future->get(100));
        var_dump($r);
    } catch (\Exception $e) {
        echo "get result timeout\n";
    }

    yield \FSth\Co\Tool::asyncSleep(1000);

    echo "cost ", microtime(true) - $start, "\n";
});

\FSth\Co\Call::go(function () {
    $start = microtime(true);

    $future = (yield \FSth\Co\Call::fork(function () {
        yield \FSth\Co\Tool::asyncSleep(500);
        yield 42;
        throw new \Exception();
    }));

    yield \FSth\Co\Tool::asyncSleep(1000);

    try {
        $r = (yield $future->get());
        var_dump($r);
    } catch (\Exception $e) {
        echo "something wrong in child task\n";
    }

    echo "cost ", microtime(true) - $start, "\n";
});