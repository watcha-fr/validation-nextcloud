<?php
declare(strict_types=1);

namespace OCA\Validation\Settings;

use OCA\Validation\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings {
	public function __construct(
		private IConfig $config,
		private IInitialState $initialState,
	) {
	}

	public function getForm(): TemplateResponse {
		// Valeurs initiales lues côté Vue via loadState('validation', ...).
		$this->initialState->provideInitialState(
			'webhook_url',
			$this->config->getAppValue(Application::APP_ID, 'webhook_url', '')
		);
		$this->initialState->provideInitialState(
			'service_user',
			$this->config->getAppValue(Application::APP_ID, 'service_user', '')
		);
		return new TemplateResponse(Application::APP_ID, 'admin', []);
	}

	public function getSection(): string {
		// Section dédiée « Validation » (voir AdminSection).
		return 'validation';
	}

	public function getPriority(): int {
		return 50;
	}
}
