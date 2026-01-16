<?php

namespace Paheko\Plugin\OIDC\Entity;

use Paheko\Entity;

class SearchState extends Entity {
    const TABLE = 'plugin_oidc_search_state';

    protected ?int $id = null;
    protected int $search_id;
    protected string $last_refresh;
}
