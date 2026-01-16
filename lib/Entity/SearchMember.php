<?php

namespace Paheko\Plugin\OIDC\Entity;

use Paheko\Entity;

class SearchMember extends Entity {
    const TABLE = 'plugin_oidc_search_members';

    protected ?int $id;
    protected int $search_id;
    protected int $user_id;
	protected ?string $updated_at = null;

}
