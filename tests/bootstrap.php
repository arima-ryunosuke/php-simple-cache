<?php

use ryunosuke\StreamWrapper\Stream\MysqlStream;
use ryunosuke\StreamWrapper\Stream\RedisStream;
use ryunosuke\StreamWrapper\Stream\S3Stream;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/ryunosuke/phpunit-extension/inc/bootstrap.php';

file_put_contents(__DIR__ . '/../src/SimpleCache/Utils.php', \ryunosuke\Functions\Transporter::exportClass(ryunosuke\SimpleCache\Utils::class, ['var_export3']));

\ryunosuke\PHPUnit\Actual::generateStub(__DIR__ . '/../src', __DIR__ . '/.stub', 2);

\ryunosuke\PHPUnit\Actual::$functionNamespaces = [];

define('STREAM_WRAPPER_DEBUG', true);

defined('REDIS_URL') or define('REDIS_URL', null);
defined('MYSQL_URL') or define('MYSQL_URL', null);
defined('S3_URL') or define('S3_URL', null);

if (REDIS_URL) {
    RedisStream::register(REDIS_URL, ['url' => true]);
    [$driver, $dbIndex] = RedisStream::resolve(REDIS_URL);
    $driver->select($dbIndex)->flushDB();
}
if (MYSQL_URL) {
    MysqlStream::register(MYSQL_URL, ['url' => true]);
    [$driver, $tablename] = MysqlStream::resolve(MYSQL_URL);
    $driver->exec("TRUNCATE TABLE $tablename");
}
if (S3_URL) {
    S3Stream::register(S3_URL, ['url' => true]);
    [$driver, $bucket] = S3Stream::resolve(S3_URL);
    $driver->refreshBucket($bucket);
}
