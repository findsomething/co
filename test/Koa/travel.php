<?php

include __DIR__ . "/../../vendor/autoload.php";

function crust($next)
{
    echo "到达<地壳>\n";
    executeNext($next);
    echo "离开<地壳>\n";
}

function upperMantle($next)
{
    echo "到达<上地幔>\n";
    executeNext($next);
    echo "离开<上地幔>\n";
}

function mantle($next)
{
    echo "到达<下地幔>\n";
    executeNext($next);
    echo "离开<下地幔>\n";
}

function outCore($next)
{
    echo "到达<外核>\n";
    executeNext($next);
    echo "离开<外核>\n";
}

function innerCore($next)
{
    echo "到达<内核>\n";
}

function outCore1($next)
{
    echo "到达<外核>\n";
    // 我们放弃内核，仅仅绕外壳一周，从另一侧返回地表
//    $next();
    echo "离开<外核>\n";
}

function innerCore1($next)
{
    throw new \Exception("岩浆");
    echo "到达<内核>\n";
}

function mantle1($next)
{
    echo "到达<下地幔>\n";
    // 我们在下地幔的救援团队及时赶到 (try catch)
    try {
        $next();
    } catch (\Exception $ex) {
        echo "遇到", $ex->getMessage(), "\n";
    }
    // 我们仍旧没有去往内核,，绕道对端下地幔，返回地表
    echo "离开<下地幔>\n";
}

function executeNext($next)
{
    if (is_callable($next)) {
        $next();
    }
}

function makeTravel(...$layers)
{
    $next = null;
    $i = count($layers);
    while ($i--) {
        $layer = $layers[$i];
        $next = function () use ($layer, $next) {
            $layer($next);
        };
    }
    return $next;
}

function compose(...$fns)
{
    return array_right_reduce($fns, function ($carry, $fn) {
        return function () use ($carry, $fn) {
            $fn($carry);
        };
    });
}

function array_right_reduce(array $input, callable $function, $initial = null)
{
    return array_reduce(array_reverse($input, true), $function, $initial);
}

//$travel = makeTravel("crust", "upperMantle", "mantle1", "outCore", "innerCore1");
//$travel();

$travel = compose("crust", "upperMantle");
$travel();