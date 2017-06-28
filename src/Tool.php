<?php
namespace FSth\Co;

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

    public static function setCtx($key, $val)
    {
        return new SysCall(function (AsyncTask $task) use ($key, $val) {
            while ($task->parent && $task = $task->parent) ;
            $task->gen->generator->$key = $val;
        });
    }

    public static function getCtx($key, $default = null)
    {
        return new SysCall(function (AsyncTask $task) use ($key, $default) {
            while ($task->parent && $task = $task->parent) ;
            if (isset($task->gen->generator->$key)) {
                return $task->gen->generator->$key;
            }
            return $default;
        });
    }
}