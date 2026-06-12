<?php
/** @var array $_ */
script('validation', 'admin-settings');
style('validation', 'admin-settings');
?>
<div id="validation-admin" class="section">
	<h2>Validation de documents</h2>
	<p class="settings-hint">Configuration du circuit de validation n8n.</p>

	<div class="field">
		<label for="val-webhook"><strong>URL du webhook n8n</strong></label><br>
		<input type="url" id="val-webhook" value="<?php p($_['webhook_url']); ?>"
		       placeholder="https://VOTRE-N8N/webhook/validation">
	</div>

	<div class="field">
		<label for="val-service"><strong>Compte de service (UID)</strong></label><br>
		<input type="text" id="val-service" value="<?php p($_['service_user']); ?>"
		       placeholder="ex. watcha">
	</div>

	<button id="val-save" class="primary">Enregistrer</button>
	<span id="val-save-result"></span>

	<hr>

	<div class="field">
		<h3>Mot de passe d'application pour n8n</h3>
		<p class="settings-hint">
			Génère (ou régénère) un mot de passe d'application pour le compte de service,
			à reporter dans le credential NextCloud de n8n. L'ancien est révoqué.
		</p>
		<button id="val-genpw">Générer / régénérer</button>
		<div id="val-pw-result"></div>
	</div>
</div>
