<template>
	<div class="v-wrapper">
		<div class="v-card">
			<header class="v-header">
				<h2>{{ t('validation', 'Validation de documents') }}</h2>
				<p class="v-subtitle">
					{{ t('validation', 'Déposez un ou plusieurs documents et indiquez les validateurs, dans l\'ordre de validation.') }}
				</p>
			</header>

			<!-- Documents -->
			<section class="v-section">
				<h3 class="v-label">{{ t('validation', 'Documents à valider') }}</h3>

				<div
					class="v-dropzone"
					:class="{ dragover }"
					role="button"
					tabindex="0"
					@click="triggerLocalPick"
					@keydown.enter.prevent="triggerLocalPick"
					@keydown.space.prevent="triggerLocalPick"
					@dragenter.prevent="dragover = true"
					@dragover.prevent="dragover = true"
					@dragleave.prevent="dragover = false"
					@drop.prevent="onDrop">
					<TrayArrowUp :size="32" class="v-dropzone-icon" />
					<span class="v-dropzone-text">
						{{ t('validation', 'Cliquez pour choisir des fichiers') }}
						<br><small>{{ t('validation', 'ou glissez-déposez ici') }}</small>
					</span>
				</div>
				<input ref="localInput" type="file" multiple hidden @change="onLocalFiles">

				<div class="v-pick-actions">
					<NcButton type="secondary" @click="pickFromNextcloud">
						<template #icon>
							<Folder :size="20" />
						</template>
						{{ t('validation', 'Choisir dans Nextcloud') }}
					</NcButton>
				</div>

				<ul v-if="files.length" class="v-filelist">
					<li v-for="f in files" :key="f.id" class="v-file">
						<component :is="f.source === 'nextcloud' ? Folder : FileIcon" :size="18" class="v-file-icon" />
						<span class="v-file-name" :title="f.name">{{ f.name }}</span>
						<span class="v-file-size">{{ humanSize(f.size) }}</span>
						<NcButton
							type="tertiary"
							:aria-label="t('validation', 'Retirer ce fichier')"
							:title="t('validation', 'Retirer ce fichier')"
							@click="removeFile(f.id)">
							<template #icon>
								<Close :size="18" />
							</template>
						</NcButton>
					</li>
				</ul>
			</section>

			<!-- Validateurs -->
			<section class="v-section">
				<h3 class="v-label">
					{{ t('validation', 'Validateurs') }}
					<span class="v-hint">{{ t('validation', '(dans l\'ordre de validation)') }}</span>
				</h3>

				<div class="v-validators">
					<div v-for="(v, i) in validators" :key="v.id" class="v-validator-row">
						<span class="v-order">{{ i + 1 }}</span>
						<NcTextField
							class="v-validator-field"
							:label="t('validation', 'Email du validateur')"
							:value="v.email"
							type="email"
							placeholder="email@exemple.fr"
							@update:value="val => v.email = val" />
						<NcButton
							type="tertiary"
							:aria-label="t('validation', 'Retirer ce validateur')"
							:title="t('validation', 'Retirer ce validateur')"
							:disabled="validators.length <= 1"
							@click="removeValidator(v.id)">
							<template #icon>
								<Close :size="18" />
							</template>
						</NcButton>
					</div>
				</div>

				<NcButton class="v-add" type="tertiary" @click="addValidator">
					<template #icon>
						<Plus :size="20" />
					</template>
					{{ t('validation', 'Ajouter un validateur') }}
				</NcButton>
			</section>

			<div class="v-actions">
				<NcButton type="primary" :disabled="submitting" @click="submit">
					<template #icon>
						<NcLoadingIcon v-if="submitting" :size="20" />
						<Send v-else :size="20" />
					</template>
					{{ submitting ? t('validation', 'Envoi…') : t('validation', 'Envoyer la demande') }}
				</NcButton>
			</div>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { getCurrentUser } from '@nextcloud/auth'
import { showError, showSuccess, getFilePickerBuilder, FilePickerType } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl, generateRemoteUrl } from '@nextcloud/router'

import NcButton from '@nextcloud/vue/components/NcButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcTextField from '@nextcloud/vue/components/NcTextField'

import Close from 'vue-material-design-icons/Close.vue'
import FileIcon from 'vue-material-design-icons/File.vue'
import Folder from 'vue-material-design-icons/Folder.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import Send from 'vue-material-design-icons/Send.vue'
import TrayArrowUp from 'vue-material-design-icons/TrayArrowUp.vue'

let uid = 0

