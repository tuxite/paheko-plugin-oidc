<?php

namespace Paheko;

use Paheko\DB;

$db = DB::getInstance();

$schemaFile = $plugin->path(). '/tables.sql';

if (!file_exists($schemaFile)) {
    throw new \RuntimeException('tables.sql missing');
}

$sql = file_get_contents($schemaFile);

$db->begin();
$db->exec($sql);
$db->commit();
