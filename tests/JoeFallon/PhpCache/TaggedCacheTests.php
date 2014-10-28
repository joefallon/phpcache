<?php
namespace tests\JoeFallon\PhpCache;

use JoeFallon\KissTest\UnitTest;
use JoeFallon\PhpCache\ApcCache;
use JoeFallon\PhpCache\TaggedCache;

/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2014 Joseph Fallon (All rights reserved)
 * @license   MIT
 */
class TaggedCacheTests extends UnitTest
{
    public function test_removeAll_only_removes_namespaced_entries()
    {
        $cache1 = new TaggedCache(new ApcCache(), 'namespace1');
        $cache2 = new TaggedCache(new ApcCache(), 'namespace2');

        $key1 = 'key1';
        $key2 = 'key2';

        $value1 = 'value1';
        $value2 = 'value2';

        $cache1->store($key1, $value1);
        $cache2->store($key2, $value2);

        $cache1->removeAll();

        $this->assertFalse($cache1->exists($key1));
        $this->assertTrue($cache2->exists($key2));
    }

    public function test_retrieve_does_not_return_expired_entries()
    {
        $cache = new TaggedCache(new ApcCache(), 'namespace1', -100);
        $cache->store('key1', 'value1');

        $this->assertEqual($cache->retrieve('key1'), null);
    }

    public function test_exists_returns_false_for_expired_entries()
    {
        $cache = new TaggedCache(new ApcCache(), 'namespace1', -100);
        $cache->store('key1', 'value1');

        $this->assertFalse($cache->exists('key1'));
    }

    public function test_deleteByTag_removes_tagged_items()
    {
        $tags  = array('tagA', 'tagB');
        $value = 'test cache item';
        $key   = 'key1';

        $cache = new TaggedCache(new ApcCache());
        $cache->store($key, $value, $tags);
        $this->assertTrue($cache->exists($key), 'before delete');
        $cache->removeByTag('tagB');
        $this->assertFalse($cache->exists($key), 'after delete');
    }

    public function test_deleteByTag_does_not_remove_untagged_items()
    {
        $tags1  = array('tagA', 'tagB');
        $value1 = 'test cache item 1';
        $key1   = 'key1';
        $value2 = 'test cache item 2';
        $key2   = 'key2';
        $tags2  = array('tagC', 'tagA');
        $value3 = 'test cache item 3';
        $key3   = 'key3';

        $cache = new TaggedCache(new ApcCache());
        $cache->store($key1, $value1, $tags1);
        $this->assertTrue($cache->exists($key1), 'exists 1');
        $cache->store($key2, $value2);
        $this->assertTrue($cache->exists($key2), 'exists 2');
        $cache->store($key3, $value3, $tags2);
        $this->assertTrue($cache->exists($key3), 'exists 3');

        $cache->removeByTag('tagA');
        $this->assertFalse($cache->exists($key1), 'after delete 1');
        $this->assertTrue($cache->exists($key2), 'after delete 2');
        $this->assertFalse($cache->exists($key3), 'after delete 3');
    }

    public function test_exists_returns_true_when_value_exists()
    {
        $value = 'test cache item';
        $key   = 'val';
        $cache = new TaggedCache(new ApcCache());
        $cache->store($key, $value);

        $this->assertTrue($cache->exists($key));
    }

    public function test_exists_returns_false_when_value_not_exists()
    {
        $key   = 'val';
        $cache = new TaggedCache(new ApcCache());

        $this->assertFalse($cache->exists($key));
    }

    public function test_remove_removes_cached_value()
    {
        $value = 'test cache item';
        $key   = 'val';
        $cache = new TaggedCache(new ApcCache());
        $cache->store($key, $value);
        $this->assertTrue($cache->exists($key));

        $cache->remove($key);
        $this->assertFalse($cache->exists($key));
    }

    public function test_save_and_retrieve_with_no_tags()
    {
        $value = 'test cache item';
        $key   = 'val';
        $cache = new TaggedCache(new ApcCache());
        $cache->store($key, $value);

        $out = $cache->retrieve($key);
        $this->assertEqual($value, $out);
    }

    public function setUp()
    {
        apc_clear_cache('user');
    }
}
