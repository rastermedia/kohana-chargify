<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Chargify webhooks controller.
 *
 * @author     Gabriel Evans <gabe@rastermedia.com>
 * @copyright  (c) 2011 Raster Media, LLC.
 * @license    http://www.opensource.org/licenses/bsd-license.php
 * @package    Chargify
 * @category   Webhooks
 */
class Kohana_Controller_Chargify_Webhooks extends Controller {

	/**
	 * @var  array  Chargify webhook payload
	 */
	protected $payload = array();

	public function before()
	{
		if ($this->request->is_initial())
		{
			// Initialize Chargify if isn't already
			Chargify::init();

			if (Chargify::$shared_key === NULL)
			{
				throw new Chargify_Exception('No site shared key configured');
			}

			$signature = $this->request->headers('x-chargify-webhook-signature') ?
				$this->request->headers('x-chargify-webhook-signature') : $this->request->param('signature');

			if (md5(Chargify::$shared_key.$this->request->body()) !== $signature)
			{
				throw new HTTP_Exception_403('Webhook signature mismatch');
			}
		}
		else
		{
			$this->payload = $this->_parse_payload();
		}
	}

	protected function _parse_payload()
	{
		$result = $this->request->post();

		if (count($result) === 1)
		{
			$this->payload = Chargify::factory(key($result))
				->load_result($result[key($result)])
				;
		}
		else
		{
			$this->payload = Chargify::factory(key($result[0]))
				->load_result($result, TRUE)
				;
		}
	}

	public function action_index()
	{
		$route = new Route('chargify/webhooks/<action>');
		$route
			->defaults(array(
				'directory'  => 'chargify',
				'controller' => 'webhooks',
				'action'     => $this->request->post('event'),
			));

		$response = Request::factory($route->uri())
			->method(Request::POST)
			->post($this->request->post('payload'))
			->execute()
			;

		$this->response
			->headers($response->headers())
			->body($response->body())
			;
	}

	public function action_signup_success()
	{
		throw new HTTP_Exception_501;
	}

	public function action_signup_failure()
	{
		throw new HTTP_Exception_501;
	}

	public function action_renewal_success()
	{
		throw new HTTP_Exception_501;
	}

	public function action_payment_success()
	{
		throw new HTTP_Exception_501;
	}

	public function action_payment_failure()
	{
		throw new HTTP_Exception_501;
	}

	public function action_billing_date_change()
	{
		throw new HTTP_Exception_501;
	}

	public function action_subscription_state_change()
	{
		throw new HTTP_Exception_501;
	}

	public function action_subscription_product_change()
	{
		throw new HTTP_Exception_501;
	}

	public function action_expiring_card()
	{
		throw new HTTP_Exception_501;
	}

}