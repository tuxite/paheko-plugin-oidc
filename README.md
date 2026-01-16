# Plugin OIDC pour Paheko

Ce plugin permet de gérer les clients et les autorisations d'accès pour le serveur [Paheko OIDC Server](https://github.com/tuxite/paheko-oidc-server). Il sert d'interface de gestion au sein de Paheko pour configurer qui peut se connecter et avec quels privilèges.

## Architecture des données

Le plugin s'appuie sur les tables suivantes :

| Table | Description |
| --- | --- |
| `plugin_oidc_clients` | Registre des clients (ID, nom, type public/confidentiel, état et hash du secret). |
| `plugin_oidc_client_redirects` | Liste blanche des URIs de redirection autorisées par client (indispensable pour la sécurité OAuth2). |
| `plugin_oidc_search_members` | Cache des utilisateurs autorisés, synchronisé à partir des recherches enregistrées de Paheko. |
| `plugin_oidc_search_state` | Suivi de l'horodatage de la dernière mise à jour pour chaque recherche enregistrée. |
| `plugin_oidc_authorizations` | Lien entre un client, une recherche enregistrée et les scopes (openid, email, etc.) accordés. |

Une vue globale, `plugin_oidc_view_clients_authorizations`, facilite la lecture consolidée de la configuration de chaque client.

## Principe de fonctionnement

Le serveur externe (**Paheko OIDC Server**) interagit avec Paheko selon deux axes :

1. **Lecture des données** : Il accède directement à la base `association.sqlite` (en lecture seule) pour valider les clients et les sessions.
2. **Synchronisation (Refresh)** : Le serveur déclenche la mise à jour de la table `plugin_oidc_search_members` en appelant l'URL `/p/oidc/refresh`.
* Cet appel est sécurisé par une signature **HMAC** (SHA-256).
* La clé secrète est générée lors de l'installation du plugin et stockée dans la configuration (`HMAC_SECRET`).


### Gestion des clients

L'interface d'administration permet de piloter le cycle de vie des clients :

* **Secret Client** : Généré aléatoirement par le navigateur à la création. **Attention :** Par mesure de sécurité, le secret n'est affiché qu'une seule fois. Seul son hachage est stocké en base.
* **Types de clients** :
* **Confidentiel (Privé)** : (ex: WordPress, Nextcloud) Le `client_secret` est requis pour l'échange de jetons.
* **Public** : (ex: Applications mobiles ou Single Page Apps) Le secret n'est pas utilisé ; la sécurité repose sur d'autres mécanismes (PKCE recommandé).


### Filtrage par Recherches Enregistrées

Le point fort de ce plugin est l'utilisation des **Recherches Enregistrées** de Paheko pour définir le contrôle d'accès.
Cela permet de restreindre dynamiquement l'accès OIDC en fonction de critères précis :

* Membres à jour de cotisation.
* Utilisateurs inscrits à une activité spécifique.
* Membres d'un groupe de travail particulier.

## Installation

1. Déposer le dossier du plugin dans le répertoire `plugins/` de votre instance Paheko (ou `/data/plugins`).
2. Activer le plugin via l'interface d'administration de Paheko.
3. Les tables SQL et la clé secrète HMAC seront automatiquement initialisées.
4. Consulter la documentation des [extensions Paheko](https://fossil.kd2.org/paheko/wiki?name=Extensions) pour plus de détails sur la gestion des plugins.
