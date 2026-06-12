/* App Validation — paramètres d'administration (vanilla) */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var saveBtn = document.getElementById('val-save');
		var genBtn = document.getElementById('val-genpw');
		if (!saveBtn) { return; }

		function post(url, data) {
			return fetch(OC.generateUrl(url), {
				method: 'POST',
				headers: { requesttoken: OC.requestToken, 'Content-Type': 'application/json' },
				body: JSON.stringify(data || {}),
			}).then(function (r) { return r.json(); });
		}

		function esc(s) {
			return String(s).replace(/[&<>"']/g, function (c) {
				return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
			});
		}

		saveBtn.addEventListener('click', function () {
			var res = document.getElementById('val-save-result');
			res.textContent = ' …';
			post('/apps/validation/settings/config', {
				webhook_url: document.getElementById('val-webhook').value.trim(),
				service_user: document.getElementById('val-service').value.trim(),
			})
				.then(function (d) { res.textContent = d.status === 'ok' ? ' Enregistré ✓' : (' Erreur : ' + (d.error || '')); })
				.catch(function () { res.textContent = ' Erreur réseau'; });
		});

		genBtn.addEventListener('click', function () {
			var res = document.getElementById('val-pw-result');
			res.textContent = 'Génération…';
			post('/apps/validation/settings/apppassword', {})
				.then(function (d) {
					if (d.status === 'ok') {
						res.innerHTML =
							'<p><strong>Mot de passe généré</strong> (copiez-le, il ne sera plus affiché) :</p>' +
							'<p>Utilisateur : <code>' + esc(d.user) + '</code></p>' +
							'<input type="text" readonly value="' + esc(d.password) + '" ' +
							'onclick="this.select()" style="width:480px;max-width:100%">';
					} else {
						res.textContent = 'Erreur : ' + (d.error || 'inconnue');
					}
				})
				.catch(function () { res.textContent = 'Erreur réseau'; });
		});
	});
})();
