<?php
declare(strict_types=1);

return [
	'routes' => [
		['name' => 'page#index',    'url' => '/',       'verb' => 'GET'],
		['name' => 'submit#submit', 'url' => '/submit', 'verb' => 'POST'],
		// Admin
		['name' => 'settings#setConfig',           'url' => '/settings/config',     'verb' => 'POST'],
		['name' => 'settings#generateAppPassword', 'url' => '/settings/apppassword', 'verb' => 'POST'],
	],
];
