<?php
namespace JoeFallon\PhpCache;

/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2014 Joseph Fallon (All rights reserved)
 * @license   MIT
 */
class TaggedCache
{
    const BASE_NAMESPACE = 'JoeFallon/Cache/TaggedCache';
    const MAX_EXPIRES    = 31557600; // 1 year
    const ALL_KEYS_TAG   = 'tagged_cache_all_keys';

    /** @var  Cacheable */
    private $_cache;
    /** @var  string */
    private $_namespace;


    /*
     * tagName -> array('key1', 'key2', 'key3');
     *
     * key1 -> array( 'expires' => '2013-01-01 10:10:10',
     *                'tags'    => array('tag1', 'tag2', 'tag3') );
     *
     * namespace -> array( all-keys );
     */

    /**
     * @param Cacheable   $cache
     * @param string|null $namespace  The $namespace allows the cache to
     *  be partitioned. See the documentation for removeAll().
     * @param int|null    $defaultExpiresInSeconds  Any cache entry that
     *  is stored without a an expiry time set will use the default expiry instead. If
     *  $defaultExpiresInSeconds is null, then the time-based cache expiry will be
     *  managed by $simpleCache.
     *
     */
    public function __construct(Cacheable $cache, $namespace = null,
                                $defaultExpiresInSeconds = null)
    {
        $this->_namespace = self::BASE_NAMESPACE . ':' . strval($namespace) . ':';
        $this->_defaultExpiresInSecs = intval($defaultExpiresInSeconds);
        $this->_cache = $cache;
    }

    /**
     * Store the given $value in the cache and assign it the key $key. Cache
     * keys are unique. Storing a value using a cache key that already exists
     * will overwrite the existing value that is stored at the cache key. The
     * cache entry will expire in $expiresInSeconds if it is not null. If
     * $expiresInSeconds is null, the $defaultExpiresInSeconds is used to
     * expire the cache entry.
     *
     * @param string $key
     * @param mixed  $value
     * @param array  $tags
     * @param string $expiresInSeconds
     */
    public function store($key, $value, array $tags = null, $expiresInSeconds = null)
    {
        $this->remove($key);
        $cacheEntry = array();

        $expiresInSeconds = intval($expiresInSeconds);
        $defaultExpires   = $this->_defaultExpiresInSecs;

        if($expiresInSeconds == 0)
        {
            $expiresInSeconds = $defaultExpires;
        }

        if($expiresInSeconds == 0)
        {
            $expiresInSeconds = self::MAX_EXPIRES;
        }

        $expires = date('Y-m-d H:i:s', time() + $expiresInSeconds);

        $cacheEntry['expires'] = strval($expires);
        $cacheEntry['tags']    = $tags;
        $cacheEntry['value']   = $value;

        $this->namespaceKeyStore($key, $cacheEntry);

        if($tags != null && count($tags) > 0)
        {
            foreach($tags as $tag)
            {
                $this->addKeyToTag($key, $tag);
            }
        }

        $this->addKeyToTag($key, self::ALL_KEYS_TAG);
    }

    /**
     * @param string $key
     * @param string $tag
     */
    private function addKeyToTag($key, $tag)
    {
        $keyList = $this->namespaceTagRetrieve($tag);

        if($keyList == null)
        {
            $keyList = array();
        }

        $keyList[] = $key;
        $keyList   = array_unique($keyList);
        $this->namespaceTagStore($tag, $keyList);
    }

    /**
     * This function retrieves the cache entry specified by $key from
     * the cache if it exists and has not expired; otherwise, null is
     * returned.
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function retrieve($key)
    {
        $cacheEntry = $this->namespaceKeyRetrieve($key);

        if($cacheEntry == null)
        {
            return null;
        }

        $expired = $this->isCacheEntryExpired($cacheEntry);

        if($expired == true)
        {
            $this->remove($key);

            return null;
        }

        $cacheValue = $cacheEntry['value'];

        return $cacheValue;
    }

    /**
     * This function returns true if the cache entry specified by
     * $key exists; otherwise, it returns false.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function exists($key)
    {
        $cacheEntry = $this->namespaceKeyRetrieve($key);

        if($cacheEntry == null)
        {
            return false;
        }

        $expired = $this->isCacheEntryExpired($cacheEntry);

        if($expired == true)
        {
            $this->remove($key);

            return false;
        }

        return true;
    }

    /**
     * This function removes the cache entry specified by $key.
     *
     * @param string $key
     */
    public function remove($key)
    {
        $this->removeKeyFromTagCacheEntries($key);
        $this->removeKeyFromSingleCacheTagEntry(self::ALL_KEYS_TAG, $key);
        $this->namespaceKeyRemove($key);
    }

