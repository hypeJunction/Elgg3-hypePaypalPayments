<?php

require_once __DIR__ . '/autoloader.php';

return function () {

	elgg_register_event_handler('init', 'system', function () {

		elgg()->payments->registerGateway(elgg()->{'payments.gateways.paypal'});

		elgg_register_plugin_hook_handler('elgg.data', 'page', \hypeJunction\Paypal\SetJsData::class);

		elgg_define_js('paypal', [
			'src' => 'https://www.paypalobjects.com/api/checkout.js',
			'exports' => 'window.paypal',
		]);

		elgg_extend_view('elgg.css', 'input/paypal/paypal.css');

		elgg_register_ajax_view('payments/method/paypal/form');

		elgg_register_plugin_hook_handler('refund', 'payments', \hypeJunction\Paypal\RefundTransaction::class);

		elgg_register_plugin_hook_handler('PAYMENT.SALE.PENDING', 'paypal', \hypeJunction\Paypal\DigestPaymentWebhook::class);
		elgg_register_plugin_hook_handler('PAYMENT.SALE.COMPLETED', 'paypal', \hypeJunction\Paypal\DigestPaymentWebhook::class);
		elgg_register_plugin_hook_handler('PAYMENT.SALE.REFUNDED', 'paypal', \hypeJunction\Paypal\DigestPaymentWebhook::class);
		elgg_register_plugin_hook_handler('PAYMENT.SALE.DENIED', 'paypal', \hypeJunction\Paypal\DigestPaymentWebhook::class);
		elgg_register_plugin_hook_handler('PAYMENT.SALE.REVERSE', 'paypal', \hypeJunction\Paypal\DigestPaymentWebhook::class);

		elgg_register_plugin_hook_handler('register', 'menu:page', \hypeJunction\Paypal\PageMenu::class);

	});

};
