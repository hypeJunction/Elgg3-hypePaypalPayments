<?php

namespace hypeJunction\Paypal;

use Couchbase\Exception;
use Elgg\Http\ResponseBuilder;
use hypeJunction\Payments\Amount;
use hypeJunction\Payments\ChargeInterface;
use hypeJunction\Payments\CreditCard;
use hypeJunction\Payments\GatewayInterface;
use hypeJunction\Payments\OrderItemInterface;
use hypeJunction\Payments\Payment;
use hypeJunction\Payments\Refund;
use hypeJunction\Payments\ShippingFee;
use hypeJunction\Payments\Tax;
use hypeJunction\Payments\TransactionInterface;
use PayPal\Api\Details;
use PayPal\Api\FundingInstrument;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payee;
use PayPal\Api\Payer;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\RefundRequest;
use PayPal\Api\Sale;
use PayPal\Api\ShippingAddress;
use PayPal\Api\Transaction;
use PayPal\Exception\PayPalConfigurationException;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Exception\PayPalInvalidCredentialException;
use PayPal\Exception\PayPalMissingCredentialException;

class PaypalGateway implements GatewayInterface {

	/**
	 * @var PaypalClient
	 */
	protected $client;

	/**
	 * Constructor
	 *
	 * @param PaypalClient $client Client
	 */
	public function __construct(PaypalClient $client) {
		$this->client = $client;
	}

	/**
	 * {@inheritdoc}
	 */
	public function id() {
		return 'paypal';
	}

