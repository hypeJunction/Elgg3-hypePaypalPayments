<?php

namespace hypeJunction\Paypal;

use Elgg\Hook;

/**
 * Javascript config handler
 */
class SetJsData {

	/**
	 * Define paypal publishable key
	 *
	 * @param \Elgg\Hook $hook Hook info
	 *
	 * @return array
	 */
	public function __invoke(Hook $hook) {
		$value = $hook->getValue();

		$svc = elgg()->paypal;
		/* @var $svc \hypeJunction\Paypal\PaypalClient */

		$value['paypal_env'] = $svc->environment;
		$value['paypal_client'] = [
			$svc->environment => $svc->client_id
		];

		return $value;
	}
}
