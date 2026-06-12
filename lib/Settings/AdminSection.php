<?php
declare(strict_types=1);

namespace OCA\Validation\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {
	public function __construct(
		private IL10N $l,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getID(): string {
		return 'validation';
	}

	public function getName(): string {
		return $this->l->t('Validation de documents');
	}

	public function getPriority(): int {
		return 75;
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath('validation', 'app-dark.svg');
	}
}
