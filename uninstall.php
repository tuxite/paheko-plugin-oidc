<?php

namespace Paheko;

use Paheko\DB;

$db = DB::getInstance();

// Suppression tables
$db->exec("DROP VIEW IF EXISTS  plugin_oidc_view_clients_authorizations;");
$db->exec('DROP TABLE IF EXISTS plugin_oidc_authorizations;');
$db->exec('DROP TABLE IF EXISTS plugin_oidc_search_members;');
$db->exec('DROP TABLE IF EXISTS plugin_oidc_search_state;');
$db->exec('DROP TABLE IF EXISTS plugin_oidc_client_redirects;');
$db->exec('DROP TABLE IF EXISTS plugin_oidc_clients;');
