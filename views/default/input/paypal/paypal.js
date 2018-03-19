define(function (require) {

	var elgg = require('elgg');
	var paypal = require('paypal');
	var Ajax = require('elgg/Ajax');

	var api = {
		init: function (id) {
			var $elem = $(id);
			var $form = $elem.closest('form');

			if ($form.find('input[name="paypal_payment_id"]').is('[data-required]')) {
				$form.find('[type="submit"]').prop('disabled', true);
			}

			var config = $.extend({}, $elem.data('config'), {
				env: elgg.data.paypal_env,
				client: elgg.data.paypal_client,
				payment: function(resolve, reject) {
					var ajax = new Ajax(false);
					ajax.action($elem.data('createPaymentUrl'), {
						data: ajax.objectify($form)
					}).done(function(data) {
						resolve(data.payment.id);
					}).fail(function(err) {
						reject(err);
					});
				},
				onAuthorize: function(data) {
					$form.find('[name="paypal_payment_id"]').val(data.paymentID || data.orderID);
					$form.find('[name="paypal_payer_id"]').val(data.payerID);

					$form.get(0).submit();
				}
			});

			paypal.Button.render(config, $elem.find('.paypal-element')[0]);
		}
	};

	return api;
});