	/**
	 * Create a new payment to be executed later
	 *
	 * @param TransactionInterface $transaction Transaction object
	 * @param array                $params      Request params
	 *
	 * @return \PayPal\Api\Payment|false
	 */
	public function createPayment(TransactionInterface $transaction, array $params = []) {

		$transaction->setStatus(TransactionInterface::STATUS_PAYMENT_PENDING);

		$merchant = $transaction->getMerchant();
		$customer = $transaction->getCustomer();

		$amount = $transaction->getAmount();
		$total = $amount->getConvertedAmount();
		$currency = $amount->getCurrency();

		$description = $transaction->getDisplayName();
		if (!$description) {
			$description = "Payment to {$merchant->getDisplayName()}";
		}

		$payee = new Payee();
		$payee->setEmail($this->client->account);

		$paypal_transaction = new Transaction();
		$paypal_transaction->setPayee($payee)
			->setInvoiceNumber($transaction->transaction_id)
			->setDescription($description);

		$item_list = new ItemList();

		$order = $transaction->getOrder();
		if ($order) {
			$items = [];
			$order_items = $order->all();
			foreach ($order_items as $order_item) {
				/* @var $order_item OrderItemInterface */

				if ($order_item->sku) {
					$sku = $order_item->sku;
				} else {
					$mid = (int) $merchant->guid;
					$iid = (int) $order_item->getId();
					$sku = "$mid-$iid";
				}

				$item = new Item();
				$item->setName($order_item->getTitle() . " ($sku)")
					->setCurrency($currency)
					->setQuantity($order_item->getQuantity())
					->setSku($sku)
					->setPrice($order_item->getPrice()->getConvertedAmount());

				$items[] = $item;
			}

			$subtotal = $order->getSubtotalAmount()->getAmount();
			$shipping = 0;
			$tax = 0;

			$order_charges = $order->getCharges();
			foreach ($order_charges as $order_charge) {
				/* @var $order_charge ChargeInterface */

				if ($order_charge instanceof ShippingFee) {
					$shipping += (int) $order_charge->getTotalAmount()->getConvertedAmount();
				} else if ($order_charge instanceof Tax) {
					$tax += (int) $order_charge->getTotalAmount()->getConvertedAmount();
				} else {
					$item = new Item();
					$item->setName(elgg_echo("payments:charge:{$order_charge->getId()}"))
						->setCurrency($currency)
						->setQuantity(1)
						->setSku($order_charge->getId())
						->setPrice($order_charge->getTotalAmount()->getConvertedAmount());

					$subtotal += $order_charge->getTotalAmount()->getAmount();
					$items[] = $item;
				}
			}

			$subtotal = (new Amount($subtotal, $currency))->getConvertedAmount();
			$shipping = (new Amount($shipping, $currency))->getConvertedamount();
			$tax = (new Amount($tax, $currency))->getConvertedAmount();

			$details = new Details();
			$details->setSubtotal($subtotal);

			if ($shipping) {
				$details->setShipping($shipping);
			}

			if ($tax) {
				$details->setTax($tax);
			}

			$item_list->setItems($items);

			$amount = new \PayPal\Api\Amount();
			$amount->setCurrency($currency)
				->setTotal($total)
				->setDetails($details);

			$paypal_transaction->setAmount($amount);

			$order_shipping_address = $order->getShippingAddress();
			if ($order_shipping_address) {
				$shipping_address = new ShippingAddress();

				$shipping_address->setCity($order_shipping_address->locality);
				$shipping_address->setCountryCode($order_shipping_address->country_code);
				$shipping_address->setPostalCode($order_shipping_address->postal_code);
				$shipping_address->setLine1($order_shipping_address->street_address);
				$shipping_address->setLine2($order_shipping_address->extended_address);
				$shipping_address->setState($order_shipping_address->region);
				$shipping_address->setRecipientName($order->getCustomer()->name);

				$item_list->setShippingAddress($shipping_address);
			}
			$paypal_transaction->setItemList($item_list);
		} else {
			$amount = new \PayPal\Api\Amount();
			$amount->setCurrency($currency)
				->setTotal($total);
		}

		$paypal_transaction->setAmount($amount);

		$success = elgg_normalize_url(elgg_http_add_url_query_elements('payments/paypal/api/success', [
			'transaction_id' => $transaction->transaction_id,
			'forward_url' => $merchant->getURL(),
		]));

		$cancel = elgg_normalize_url(elgg_http_add_url_query_elements('payments/paypal/api/cancel', [
			'transaction_id' => $transaction->transaction_id,
			'forward_url' => $merchant->getURL(),
		]));

		$redirectUrls = new RedirectUrls();
		$redirectUrls->setReturnUrl($success)
			->setCancelUrl($cancel);

		$payer = new Payer();
		$payer->setPaymentMethod("paypal");

		$payment = new \PayPal\Api\Payment();
		$payment->setIntent("sale")
			->setPayer($payer)
			->setRedirectUrls($redirectUrls)
			->setTransactions([$paypal_transaction]);

		try {
			$payment->create($this->client->getApiContext());
		} catch (PayPalConnectionException $ex) {
			register_error(elgg_echo('payments:paypal:api:connection_error'));
			elgg_log($ex->getMessage() . ': ' . print_r(json_decode($ex->getData()), true), 'ERROR');

			return false;
		} catch (PayPalConfigurationException $ex) {
			register_error(elgg_echo('payments:paypal:api:configuration_error'));

			return false;
		} catch (PayPalInvalidCredentialException $ex) {
			register_error(elgg_echo('payments:paypal:api:invalid_credentials_error'));

			return false;
		} catch (PayPalMissingCredentialException $ex) {
			register_error(elgg_echo('payments:paypal:api:missing_credentials_error'));

			return false;
		} catch (Exception $ex) {
			register_error($ex->getMessage());

			return false;
		}

		return $payment;
	}

