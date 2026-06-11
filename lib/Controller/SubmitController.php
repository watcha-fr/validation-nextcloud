<?php
declare(strict_types=1);

namespace OCA\Validation\Controller;

use OCA\Validation\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class SubmitController extends Controller {
	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private IConfig $config,
		private IRootFolder $rootFolder,
		private IClientService $clientService,
		private IURLGenerator $urlGenerator,
		private LoggerInterface $logger,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * Reçoit les fichiers + les emails des validateurs, stocke les fichiers dans
	 * l'espace du compte de service, puis déclenche le webhook n8n.
	 */
	#[NoAdminRequired]
	public function submit(): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse(['error' => 'non authentifié'], 401);
		}

		// --- validateurs (champ validators[]) ---
		$validators = $this->request->getParam('validators', []);
		if (is_string($validators)) {
			$validators = [$validators];
		}
		$validators = array_values(array_unique(array_filter(array_map(
			static fn ($e) => strtolower(trim((string)$e)),
			(array)$validators
		))));
		if (count($validators) === 0) {
			return new JSONResponse(['error' => 'aucun validateur'], 400);
		}

		// --- fichiers déposés ($_FILES['files']) ---
		$files = $_FILES['files'] ?? null;
		if (!$files || empty($files['name'])) {
			return new JSONResponse(['error' => 'aucun fichier'], 400);
		}

		// --- compte de service (occ config:app:set validation service_user --value=...) ---
		$serviceUser = $this->config->getAppValue(Application::APP_ID, 'service_user', '');
		if ($serviceUser === '') {
			return new JSONResponse(['error' => 'service_user non configuré'], 500);
		}

		// --- dossier de soumission : /Validation/<submissionId> chez le compte de service ---
		$sid = date('YmdHis') . '-' . substr(bin2hex(random_bytes(4)), 0, 6); // identifiant technique
		// Nom de dossier lisible : c'est ce que verra le validateur dans le partage.
		$displayName = $user->getDisplayName() !== '' ? $user->getDisplayName() : $user->getUID();
		$safeName = str_replace(['/', '\\'], '-', $displayName);
		$folderName = 'Validation - ' . $safeName . ' - ' . date('d-m-Y H\hi\ms');
		try {
			$serviceFolder = $this->rootFolder->getUserFolder($serviceUser);
			$root = $serviceFolder->nodeExists('Validation')
				? $serviceFolder->get('Validation')
				: $serviceFolder->newFolder('Validation');
			// garantir l'unicité du nom de dossier
			$unique = $folderName;
			$i = 2;
			while ($root->nodeExists($unique)) {
				$unique = $folderName . ' (' . $i . ')';
				$i++;
			}
			$folderName = $unique;
			$folder = $root->newFolder($folderName);
		} catch (\Throwable $e) {
			$this->logger->error('Validation: création dossier impossible: ' . $e->getMessage());
			return new JSONResponse(['error' => 'stockage indisponible'], 500);
		}

		$names = (array)$files['name'];
		$tmps = (array)$files['tmp_name'];
		$fileNames = [];
		foreach ($names as $i => $name) {
			if ($name === '' || !isset($tmps[$i]) || !is_uploaded_file($tmps[$i])) {
				continue;
			}
			$content = file_get_contents($tmps[$i]);
			$folder->newFile((string)$name, $content);
			$fileNames[] = (string)$name;
		}
		if (count($fileNames) === 0) {
			return new JSONResponse(['error' => 'aucun fichier valide'], 400);
		}

		// --- payload + webhook n8n ---
		$payload = [
			'nextcloudUrl'     => $this->urlGenerator->getAbsoluteURL('/'),
			'submissionId'     => $sid,
			'submissionFolder' => '/Validation/' . $folderName,
			'submitterUid'     => $user->getUID(),
			'submitterEmail'   => (string)$user->getEmailAddress(),
			'submitterName'    => $user->getDisplayName(),
			'fileNames'        => $fileNames,
			'validators'       => $validators,
		];
		$webhook = $this->config->getAppValue(
			Application::APP_ID,
			'webhook_url',
			'https://teamnet.app.n8n.cloud/webhook/validation'
		);
		try {
			$this->clientService->newClient()->post($webhook, ['json' => $payload]);
		} catch (\Throwable $e) {
			// n8n indisponible : on ne casse pas la soumission.
			$this->logger->warning('Validation: webhook n8n injoignable: ' . $e->getMessage());
		}

		return new JSONResponse([
			'status' => 'ok',
			'submissionId' => $sid,
			'files' => $fileNames,
		]);
	}
}
