CREATE TABLE IF NOT EXISTS plugin_oidc_clients (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    name                TEXT NOT NULL,
    description         TEXT,

    client_id           TEXT UNIQUE, -- client_id
    client_secret_hash  TEXT NOT NULL,

    is_confidential     INTEGER NOT NULL DEFAULT 1, -- public vs confidentiel
    enabled             INTEGER NOT NULL DEFAULT 1,

    created_at          TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TEXT
);

CREATE TABLE IF NOT EXISTS plugin_oidc_client_redirects (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id    INTEGER NOT NULL,
    redirect_uri TEXT NOT NULL,
    enabled      INTEGER NOT NULL DEFAULT 1,

    created_at   TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (client_id) REFERENCES plugin_oidc_clients(id) ON DELETE CASCADE
);

CREATE UNIQUE INDEX IF NOT EXISTS plugin_oidc_client_redirect_unique
ON plugin_oidc_client_redirects (client_id, redirect_uri);

CREATE TABLE IF NOT EXISTS plugin_oidc_search_members (
    search_id   INTEGER NOT NULL,
    user_id     INTEGER NOT NULL,
    updated_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (search_id, user_id),
    FOREIGN KEY (search_id) REFERENCES searches(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS plugin_oidc_search_state (
    search_id     INTEGER PRIMARY KEY,
    last_refresh  TEXT NOT NULL,

    FOREIGN KEY (search_id) REFERENCES searches(id)
);

CREATE TABLE IF NOT EXISTS plugin_oidc_authorizations (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,

    client_id       INTEGER    NOT NULL,
    redirect_uri    TEXT    NOT NULL,
    search_id       INTEGER    NOT NULL,

    scopes          TEXT    NOT NULL, -- JSON ["openid","email"]

    enabled         INTEGER NOT NULL DEFAULT 1,

    created_at      TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TEXT,

    FOREIGN KEY (client_id) REFERENCES plugin_oidc_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (search_id) REFERENCES searches(id)
);

CREATE INDEX IF NOT EXISTS plugin_oidc_auth_lookup
ON plugin_oidc_authorizations (
    client_id,
    redirect_uri,
    search_id,
    enabled
);

CREATE VIEW IF NOT EXISTS plugin_oidc_view_clients_authorizations AS
SELECT
    c.id            AS client_pk,
    c.name          AS client_name,
    c.description   AS client_description,
    c.client_id     AS oauth_client_id,
    c.is_confidential,
    c.enabled       AS client_enabled,
    c.created_at    AS client_created_at,
    c.updated_at    AS client_updated_at,

    /* Redirect URIs (table dédiée, logique OIDC correcte) */
    (
        /* SELECT GROUP_CONCAT(redirect_uri, ', ') */
        SELECT json_group_array(redirect_uri)
        FROM (
            SELECT DISTINCT r.redirect_uri
            FROM plugin_oidc_client_redirects r
            WHERE r.client_id = c.id
              AND r.enabled = 1
        )
    ) AS redirect_uris_json,

    /* Search unique associée au client */
    (
        SELECT a.search_id
        FROM plugin_oidc_authorizations a
        WHERE a.client_id = c.id
        LIMIT 1
    ) AS search_id,

    (
        SELECT s.label
        FROM plugin_oidc_authorizations a
        JOIN searches s ON s.id = a.search_id
        WHERE a.client_id = c.id
        LIMIT 1
    ) AS search_label,

    /* Scopes distincts toutes autorisations confondues */
    (
        SELECT json_group_array(scope)
        FROM (
            SELECT DISTINCT je.value AS scope
            FROM plugin_oidc_authorizations a
            JOIN json_each(a.scopes) je
            WHERE a.client_id = c.id
        )
    ) AS allowed_scopes,

    /* Au moins une autorisation active */
    EXISTS (
        SELECT 1
        FROM plugin_oidc_authorizations a
        WHERE a.client_id = c.id
          AND a.enabled = 1
    ) AS is_authorized

FROM plugin_oidc_clients c;
