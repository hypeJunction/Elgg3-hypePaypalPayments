<?php

$intent = elgg_extract('intent', $vars, 'sale');
$create_payment_url = elgg_generate_action_url("payments/checkout/paypal/$intent");

$id = "paypal-input-" . base_convert(mt_rand(), 10, 36);

$paypal = elgg_format_element('div', [
	'class' => 'paypal-element',
]);

$errors = elgg_format_element('div', [
	'class' => 'paypal-errors hidden',
]);

$hidden = elgg_view_field([
	'#type' => 'hidden',
	'name' => 'paypal_payment_id',
	'data-required' => elgg_extract('required', $vars, false),
]);

$hidden .= elgg_view_field([
	'#type' => 'hidden',
	'name' => 'paypal_payer_id',
]);

$amount = elgg_extract('amount', $vars);
/* @var $amount \hypeJunction\Payments\Amount */

$attrs = [
	'id' => $id,
	'data-paypal' => '',
	'data-create-payment-url' => $create_payment_url,
];

$config = elgg_extract('config', $vars, []);
if (!isset($config['style'])) {
	$config['style']['color'] = 'blue';
	$config['style']['shape'] = 'rect';
	$config['style']['size'] = 'large';
}

$attrs['data-config'] = json_encode($config);

echo elgg_format_element('div', $attrs, $paypal . $errors . $hidden);
?>

<script>
	require(['input/paypal/paypal'], function (paypal) {
		paypal.init('#<?= $id ?>');
	});
</script>