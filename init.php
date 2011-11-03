<?php defined('SYSPATH') OR die('No direct script access.');

// Add an additional response message for 422 status codes
Response::$messages[422] = 'Unprocessable Entity';

// Set a default route for the webhooks controller
Route::set('chargify-webhooks', 'chargify/webhooks(/<signature>)')
	->defaults(array(
		'directory'  => 'chargify',
		'controller' => 'webhooks',
		'action'     => 'index',
	));
