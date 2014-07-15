<?php
use JoeFallon\KissTest\UnitTest;
require('config/main.php');

new \tests\JoeFallon\Cache\ApcCacheTests();
new \tests\JoeFallon\Cache\TaggedCacheTests();

UnitTest::getAllUnitTestsSummary();
