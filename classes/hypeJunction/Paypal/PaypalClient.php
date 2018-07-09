<?php

namespace hypeJunction\Paypal;

use Paypal\Account;
use Paypal\ApiResource;
use PayPal\Auth\OAuthTokenCredential;
use Paypal\BalanceTransaction;
use Paypal\Card;
use Paypal\Charge;
use Paypal\CountrySpec;
use Paypal\Customer;
use Paypal\Invoice;
use Paypal\Paypal;
use Paypal\PaypalObject;
use Paypal\Plan;
use Paypal\Refund;
use PayPal\Rest\ApiContext;
use Paypal\Subscription;

/**
 * @property string $environment
 * @property string $account
 * @property string $client_id
 * @property string $secret
 * @property string $webhook_id
 */
class PaypalClient {

	/**
	 * Configure the client
	 */
	public function setup() {

		$this->environment = elgg_get_plugin_setting('environment', 'hypePayments');

		switch ($this->environment) {
			default :
				$this->account = elgg_get_plugin_setting('sandbox_account', 'hypePaypalPayments');
				$this->client_id = elgg_get_plugin_setting('sandbox_client_id', 'hypePaypalPayments');
				$this->secret = elgg_get_plugin_setting('sandbox_secret', 'hypePaypalPayments');
				$this->webhook_id = elgg_get_plugin_setting('sandbox_webhook_id', 'hypePaypalPayments');
				break;

			case 'production' :
				$this->account = elgg_get_plugin_setting('production_account', 'hypePaypalPayments');
				$this->client_id = elgg_get_plugin_setting('production_client_id', 'hypePaypalPayments');
				$this->secret = elgg_get_plugin_setting('production_secret', 'hypePaypalPayments');
				$this->webhook_id = elgg_get_plugin_setting('production_webhook_id', 'hypePaypalPayments');
				break;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function __get($name) {
		return $this->$name;
	}

	/**
	 * Returns PP API context
	 * @return ApiContext
	 */
	public function getApiContext() {
		$credential= new OAuthTokenCredential($this->client_id, $this->secret);
		$api_context = new ApiContext($credential);

		$api_context->setConfig([
			'mode' => $this->environment === 'production' ? 'live' : 'sandbox',
			'log.LogEnabled' => true,
			'log.FileName' => elgg_get_config('dataroot') . "paypal/{$this->environment}.log",
			'log.LogLevel' => 'DEBUG',
			'cache.enabled' => true,
			'cache.FileName' => elgg_get_config('dataroot') . 'paypal/auth.test.cache',
		]);

		return $api_context;

	}
}