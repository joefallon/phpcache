<?php
namespace JoeFallon\Cache;

/**
 * @author    Joseph Fallon <joseph.t.fallon@gmail.com>
 * @copyright Copyright 2014 Joseph Fallon (All rights reserved)
 * @license   MIT
 * @package   JoeFallon\Cache
 */
class TaggedCache
{
    const BASE_KEY    = 'JoeFallon/Cache/TaggedCache';
    const MAX_EXPIRES = 99999999; // forever (99999999 sec =~ 3.1689 years)

    /** @var  ISimpleCache */
    private $_simpleCache;
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
     * @param ISimpleCache $simpleCache
     * @param string|null  $namespace               The $namespace allows the cache to
     *                                              be partitioned. See the documentation for
     *                                              removeAll().
     * @param int|null     $defaultExpiresInSeconds Any cache entry that
     *                                              is stored without a an expiry time set will
     *                                              use the default expiry instead. If
     *                                              $defaultExpiresInSeconds is null, then
     *                                              the time-based cache expiry will be managed
     *                                              by $simpleCache.
     */
    public function __construct(ISimpleCache $simpleCache,
                                $namespace = null,
                                $defaultExpiresInSeconds = null)
    {
        $this->_namespace            = self::BASE_KEY . ':' . strval($namespace) . ':';
        $this->_defaultExpiresInSecs = intval($defaultExpiresInSeconds);
        $this->_simpleCache          = $simpleCache;
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
        $namespacedKey = $this->_namespace . $key;
        $this->remove($namespacedKey);
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

        $this->_simpleCache->store($namespacedKey, $cacheEntry);

        if($tags != null && count($tags) > 0)
        {
            foreach($tags as $tag)
            {
                $this->addKeyToTag($namespacedKey, $tag);
            }
        }
    }

