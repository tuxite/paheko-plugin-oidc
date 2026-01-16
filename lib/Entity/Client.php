<?php

namespace Paheko\Plugin\OIDC\Entity;

use Paheko\Entity;

class Client extends Entity {
	const TABLE = 'plugin_oidc_clients';

	protected ?int $id = null;
	protected string $client_id;
	protected string $name;
	protected string $description;
	protected string $client_secret_hash;
	protected bool $is_confidential = true;
	protected bool $enabled = true;
	protected ?string $created_at = null;
	protected ?string $updated_at = null;

}
