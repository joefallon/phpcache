<?php
namespace JoeFallon\PhpCache;

class ApcCache implements Cacheable
{
    /**
     * Store the given $value in the cache and assign it the key $key. Cache keys are unique.
     * Storing a value using a cache key that already exists will overwrite the existing value
     * that is stored at the cache key.
     *
     * @param string $key
     * @param mixed $value
     */
    public function store($key, $value)
    {
        $key = strval($key);
        apc_store($key, $value);
    }

    /**
     * Retrieve the value specified by the $key from the cache if it exists, null otherwise.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function retrieve($key)
    {
        $key = strval($key);

        if($this->exists($key) == false)
        {
            return null;
        }

        $value = apc_fetch($key);

        return $value;
    }

    /**
     * Return true if the value exists otherwise, return false.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function exists($key)
    {
        $key = strval($key);

        return apc_exists($key);
    }

    /**
     * Removes the value from the cache given the $key.
     *
     * @param string $key
     */
    public function remove($key)
    {
        $key = strval($key);
        apc_delete($key);
    }


    /**
     * Remove all values from the cache.
     */
    public function removeAll()
    {
        apc_clear_cache("user");
    }
}
