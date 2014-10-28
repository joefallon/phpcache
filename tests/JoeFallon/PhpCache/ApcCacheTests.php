<?php
namespace tests\JoeFallon\PhpCache;

use JoeFallon\KissTest\UnitTest;
use JoeFallon\PhpCache\ApcCache;

/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2014 Joseph Fallon (All rights reserved)
 * @license   MIT
 */
class ApcCacheTests extends UnitTest
{
    public function test_store_and_retrieve()
    {
        $key   = 'key1';
        $val   = 'val1';
        $cache = new ApcCache();
        $cache->removeAll();

        $this->assertEqual(null, $cache->retrieve($key));
        $cache->store($key, $val);
        $this->assertEqual($val, $cache->retrieve($key));
    }


    public function test_exists_returns_false_when_key_does_not_exist()
    {
        $key   = 'key1';
        $cache = new ApcCache();
        $cache->removeAll();
        $this->assertFalse($cache->exists($key));
    }


    public function test_exists_returns_true_when_key_exists()
    {
        $key   = 'key1';
        $val   = 'val1';
        $cache = new ApcCache();
        $this->assertFalse($cache->exists($key));
        $cache->removeAll();

        $this->assertEqual(null, $cache->retrieve($key));
        $cache->store($key, $val);

        $this->assertTrue($cache->exists($key));
    }


    public function test_remove()
    {
        $key   = 'key1';
        $val   = 'val1';
        $cache = new ApcCache();
        $cache->removeAll();

        $this->assertEqual(null, $cache->retrieve($key));
        $cache->store($key, $val);

        $this->assertTrue($cache->exists($key));

        $cache->remove($key);
        $this->assertFalse($cache->exists($key));
    }


    public function test_removeAll()
    {
        $key   = 'key1';
        $val   = 'val1';
        $cache = new ApcCache();
        $cache->removeAll();

        $this->assertEqual(null, $cache->retrieve($key));
        $cache->store($key, $val);
        $cache->removeAll();
        $this->assertFalse($cache->exists($key));
    }
}
