<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Chargify subscription resource.
 *
 * @author     Gabriel Evans <gabe@rastermedia.com>
 * @copyright  (c) 2011 Raster Media, LLC.
 * @license    http://www.opensource.org/licenses/bsd-license.php
 * @package    Chargify
 * @category   Resources
 */
class Kohana_Chargify_Subscription extends Chargify {

	protected $_embeds_one = array(
		'payment_profile_attributes' => array('resource' => 'credit_card'),
		'customer_attributes'        => array('resource' => 'customer'),
		'product'                    => array(),
	);

	protected $_aliases = array(
		'credit_card'            => 'payment_profile_attributes',
		'credit_card_attributes' => 'payment_profile_attributes',
		'customer'               => 'customer_attributes',
	);

	public function find_by_customer($customer)
	{
		if (is_object($customer))
		{
			$customer = $customer->id;
		}

		return $this->get('/customers/'.$customer.'/subscriptions');
	}

	public function create()
	{
		if (isset($this->product_handle))
		{
			unset($this->_embedded['product']);
		}

		$payment_profile = $this->payment_profile_attributes->as_array();

		if (empty($payment_profile))
		{
			unset($this->_embedded['payment_profile_attributes']);
		}

		return parent::create();
	}

}