{include file="_head.tpl" title="OpenID Connect — Configuration"}

{form_errors}

{if isset($_GET['ok']) && !$form->hasErrors()}
	<p class="confirm block">La configuration a été enregistrée.</p>
{/if}

<table class="list">
	<thead>
		<tr>
			<td>Client</td>
			<td>Scopes</td>
			<td>Identifiant client</td>
			<td>Type</td>
			<td>URL</td>
			<td>Liste d'utilisateurs</td>
			<td class="actions"></td>
		</tr>
	</thead>
	<tbody>
	{foreach from=$clients item="n"}
		<tr>
			<th>{$n.client_name}</th>
			<td><ul>
			{foreach from=$n.allowed_scopes|json_decode item="scope"}
				<li>{$scope}</li>
			{/foreach}
			</ul></td>
			<td>{$n.oauth_client_id}</td>
			<td>{if $n.is_confidential ?? true}Privé{else}Public{/if}</td>
			<td><ul>
			{foreach from=$n.redirect_uris_json|json_decode item="url"}
				<li><a href="{$url}">{$url}</li>
			{/foreach}
			</ul></td>
			<td>
				<a href={$search_url}?id={$n.search_id}>{$saved_searches[$n.search_id]}</a>
			</td>
			<td class="actions">
				<form method="post" action="{$self_url}">
				{csrf_field key=$csrf_key}
				{input type="hidden" name="client_pk" default=$n.client_pk}
				{if $n.client_enabled}
					{button name="disable" label="Désactiver" shape="eye-off" type="submit"}
			 	{else}
					{button name="enable" label="Activer" shape="eye-off" type="submit"}
				{/if}
					{button name="delete" label="Supprimer" shape="delete" type="submit"}
				</form>
			</td>
		</tr>
	{/foreach}
	</tbody>
</table>


<form method="post" action="{$self_url}">
	<fieldset>
		<legend>Ajouter un client OpenID</legend>
		<dl>
			{input type="text" name="name" label="Nom du client" required=true}
			{input type="text" name="description" label="Description"}
			{input type="text" name="scope" label="Scope" required=true placeholder="openid profile"}
			{input type="textarea" name="redirect" label="Adresse(s) de redirection" required=true}
			<dt>
				<label for="client_id">Identifiant client</label>
			</dt>
			<dd>
				{literal}
				<input type="text" id="client_id" name="client_id" readonly>
				{/literal}
			</dd>
			<dt>
				<label for="client_secret">Secret (à conserver)</label>
			</dt>
			<dd>
				{literal}
				<input type="text" id="client_secret" name="client_secret" readonly>
				{/literal}
			</dd>
		</dl>

		<dl>
			{input type="select" name="is_confidential" label="Type de client" options=$client_type required=true}
		</dl>

		<dl>
			{input type="select" name="allowed" label="Utilisateurs autorisés" options=$saved_searches required=true}
		</dl>

	</fieldset>
	<p class="submit">
		{csrf_field key=$csrf_key}
		{button name="add" label="Ajouter" shape="right" type="submit" class="main"}
	</p>
</form>

{literal}
<script type="text/javascript">
function generateSecret() {
    const bytes = new Uint8Array(32);
    crypto.getRandomValues(bytes);
    return btoa(String.fromCharCode(...bytes))
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=+$/, '');
}

function slugify(str) {
  return String(str)
    .normalize('NFKD') // split accented characters into their base characters and diacritical marks
    .replace(/[\u0300-\u036f]/g, '') // remove all the accents, which happen to be all in the \u03xx UNICODE block.
    .trim() // trim leading or trailing whitespace
    .toLowerCase() // convert to lowercase
    .replace(/[^a-z0-9 -]/g, '') // remove non-alphanumeric characters
    .replace(/\s+/g, '-') // replace spaces with hyphens
    .replace(/-+/g, '-'); // remove consecutive hyphens
}

// Sélection de l'élément input
const inputElement = document.querySelector('#f_name');

// Fonction à exécuter lors du changement
function handleInputChange(event) {
	var valeur = event.target.value;
	document.querySelector('#client_id').value = slugify(valeur);
}

// Écouteur d'événement pour l'événement 'input'
inputElement.addEventListener('input', handleInputChange);

document.querySelector('#client_secret').value = generateSecret();
</script>
{/literal}

{include file="_foot.tpl"}
