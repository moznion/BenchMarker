<?php

require_once __DIR__ . '/../vendor/autoload.php';

$benchmarker = new \Moznion\BenchMarker();
$bench = $benchmarker->countIt(0.1,
    function () {
        // do something
    }
);

var_dump($bench);

