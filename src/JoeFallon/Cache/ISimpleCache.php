<?php
namespace JoeFallon\Cache;

/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2014 Joseph Fallon (All rights reserved)
 * @license   MIT
 * @package   JoeFallon\Cache
 */
interface ISimpleCache
{
    /**
     * Store the given $value in the cache and assign it
     * the key $key. Cache keys are unique. Storing
     * a value using a cache key that already exists will
     * overwrite the existing value that is stored at the
     * cache key.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function store($key, $value);


    /**
     * Retrieve the value specified by the $key from
     * the cache if it exists, null otherwise.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function retrieve($key);


    /**
     * Return true if the value exists otherwise, return false.
     *
     * @param $key
     *
     * @return boolean
     */
    public function exists($key);


    /**
     * Removes the value from the cache given the $key.
     *
     * @param $key
     */
    public function remove($key);
}