    /**
     * This function removes all cache entries that have been tagged
     * with $tag.
     *
     * @param string $tag
     */
    public function removeByTag($tag)
    {
        $keyList = $this->namespaceTagRetrieve($tag);

        if($keyList == null || is_array($keyList) == false || count($keyList) == 0)
        {
            return;
        }

        foreach($keyList as $key)
        {
            $this->remove($key);
        }

        $this->namespaceTagRemove($tag);
    }

    /**
     * This function removes all values from the cache in the current
     * namespace.
     */
    public function removeAll()
    {
        $keyList = $this->namespaceTagRetrieve(self::ALL_KEYS_TAG);

        if($keyList == null || is_array($keyList) == false || count($keyList) == 0)
        {
            return;
        }

        foreach($keyList as $key)
        {
            $this->remove($key);
        }

        $this->namespaceTagRemove(self::ALL_KEYS_TAG);
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    private function namespaceKeyStore($key, $value)
    {
        $namespacedKey = $this->namespaceKey($key);
        $simpleCache   = $this->_cache;
        $simpleCache->store($namespacedKey, $value);
    }

    /**
     * @param string $key
     */
    private function namespaceKeyRemove($key)
    {
        $namespacedKey = $this->namespaceKey($key);
        $simpleCache   = $this->_cache;
        $simpleCache->remove($namespacedKey);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    private function namespaceKeyRetrieve($key)
    {
        $namespacedKey = $this->namespaceKey($key);
        $simpleCache   = $this->_cache;
        $cacheEntry    = $simpleCache->retrieve($namespacedKey);

        return $cacheEntry;
    }

    /**
     * @param string $tag
     * @param array  $keys
     */
    private function namespaceTagStore($tag, $keys)
    {
        $namespacedTag = $this->namespaceTag($tag);
        $simpleCache   = $this->_cache;
        $simpleCache->store($namespacedTag, $keys);
    }

    /**
     * @param string $tag
     */
    private function namespaceTagRemove($tag)
    {
        $namespacedTag = $this->namespaceTag($tag);
        $simpleCache   = $this->_cache;
        $simpleCache->remove($namespacedTag);
    }

    /**
     * @param string $tag
     *
     * @return array
     */
    private function namespaceTagRetrieve($tag)
    {
        $namespacedTag = $this->namespaceTag($tag);
        $simpleCache   = $this->_cache;
        $cacheEntry    = $simpleCache->retrieve($namespacedTag);

        return $cacheEntry;
    }

    /**
     * @param array $cacheEntry
     *
     * @return bool
     */
    private function isCacheEntryExpired($cacheEntry)
    {
        if($cacheEntry == null)
        {
            return true;
        }

        $expires = $cacheEntry['expires'];

        if(empty($expires) == true)
        {
            return false;
        }

        if($expires <= date('Y-m-d H:i:s'))
        {
            return true;
        }

        return false;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function namespaceKey($key)
    {
        return $this->_namespace . 'key:' . strval($key);
    }

    /**
     * @param string $tag
     *
     * @return string
     */
    private function namespaceTag($tag)
    {
        return $this->_namespace . 'tag:' . strval($tag);
    }

    /**
     * @param string $key
     */
    private function removeKeyFromTagCacheEntries($key)
    {
        $cacheEntry = $this->namespaceKeyRetrieve($key);

        if($cacheEntry == null || isset($cacheEntry['tags']) == false)
        {
            return;
        }

        $tags = $cacheEntry['tags'];

        if(is_array($tags) == false || count($tags) == 0)
        {
            return;
        }

        foreach($tags as $tag)
        {
            $this->removeKeyFromSingleCacheTagEntry($tag, $key);
        }
    }

    /**
     * @param string $tag
     * @param string $key
     */
    private function removeKeyFromSingleCacheTagEntry($tag, $key)
    {
        $keyList = $this->namespaceTagRetrieve($tag);

        if($keyList == null || is_array($keyList) == false || count($keyList) == 0)
        {
            return;
        }

        $keyList = array_diff($keyList, array($key));
        $this->namespaceKeyStore($tag, $keyList);
    }
}
