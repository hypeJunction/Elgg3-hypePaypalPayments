<?php

$entity = elgg_extract('entity', $vars);

$link = elgg_view('output/url', [
	'href' => elgg_generate_url('payments:paypal:webhooks'),
]);

$message = elgg_echo('payments:paypal:settings:webhooks', [$link]);
echo elgg_view_message('notice', $message, [
	'title' => false,
]);

$fields = [
	'sandbox_account' => 'text',
	'sandbox_client_id' => 'text',
	'sandbox_secret' => 'text',
	'sandbox_webhook_id' => 'text',
	'production_account' => 'text',
	'production_client_id' => 'text',
	'production_secret' => 'text',
	'production_webhook_id' => 'text',
];

foreach ($fields as $name => $options) {
	if (is_string($options)) {
		$options = [
			'#type' => $options,
		];
	}

	$options['name'] = "params[$name]";
	$options['value'] = $entity->$name;
	$options['#label'] = elgg_echo("payments:paypal:setting:$name");

	echo elgg_view_field($options);
}