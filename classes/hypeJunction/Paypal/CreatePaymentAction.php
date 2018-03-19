<?php

namespace hypeJunction\Paypal;

use Elgg\EntityNotFoundException;
use Elgg\Http\ResponseBuilder;
use Elgg\Request;
use hypeJunction\Payments\Transaction;

class CreatePaymentAction {

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
			$transaction = Transaction::getFromId($transaction_id);

			if (!$transaction) {
				throw new EntityNotFoundException();
			}

			$paypal_adapter = elgg()->{'payments.gateways.paypal'};

			/* @var $paypal_adapter \hypeJunction\Paypal\PaypalGateway */

			if ($payment = $paypal_adapter->createPayment($transaction, $request->getParams())) {
				return elgg_ok_response([
					'payment' => $payment->toArray(),
				]);
			}

			return elgg_error_response(elgg_echo('payments:paypal:pay:failed'));
		});
	}
}