	/**
	 * Execute approved payment
	 *
	 * @param Transaction $transaction Transaction object
	 * @param array       $params      Request params
	 *
	 * @return ResponseBuilder
	 */
	public function pay(TransactionInterface $transaction, array $params = []) {

		$transaction->setPaymentMethod('paypal');

		$payment_id = elgg_extract('paypal_payment_id', $params);
		$payment = \PayPal\Api\Payment::get($payment_id, $this->client->getApiContext());

		$payer_id = elgg_extract('paypal_payer_id', $params);
		$execution = new PaymentExecution();
		$execution->setPayerId($payer_id);

		try {
			$transaction->paypal_payment_id = $payment->getId();

			$payment->execute($execution, $this->client->getApiContext());
		} catch (PayPalConnectionException $ex) {
			elgg_log($ex->getMessage() . ': ' . print_r(json_decode($ex->getData()), true), 'ERROR');

			$msg = elgg_echo('payments:paypal:api:connection_error');

			return elgg_error_response($msg, REFERRER, ELGG_HTTP_BAD_REQUEST);
		} catch (PayPalConfigurationException $ex) {
			$msg = elgg_echo('payments:paypal:api:connection_error');

			return elgg_error_response($msg, REFERRER, ELGG_HTTP_BAD_REQUEST);
		} catch (PayPalInvalidCredentialException $ex) {
			$msg = elgg_echo('payments:paypal:api:invalid_credentials_error');

			return elgg_error_response($msg, REFERRER, ELGG_HTTP_BAD_REQUEST);

		} catch (PayPalMissingCredentialException $ex) {
			$msg = elgg_echo('payments:paypal:api:missing_credentials_error');

			return elgg_error_response($msg, REFERRER, ELGG_HTTP_BAD_REQUEST);
		} catch (Exception $ex) {
			return elgg_error_response($ex->getMessage(), REFERRER, ELGG_HTTP_INTERNAL_SERVER_ERROR);
		}

		if ($payment->getState() == 'failed') {
			$transaction->setStatus(TransactionInterface::STATUS_FAILED);

			$msg = elgg_echo('payments:paypal:pay:failed');

			return elgg_error_response($msg, REFERRER, ELGG_HTTP_BAD_REQUEST);
		} else {
			// We can't say for sure what the status of the payment is
			// If funded by e-check, this API endpoint tells us that the payment is complete
			// whereas in fact the payment is pending
			// So, we are going to wait for a webhook to let us know for use
			$this->updateTransactionStatus($transaction);
		}

		return elgg_ok_response([
			'transaction' => $transaction,
		], elgg_echo('payments:paypal:pay:pending'));
	}

	/**
	 * Cancel payment
	 * @return bool
	 */
	public function cancelPayment(TransactionInterface $transaction) {
		$transaction->setStatus(TransactionInterface::STATUS_FAILED);

		return true;
	}

