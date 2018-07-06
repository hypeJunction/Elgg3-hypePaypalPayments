define(function (require) {

	var elgg = require('elgg');
	var paypal = require('paypal');
	var Ajax = require('elgg/Ajax');
	var Form = require('ajax/Form');

	var api = {
		init: function (id) {
			var $elem = $(id);
			var $form = $elem.closest('form');
			var form = new Form($form);

			form.onSubmit(function (resolve, reject) {
				if (!$form.find('input[name="paypal_payment_id"]').is('[data-required]')) {
					return resolve();
				}

				if (!$form.find('[name="paypal_payment_id"]').val()) {
					return reject('Paypal payment must be authorized');
				}
			};

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
				}
			});

			paypal.Button.render(config, $elem.find('.paypal-element')[0]);
		}
	};

	return api;
});