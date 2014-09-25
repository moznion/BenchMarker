<?php

require_once __DIR__ . '/../vendor/autoload.php';

$benchmarker = new \Moznion\BenchMarker();
$bench = $benchmarker->timeThese(100,
    [
        "code A" => function () {
            // do something
        },
        "code B" => function () {
            // do something
        }
    ]
);

