<?php
declare(strict_types=1);

namespace OCA\Validation\Controller;

use OCA\Validation\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

class PageController extends Controller {
	public function __construct(IRequest $request) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function index(): TemplateResponse {
		// La page (et son JS) est servie aux utilisateurs connectés.
		return new TemplateResponse(Application::APP_ID, 'index');
	}
}
