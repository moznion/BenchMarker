<?php

namespace Moznion;

use \Moznion\BenchMarker\Time as Time;
use \Moznion\BenchMarker\Style as Style;

class BenchMarker
{
    private $min_count = 4;
    private $min_cpu = 0.4;
    private $default_for = 3;

    private $style;
    private $style_name;
    private $format;

    /**
     * @param null $style_name
     * @param null $format
     */
    public function __construct($style_name = null, $format = null)
    {
        $this->format = $format;
        if (is_null($this->format)) {
            $this->format = '5.2f';
        }

        if (is_null($style_name)) {
            $style_name = 'auto';
        }
        $this->style = Style::createStyle($style_name, $this->format);
        $this->style_name = ucfirst($style_name);
    }

    /**
     * @param $count
     * @param callable $code
     * @return Time
     */
    public function timeIt($count, callable $code)
    {
        $nop = function () {
            // NOP
        };

        $time_nop = $this->runLoop($count, $nop); // TODO cache it
        $time_code = $this->runLoop($count, $code);

        $result = $this->timeDiff($time_nop, $time_code);
        $result->count = $count;
        return $result;
    }

    /**
     * @param $time
     * @param callable $code
     * @return Time
     */
    public function countIt($time, callable $code)
    {
        $min_for = 0.1;

        if ($time === 0) {
            $time = $this->default_for;
        } elseif ($time < 0) {
            $time = -$time;
        }

        if ($time < $min_for) {
            die("countIt({$time}, ...): time limit cannot be less than {$min_for}.\n");
        }

        $tc = null;
        $cnt = 0;
        for ($n = 1; ; $n *= 2) {
            $t0 = Time::getNow();
            $td = $this->timeIt($n, $code);
            $t1 = Time::getNow();

            $tc = $td->user_time + $td->sys_time;

            if ($tc <= 0 and $n > 1024) {
                $diff = $this->timeDiff($t0, $t1);

                // note that $diff is the total CPU time taken to call timeIt(),
                // while $tc is is difference in CPU secs between the empty run
                // and the code run. If the code is trivial, its possible
                // for $diff to get large while $tc is still zero (or slightly
                // negative). Bail out once timeIt() starts taking more than a
                // few seconds without noticeable difference.
                if ($diff->user_time + $diff->sys_time > 8 || ++$cnt > 16) {
                    die("Timing is consistently zero in estimation loop, cannot benchmark. N={$n}\n");
                }
            } else {
                $cnt = 0;
            }

            if ($tc > 0.1) {
                break;
            }
        }

        $nmin = $n;

        // Get $n high enough that we can guess the final $n with some accuracy.
        $time_practice = 0.1 * $time; // Target/time practice.
        while ($tc < $time_practice) {
            // The 5% fudge is to keep us from iterating again all
            // that often (this speeds overall responsiveness when $time is big
            // and we guess a little low). This does not noticeably affect
            // accuracy since we're not counting these times.
            $n = (int)($time_practice * 1.05 * $n / $tc);
            $td = $this->timeIt($n, $code);
            $new_tc = $td->user_time + $td->sys_time;

            // Make sure we are making progress.
            $tc = $new_tc > 1.2 * $tc ? $new_tc : 1.2 * $tc;
        }

        // Now, do the 'for real' timing(s), repeating until we exceed the max.
        $n_total = 0;
        $real_time_total = 0.0;
        $sys_time_total = 0.0;
        $user_time_total = 0.0;
        $child_sys_time_total = 0.0;
        $child_user_time_total = 0.0;


        // The 5% fudge is because $n is often a few % low even for routines
        // with stable times and avoiding extra timeIt()s is nice for accuracy's sake.
        $n = (int)($n * (1.05 * $time / $tc));
        $cnt = 0;
        while (1) {
            $td = $this->timeIt($n, $code);

            $n_total += $n;
            $real_time_total += $td->real_time;
            $sys_time_total += $td->sys_time;
            $user_time_total += $td->user_time;
            $child_sys_time_total += $td->child_sys_time;
            $child_user_time_total += $td->child_user_time;

            $time_total = $user_time_total + $sys_time_total;
            if ($time_total >= $time) {
                break;
            }

            if ($time_total <= 0) {
                if (++$cnt > 16) {
                    die("Timing is consistently zero, cannot benchmark. N=$n");
                }
            } else {
                $cnt = 0;
            }

            $time_total = $time_total < 0.01 ? 0.01 : $time_total;

            $rate = $time / $time_total - 1; // Linear approximation.
            $n = (int)($rate * $n_total);
            $n = $n < $nmin ? $nmin : $n;
        }

        $result = new Time(
            $real_time_total,
            $sys_time_total,
            $user_time_total,
            $child_sys_time_total,
            $child_user_time_total
        );

        $result->count = $n_total;

        return $result;
    }

