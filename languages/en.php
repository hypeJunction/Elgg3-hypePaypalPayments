<?php

return [
	'payments:paypal:settings' => 'Paypal Settings',
	'payments:paypal:transactions' => 'Paypal Transactions',

	'payments:paypal:setting:sandbox_account' => 'Sandbox Account (email)',
	'payments:paypal:setting:sandbox_client_id' => 'Sandbox Client ID',
	'payments:paypal:setting:sandbox_secret' => 'Sandbox Secret',
	'payments:paypal:setting:sandbox_webhook_id' => 'Sandbox Webhook ID',
	'payments:paypal:setting:production_account' => 'Production Account (email)',
	'payments:paypal:setting:production_client_id' => 'Production Client ID',
	'payments:paypal:setting:production_secret' => 'Production Secret',
	'payments:paypal:setting:production_webhook_id' => 'Production Webhook ID',

	'payments:paypal:card:processing' => 'Validating ...',

	'payments:paypal:settings:webhooks' => 'Please configure your Paypal app to send webhooks to %s',

	'payments:method:paypal' => 'PayPal',

	'payments:paypal:no_source' => 'Payment source is missing',

	'payments:charges:paypal_fee' => 'Processing Fee',

	'payments:paypal:validating' => 'Validating...',

	'payments:paypal:pay:paid' => 'Your payment was successfully received',
	'payments:paypal:pay:pending' => 'Your payment was successful and should clear shortly',
	'payments:paypal:pay:failed' => 'Payment has failed',
	'payments:paypal:pay:payment_pending' => 'The charge was successful and the payment is pending',

	'payments:paypal:api:transaction:successful' => 'PayPal payment successfully completed',
	'payments:paypal:api:transaction:cancelled' => 'PayPal payment was not completed',

	'payments:paypal:api:connection_error' => 'There was an error contacting PayPal',
	'payments:paypal:api:configuration_error' => 'PayPal client is not configured correctly',
	'payments:paypal:api:invalid_credentials_error' => 'PayPal credentials are invalid',
	'payments:paypal:api:missing_credentials_error' => 'PayPal credentials are missing',

	'payments:paypal:paypal_balance' => 'PayPal',
];