    private function addKeyToTag($key, $tag)
    {
        $namespacedTag = $this->_namespace . $tag;
        $keyList = $this->_simpleCache->retrieve($namespacedTag);

        if($keyList == null)
        {
            $keyList = array($key);
            $this->_simpleCache->store($namespacedTag, $keyList);
        }

        $keyList[] = $key;
        $keyList = array_unique($keyList);
        $this->_simpleCache->store($namespacedTag, $keyList);
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
        $namespacedKey = $this->_namespace . $key;

        if($this->exists($namespacedKey) == true)
        {
            return $this->_simpleCache->retrieve($namespacedKey);
        }

        return null;
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
        $namespacedKey = $this->_namespace . $key;
        $exists = $this->_simpleCache->exists($namespacedKey);

        if($exists == null)
        {
            return false;
        }

        $cacheEntry = $this->_simpleCache->retrieve($namespacedKey);
        $isExpired  = $this->isCacheEntryExpired($cacheEntry);

        if($isExpired == true)
        {
            $this->remove($namespacedKey);

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
        $namespacedKey = $this->_namespace . $key;
        $this->removeKeyFromTags($key);
        $this->_simpleCache->remove($namespacedKey);
    }


    private function removeKeyFromTags($key)
    {
        $namespacedKey = $this->_namespace . $key;
        $cacheEntry = $this->_simpleCache->retrieve($namespacedKey);

        if($cacheEntry == null)
        {
            return;
        }

        $tags = $cacheEntry['tags'];

        if(is_array($tags) == true || count($tags) > 0)
        {
            foreach($tags as $tag)
            {
                $this->removeKeyFromTagEntry($key, $tag);
            }
        }
    }


    private function removeKeyFromTagEntry($key, $tag)
    {
        $namespacedTag = $this->_namespace . $tag;
        $keyList    = $this->_simpleCache->retrieve($namespacedTag);

        if($keyList == null)
        {
            return;
        }

        if(is_array($keyList) && count($keyList) == 0)
        {
            $this->_simpleCache->remove($namespacedTag);
            return;
        }

        $newKeyList = array_diff($keyList, array($key));
        $this->_simpleCache->store($namespacedTag, $newKeyList);
    }


    /**
     * This function removes all cache entries that have been tagged
     * with $tag.
     *
     * @param string $tag
     */
    public function removeByTag($tag)
    {
        $keyList = $this->_simpleCache->retrieve($tag);
        $this->_simpleCache->remove($tag);

        if(isset($keyList))
        {
            foreach($keyList as $key)
            {
                $this->remove($key);
            }
        }
    }


    /**
     * This function removes all values from the cache in the current
     * namespace.
     */
    public function removeAll()
    {
        //        $this->verifyCacheMetadata();
        //
        //        $simpleCache = $this->_simpleCache;
        //        $expiresList = $this->retrieveExpiresList();
        //
        //        foreach($expiresList as $key => $expire)
        //        {
        //            $simpleCache->remove($key);
        //        }
        //
        //        $this->resetCacheMetadata();
    }


    //    private function verifyCacheMetadata()
    //    {
    //        $this->verifyCachedExpiresList();
    //        $this->verifyCachedTagsList();
    //    }
    //
    //
    //    private function verifyCachedTagsList()
    //    {
    //        $simpleCache = $this->_simpleCache;
    //        $tagsKey     = $this->_tagsKey;
    //
    //        if($simpleCache->exists($tagsKey) == false)
    //        {
    //            $this->resetCacheMetadata();
    //        }
    //    }
    //
    //
    //    private function verifyCachedExpiresList()
    //    {
    //        $simpleCache = $this->_simpleCache;
    //        $expiresKey  = $this->_expiresKey;
    //
    //        if($simpleCache->exists($expiresKey) == false)
    //        {
    //            $this->resetCacheMetadata();
    //        }
    //    }
    //
    //
    //    private function resetCacheMetadata()
    //    {
    //        $simpleCache = $this->_simpleCache;
    //        $tagsKey     = $this->_tagsKey;
    //        $expiresKey  = $this->_expiresKey;
    //
    //        $simpleCache->store($expiresKey, array());
    //        $simpleCache->store($tagsKey, array());
    //    }
    //
    //
    //    /**
    //     * @param string $key
    //     */
    //    private function removeKeyFromTagList($key)
    //    {
    //        $tagsList = $this->retrieveTagsList();
    //
    //        /*
    //         * $tags = array(
    //         *      'tag-name1' => array('key-name1', 'key-name2', 'key-name3')
    //         *      'tag-name2' => array('key-name4', 'key-name4', 'key-name2')
    //         * );
    //         */
    //        $newTags = array();
    //
    //        foreach($tagsList as $tagName => $keyList)
    //        {
    //            $newTags[$tagName] = array_diff($keyList, array($key));
    //        }
    //
    //        $this->storeTagsList($tagsList);
    //    }
    //
    //
    //    /**
    //     * @param string $key
    //     * @param array  $tags
    //     */
    //    private function addTags($key, array $tags = null)
    //    {
    //        if($tags == null)
    //        {
    //            return;
    //        }
    //
    //        $cachedTagList = $this->retrieveTagsList();
    //
    //        foreach($tags as $tag)
    //        {
    //            if(isset($cachedTagList[$tag]) == true)
    //            {
    //                $keyList             = $cachedTagList[$tag];
    //                $keyList[]           = $key;
    //                $keyList             = array_unique($keyList);
    //                $cachedTagList[$tag] = $keyList;
    //            }
    //            else
    //            {
    //                $cachedTagList[$tag] = array($key);
    //            }
    //        }
    //
    //        $this->storeTagsList($cachedTagList);
    //    }
    //
    //
    //    /**
    //     * @param string   $key
    //     * @param int|null $expiresInSeconds
    //     */
    //    private function setExpires($key, $expiresInSeconds = null)
    //    {
    //        $expiresInSeconds = intval($expiresInSeconds);
    //        $defaultExpires   = $this->_defaultExpiresInSecs;
    //
    //        if($expiresInSeconds == 0)
    //        {
    //            $expiresInSeconds = $defaultExpires;
    //        }
    //
    //        if($expiresInSeconds == 0)
    //        {
    //            $expiresInSeconds = 99999999; // forever (~3.1689 years)
    //        }
    //
    //        $expiresList       = $this->retrieveExpiresList();
    //        $expiresList[$key] = date('Y-m-d H:i:s', time() + $expiresInSeconds);
    //
    //        $this->storeExpiresList($expiresList);
    //    }
    //
    //
    //    /**
    //     * @param string $key
    //     */
    //    private function removeKeyFromExpiresList($key)
    //    {
    //        $expiresList = $this->retrieveExpiresList();
    //
    //        if(isset($expiresList[$key]) == true)
    //        {
    //            unset($expiresList[$key]);
    //            $this->storeExpiresList($expiresList);
    //        }
    //    }
    //
    //
    //    /**
    //     * @param string $key
    //     */
    //    private function removeKeyFromMetadata($key)
    //    {
    //        $this->removeKeyFromTagList($key);
    //        $this->removeKeyFromExpiresList($key);
    //    }
    //
    //
    //    private function retrieveExpiresList()
    //    {
    //        $simpleCache = $this->_simpleCache;
    //        $expiresKey  = $this->_expiresKey;
    //        $expiresList = $simpleCache->retrieve($expiresKey);
    //
    //        return $expiresList;
    //    }
    //
    //
    //    /**
    //     * @param array $expiresList
    //     */
    //    private function storeExpiresList(array $expiresList)
    //    {
    //        $simpleCache = $this->_simpleCache;
    //        $expiresKey  = $this->_expiresKey;
    //        $simpleCache->store($expiresKey, $expiresList);
    //    }
    //
    //
    //    private function retrieveTagsList()
    //    {
    //        $simpleCache = $this->_simpleCache;
    //        $tagsKey     = $this->_tagsKey;
    //        $tagsList    = $simpleCache->retrieve($tagsKey);
    //
    //        return $tagsList;
    //    }
    //
    //
    //    /**
    //     * @param array $tagsList
    //     */
    //    private function storeTagsList(array $tagsList)
    //    {
    //        $simpleCache = $this->_simpleCache;
    //        $tagsKey     = $this->_tagsKey;
    //        $simpleCache->store($tagsKey, $tagsList);
    //    }
    //
    //
    //    /**
    //     * @param string $tag
    //     */
    //    private function removeTagFromTagsList($tag)
    //    {
    //        $tagList = $this->retrieveTagsList();
    //        unset($tagList[$tag]);
    //        $this->storeTagsList($tagList);
    //    }


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
}
