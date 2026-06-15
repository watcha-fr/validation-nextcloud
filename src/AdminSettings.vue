<template>
	<NcSettingsSection
		:name="t('validation', 'Validation de documents')"
		:description="t('validation', 'Configuration du circuit de validation n8n.')">

		<div class="v-field">
			<NcTextField
				:label="t('validation', 'URL du webhook n8n')"
				:value="webhookUrl"
				type="url"
				placeholder="https://VOTRE-N8N/webhook/validation"
				@update:value="val => webhookUrl = val" />
		</div>

		<div class="v-field">
			<NcTextField
				:label="t('validation', 'Compte de service (UID)')"
				:value="serviceUser"
				placeholder="ex. watcha"
				@update:value="val => serviceUser = val" />
		</div>

		<NcButton type="primary" :disabled="saving" @click="save">
			<template #icon>
				<NcLoadingIcon v-if="saving" :size="20" />
			</template>
			{{ t('validation', 'Enregistrer') }}
		</NcButton>

		<h3 class="v-subhead">{{ t('validation', "Mot de passe d'application pour n8n") }}</h3>
		<p class="v-hint">
			{{ t('validation', "Génère (ou régénère) un mot de passe d'application pour le compte de service, à reporter dans le credential NextCloud de n8n. L'ancien est révoqué.") }}
		</p>
		<NcButton :disabled="generating" @click="generate">
			<template #icon>
				<NcLoadingIcon v-if="generating" :size="20" />
			</template>
			{{ t('validation', 'Générer / régénérer') }}
		</NcButton>

		<NcNoteCard v-if="generated" type="warning" class="v-pwcard">
			<p><strong>{{ t('validation', 'Mot de passe généré') }}</strong> — {{ t('validation', 'copiez-le, il ne sera plus affiché.') }}</p>
			<p>{{ t('validation', 'Utilisateur :') }} <code>{{ generated.user }}</code></p>
			<NcTextField
				class="v-pwfield"
				:label="t('validation', 'Mot de passe')"
				:value="generated.password"
				:readonly="true"
				@focus="selectAll" />
		</NcNoteCard>
	</NcSettingsSection>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

import NcButton from '@nextcloud/vue/components/NcButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'

export default {
	name: 'AdminSettings',
	components: { NcButton, NcLoadingIcon, NcNoteCard, NcSettingsSection, NcTextField },
	data() {
		return {
			webhookUrl: loadState('validation', 'webhook_url', ''),
			serviceUser: loadState('validation', 'service_user', ''),
			saving: false,
			generating: false,
			generated: null,
		}
	},
	methods: {
		t,
		async save() {
			this.saving = true
			try {
				const { data } = await axios.post(generateUrl('/apps/validation/settings/config'), {
					webhook_url: this.webhookUrl.trim(),
					service_user: this.serviceUser.trim(),
				})
				if (data.status === 'ok') {
					showSuccess(t('validation', 'Enregistré'))
				} else {
					showError(t('validation', 'Erreur : ') + (data.error || ''))
				}
			} catch (e) {
				showError(t('validation', 'Erreur réseau'))
			} finally {
				this.saving = false
			}
		},
		async generate() {
			this.generating = true
			this.generated = null
			try {
				const { data } = await axios.post(generateUrl('/apps/validation/settings/apppassword'), {})
				if (data.status === 'ok') {
					this.generated = { user: data.user, password: data.password }
				} else {
					showError(t('validation', 'Erreur : ') + (data.error || t('validation', 'inconnue')))
				}
			} catch (e) {
				showError(t('validation', 'Erreur réseau'))
			} finally {
				this.generating = false
			}
		},
		selectAll(e) {
			e.target.select()
		},
	},
}
</script>

<style scoped lang="scss">
.v-field { margin-bottom: 14px; max-width: 480px; }
.v-subhead { margin-top: 28px; }
.v-hint { color: var(--color-text-maxcontrast); max-width: 600px; }
.v-pwcard { margin-top: 16px; }
.v-pwfield { margin-top: 8px; max-width: 480px; }
code { background: var(--color-background-dark); padding: 2px 6px; border-radius: 4px; }
</style>