export default {
	name: 'App',
	components: {
		NcButton, NcLoadingIcon, NcTextField,
		Close, FileIcon, Folder, Plus, Send, TrayArrowUp,
	},
	data() {
		return {
			files: [],
			validators: [{ id: ++uid, email: '' }],
			dragover: false,
			submitting: false,
			// exposés au template pour <component :is>
			Folder,
			FileIcon,
		}
	},
	methods: {
		t,

		humanSize(bytes) {
			if (bytes < 1024) { return bytes + ' o' }
			const units = ['Ko', 'Mo', 'Go']
			let i = -1
			do { bytes /= 1024; i++ } while (bytes >= 1024 && i < units.length - 1)
			return bytes.toFixed(1) + ' ' + units[i]
		},

		/* ---------- Fichiers ---------- */
		addFiles(list, source) {
			for (const f of list) {
				if (this.files.some(x => x.name === f.name && x.size === f.size)) { continue }
				this.files.push({ id: ++uid, name: f.name, size: f.size, file: f, source })
			}
		},
		removeFile(id) {
			this.files = this.files.filter(f => f.id !== id)
		},
		triggerLocalPick() {
			this.$refs.localInput.click()
		},
		onLocalFiles(e) {
			if (e.target.files && e.target.files.length) {
				this.addFiles(Array.from(e.target.files), 'local')
			}
			e.target.value = ''
		},
		onDrop(e) {
			this.dragover = false
			if (e.dataTransfer && e.dataTransfer.files.length) {
				this.addFiles(Array.from(e.dataTransfer.files), 'local')
			}
		},
		async pickFromNextcloud() {
			const picker = getFilePickerBuilder(t('validation', 'Sélectionner des documents dans Nextcloud'))
				.setMultiSelect(true)
				.allowDirectories(false)
				.setType(FilePickerType.Choose)
				.build()
			let res
			try {
				res = await picker.pick()
			} catch (e) {
				return // annulé
			}
			const paths = Array.isArray(res) ? res : [res]
			const dav = generateRemoteUrl('dav/files/' + getCurrentUser().uid)
			for (const p of paths) {
				if (!p) { continue }
				try {
					const r = await axios.get(dav + p.split('/').map(encodeURIComponent).join('/'), { responseType: 'blob' })
					const name = p.split('/').filter(Boolean).pop()
					this.addFiles([new File([r.data], name, { type: r.data.type })], 'nextcloud')
				} catch (e) {
					showError(t('validation', 'Impossible de récupérer « {f} »', { f: p }))
				}
			}
		},

		/* ---------- Validateurs ---------- */
		addValidator() {
			this.validators.push({ id: ++uid, email: '' })
		},
		removeValidator(id) {
			if (this.validators.length <= 1) { return }
			this.validators = this.validators.filter(v => v.id !== id)
		},

		/* ---------- Envoi ---------- */
		async submit() {
			if (!this.files.length) {
				showError(t('validation', 'Veuillez sélectionner au moins un document.'))
				return
			}
			const emails = this.validators.map(v => v.email.trim()).filter(Boolean)
			if (!emails.length) {
				showError(t('validation', 'Veuillez saisir au moins un validateur.'))
				return
			}

			const fd = new FormData()
			this.files.forEach(f => fd.append('files[]', f.file, f.name))
			emails.forEach(e => fd.append('validators[]', e))

			this.submitting = true
			try {
				const { data } = await axios.post(generateUrl('/apps/validation/submit'), fd)
				if (data.status === 'ok') {
					showSuccess(t('validation', 'Votre demande a bien été envoyée ({n} document(s)).', { n: data.files.length }))
					this.files = []
					this.validators = [{ id: ++uid, email: '' }]
				} else {
					showError(t('validation', 'Erreur : ') + (data.error || t('validation', 'inconnue')))
				}
			} catch (e) {
				showError(t('validation', 'Erreur réseau lors de l\'envoi.'))
			} finally {
				this.submitting = false
			}
		},
	},
}
</script>

<style scoped lang="scss">
.v-wrapper {
	padding: 24px;
}

.v-card {
	max-width: 560px;
	margin: 0 auto;
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large, 12px);
	padding: 24px 28px 28px;
	box-shadow: 0 0 6px var(--color-box-shadow, rgba(0, 0, 0, .1));
}

.v-header h2 { margin: 0 0 4px; }
.v-subtitle { color: var(--color-text-maxcontrast); margin: 0; }

.v-section { margin-top: 26px; }
.v-label { font-size: 15px; font-weight: 600; margin: 0 0 12px; }
.v-hint { font-weight: normal; color: var(--color-text-maxcontrast); font-size: 13px; }

/* Zone de dépôt */
.v-dropzone {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 8px;
	padding: 26px 16px;
	border: 2px dashed var(--color-border-dark);
	border-radius: var(--border-radius-large, 12px);
	cursor: pointer;
	text-align: center;
	color: var(--color-text-maxcontrast);
	transition: border-color .15s ease, background-color .15s ease;
}
.v-dropzone:hover,
.v-dropzone.dragover {
	border-color: var(--color-primary-element);
	background-color: var(--color-background-hover);
}
.v-dropzone-icon { color: var(--color-text-maxcontrast); }
.v-dropzone-text { font-size: 14px; }
.v-dropzone-text small { color: var(--color-text-maxcontrast); }

.v-pick-actions { margin-top: 12px; display: flex; justify-content: center; }

/* Liste des fichiers */
.v-filelist { list-style: none; padding: 0; margin: 12px 0 0; display: flex; flex-direction: column; gap: 6px; }
.v-file {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 4px 4px 4px 14px;
	background: var(--color-background-dark);
	border-radius: var(--border-radius, 8px);
}
.v-file-icon { color: var(--color-text-maxcontrast); flex: 0 0 auto; }
.v-file-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.v-file-size { color: var(--color-text-maxcontrast); font-size: 12px; white-space: nowrap; }

/* Validateurs */
.v-validators { display: flex; flex-direction: column; gap: 8px; }
.v-validator-row { display: flex; align-items: center; gap: 10px; }
.v-order {
	flex: 0 0 auto;
	width: 24px; height: 24px;
	border-radius: 50%;
	background: var(--color-primary-element);
	color: var(--color-primary-element-text);
	display: flex; align-items: center; justify-content: center;
	font-size: 12px; font-weight: 600;
}
.v-validator-field { flex: 1; min-width: 0; }

.v-add { margin-top: 12px; }
.v-actions { margin-top: 28px; }
</style>