	/**
	 * Update transaction status via API call
	 *
	 * @param TransactionInterface $transaction Transaction
	 *
	 * @return TransactionInterface
	 */
	public function updateTransactionStatus(TransactionInterface $transaction) {

		if (!$transaction->paypal_payment_id) {
			return $transaction;
		}

		try {
			$payment = \PayPal\Api\Payment::get($transaction->paypal_payment_id, $this->client->getApiContext());
			$paypal_transaction = array_shift($payment->getTransactions());
			/* @var $paypal_transaction Transaction */

			foreach ($paypal_transaction->getRelatedResources() as $related) {
				if ($related->getSale()) {
					$sale = $related->getSale();
					break;
				}
			}

			if (!$sale) {
				return $transaction;
			}

			if (!$transaction->paypal_sale_id) {
				$transaction->paypal_sale_id = $sale->getId();
				switch ($payment->getPayer()->getPaymentMethod()) {
					case 'paypal' :
						$transaction->setFundingSource(new PaypalBalance());
						break;

					case 'credit_card' :
						$instruments = $payment->getPayer()->getFundingInstruments();
						if ($instruments) {
							$instrument = array_shift($instruments);
							if ($instrument) {
								/* @var $instrument FundingInstrument */
								$credit_card = $instrument->getCreditCard();
								if ($credit_card) {
									$cc = new CreditCard();
									$cc->id = $credit_card->id;
									$cc->last4 = substr($credit_card->getNumber(), -4);
									$cc->brand = $credit_card->getType();
									$cc->exp_month = $credit_card->getExpireMonth();
									$cc->exp_year = $credit_card->getExpireYear();
									$transaction->setFundingSource($cc);
								}
							}
						}
						break;
				}
			}
		} catch (Exception $ex) {
			elgg_log($ex->getMessage(), 'ERROR');

			return $transaction;
		}

		switch ($sale->getState()) {
			case 'created' :
			case 'pending' :
			case 'processed' :
				$transaction->setStatus(TransactionInterface::STATUS_PAYMENT_PENDING);
				break;

			case 'completed' :
				if ($transaction->status != TransactionInterface::STATUS_PAID) {
					$payment = new Payment();
					$payment->setTimeCreated(time())
						->setAmount(Amount::fromString($sale->getAmount()->getTotal(), $sale->getAmount()->getCurrency()))
						->setPaymentMethod('paypal')
						->setDescription(elgg_echo('payments:payment'));
					$transaction->addPayment($payment);
					$transaction->setStatus(TransactionInterface::STATUS_PAID);

					$processor_fee = Amount::fromString((string) $sale->getTransactionFee()->getValue(), $sale->getTransactionFee()->getCurrency());
					$transaction->setProcessorFee($processor_fee);
				}
				break;

			case 'refunded' :
			case 'partially_refunded' :
				if ($sale->getState() == 'refunded') {
					$transaction->setStatus(TransactionInterface::STATUS_REFUNDED);
				} else {
					$transaction->setStatus(TransactionInterface::STATUS_PARTIALLY_REFUNDED);
				}


				$payments = $transaction->getPayments();
				$payment_ids = array_map(function ($payment) {
					return $payment->paypal_refund_id;
				}, $payments);

				foreach ($paypal_transaction->getRelatedResources() as $related) {
					if (!$related->getRefund()) {
						continue;
					}

					$paypal_refund = $related->getRefund();

					if (in_array($paypal_refund->getId(), $payment_ids)) {
						continue;
					}

					/**
					 * @todo: deduct refunded paypal fee from processor fee amount
					 * Currently, not possible because PP API is dumb
					 * https://github.com/paypal/PayPal-Ruby-SDK/issues/106#issuecomment-262592048
					 */

					$refund = new Refund();
					$refund->setTimeCreated(strtotime($paypal_refund->getCreateTime()))
						->setAmount(Amount::fromString((string) -$paypal_refund->getAmount()->getTotal(), $paypal_refund->getAmount()->getCurrency()))
						->setPaymentMethod('paypal')
						->setDescription(elgg_echo('payments:refund'));
					$refund->paypal_refund_id = $paypal_refund->getId();
					$transaction->addPayment($refund);
				}

				break;
		}

		return $transaction;
	}

	/**
	 * {@inheritdoc}
	 */
	public function refund(TransactionInterface $transaction) {

		$this->updateTransactionStatus($transaction);
		if (!$transaction->paypal_sale_id) {
			return false;
		}

		$transaction->setStatus(TransactionInterface::STATUS_REFUND_PENDING);

		try {
			$sale = Sale::get($transaction->paypal_sale_id, $this->client->getApiContext());
			$amount = $sale->getAmount();
			// Sale amount includes details, which apparently PayPal API doesn't like for refunds
			$refund_amount = new \PayPal\Api\Amount();
			$refund_amount->setTotal($amount->getTotal())
				->setCurrency($amount->getCurrency());

			$refund = new RefundRequest();
			$refund->setAmount($refund_amount)
				->setInvoiceNumber($transaction->transaction_id);

			$sale->refundSale($refund, $this->client->getApiContext());
		} catch (\Exception $ex) {
			elgg_log($ex->getMessage(), 'ERROR');

			return false;
		}

		$this->updateTransactionStatus($transaction);

		return true;
	}


}
