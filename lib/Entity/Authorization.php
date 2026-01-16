<?php

namespace Paheko\Plugin\OIDC\Entity;

use Paheko\Entity;

class Authorization extends Entity{
	const TABLE = 'plugin_oidc_authorizations';

    protected ?int $id = null;
	protected int $client_id;
	protected string $redirect_uri;
	protected string $scopes;
	protected bool $enabled = true;
    protected int $search_id;
	protected ?string $created_at = null;
	protected ?string $updated_at = null;
}
