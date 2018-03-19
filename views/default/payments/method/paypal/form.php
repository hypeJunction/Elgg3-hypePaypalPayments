<?php

echo elgg_view_field(array_merge($vars, [
	'#type' => 'paypal/paypal',
	'required' => true,
]));
