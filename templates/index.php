<?php
/** @var \OCP\IL10N $l */
script('validation', 'validation');
style('validation', 'validation');
?>
<div id="validation-app" class="section">
	<h2>Validation de documents</h2>
	<p class="settings-hint">Déposez un ou plusieurs documents et indiquez les validateurs (dans l'ordre de validation).</p>

	<form id="validation-form">
		<div class="field">
			<label for="vfiles"><strong>Documents à valider</strong></label><br>
			<input type="file" id="vfiles" name="files[]" multiple required>
		</div>

		<div id="validators" class="field">
			<label><strong>Validateurs (emails)</strong></label><br>
			<input type="email" class="validator" required placeholder="Email du validateur 1">
		</div>

		<button type="button" id="add-validator">+ Ajouter un validateur</button>
		<br><br>
		<button type="submit" class="primary" id="submit-btn">Envoyer la demande</button>
	</form>

	<div id="result" class="field"></div>
</div>
