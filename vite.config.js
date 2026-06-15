import { createAppConfig } from '@nextcloud/vite-config'

// Deux points d'entrée : la page de dépôt (main) et l'onglet d'administration.
// Les bundles sont émis dans js/ (validation-main.mjs, validation-adminSettings.mjs)
// et doivent être COMMITÉS (déploiement par docker cp, pas de CI).
export default createAppConfig({
	main: 'src/main.js',
	adminSettings: 'src/adminSettings.js',
}, {
	appName: 'validation',
	createEmptyCSSEntryPoints: true,
})
