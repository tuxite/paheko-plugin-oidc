<?php

namespace Paheko\Plugin\OIDC;

use Paheko\DB;

use Paheko\Entities\Plugin;
use Paheko\Entities\Search as SE;

use Paheko\Users\Session;
use Paheko\Search;

use Paheko\ValidationException;

use KD2\DB\EntityManager;
use Paheko\UserException;

use Paheko\Plugin\OIDC\Entity;

class AuthorizationManager
{

	protected Plugin $plugin;
	protected array $clients;

	public function __construct(Plugin $plugin)
	{
		$this->plugin = $plugin;
		$this->set_HMAC();
	}

	private function get_clients(){
		$em = EntityManager::getInstance(Entity\ClientView::class);
		return $em->all('SELECT * FROM @TABLE;');
	}

	public function saved_searches(): array
	{
		$data = Search::list('users', Session::getUserId());

		// Convert as an array of id/label
		$out = [];
		foreach ($data as $item) {
			$out[$item->id] = $item->label;
		}

		return $out;
	}

/**
     * Adds a new OIDC client.
     *
     * @param array $data
     * @return Entity\Client
     */
    private function add_client(array $data): Entity\Client
    {
        $client = new Entity\Client();
        $client->client_id = $data["client_id"];
        $client->client_secret_hash = $data["client_secret_hash"];
        $client->name = $data["name"];
        $client->description = $data["description"];
		$client->is_confidential = $data["is_confidential"];
        $client->save();
        return $client;
    }

	public function add(
		string $name,
		string $scope,
		string $description,
		string $redirect,
		string $saved_search,
		string $client_id,
		string $secret_hash,
		bool $is_confidential)
	{

		$client_data = array(
			"client_id" => $client_id,
			"name" => trim($name),
			"description" => trim($description),
			"client_secret_hash" => $secret_hash,
			"is_confidential" => $is_confidential,
		);
		$client = $this->add_client($client_data);
		$client_id = $client->id();

		// Add ClientRedirect
		// URL validation
		$splitted_url = explode("\n", $redirect);
		$urls = [];
		foreach ($splitted_url as $line) {

			// Supprimer les espaces en début et fin de ligne
			$cleaned_line = trim($line);

			// Ajouter la ligne nettoyée au tableau si elle n'est pas vide
			if (empty($cleaned_line)) {
				continue;
			}

			if (filter_var($cleaned_line, FILTER_VALIDATE_URL)) {
				$urls[] = $cleaned_line;
			} else {
				throw new ValidationException('Invalid URL: ' . $cleaned_line);
			}

		}
		sort($urls);

		// Validation des claims/scopes
		$splitted = explode(" ", $scope);
		sort($splitted);
		$scopes = json_encode($splitted);

		// User list validation (check if the saved search exists)
		if (!is_numeric($saved_search)) {
			throw new ValidationException("Invalid saved search id: " . $saved_search);
		}

		// Creation des membres
		$search_id = $this->refresh_search_members(search_id: (int) $saved_search);

		foreach ($urls as $url) {
			// Create ClientRedirect Entity
			$client_redirect = new Entity\ClientRedirect();
			$client_redirect->client_id = $client_id;
			$client_redirect->redirect_uri = $url;
			$client_redirect->save();

			// Create Authorization Entity
			$authorization = new Entity\Authorization();
			$authorization->client_id = $client_id;
			$authorization->redirect_uri = $url;
			$authorization->scopes = $scopes;
			$authorization->search_id = $search_id;
			$authorization->save();
		}
	}

	private function get_search(int $search_id){
		$s = Search::get($search_id);

		if (!$s) {
			throw new ValidationException('Recherche inconnue ou invalide');
		}

		return $s;
	}

	public function refresh_search_members(int $search_id){
		$s = $this->get_search($search_id);

		if (!$s) {
			throw new ValidationException('Recherche inconnue ou invalide');
		}

		// Delete the old members if any
		$this->delete_search_members($search_id);

		// Add the new members
		$this->add_search_members($s);

		// Update the state
		$this->refresh_state($search_id);

		return $search_id;
	}

	private function add_search_members(SE $s){
		$result = $s->query();
		while($u=$result->fetchArray()){
			$member = new Entity\SearchMember();
			$member->user_id = $u["id"];
			$member->search_id = $s->id;
			$member->save();
			}
	}

	private function delete_search_members(int $search_id){
		DB::getInstance()->exec("DELETE FROM plugin_oidc_search_members WHERE search_id=$search_id");
	}

	public function refresh_state(int $search_id){
		$query = <<<EOF
		INSERT INTO plugin_oidc_search_state (search_id, last_refresh)
		VALUES($search_id, CURRENT_TIMESTAMP)
		ON CONFLICT(search_id)
		DO UPDATE SET last_refresh=CURRENT_TIMESTAMP;
		EOF;

		DB::getInstance()->exec($query);
	}

	public function remove(string $id): void
	{
		$client = $this->getClient($id);
		$client->delete();
	}

	public function enable(string $id): void
	{
		$client = $this->getClient($id);
		$client->enabled = true;
		$client->save();
		$authorizations = $this->getAuthorizations(client_id: $id);
		foreach ($authorizations as $authorization) {
			$authorization->enabled = true;
			$authorization->save();
		}
	}

	public function disable(string $id): void
	{
		$client = $this->getClient($id);
		$client->enabled = false;
		$client->save();
		$authorizations = $this->getAuthorizations(client_id: $id);
		foreach ($authorizations as $authorization) {
			$authorization->enabled = false;
			$authorization->save();
		}
	}

	public function refresh(string $id): void
	{
		$authorizations = $this->getAuthorizations(client_id: $id);
		foreach ($authorizations as $authorization) {
			$this->refresh_search_members($authorization->search_id);
		}
	}

	public function save(): void
	{
		$this->plugin->save();
	}

	public function list(): array
	{
		$this->clients = $this->get_clients();

		return $this->clients;
	}

	public function getClient(int $id): ?Entity\Client
    {
        return EntityManager::findOneById(Entity\Client::class, $id);
    }

	public function getAuthorizations(int $client_id): array
    {
		$query = "SELECT * FROM @TABLE WHERE client_id = ?;";
		$em = EntityManager::getInstance(Entity\Authorization::class);
		return $em->all($query, $client_id);
    }

	public function set_HMAC(){
		if (null === $this->plugin->getConfig("HMAC_SECRET")){
			$this->plugin->setConfigProperty('HMAC_SECRET', bin2hex(random_bytes(32)));
			$this->plugin->save();
		}
	}

}
