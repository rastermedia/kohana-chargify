<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Chargify product resource model.
 *
 * @author     Gabriel Evans <gabe@rastermedia.com>
 * @copyright  (c) 2011 Raster Media, LLC.
 * @license    http://www.opensource.org/licenses/bsd-license.php
 * @package    Chargify
 * @category   Resources
 */
class Kohana_Chargify_Product extends Chargify {

	protected $_embeds_one = array(
		'product_family' => array(),
	);

	/**
	 * Finds a single product by its API handle.
	 *
	 * @param   string  $handle  API handle
	 * @return  Chargify_Product
	 */
	public function find_by_handle($handle)
	{
		return $this->request(Request::GET, 'handle/'.$handle);
	}

} // End Chargify_Product