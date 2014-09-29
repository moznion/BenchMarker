<?php

require_once __DIR__ . '/../vendor/autoload.php';

use \Moznion\BenchMarker as BenchMarker;

class CacheTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function checkOverwrittenCache()
    {
        $b = new BenchMarker();

        $b->timeIt(1, function () {
            return "foo";
        }); // cache of this expected to be overwritten

        $b->timeIt(1, function () {
            return "foo";
        });

        $b->timeIt(10, function () {
            return "bar";
        });

        $nop_cache = $this->getPrivateValue($b, 'nop_cache');
        $this->assertEquals(2, count($nop_cache));
    }

    /**
     * @test
     */
    public function okToEnableCacheWithConstructor()
    {
        $b = new \Moznion\BenchMarker(null, null, true);
        $enable_cache = $this->getPrivateValue($b, 'enable_cache');
        $this->assertEquals(true, $enable_cache);
    }

    /**
     * @test
     */
    public function okToEnableAndDisableCacheWithMethod()
    {
        $b = new \Moznion\BenchMarker();

        $enable_cache = $this->getPrivateValue($b, 'enable_cache');
        $this->assertEquals(false, $enable_cache);

        $b->enableCache();
        $enable_cache = $this->getPrivateValue($b, 'enable_cache');
        $this->assertEquals(true, $enable_cache);

        $b->disableCache();
        $enable_cache = $this->getPrivateValue($b, 'enable_cache');
        $this->assertEquals(false, $enable_cache);
    }

    /**
     * @test
     */
    public function successToClearByCount()
    {
        $b = new BenchMarker();

        $b->timeIt(1, function () {
            return "foo";
        });

        $b->timeIt(10, function () {
            return "bar";
        });

        $b->clearCache(10);

        $nop_cache = $this->getPrivateValue($b, 'nop_cache');
        $this->assertEquals(1, count($nop_cache));
    }

    /**
     * @test
     */
    public function successToClearAll()
    {
        $b = new BenchMarker();

        $b->timeIt(1, function () {
            return "foo";
        });

        $b->timeIt(10, function () {
            return "bar";
        });

        $b->clearAllCache();

        $nop_cache = $this->getPrivateValue($b, 'nop_cache');
        $this->assertEquals([], $nop_cache);
    }

    /**
     * @param BenchMarker $b
     * @param String $attribute
     * @return mixed
     */
    private function getPrivateValue (\Moznion\BenchMarker $b, $attribute) {
        $reflectionClass = new ReflectionClass($b);
        $ref_cache = $reflectionClass->getProperty($attribute);
        $ref_cache->setAccessible(true);
        return $ref_cache->getValue($b);
    }
}
