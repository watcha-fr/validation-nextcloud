<?php
/** @var \OCP\IL10N $l */
script('validation', 'validation');
style('validation', 'validation');
?>
<div id="validation-app">
	<div class="v-card">
		<header class="v-header">
			<h2>Validation de documents</h2>
			<p class="v-subtitle">Déposez un ou plusieurs documents et indiquez les validateurs, dans l'ordre de validation.</p>
		</header>

		<form id="validation-form" novalidate>
			<!-- Documents -->
			<section class="v-section">
				<h3 class="v-label">Documents à valider</h3>
				<label id="v-dropzone" for="vfiles" class="v-dropzone">
					<span class="v-dropzone-icon" aria-hidden="true">⬆</span>
					<span class="v-dropzone-text">Cliquez pour choisir des fichiers<br><small>ou glissez-déposez ici</small></span>
					<input type="file" id="vfiles" name="files[]" multiple required hidden>
				</label>
				<ul id="v-filelist" class="v-filelist"></ul>
			</section>

			<!-- Validateurs -->
			<section class="v-section">
				<h3 class="v-label">Validateurs <span class="v-hint">(dans l'ordre de validation)</span></h3>
				<div id="validators" class="v-validators"></div>
				<button type="button" id="add-validator" class="v-add">＋ Ajouter un validateur</button>
			</section>

			<div class="v-actions">
				<button type="submit" class="primary" id="submit-btn">Envoyer la demande</button>
			</div>

			<div id="result" class="v-result" role="status" hidden></div>
		</form>
	</div>
</div>
