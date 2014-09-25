<?php

require_once __DIR__ . '/../vendor/autoload.php';

class BasicTest extends PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        $this->b = new \Moznion\BenchMarker();
    }

    public function testTimeIt()
    {
        $func = function () {
        };

        $result = $this->b->timeIt(1, $func);
        $this->assertEquals(true, $result instanceof Moznion\BenchMarker\Time);
    }

    public function testCountIt()
    {
        $func = function () {
            for ($i = 0; $i < 100; $i++) {
            }
        };

        $result = $this->b->countIt(0.1, $func);
        $this->assertEquals(true, $result instanceof Moznion\BenchMarker\Time);
    }

    public function testTimeThis()
    {
        $func = function () {
        };

        ob_start();

        $result = $this->b->timeThis(1, $func);

        $captured = ob_get_contents();

        ob_end_clean();

        $messages = preg_split("/\r?\n/", $captured);
        $message = $messages[0];
        $warning_message = $messages[1];

        $this->assertEquals(true, $result instanceof Moznion\BenchMarker\Time);

        $this->assertEquals(true, preg_match('/^timeThis 1: [0-9.e\-]+ wallclock secs \(.+\)$/', $message));
        if ($warning_message) {
            $this->assertEquals(true, preg_match('/^\s+\(warning: too few iterations for a reliable count\)$/', $warning_message));
        }
    }

    public function testTimeThese()
    {
        $func = function () {
        };

        ob_start();

        $result = $this->b->timeThese(1, ["code A" => $func, "code B" => $func]);

        $captured = ob_get_contents();
        $messages = preg_split("/\r?\n/", $captured);

        ob_end_clean();

        $this->assertEquals(2, count($result));
        foreach ($result as $r) {
            $this->assertEquals(true, $r instanceof Moznion\BenchMarker\Time);
        }

        $this->assertEquals(true, preg_match('/^Benchmark: timing 1 iterations of code A, code B...$/', $messages[0]));

        $num_of_msg = 0;
        foreach ($messages as $message) {
            if (preg_match('/^\s+code [AB]: [0-9.e\-]+ wallclock secs \(.+\)$/', $message)) {
                $num_of_msg++;
            }
        }
        $this->assertEquals(2, $num_of_msg);
    }

    public function testCmpThese()
    {
        $func = function () {
        };

        ob_start();

        $result = $this->b->cmpThese(1, ["code A" => $func, "code B" => $func]);

        $captured = ob_get_contents();

        ob_end_clean();

        $this->assertEquals(3, count($result));

        $row = $result[0];
        $this->assertEquals("", $row[0]);
        $this->assertEquals(true, preg_match('/(?:Rate|s\/iter)/', $row[1]));
        $this->assertEquals("code A", $row[2]);
        $this->assertEquals("code B", $row[3]);

        $row = $result[1];
        $this->assertEquals("code A", $row[0]);
        $this->assertEquals(true, preg_match('/^\d+\/s$/', $row[1]));
        $this->assertEquals("--", $row[2]);
        $this->assertEquals(true, preg_match('/^-?\d+%$/', $row[3]));

        $row = $result[2];
        $this->assertEquals("code B", $row[0]);
        $this->assertEquals(true, preg_match('/^\d+\/s$/', $row[1]));
        $this->assertEquals(true, preg_match('/^-?\d+%$/', $row[2]));
        $this->assertEquals("--", $row[3]);

        $messages = preg_split("/\r?\n/", $captured);
        $table_rows = [];
        foreach ($messages as $message) {
            if ($message === "" || preg_match('/^\s+\(warning: too few iterations for a reliable count\)$/', $message)) {
                continue;
            }
            array_push($table_rows, $message);
        }

        $this->assertEquals(true, preg_match('/^\s+(?:Rate|s\/iter)\s+code A\s+code B\s+$/', $table_rows[0]));
        $this->assertEquals(true, preg_match('/^\s*code A  \d+\/s\s+--\s+-?\d+%\s+$/', $table_rows[1]));
        $this->assertEquals(true, preg_match('/^\s*code B  \d+\/s\s+-?\d+%\s+--\s+$/', $table_rows[2]));
    }
}

