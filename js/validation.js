/* App Validation — frontend vanilla (sans build) */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var form = document.getElementById('validation-form');
		if (!form) { return; }

		var addBtn = document.getElementById('add-validator');
		var result = document.getElementById('result');
		var submitBtn = document.getElementById('submit-btn');
		var validatorsBox = document.getElementById('validators');
		var fileInput = document.getElementById('vfiles');
		var dropzone = document.getElementById('v-dropzone');
		var fileList = document.getElementById('v-filelist');

		/* ---------- Fichiers (DataTransfer pour pouvoir en retirer) ---------- */
		var dt = new DataTransfer();

		function humanSize(bytes) {
			if (bytes < 1024) { return bytes + ' o'; }
			var units = ['Ko', 'Mo', 'Go'];
			var i = -1;
			do { bytes /= 1024; i++; } while (bytes >= 1024 && i < units.length - 1);
			return bytes.toFixed(1) + ' ' + units[i];
		}

		function syncFiles() {
			fileInput.files = dt.files;
			renderFiles();
		}

		function addFiles(list) {
			for (var i = 0; i < list.length; i++) {
				var f = list[i];
				var dup = false;
				for (var j = 0; j < dt.files.length; j++) {
					if (dt.files[j].name === f.name && dt.files[j].size === f.size) { dup = true; break; }
				}
				if (!dup) { dt.items.add(f); }
			}
			syncFiles();
		}

		function removeFile(idx) {
			var ndt = new DataTransfer();
			for (var i = 0; i < dt.files.length; i++) {
				if (i !== idx) { ndt.items.add(dt.files[i]); }
			}
			dt = ndt;
			syncFiles();
		}

		function renderFiles() {
			fileList.innerHTML = '';
			for (var i = 0; i < dt.files.length; i++) {
				(function (idx, file) {
					var li = document.createElement('li');
					li.className = 'v-file';

					var name = document.createElement('span');
					name.className = 'v-file-name';
					name.textContent = file.name;

					var size = document.createElement('span');
					size.className = 'v-file-size';
					size.textContent = humanSize(file.size);

					var rm = document.createElement('button');
					rm.type = 'button';
					rm.className = 'v-file-remove';
					rm.title = 'Retirer ce fichier';
					rm.setAttribute('aria-label', 'Retirer ce fichier');
					rm.textContent = '✕';
					rm.addEventListener('click', function () { removeFile(idx); });

					li.appendChild(name);
					li.appendChild(size);
					li.appendChild(rm);
					fileList.appendChild(li);
				})(i, dt.files[i]);
			}
		}

		fileInput.addEventListener('change', function () {
			if (fileInput.files && fileInput.files.length) {
				// l'utilisateur vient de choisir via la boîte de dialogue
				var picked = fileInput.files;
				// si le set natif diffère du nôtre, on fusionne
				if (picked !== dt.files) { addFiles(picked); }
			}
		});

		['dragenter', 'dragover'].forEach(function (ev) {
			dropzone.addEventListener(ev, function (e) { e.preventDefault(); dropzone.classList.add('dragover'); });
		});
		['dragleave', 'drop'].forEach(function (ev) {
			dropzone.addEventListener(ev, function (e) { e.preventDefault(); dropzone.classList.remove('dragover'); });
		});
		dropzone.addEventListener('drop', function (e) {
			if (e.dataTransfer && e.dataTransfer.files.length) { addFiles(e.dataTransfer.files); }
		});

		/* ---------- Validateurs (ajout / retrait / renumérotation) ---------- */
		function renumber() {
			var rows = validatorsBox.querySelectorAll('.v-validator-row');
			rows.forEach(function (row, i) {
				row.querySelector('.v-order').textContent = (i + 1);
				var inp = row.querySelector('.validator');
				inp.required = (i === 0);
			});
			// on garde toujours au moins un validateur
			rows.forEach(function (row) {
				row.querySelector('.v-remove').disabled = (rows.length <= 1);
			});
		}

		function addValidatorRow(focus) {
			var row = document.createElement('div');
			row.className = 'v-validator-row';

			var order = document.createElement('span');
			order.className = 'v-order';

			var input = document.createElement('input');
			input.type = 'email';
			input.className = 'validator';
			input.placeholder = 'email@exemple.fr';

			var rm = document.createElement('button');
			rm.type = 'button';
			rm.className = 'v-remove';
			rm.title = 'Retirer ce validateur';
			rm.setAttribute('aria-label', 'Retirer ce validateur');
			rm.textContent = '✕';
			rm.addEventListener('click', function () { row.remove(); renumber(); });

			row.appendChild(order);
			row.appendChild(input);
			row.appendChild(rm);
			validatorsBox.appendChild(row);
			renumber();
			if (focus) { input.focus(); }
		}

		addBtn.addEventListener('click', function () { addValidatorRow(true); });

		// premier validateur au chargement
		addValidatorRow(false);

		/* ---------- Envoi ---------- */
		function showResult(msg, kind) {
			result.textContent = msg;
			result.className = 'v-result ' + (kind || '');
			result.hidden = false;
		}

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			result.hidden = true;

			if (!dt.files.length) { showResult('Veuillez sélectionner au moins un document.', 'error'); return; }

			var fd = new FormData();
			for (var i = 0; i < dt.files.length; i++) { fd.append('files[]', dt.files[i]); }

			var count = 0;
			validatorsBox.querySelectorAll('.validator').forEach(function (v) {
				if (v.value.trim()) { fd.append('validators[]', v.value.trim()); count++; }
			});
			if (count === 0) { showResult('Veuillez saisir au moins un validateur.', 'error'); return; }

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
						// reset complet
						dt = new DataTransfer();
						syncFiles();
						validatorsBox.innerHTML = '';
						addValidatorRow(false);
						showResult('Votre demande a bien été envoyée (' + d.files.length + ' document(s)).', 'success');
					} else {
						showResult('Erreur : ' + (d.error || 'inconnue'), 'error');
					}
				})
				.catch(function () { showResult('Erreur réseau lors de l\'envoi.', 'error'); })
				.finally(function () { submitBtn.disabled = false; submitBtn.textContent = 'Envoyer la demande'; });
		});
	});
})();
