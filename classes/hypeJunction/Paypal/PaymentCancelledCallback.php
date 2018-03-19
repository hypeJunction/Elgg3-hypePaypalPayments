<?php

namespace hypeJunction\Paypal;

use Elgg\EntityNotFoundException;
use Elgg\Request;
use hypeJunction\Payments\Transaction;

class PaymentCancelledCallback {

	/**
	 * @param Request $request
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function __invoke(Request $request) {

		return elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES, function () use ($request) {

			$transaction_id = $request->getParam('transaction_id');

			$transaction = Transaction::getFromID($transaction_id);

			if (!$transaction) {
				throw new EntityNotFoundException();
			}

			$gateway = elgg()->{'payments.gateways.paypal'};
			/* @var $gateway \hypeJunction\Paypal\PaypalGateway */

			$gateway->cancelPayment($transaction);

			$forward_url = $request->getParam('forward_url');
			if (!$forward_url) {
				$forward_url = "payments/transaction/$transaction_id";
			}

			return elgg_redirect_response($forward_url);
		});
	}
}