<?php

namespace Benchmarker;

class Time
{
    public $real_time;
    public $sys_time;
    public $user_time;
    public $child_sys_time;
    public $child_user_time;

    public function __construct($time, $sys_time, $user_time, $child_sys_time, $child_user_time)
    {
        $this->real_time = $time;
        $this->sys_time = $sys_time;
        $this->user_time = $user_time;
        $this->child_sys_time = $child_sys_time;
        $this->child_user_time = $child_user_time;
    }

    public static function getNow()
    {
        $time = microtime(true);
        $usage = getrusage();
        $child_usage = getrusage(1);

        $sys_time = $usage["ru_stime.tv_usec"];
        $user_time = $usage["ru_utime.tv_usec"];

        $child_sys_time = $child_usage["ru_stime.tv_usec"];
        $child_user_time = $child_usage["ru_utime.tv_usec"];

        return new Time($time, $sys_time, $user_time, $child_sys_time, $child_user_time);
    }

    public function getDiff(Time $old_time)
    {
        return new Time(
            $this->real_time - $old_time->real_time,
            $this->sys_time - $old_time->sys_time,
            $this->user_time - $old_time->user_time,
            $this->child_sys_time - $old_time->child_sys_time,
            $this->child_user_time - $old_time->child_user_time
        );
    }

    public function getParentCPUTime()
    {
        return $this->sys_time + $this->user_time;
    }

    public function getChildCPUTime()
    {
        return $this->child_sys_time + $this->child_user_time;
    }

    public function getAllCPUTime()
    {
        return $this->getParentCPUTime() + $this->getChildCPUTime();
    }
}
