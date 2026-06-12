<?php
declare(strict_types=1);

namespace OCA\Validation\Controller;

use OCA\Validation\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Authentication\Token\IProvider;
use OCP\Authentication\Token\IToken;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur réservé aux administrateurs (pas de #[NoAdminRequired]).
 */
class SettingsController extends Controller {
	private const TOKEN_NAME = 'n8n - Validation';

	public function __construct(
		IRequest $request,
		private IConfig $config,
		private IUserManager $userManager,
		private ISecureRandom $random,
		private IProvider $tokenProvider,
		private LoggerInterface $logger,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/** Enregistre l'URL du webhook et le compte de service. */
	public function setConfig(string $webhook_url = '', string $service_user = ''): JSONResponse {
		$this->config->setAppValue(Application::APP_ID, 'webhook_url', trim($webhook_url));
		$this->config->setAppValue(Application::APP_ID, 'service_user', trim($service_user));
		return new JSONResponse(['status' => 'ok']);
	}

	/**
	 * Génère (ou régénère) un mot de passe d'application pour le compte de service,
	 * à reporter dans le credential n8n. L'ancien token du même nom est révoqué.
	 */
	public function generateAppPassword(): JSONResponse {
		$uid = $this->config->getAppValue(Application::APP_ID, 'service_user', '');
		if ($uid === '' || $this->userManager->get($uid) === null) {
			return new JSONResponse(['error' => 'Compte de service invalide ou non configuré'], 400);
		}

		// révoquer les anciens tokens du même nom (régénération)
		try {
			foreach ($this->tokenProvider->getTokenByUser($uid) as $t) {
				if ($t->getName() === self::TOKEN_NAME) {
					$this->tokenProvider->invalidateTokenById($uid, $t->getId());
				}
			}
		} catch (\Throwable $e) {
			$this->logger->warning('Validation: purge anciens tokens: ' . $e->getMessage());
		}

		$password = $this->random->generate(
			72,
			ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS
		);
		try {
			$this->tokenProvider->generateToken(
				$password,
				$uid,
				$uid,
				null,
				self::TOKEN_NAME,
				IToken::PERMANENT_TOKEN
			);
		} catch (\Throwable $e) {
			$this->logger->error('Validation: génération token: ' . $e->getMessage());
			return new JSONResponse(['error' => 'Génération impossible : ' . $e->getMessage()], 500);
		}

		return new JSONResponse(['status' => 'ok', 'user' => $uid, 'password' => $password]);
	}
}
