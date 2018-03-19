<?php

namespace hypeJunction\Paypal;

use Elgg\BadRequestException;
use Elgg\Http\ResponseBuilder;
use Elgg\HttpException;
use Elgg\Request;
use PayPal\Api\VerifyWebhookSignature;
use PayPal\Api\WebhookEvent;
use Paypal\Error\SignatureVerification;
use PayPal\Exception\PayPalConfigurationException;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Exception\PayPalInvalidCredentialException;
use PayPal\Exception\PayPalMissingCredentialException;
use UnexpectedValueException;

class DigestWebhook {

	/**
	 * Digest paypal webhook
	 *
	 * @param Request $request Request
	 *
	 * @return ResponseBuilder
	 * @throws BadRequestException
	 * @throws HttpException
	 */
	public function __invoke(Request $request) {

		elgg_set_viewtype('json');

		elgg_set_http_header('Content-Type: application/json');

		$paypal = elgg()->paypal;
		/* @var $paypal PaypalClient */

		try {
			$payload = _elgg_services()->request->getContent();
			$headers = _elgg_services()->request->headers;

			$verification = new VerifyWebhookSignature();
			$verification->setWebhookId($paypal->webhook_id);
			$verification->setAuthAlgo($headers->get('PAYPAL-AUTH-ALGO'));
			$verification->setTransmissionId($headers->get('PAYPAL-TRANSMISSION-ID'));
			$verification->setCertUrl($headers->get('PAYPAL-CERT-URL'));
			$verification->setTransmissionSig($headers->get('PAYPAL-TRANSMISSION-SIG'));
			$verification->setTransmissionTime($headers->get('PAYPAL-TRANSMISSION-TIME'));

			$webhook_event = new WebhookEvent();
			$webhook_event->fromJson($payload);

			$verification->setRequestBody($webhook_event);

			$response = $verification->post($paypal->getApiContext());

			if ($response->getVerificationStatus() !== 'SUCCESS') {
				throw new BadRequestException();
			}

		} catch (PayPalConfigurationException $ex) {
			$msg = elgg_echo('payments:paypal:api:connection_error');

			return elgg_error_response($msg, REFERRER, ELGG_HTTP_BAD_REQUEST);
		} catch (PayPalInvalidCredentialException $ex) {
			$msg = elgg_echo('payments:paypal:api:invalid_credentials_error');

			return elgg_error_response($msg, REFERRER, ELGG_HTTP_BAD_REQUEST);

		} catch (PayPalMissingCredentialException $ex) {
			$msg = elgg_echo('payments:paypal:api:missing_credentials_error');

			return elgg_error_response($msg, REFERRER, ELGG_HTTP_BAD_REQUEST);
		} catch (PayPalConnectionException $ex) {
			$msg = elgg_echo('payments:paypal:api:missing_credentials_error');

			return elgg_error_response($msg, REFERRER, ELGG_HTTP_BAD_REQUEST);
		} catch (\Exception $ex) {
			//return elgg_error_response($ex->getMessage(), REFERRER, $ex->getCode());
		}

		$data = json_decode($payload);

		$result = elgg_trigger_plugin_hook($data->event_type, 'paypal', ['data' => $data]);

		if ($result === false) {
			return elgg_error_response(
				'Event was not digested because one of the handlers refused to process data',
				REFERRER,
				ELGG_HTTP_INTERNAL_SERVER_ERROR
			);
		}

		return elgg_ok_response(['result' => $result]);

	}

}