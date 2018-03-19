<?php

namespace hypeJunction\Paypal;

use Elgg\Hook;
use hypeJunction\Payments\Transaction;

class DigestPaymentWebhook {

	/**
	 * Digest charge webhook and update transaction status
	 *
	 * @param Hook $hook Hook
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function __invoke(Hook $hook) {

		elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES, function () use ($hook) {
			$data = $hook->getParam('data');

			$transaction_id = $data->resource->invoice_number;
			$transaction = Transaction::getFromId($transaction_id);

			if (!$transaction) {
				return;
			}

			$gateway = elgg()->{'payments.gateways.paypal'};
			/* @var $gateway \hypeJunction\Paypal\PaypalGateway */

			$gateway->updateTransactionStatus($transaction);

			return $transaction;
		});
	}
}