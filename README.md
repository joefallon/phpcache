Joe's PHP Cache
===============

By [Joe Fallon](http://blog.joefallon.net)

Normally, cache entries are invalidated using one of two methods:

1. The cache entry is explicitly removed via a call to a `remove()` method of
   some sort.
2. The cache entry is removed due to time-based expiration.
3. The cache entry is removed due to being ejected to make room for newer entries.

Unfortunately, this does not allow a whole group of cache entries to be removed
because they are dependent on a common factor. For example, let's assume we have
a blog and the blog has many posts. Additionally, let's assume that a list of all
of the posts has been added to the cache. Let's set the cache key for the group
of posts to `all_posts`. Additionally, let's add a tag to this cache entry called
`posts_table`.

Then, let's say we cache a single post. Let's give it a key of `post_id_45`. Also,
let's give it a tag of `posts_table`. So now we have all the posts as a cahce
entry and the post with an ID of 45 as a cache entry. If we delete the post with
id of 45, then of course remove it from the cache. However, we should also delete
by tag every cache entry with the tag `posts_table`. This will remove the list of
all posts from the previous paragraph. Typically, there are many cache entries
that are dependent on the contents of the posts table not changing. Using one or
more tags to  clear groups of cache entries make cache management much easier
and less prone to error.

TaggedCache Class
-----------------

The `TaggedCache` class allows the usage of simple key/value cache as a cache
backend. In fact, any class that implements `ISimpleCache` can be used as a
cache backend for `TaggedCache`. Currently, only APC is implemented. However,
it is super easy to add more and I look forward to pull requests.

In addition to tags, `TaggedCache` supports several additional features:

1. The cache can be namespaced to allow more than one cache to be segragated
   within a backend.
2. A default expires time in seconds is available to allow more aggressive
   cache expiration than the backend default expires time. This can be useful
   for data retrieved from other web services (e.g. feeds).
3. Each cache entry can have a custom expires time.

Here is a list of methods available in `TaggedCache`:

```php
store($key, $value, array $tags = null, $expiresInSeconds = null)
retrieve($key)
exists($key)
remove($key)
removeByTag($tag)
removeAll();
```

