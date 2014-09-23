<?php

namespace Benchmarker\Style;

use \Benchmarker\Style as Style;

class Nop extends Style {
    public function say ($str) {
        print $str;
    }

    public function spewTimeString(
        $real_time,
        $user_time,
        $sys_time,
        $child_user_time,
        $child_sys_time,
        $all_cpu_time,
        $parent_cpu_time,
        $child_cpu_time
    ) {
        $f = $this->format;

        return sprintf(
            "%2g wallclock secs (%$f cusr + %$f csys = %$f CPU)",
            $real_time,
            $child_user_time,
            $child_sys_time,
            $child_cpu_time
        );
    }
}