    /**
     * @param $count
     * @param callable $code
     * @param null $title
     * @return Time
     */
    public function timeThis($count, callable $code, $title = null)
    {
        $forn = null;
        if ($count > 0) {
            if ((int)$count < $count) {
                die("non-integer loop count $count, stopped");
            }
            $result_time = $this->timeIt($count, $code);

            if (is_null($title)) {
                $title = "timeThis $count";
            }
        } else {
            $fort = $this->nToFor($count);
            $result_time = $this->countIt($count, $code);
            $forn = $result_time->count;

            if (is_null($title)) {
                $title = "timeThis $fort";
            }
        }

        $style = $this->style;
        $style->say(sprintf("%10s: ", $title));

        print $this->timeStr($result_time, $forn) . "\n";

        if (
            (!is_null($forn) &&
                (
                    $forn < $this->min_count ||
                    ($result_time->real_time < 1 && $forn < 1000)
                )
            ) ||
            $result_time->getAllCPUTime() < $this->min_cpu
        ) {
            // A conservative warning to spot very silly tests.
            // Don't assume that your benchmark is ok simply because
            // you don't get this warning!
            print "            (warning: too few iterations for a reliable count)\n";
        }

        return $result_time;
    }

    /**
     * @param $count
     * @param array $codes
     * @param null $quiet
     * @return array
     */
    public function timeThese($count, array $codes, $quiet = null)
    {
        $original_style = $this->style;
        if (! is_null($quiet)) {
            $this->style = Style::createStyle('none', $this->format);
        }
        $style = $this->style;

        $style->say("Benchmark: ");

        if ($count > 0) {
            if ((int)$count < $count) {
                die("non-integer loopcount $count, stopped");
            }
            $style->say("timing $count iterations of");
        } else {
            $style->say("running");
            $for = $this->nToFor($count);
            if ($count > 1) {
                $style->say(", each");
            }
            $style->say(" for at least $for CPU seconds");
        }

        $results = [];
        $names = array_keys($codes);
        sort($names);

        $style->say(" " . implode(', ', $names));
        $style->say("...\n");

        // we could save the results in an array and produce a summary here
        // sum, min, max, avg etc etc
        foreach ($names as $name) {
            $code = $codes[$name];
            if (!is_callable($code)) {
                die("Value of codes must be callable");
            }
            $results[$name] = $this->timeThis($count, $code, $name);
        }

        $this->style = $original_style;

        return $results;
    }

    /**
     * @param $count
     * @param array $codes
     * @return array
     */
    public function cmpThese($count, array $codes)
    {
        $results = $this->timeThese($count, $codes, true);

        $rates = [];
        $titles = array_keys($results);
        foreach ($titles as $title) {
            // The epsilon fudge here is to prevent div by 0.  Since clock
            // resolutions are much larger, it's below the noise floor.
            $elapsed = null;
            $result = $results[$title];
            switch ($this->style_name) {
                case 'nop':
                    $elapsed = $result->getChildCPUTime();
                    break;
                case 'noc':
                    $elapsed = $result->getParentCPUTime();
                    break;
                default:
                    $elapsed = $result->getAllCPUTime();
            }

            $rates[$title] = $result->count / ($elapsed + 0.000000000000001);
        }

        $display_as_rate = false;
        if ($rates) {
            $_rates = $rates;
            sort($_rates);

            // If more than half of the rates are greater than one...
            $display_as_rate = $_rates[(count($_rates) - 1) >> 1] > 1;
        }

        $rows = [];
        $col_widths = [];

        $top_row = ['', $display_as_rate ? 'Rate' : 's/iter'];
        foreach ($titles as $title) {
            array_push($top_row, $title);
        }

        array_push($rows, $top_row);

        foreach ($top_row as $column) {
            array_push($col_widths, strlen($column));
        }

        // Build the data rows
        // We leave the last column in even though it never has any data.  Perhaps
        // it should go away.  Also, perhaps a style for a single column of
        // percentages might be nice.
        foreach ($titles as $row_title) {
            $row = [];

            array_push($row, $row_title);

            if (strlen($row_title) > $col_widths[0]) {
                $col_widths[0] = strlen($row_title);
            }

            // We assume that we'll never get a 0 rate.
            $row_rate = $rates[$row_title];
            $rate = $display_as_rate ? $row_rate : 1 / $row_rate;

            // Only give a few decimal places before switching to sci. notation,
            // since the results aren't usually that accurate anyway.
            if ($rate >= 100) {
                $format = "%0.0f";
            } elseif ($rate >= 10) {
                $format = "%0.1f";
            } elseif ($rate >= 1) {
                $format = "%0.2f";
            } elseif ($rate >= 0.1) {
                $format = "%0.3f";
            } else {
                $format = "%0.2e";
            }

            if ($display_as_rate) {
                $format .= "/s";
            }

            $formatted_rate = sprintf($format, $rate);
            array_push($row, $formatted_rate);

            if (strlen($formatted_rate) > $col_widths[1]) {
                $col_widths[1] = strlen($formatted_rate);
            }

            $col_num = 2;
            foreach ($titles as $col_title) {
                if ($col_title === $row_title) {
                    $out = "--";
                } else {
                    $col_rate = $rates[$col_title];
                    $out = sprintf("%.0f%%", 100 * $row_rate / $col_rate - 100);
                }

                array_push($row, $out);

                if (strlen($out) > $col_widths[$col_num]) {
                    $col_widths[$col_num] = strlen($out);
                }

                if (strlen($row[0]) > $col_widths[$col_num]) {
                    $col_widths[$col_num] = strlen($row[0]);
                }

                $col_num++;
            }

            array_push($rows, $row);
        }

        if ($this->style_name === "None") {
            return $rows;
        }

        for ($i = 0; $i < count($rows); $i++) {
            $row = $rows[$i];
            $str = '';
            for ($j = 0; $j < count($row); $j++) {
                $width = $col_widths[$j];
                $str .= sprintf("%{$width}s  ", $row[$j]);
            }
            echo "{$str}\n";
        }

        return $rows;
    }

