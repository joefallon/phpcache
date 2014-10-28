<?php
use JoeFallon\KissTest\UnitTest;
require('config/main.php');

new \tests\JoeFallon\PhpCache\ApcCacheTests();
new \tests\JoeFallon\PhpCache\TaggedCacheTests();

UnitTest::getAllUnitTestsSummary();

//echo "<pre>" . print_r(apc_cache_info('user'), true);
