<?php

namespace Moznion\BenchMarker\Style;

trait TimeString {
    public function spewNocTimeString($real_time, $user_time, $sys_time, $parent_cpu_time) {
        $f = $this->format;

        return sprintf(
            "%2g wallclock secs (%$f usr + %$f sys = %$f CPU)",
            $real_time,
            $user_time,
            $sys_time,
            $parent_cpu_time
        );
    }

    public function spewAllTimeString($real_time, $user_time, $sys_time, $child_user_time, $child_sys_time, $all_cpu_time) {
        $f = $this->format;
        return sprintf(
            "%2g wallclock secs (%$f usr %$f sys + %$f cusr %$f csys = %$f CPU)",
            $real_time,
            $user_time,
            $sys_time,
            $child_user_time,
            $child_sys_time,
            $all_cpu_time
        );
    }
}