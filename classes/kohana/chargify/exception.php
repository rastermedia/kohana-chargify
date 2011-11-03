<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Chargify exception class.
 *
 * @author     Gabriel Evans <gabe@rastermedia.com>
 * @copyright  (c) 2011 Raster Media, LLC.
 * @license    http://www.opensource.org/licenses/bsd-license.php
 * @package    Chargify
 * @category   Exceptions
 */
class Kohana_Chargify_Exception extends Kohana_Request_Exception {

	protected $_errors;

	public function __construct($message, array $values = NULL, $code = 0, array $errors = array())
	{
		$this->_errors = $errors;

		parent::__construct($message, $values, $code);
	}

	public function errors()
	{
		return $this->_errors;
	}

} // End Chargify_Exception