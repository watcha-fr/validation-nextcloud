<?php
declare(strict_types=1);

namespace OCA\Validation\Settings;

use OCA\Validation\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings {
	public function __construct(private IConfig $config) {
	}

	public function getForm(): TemplateResponse {
		$params = [
			'webhook_url'  => $this->config->getAppValue(Application::APP_ID, 'webhook_url', ''),
			'service_user' => $this->config->getAppValue(Application::APP_ID, 'service_user', ''),
		];
		return new TemplateResponse(Application::APP_ID, 'admin', $params);
	}

	public function getSection(): string {
		// Apparaît dans Administration → Paramètres additionnels.
		return 'additional';
	}

	public function getPriority(): int {
		return 50;
	}
}
