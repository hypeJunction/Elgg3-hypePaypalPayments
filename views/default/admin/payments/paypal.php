<?php

use hypeJunction\Payments\Transaction;

$invoice = get_input('invoice');
$status = get_input('status');

$options = [
	'types' => 'object',
	'subtypes' => Transaction::SUBTYPE,
	'list_type' => 'table',
	'metadata_name_value_pairs' => [
		'payment_method' => 'paypal',
	],
	'columns' => \hypeJunction\Payments\Transaction::getTableColumns(),
	'list_class' => 'payments-transactions',
	'item_class' => 'payments-transaction',
	'no_results' => elgg_echo('payments:transactions:no_results'),
];

if ($invoice) {
	$options['guids'] = $invoice;
}

if ($status) {
	$options['metadata_name_value_pairs'][] = [
		'name' => 'status',
		'value' => $status,
	];
}

echo elgg_view_form('payments/paypal/search', [
	'disable_security' => true,
	'method' => 'GET',
	'action' => 'admin/payments/paypal',
]);

echo elgg_list_entities($options);
