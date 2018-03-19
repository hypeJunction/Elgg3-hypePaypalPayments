<?php

return [
	'actions' => [
		'payments/checkout/paypal/sale' => [
			'controller' => \hypeJunction\Paypal\CreateSubscriptionAction::class,
			'access' => 'public',
			'middleware' => [
				\Elgg\Router\Middleware\AjaxGatekeeper::class,
			],
		],
		'payments/checkout/paypal' => [
			'controller' => \hypeJunction\Paypal\CheckoutAction::class,
			'access' => 'public',
		],
	],
	'routes' => [
		'payments:paypal:webhooks' => [
			'path' => '/payments/paypal/webhooks',
			'controller' => \hypeJunction\Paypal\DigestWebhook::class,
			'walled' => false,
		],
		'payments:paypal:success' => [
			'path' => '/payments/paypal/api/success',
			'controller' => \hypeJunction\Paypal\PaymentAuthorizedCallback::class,
			'walled' => false,
		],
		'payments:paypal:cancel' => [
			'path' => '/payments/paypal/api/cancel',
			'controller' => \hypeJunction\Paypal\PaymentCancelledCallback::class,
			'walled' => false,
		],
	],
];
