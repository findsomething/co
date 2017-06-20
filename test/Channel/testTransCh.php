<?php

include __DIR__ . "/../../vendor/autoload.php";

$ch = \FSth\Co\Call::chan();

\FSth\Co\Call::go(function () use ($ch) {
    $anotherCh = \FSth\Co\Call::chan();
    yield $ch->send($anotherCh);
    echo "send another channel\n";
    yield $anotherCh->send("HELLO");
    echo "send hello through another channel\n";
});

\FSth\Co\Call::go(function () use ($ch) {
    $anotherCh = (yield $ch->recv());
    echo "recv another channel\n";
    $val = (yield $anotherCh->recv());
    echo $val, "\n";
});