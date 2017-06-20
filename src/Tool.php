<?php
namespace FSth\Co;

use FSth\Co\Call;

class Tool
{
    public static function asyncSleep($ms)
    {
        return Call::callCC(function ($k) use ($ms) {
            swoole_timer_after($ms, function () use ($k) {
                $k(null);
            });
        });
    }
}