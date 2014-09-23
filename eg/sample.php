<?php

require_once __DIR__.'/../vendor/autoload.php';

$benchmarker = new \Moznion\BenchMarker();

$foo = "hoge";
$bar = "fuga";
$bench = $benchmarker->timeit(10, function () use ($foo, $bar) { for ($i = 0; $i < 10000; $i++) {$foo.$bar;} });
var_dump($bench);

