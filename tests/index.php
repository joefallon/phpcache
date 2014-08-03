<?php
use JoeFallon\KissTest\UnitTest;
require('config/main.php');

new \tests\JoeFallon\Cache\ApcCacheTests();
new \tests\JoeFallon\Cache\TaggedCacheTests();

UnitTest::getAllUnitTestsSummary();

echo "<pre>" . print_r(apc_cache_info('user'), true);
