<?php

return [
	'paypal' => \DI\object(\hypeJunction\Paypal\PaypalClient::class)
		->method('setup'),

	'payments.gateways.paypal' => \DI\object(\hypeJunction\Paypal\PaypalGateway::class)
		->constructor(\DI\get('paypal')),

];
