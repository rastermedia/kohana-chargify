<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Chargify [coupons](http://docs.chargify.com/api-coupons) resource model.
 *
 * @author     Gabriel Evans <gabe@rastermedia.com>
 * @copyright  (c) 2011 Raster Media, LLC.
 * @license    http://www.opensource.org/licenses/bsd-license.php
 * @package    Chargify
 * @category   Resources
 */
class Kohana_Chargify_Coupon extends Chargify {

	public function find_by_code($code)
	{
		return $this->request(Request::GET, 'find', NULL, array('code' => $code));
	}

	public function validate($id)
	{
		if ( ! ctype_digit($id))
		{
			try
			{
				// Find the coupon code's ID
				$id = $this->find_by_code($id)->id;
			}
			catch (Chargify_Exception $e)
			{
				// Give up, the code wasn't found
				return FALSE;
			}
		}

		try
		{
			// Check that the coupon is valid
			$this->request(Request::GET, $id.'/validate');
		}
		catch (Chargify_Exception $e)
		{
			if ($e->getCode() !== 404)
			{
				// The request failed for some reason other than a 404
				throw new Chargify_Exception($e->getMessage(), NULL, $e->getCode(), $e->errors());
			}

			return FALSE;
		}

		return TRUE;
	}

}