#BenchMarker [![Build Status](https://travis-ci.org/moznion/BenchMarker.svg)](https://travis-ci.org/moznion/BenchMarker)

Benchmark running times of PHP code (inspired by [Benchmark.pm](http://perldoc.perl.org/Benchmark.html))

Synopsis
---------

```php
$benchmarker = new \Moznion\Benchmarker("all");

$code = function () use ($foo) {
    // do something
};

$benchmarker->timeThese(10000, [
    "code A" => function () use ($bar) {
        // do something
    },
    "code B" => $code, // it can also take variable of anonymous function
]);
// Sample of Output:
//    code A: 3.9326 wallclock secs (3sr + 0.2562ys = PU)
//    code B: 6.40877 wallclock secs (6sr + 0.81337ys = PU)

$benchmarker->cmpThese(10000, [
    "code A" => function () use ($bar) {
        // do something
    },
    "code B" => $code, // it can also take variable of anonymous function
]);
// Sample of Output:
//               Rate  code A  code B
//    code A  22571/s      --    -49%
//    code B  44656/s     98%      --
```

Description
-----------

Library for benchmarking the running times of PHP code.

This library is inspired by [Benchmark of Perl](http://perldoc.perl.org/Benchmark.html)

Methods
-------

### `new($style_name=null, $format=null, $enable_cache=null)`

Creates the instance of BenchMarker.

- `$style_name (String)`

Specify the name of style. Default value is `auto`.

Please see also [Style](https://github.com/moznion/BenchMarker#style).

- `$format (String)`

Specify the format to format the values of benchmark's result.
It supports the format which is compatible with `sprintf()`'s one.

Default value is '5.2f'.

- `$enable_cache (Boolean)`

Specify cache the elapsed time of nop function (elapsed time of nop is used internal for accuracy).
If you enable caching, processing time of benchmark will be faster.

Default value is 'false'.

### `timeIt($count, $code)`

Run a chunk of code and see how long it goes.

This method returns [instance of result-time](https://github.com/moznion/BenchMarker#result-time).

- `$count`

`$count` is the number of times to run the loop. This argument must be positive integer.

- `$code`

`$code` is the code to run. This argument must be callable variable.

### `countIt($time, $code)`

See how many times a chunk of code runs in a given time.

This method returns [instance of result-time](https://github.com/moznion/BenchMarker#result-time).

- `$time`

`$time` is the minimum length of time to run the loop.

- `$code`

`$code` is the code to run. This argument must be callable variable.

### `timeThis($count, $code, $title=null)`

Run a chunk of code several times and print result of benchmark.

This method returns [instance of result-time](https://github.com/moznion/BenchMarker#result-time).

- `$count`

`$count` is the number of times to run the loop. This argument must be positive integer.

This argument can be zero or negative.
This means the minimum number of CPU seconds to run.
A zero signifies the default of 3 seconds.

- `$code`

`$code` is the code to run. This argument must be callable variable.

- `$title`

Title of result. `$title` defaults to "timethis $count".

### `timeThese($count, $codes)`

Run several chunks of code several times and print result of benchmark.

This method returns [instance of result-time](https://github.com/moznion/BenchMarker#result-time) of array for each codes.

- `$count`

`$count` is the number of times to run the loop. This argument must be positive integer.

This argument can be zero or negative.
This means the minimum number of CPU seconds to run.
A zero signifies the default of 3 seconds.

- `$codes`

`$codes` is the array of codes to run.

### `cmpThese($count, $codes)`

Print results of timeThese as a comparison chart.

- `$count`

`$count` is the number of times to run the loop. This argument must be positive integer.

This argument can be zero or negative.
This means the minimum number of CPU seconds to run.
A zero signifies the default of 3 seconds.

- `$codes`

`$codes` is the array of codes to run.

### `timeStr($time_result, $count=null)`

Print formatted time.

- `$time_result`

[instance of result-time](https://github.com/moznion/BenchMarker#result-time).

- `$count`

Number of loops. If this argument is not null then this method appends the rate.

### `timeDiff($new_time, $old_time)`

Calculates difference between `$new_time` and `$old_time`.

Arguments must be [instance of result-time](https://github.com/moznion/BenchMarker#result-time).

### `clearCache($count)`

Clear the caching of nop function which is related $count.

### `clearAllCache()`

Clear all of the caching of nop function.

### `disableCache()`

Disable to cache the caching of nop function.

### `enableCache()`

Enable to cache the caching of nop function.

Result Time
-----------

Time class has 6 public members.

- `$real_time`

Real duration.

- `$sys_time`

Duration of system time.

- `$user_time`

Duration of user time.

- `$child_sys_time`

Duration of system time of child processes.

- `$child_user_time`

Duration of user time of child processes.

- `$count`

Times of loop to run.

Style
-----

This library supports 5 styles.

- all

Output all of the result of benchmarking.

- noc

Output the result of benchmarking without child processes result.

- nop

Output the result of benchmarking without parent processes result.

- none

Output nothing.

- auto

If child processes CPU time is bigger than 0 then this style is the same as "all". Elsewise, this style is the same as "noc".

Requires
--------

PHP (version 5.4 or later)

See Also
--------

- [examples](https://github.com/moznion/BenchMarker/tree/master/eg)
- [Benchmark](http://perldoc.perl.org/Benchmark.html)

Notes
-----

This library doesn't work on Microsoft Windows.

License
-------

MIT

