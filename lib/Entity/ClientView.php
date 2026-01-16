<?php

namespace Paheko\Plugin\OIDC\Entity;

use Paheko\Entity;

class ClientView extends Entity {
	const TABLE = 'plugin_oidc_view_clients_authorizations';

	protected int $client_pk;
	protected string $client_name;
	protected ?string $client_description;
	protected string $oauth_client_id;
    protected string $client_created_at;
    protected ?string $client_updated_at;
	protected bool $is_confidential;
	protected bool $client_enabled;
	protected string $redirect_uris_json;
    protected int $search_id;
    protected string $search_label;
    protected string $allowed_scopes;
	protected bool $is_authorized;

}
