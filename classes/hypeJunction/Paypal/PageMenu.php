<?php

namespace hypeJunction\Paypal;

use Elgg\Hook;

class PageMenu {

	public function __invoke(Hook $hook) {

		$menu = $hook->getValue();

		$menu[] = \ElggMenuItem::factory([
			'name' => 'payments:paypal:settings',
			'parent_name' => 'payments',
			'href' => 'admin/plugin_settings/hypePaypalPayments',
			'text' => elgg_echo('payments:paypal:settings'),
			'icon' => 'cog',
			'context' => ['admin'],
			'section' => 'configure',
		]);

		$menu[] = \ElggMenuItem::factory([
			'name' => 'payments:paypal:transactions',
			'parent_name' => 'payments',
			'href' => 'admin/payments/paypal',
			'text' => elgg_echo('payments:paypal:transactions'),
			'icon' => 'exchange',
			'context' => ['admin'],
			'section' => 'configure',
		]);

		return $menu;
	}
}