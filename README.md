hypePaypalPayments
==================

A wrapper for Paypal's PHP SDK

## Webhooks

Configure your Paypal application to send webhooks to ```https://<your-elgg-site>/payments/paypal/webhooks```

To digest a webhook, register a plugin hook handler:

```php
elgg_register_plugin_hook_handler('BILLING.SUBSCRIPTION.EXPIRED', 'paypal', HandleExpiredSubscription::class);

class HandleExpiredSubscription {
	public function __invoke(\Elgg\Hook $hook) {
		$webhook_data = $hook->getParam('data');
		
		// ... do stuff
		
		return $result; // Result will be reported back to paypal
	}
}

```

## Paypal Button

To display a pay button:

```php
echo elgg_view_field([
	'#type' => 'paypal/paypal',
	'required' => true,
]);
```

You can then retrieve the value of the Paypal's payment and payer ID your action:

```php
$payment_id = get_input('paypal_payment_id');
$payer_id = get_input('payer_id');

elgg()->{'payments.gateways.paypal'}->pay($transaction, [
	'paypal_payment_id' => $payment_id,
	'paypal_payer_id' => $payer_id,
]);
```