<?php

require_once __DIR__ . '/../vendor/autoload.php';

$benchmarker = new \Moznion\BenchMarker();
$bench = $benchmarker->timeIt(100,
    function () {
        // do something
    }
);

var_dump($bench);

