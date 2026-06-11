/* App Validation — frontend vanilla (sans build) */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var form = document.getElementById('validation-form');
		var addBtn = document.getElementById('add-validator');
		var result = document.getElementById('result');
		var submitBtn = document.getElementById('submit-btn');
		if (!form) { return; }

		// Ajouter dynamiquement des champs validateur
		addBtn.addEventListener('click', function () {
			var container = document.getElementById('validators');
			var n = container.querySelectorAll('.validator').length + 1;
			var input = document.createElement('input');
			input.type = 'email';
			input.className = 'validator';
			input.placeholder = 'Email du validateur ' + n + ' (facultatif)';
			container.appendChild(document.createElement('br'));
			container.appendChild(input);
		});

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			result.textContent = '';

			var fd = new FormData();
			var files = document.getElementById('vfiles').files;
			if (!files.length) { result.textContent = 'Veuillez sélectionner au moins un document.'; return; }
			for (var i = 0; i < files.length; i++) {
				fd.append('files[]', files[i]);
			}
			var validators = document.querySelectorAll('.validator');
			var count = 0;
			validators.forEach(function (v) {
				if (v.value.trim()) { fd.append('validators[]', v.value.trim()); count++; }
			});
			if (count === 0) { result.textContent = 'Veuillez saisir au moins un validateur.'; return; }

			submitBtn.disabled = true;
			submitBtn.textContent = 'Envoi…';

			fetch(OC.generateUrl('/apps/validation/submit'), {
				method: 'POST',
				headers: { requesttoken: OC.requestToken },
				body: fd,
			})
				.then(function (r) { return r.json(); })
				.then(function (d) {
					if (d.status === 'ok') {
						form.reset();
						// remet un seul champ validateur
						var container = document.getElementById('validators');
						container.querySelectorAll('.validator').forEach(function (el, idx) { if (idx > 0) { el.remove(); } });
						result.textContent = 'Votre demande a bien été envoyée (' + d.files.length + ' document(s)).';
					} else {
						result.textContent = 'Erreur : ' + (d.error || 'inconnue');
					}
				})
				.catch(function () { result.textContent = 'Erreur réseau lors de l\'envoi.'; })
				.finally(function () { submitBtn.disabled = false; submitBtn.textContent = 'Envoyer la demande'; });
		});
	});
})();
