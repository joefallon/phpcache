<?php
namespace JoeFallon\PhpCache;

class ApcCache implements Cacheable
{
    /**
     * store the given $value in the cache and assign it the key $key. cache keys are unique.
     * storing a value using a cache key that already exists will overwrite the existing value
     * that is stored at the cache key.
     *
     * @param string $key
     * @param mixed $value
     */
    public function store($key, $value)
    {
        $key = strval($key);
        apcu_store($key, $value);
    }

    /**
     * retrieve the value specified by the $key from the cache if it exists, null otherwise.
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

        $value = apcu_fetch($key);

        return $value;
    }

    /**
     * return true if the value exists otherwise, return false.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function exists($key)
    {
        $key = strval($key);

        return apcu_exists($key);
    }

    /**
     * removes the value from the cache given the $key.
     *
     * @param string $key
     */
    public function remove($key)
    {
        $key = strval($key);
        apcu_delete($key);
    }


    /**
     * remove all values from the cache.
     */
    public function removeall()
    {
        apcu_clear_cache();
    }
}
