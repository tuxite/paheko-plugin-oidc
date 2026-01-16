<?php

namespace Paheko;

use Paheko\Users\Session;

use Paheko\Plugin\OIDC\AuthorizationManager;

$session = Session::getInstance();
$session->requireAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN);

$csrf_key = 'plugin_openid_connect';
$n = new AuthorizationManager($plugin);

// Add a new client
$form->runIf(condition: 'add', fn: function () use ($n): void {

	$secret = $_POST['client_secret'] ?? null;

	if (!$secret || strlen($secret) < 32) {
		throw new UserException('Secret invalide');
	}

	$n->add(
		name: f('name'),
		description: f('description'),
		scope: f('scope'),
		redirect: f('redirect'),
		saved_search: f('allowed'),
		client_id: f('client_id'),
		secret_hash: password_hash($secret, PASSWORD_DEFAULT),
		is_confidential: (int)f('is_confidential')
	);
	$n->save();
}, csrf_key: $csrf_key, redirect: './config.php?ok');

// Delete a client
$form->runIf(condition: 'delete' ?? null !== null, fn: function () use ($n) {
	$n->remove((int)f(key: 'client_pk'));
	$n->save();
}, csrf_key: null, redirect: './config.php?ok');

// Enable a client
$form->runIf(condition: 'enable' ?? null !== null, fn: function () use ($n) {
	$n->enable((int)f(key: 'client_pk'));
	$n->save();
}, csrf_key: null, redirect: './config.php?ok');

// Disable a client
$form->runIf(condition: 'disable' ?? null !== null, fn: function () use ($n) {
	$n->disable((int)f(key: 'client_pk'));
	$n->save();
}, csrf_key: null, redirect: './config.php?ok');

// Template
$client_type = [
	"0" => "Client public",
	"1" => "Client privé"
];

$tpl->assign('clients', $n->list());
$tpl->assign('saved_searches', $n->saved_searches());
$tpl->assign('search_url', ADMIN_URL . 'users/search.php');
$tpl->assign('client_type', $client_type);

$tpl->assign(compact('csrf_key'));
$tpl->register_modifier('json_decode', 'json_decode');

$tpl->display(PLUGIN_ROOT . '/templates/config.tpl');
