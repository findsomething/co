<?php

include __DIR__ . "/../../vendor/autoload.php";

$ch = \FSth\Co\Call::chan(2);

\FSth\Co\Call::go(function () use ($ch) {
    while (true) {
        $recv = (yield $ch->recv());
        echo "recv $recv\n";
    }
});

\FSth\Co\Call::go(function () use ($ch) {
    for ($i = 1; $i <= 4; $i++) {
        yield $ch->send($i);
        echo "send $i\n";
    }
});