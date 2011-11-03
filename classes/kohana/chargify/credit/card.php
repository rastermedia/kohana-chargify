<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Chargify credit card resource.
 *
 * @author     Gabriel Evans <gabe@rastermedia.com>
 * @copyright  (c) 2011 Raster Media, LLC.
 * @license    http://www.opensource.org/licenses/bsd-license.php
 * @package    Chargify
 * @category   Resources
 */
class Kohana_Chargify_Credit_Card extends Chargify {

	public function create()
	{
		throw new Chargify_Exception(':resource resource does not support creation', array(
			':resource' => 'Credit card',
		));
	}

	public function update()
	{
		throw new Chargify_Exception(':resource resource does not support updates', array(
			':resource' => 'Credit card',
		));
	}

} // End Chargify_Credit_Card
