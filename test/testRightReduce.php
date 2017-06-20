<?php
include __DIR__ . "/../vendor/autoload.php";

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

function make(...$fns)
{
    $next = null;
    $i = count($fns);
    while ($i--) {
        $fn = $fns[$i];
        $next = function () use ($fn, $next) {
            $fn($next);
        };
    }
    return $next;
}

function test1($next)
{
    var_dump($next);
    echo "test1 begin\n";
    $next();
    echo "test1 end\n";
}

function test2($next)
{
    var_dump($next);
    echo "test2 begin\n";
    $next();
    echo "test2 end\n";
}

$input = ["test1", "test2"];

$function = compose(...$input);
$function();

var_dump('....');

$function = make(...$input);
$function();


//function crust($next)
//{
//    echo "到达<地壳>\n";
//    $next();
//    echo "离开<地壳>\n";
//}
//
//function upperMantle($next)
//{
//    echo "到达<上地幔>\n";
//    $next();
//    echo "离开<上地幔>\n";
//}
//
//function mantle($next)
//{
//    echo "到达<下地幔>\n";
//    $next();
//    echo "离开<下地幔>\n";
//}
//
//function outerCore($next)
//{
//    echo "到达<外核>\n";
//    $next();
//    echo "离开<外核>\n";
//}
//
//function innerCore($next)
//{
//    echo "到达<内核>\n";
//}
//
//// 我们逆序组合组合, 返回入口
//function makeTravel(...$layers)
//{
//    $next = null;
//    $i = count($layers);
//    while ($i--) {
//        $layer = $layers[$i];
//        $next = function() use($layer, $next) {
//            // 这里next指向穿越下一次的函数，作为参数传递给上一层调用
//            $layer($next);
//        };
//    }
//    return $next;
//}
//
//
//// 我们途径 crust -> upperMantle -> mantle -> outerCore -> innerCore 到达地心
//// 然后穿越另一半球  -> outerCore -> mantle -> upperMantle -> crust
//
//$travel = makeTravel("crust", "upperMantle", "mantle", "outerCore", "innerCore");
//$travel();