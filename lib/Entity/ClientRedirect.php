<?php

namespace Paheko\Plugin\OIDC\Entity;

use Paheko\Entity;

class ClientRedirect extends Entity {
	const TABLE = 'plugin_oidc_client_redirects';

	protected ?int $id = null;
	protected int $client_id;
	protected string $redirect_uri;
	protected bool $enabled = true;
	protected ?string $created_at = null;
}
