<?php

include __DIR__ . "/../../vendor/autoload.php";

$ch = \FSth\Co\Call::chan();

\FSth\Co\Call::go(function () use ($ch) {
    while (true) {
        yield $ch->send("producer 1");
        yield \FSth\Co\Tool::asyncSleep(1000);
    }
});

\FSth\Co\Call::go(function () use ($ch) {
    while (true) {
        yield $ch->send("producer 2");
        yield \FSth\Co\Tool::asyncSleep(1000);
    }
});

\FSth\Co\Call::go(function () use ($ch) {
    while (true) {
        $recv = (yield $ch->recv());
        echo "consumer1: $recv\n";
    }
});

\FSth\Co\Call::go(function () use ($ch) {
    while (true) {
        $recv = (yield $ch->recv());
        echo "consumer2: $recv\n";
    }
});