    /**
     * @param Time $time_result
     * @param null $count
     * @return string
     */
    public function timeStr(Time $time_result, $count = null)
    {
        $style = $this->style;
        $style_name = $this->style_name;
        if ($style_name === 'None') {
            return '';
        }

        $parent_cpu_time = $time_result->getParentCPUTime();
        $child_cpu_time = $time_result->getChildCPUTime();
        $all_cpu_time = $time_result->getAllCPUTime();

        $real_time = $time_result->real_time;
        $user_time = $time_result->user_time;
        $sys_time = $time_result->sys_time;
        $child_user_time = $time_result->child_user_time;
        $child_sys_time = $time_result->child_sys_time;

        $f = $this->format;

        $elapsed = $all_cpu_time;

        $time_string = $style->spewTimeString(
            $real_time,
            $user_time,
            $sys_time,
            $child_user_time,
            $child_sys_time,
            $all_cpu_time,
            $parent_cpu_time,
            $child_cpu_time
        );

        if ($style_name === "noc") {
            $elapsed = $parent_cpu_time;
        } elseif ($style_name === "nop") {
            $elapsed = $child_cpu_time;
        }

        if ($count && $elapsed) {
            $time_string .= sprintf(" @ %$f/s (n=%d)", $count / ($elapsed), $count);
        }
        return $time_string;
    }

    /**
     * @param Time $t1
     * @param Time $t2
     * @return Time
     */
    public function timeDiff(Time $t1, Time $t2)
    {
        return $t2->getDiff($t1);
    }

    /**
     * @param $count
     * @param callable $code
     * @return Time
     */
    private function runLoop($count, callable $code)
    {
        if ($count < 0) {
            die("negative loop count: $count");
        }

        $sub = function () use ($count, $code) {
            for ($i = 0; $i < $count; $i++) {
                $code();
            }
        };

        // Wait for the user timer to tick.
        // This makes the error range more like -0.01, +0.
        // If we don't wait, then it's more like -0.01, +0.01.
        // This may not seem important, but it significantly reduces the chances of
        // getting a too low initial $count in the initial, 'find the minimum' loop
        // in countIt(). This, in turn, can reduce the number of calls to
        // runLoop() a lot, and thus reduce additive errors.
        $t0 = null;
        $time_base = Time::getNow()->user_time;
        while (1) {
            $t0 = Time::getNow();
            if ($t0->user_time !== $time_base) {
                break;
            }
        }

        $sub();

        $t1 = Time::getNow();

        return $this->timeDiff($t0, $t1);
    }

    /**
     * @param $n
     * @return int|null
     */
    private function nToFor($n)
    {
        if ($n === 0) {
            return $this->default_for;
        }

        if ($n < 0) {
            return -$n;
        }

        return null;
    }
}

