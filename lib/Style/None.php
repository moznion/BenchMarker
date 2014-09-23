<?php

namespace Benchmarker\Style;

use \Benchmarker\Style as Style;

class None extends Style {
    public function say ($str) {
        // NOP
    }

    public function spewTimeString(
        $real_time,
        $user_time,
        $sys_time,
        $child_user_time,
        $child_sys_time,
        $cpu_time,
        $parent_cpu_time,
        $child_cpu_time
    ) {
        return '';
    }
}