<?php

namespace hypeJunction\Paypal;

use Elgg\EntityNotFoundException;
use Elgg\Http\ResponseBuilder;
use Elgg\Request;

class CheckoutAction {

	/**
	 * Checkout with paypal
	 *
	 * @param Request $request Request
	 *
	 * @return ResponseBuilder
	 * @throws \Exception
	 */
	public function __invoke(Request $request) {

		return elgg_call(ELGG_IGNORE_ACCESS, function () use ($request) {
			$transaction_id = $request->getParam('transaction_id');
			$transaction = \hypeJunction\Payments\Transaction::getFromId($transaction_id);

			if (!$transaction) {
				throw new EntityNotFoundException(elgg_echo('payments:error:not_found'));
			}

			$paypal_adapter = elgg()->{'payments.gateways.paypal'};
			/* @var $paypal_adapter \hypeJunction\Paypal\PaypalGateway */

			return $paypal_adapter->pay($transaction, $request->getParams());
		});
	}
}