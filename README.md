# App Nextcloud « Validation de documents »

App **minimale et indépendante de Forms** : une page de dépôt (documents + emails des
validateurs) qui stocke les fichiers dans le compte de service et déclenche le circuit
de validation n8n. Coexiste avec l'app Forms officielle (id `validation`, namespace
`OCA\Validation`). **Frontend sans build** (template + JS vanilla) → aucun `npm`.

## Arborescence
```
validation/
├── appinfo/info.xml          # id=validation, navigation
├── appinfo/routes.php        # GET / (page), POST /submit
├── lib/AppInfo/Application.php
├── lib/Controller/PageController.php    # sert la page
├── lib/Controller/SubmitController.php  # upload + stockage + webhook n8n
├── templates/index.php       # formulaire (upload multi + validateurs)
├── js/validation.js          # envoi (fetch), vanilla
└── css/validation.css
```

## Déploiement (Docker, conteneur `nextcloud`)
```bash
# copier le dossier dans les apps custom
docker cp validation nextcloud:/var/www/html/custom_apps/validation
docker exec nextcloud chown -R www-data:www-data /var/www/html/custom_apps/validation

# activer l'app
docker exec -u www-data nextcloud php occ app:enable validation

# configurer le compte de service (où sont stockés les fichiers) et le webhook
docker exec -u www-data nextcloud php occ config:app:set validation service_user --value="watcha"
docker exec -u www-data nextcloud php occ config:app:set validation webhook_url --value="https://teamnet.app.n8n.cloud/webhook/validation"
```
La page est ensuite accessible via l'entrée **« Validation »** du menu Nextcloud
(`/apps/validation/`), pour tout utilisateur connecté.

## Données envoyées au webhook n8n
```json
{
  "nextcloudUrl":     "https://INSTANCE/nextcloud/",
  "submissionId":     "20260611153012-a1b2c3",
  "submissionFolder": "/Validation/20260611153012-a1b2c3",
  "submitterUid":     "watcha",
  "submitterEmail":   "...",
  "submitterName":    "...",
  "fileNames":        ["doc1.pdf", "doc2.txt"],
  "validators":       ["valid1@x.fr", "valid2@x.fr"]
}
```
Les fichiers sont écrits dans `/Validation/<submissionId>/` chez le compte de service.

## Ajustements côté n8n (légers)
Le payload est **déjà structuré** (plus besoin de parser les `answers` de Forms).
Adapter 2 nœuds :

1. **Code in JavaScript** :
```js
const b = $json.body;
return [{ json: {
  documents:      (b.fileNames || []).map(n => ({ fileName: n })),
  file_names:     b.fileNames || [],
  recipients:     b.validators || [],
  submitter_email: b.submitterEmail || '',
  submitter_uid:   b.submitterUid || '',
  submitter_name:  b.submitterName || '',
  submission_folder: b.submissionFolder
}}];
```
2. **Extract Form Data** : `path_file` =
   `={{ $('Nextcloud Forms Webhook').item.json.body.submissionFolder }}`
   (soit `/Validation/<id>` au lieu de `/Forms/...`).

Le reste du circuit (séquentiel, partages, déplacement, relances, multi-instances,
routage par `nextcloud_url`) est **inchangé**. Le webhook reste `/webhook/validation`.

## Limites / à vérifier (v0.1)
- Scaffold non testé en runtime : valider l'upload (`$_FILES['files']`), la création
  de dossier chez le compte de service, et l'entrée de navigation.
- Pas de limite de taille/type de fichier (à ajouter si besoin).
- Accès page = tout utilisateur connecté (pas de restriction par groupe).
- Pas de table BDD : l'état reste géré par n8n.
