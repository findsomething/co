<?php

include __DIR__ . "/../../vendor/autoload.php";

function async_sleep($ms)
{
    return \FSth\Co\Call::callCC(function ($k) use ($ms) {
        swoole_timer_after($ms, function () use ($k) {
            $k(null);
        });
    });
}

$pingCh = \FSth\Co\Call::chan();
$pongCh = \FSth\Co\Call::chan();

\FSth\Co\Call::go(function () use ($pingCh, $pongCh) {
    while (true) {
        echo(yield $pingCh->recv());
        yield $pongCh->send("PONG\n");

        yield async_sleep(1000);
    }
});

\FSth\Co\Call::go(function () use ($pingCh, $pongCh) {
    while (true) {
        echo(yield $pongCh->recv());
        yield $pingCh->send("PING\n");

        yield async_sleep(1000);
    }
});

\FSth\Co\Call::go(function () use($pingCh) {
    echo "start up\n";
    yield $pingCh->send("PING");
});