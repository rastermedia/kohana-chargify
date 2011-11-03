<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * This is a torrible, horrible, no good, very bad class extending
 * [Request] to get around the inability to set options on `Request_Client`
 * in Kohana 3.1.
 *
 * [!!] Don't rely on this class. It will be gone soon.
 *
 * @package    Chargify
 * @category   Request/Result
 * @author     Gabriel Evans <gabriel@codeconcoction.com>
 * @copyright  copyright_placeholder
 * @license    license_placeholder
 */
class Kohana_Chargify_Request extends Request {

	public function __construct($uri, Cache $cache = NULL, $injected_routes = array())
	{
		parent::__construct($uri, $cache, $injected_routes);

		$this->_client
			->options(CURLOPT_USERPWD, Chargify::$api_key.':x')
			->options(CURLOPT_USERAGENT, 'Kohana Chargify v'.Chargify::VERSION.' (https://github.com/gevans/kohana-chargify)');
			;
	}

	public static function factory($uri = TRUE, Cache $cache = NULL, $injected_routes = array())
	{
		return new Chargify_Request($uri, $cache, $injected_routes);
	}

} // End Chargify_Request