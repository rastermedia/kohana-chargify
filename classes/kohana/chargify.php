<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * API abstraction for Chargify with a fancy Active Resource-style pattern.
 *
 * @author     Gabriel Evans <gabe@rastermedia.com>
 * @copyright  (c) 2011 Raster Media, LLC.
 * @license    http://www.opensource.org/licenses/bsd-license.php
 * @package    Chargify
 */
class Kohana_Chargify {

	// Release version
	const VERSION = '0.1.0';

	/**
	 * @var   string  Chargify site subdomain
	 * @link  http://docs.chargify.com/sites
	 */
	public static $subdomain;

	/**
	 * @var   string  Chargify API key
	 * @link  http://docs.chargify.com/api-authentication
	 */
	public static $api_key;

	/**
	 * @var   string  Chargify site shared key
	 * @link  http://docs.chargify.com/webhooks#finding-your-site-shared-key
	 */
	public static $shared_key;

	/**
	 * @var  string  Format used for requests (`json` or `xml`)
	 */
	public static $format = 'json';

	/**
	 * @var  string  Default configuration group
	 */
	public static $default = 'default';

	/**
	 * @var  boolean  Has [Chargify::init()] been called?
	 */
	protected static $_init = FALSE;

	/**
	 * @var  array  Resource attributes
	 */
	protected $_attributes = array();

	/**
	 * @var  array  "Embeds one" relationships
	 */
	protected $_embeds_one = array();

	/**
	 * @var  array  Aliased attributes and relationships
	 */
	protected $_aliases = array();

	/**
	 * @var  array  Embedded resource objects
	 */
	protected $_embedded = array();

	/**
	 * @var  array
	 */
	protected $_changed = array();

	/**
	 * @var  array
	 */
	protected $_errors = array();

	/**
	 * @var  boolean
	 */
	protected $_loaded = FALSE;

	/**
	 * @var  boolean
	 */
	protected $_saved = FALSE;

	/**
	 * @var  string
	 */
	protected $_resource_name;

	/**
	 * @var  string
	 */
	protected $_resource_plural;

	/**
	 * @param   array  $options
	 * @return  void
	 */
	public static function init(array $options = NULL)
	{
		if ($options === NULL)
		{
			// Is Chargify already initialized?
			if (Chargify::$_init === TRUE)
			{
				return;
			}

			$options = Kohana::config('chargify.'.Chargify::$default);
		}

		if (Arr::get($options, 'subdomain') === NULL)
		{
			throw new Chargify_Exception('No API subdomain specified in configuration');
		}

		// Set site subdomain
		Chargify::$subdomain = $options['subdomain'];

		if (Arr::get($options, 'api_key') === NULL)
		{
			throw new Chargify_Exception('No API key specified in configuration');
		}

		// Set API key
		Chargify::$api_key = $options['api_key'];

		// Set site shared key
		Chargify::$shared_key = Arr::get($options, 'shared_key');

		// Chargify config is initialized
		Chargify::$_init = TRUE;
	}

	/**
	 * @return  void
	 */
	protected function _initialize()
	{
		if ( ! $this->_resource_name)
		{
			// Set the resource name
			$this->_resource_name = strtolower(substr(get_class($this), 9));
		}

		if ( ! $this->_resource_plural)
		{
			// Set the plural name
			$this->_resource_plural = Inflector::plural($this->_resource_name);
		}

		foreach ($this->_embeds_one as $resource => $options)
		{
			if ( ! array_key_exists('resource', $options))
			{
				// Set default resource name
				$this->_embeds_one[$resource]['resource'] = $options['resource'] = $resource;
			}

			// Store instance of resource object
			$this->_embedded[$resource] = Chargify::factory($options['resource']);
		}
	}

	/**
	 * @param  integer  $id
	 */
	protected function __construct($id = NULL)
	{
		if (Chargify::$_init === FALSE)
		{
			Chargify::init();
		}

		$this->_initialize();

		if ($id !== NULL)
		{
			$this->find($id);
		}
	}

	/**
	 * @param   string   $resource
	 * @param   integer  $id
	 * @return  Chargify
	 */
	public static function factory($resource, $id = NULL)
	{
		// Set class name
		$resource = 'Chargify_'.ucfirst($resource);

		return new $resource($id);
	}

	/**
	 * Handles retrieval of all resource values, relational references, and embedded
	 * resources.
	 *
	 * @param   string  $attribute  Attribute name
	 * @return  mixed
	 */
	public function __get($attribute)
	{
		if (array_key_exists($attribute, $this->_attributes))
		{
			// Return the resource's attribute
			return $this->_attributes[$attribute];
		}
		elseif (array_key_exists($attribute, $this->_aliases))
		{
			// Return the aliased embedded resource
			return $this->_embedded[$this->_aliases[$attribute]];
		}
		elseif (array_key_exists($attribute, $this->_embedded))
		{
			// Return the embedded resource
			return $this->_embedded[$attribute];
		}
		else
		{
			throw new Kohana_Exception('The :attribute attribute does not exist in the :class class',
				array(':attribute' => $attribute, ':class' => get_class($this)));
		}
	}

	/**
	 *
	 */
	public function __set($attribute, $value)
	{
		if (array_key_exists($attribute, $this->_aliases))
		{
			// Set aliased embedded resource
			$this->set($this->_aliases[$attribute], $value);
		}
		else
		{
			$this->set($attribute, $value);
		}
	}

	/**
	 *
	 */
	public function __isset($attribute)
	{
		return (isset($this->_attributes[$attribute]) OR
			isset($this->_embedded[$attribute]));
	}

	/**
	 *
	 */
	public function __unset($attribute)
	{
		if (array_key_exists($attribute, $this->_attributes))
		{
			unset($this->_attributes[$attribute]);
		}
		elseif (array_key_exists($attribute, $this->_embedded[$attribute]))
		{
			// Reset the embedded resource
			$this->_embedded[$attribute]->reset();
		}
	}

	/**
	 * @chainable
	 * @return  $this;
	 */
	public function reset()
	{
		// Clear attributes
		foreach ($this->_attributes as $attribute)
		{
			$this->_attributes[$attribute] = NULL;
		}

		foreach ($this->_embeds_one as $resource => $options)
		{
			$this->_embedded[$resource]->reset();
		}

		// Resource is no longer saved or loaded
		$this->_loaded = $this->_saved = FALSE;

		return $this;
	}

	/**
	 *
	 */
	public function set($attribute, $value)
	{
		if (array_key_exists($attribute, $this->_attributes))
		{
			// See if the data really changed
			if ($value !== $this->_attributes[$attribute])
			{
				$this->_attributes[$attribute] = $value;

				// Data has changed
				$this->_changed[$attribute] = $attribute;

				// Resource is no longer saved
				$this->_saved = FALSE;
			}
		}
		elseif (array_key_exists($attribute, $this->_embedded))
		{
			if ( ! Arr::is_assoc($value))
			{
				if (get_class($value) !== get_class($this->_embedded[$attribute]))
				{
					throw new Kohana_Exception('Embedded resource :attribute must be an instance of :class', array(
						':attribute' => $attribute,
						':class'     => get_class($this->_embedded[$attribute]),
					));
				}
			}
			else
			{
				$value = Chargify::factory($this->_embeds_one[$attribute]['resource'])
					->values($value);
			}

			$this->_embedded[$attribute] = $value;

			// Data has changed
			$this->_changed[$attribute] = $attribute;

			// Resource is no longer saved
			$this->_saved = FALSE;
		}
		else
		{
			// Set a new attribute on the current resource
			$this->_attributes[$attribute] = $value;

			// Data has changed
			$this->_changed[$attribute] = $attribute;

			// Resource is no longer saved
			$this->_saved = FALSE;
		}
	}

	/**
	 *
	 */
	public function reload()
	{
		$id = $this->id;

		// Replace attributes with an empty array
		$this->_changed = $this->_embedded = $this->_attributes = array();

		// Reinitialize object
		$this->_initialize();

		if ($this->loaded())
		{
			$this->find($id);
		}
		else
		{
			$this->_loaded = $this->_saved = FALSE;
		}
	}

	/**
	 *
	 */
	public function find($id)
	{
		return $this->request(Request::GET, $id);
	}

	/**
	 *
	 */
	public function find_all()
	{
		return $this->request(Request::GET);
	}

	/**
	 *
	 */
	public function count_all()
	{
		return $this->find_all()->count();
	}

	/**
	 *
	 */
	public function save()
	{
		return $this->loaded() ? $this->update() : $this->create();
	}

	/**
	 *
	 */
	public function create()
	{
		return $this->post($this);
	}

	/**
	 *
	 */
	public function update()
	{
		return $this->put($this, $this->id);
	}

	/**
	 *
	 */
	public function destroy()
	{
		return $this->delete($this->id);
		throw new Chargify_Exception('The :resource resource :method method is not implemented', array(
			':resource' => $this->_resource_plural,
			':method'   => 'delete',
		));
	}

	/**
	 * Set values from an array with support for one-one relationships.
	 * This method should be used for loading in post data, etc.
	 *
	 * @param  array  $values    Array of attribute => value
	 * @param  array  $expected  Array of keys to take from $values
	 */
	public function values(array $values, array $expected = NULL)
	{
		// Default to expecting anything that's passed
		if ($expected === NULL)
		{
			$expected = array_keys($values);
		}

		// Don't set the primary key
		unset($values['id'], $expected['id']);

		foreach ($expected as $key => $column)
		{
			// isset() fails when the value is NULL (we want it to pass)
			if ( ! array_key_exists($column, $values))
				continue;

			if (is_array($values[$column]))
			{
				// Try to set values to an embedded resource
				$this->{$column}->values($values[$column]);
			}
			else
			{
				// Update the column, respects __set()
				$this->$column = $values[$column];
			}
		}

		return $this;
	}

	/**
	 *
	 */
	public function load_results($results, $multiple = FALSE)
	{
		if ($multiple === FALSE)
		{
			return $this->load_values($results[$this->_resource_name], TRUE);
		}
		else
		{
			$row = array();
			foreach ($results as $key => $resource)
			{
				$rows[] = Chargify::factory(key($resource))
					->load_values($resource[key($resource)], TRUE);
			}

			return new Chargify_Result($rows);
		}
	}

	/**
	 * Loads an array of values into the current object, optionally setting the
	 * `_loaded` property to `TRUE`.
	 *
	 * @param  array    $values  Values to load
	 * @param  boolean  $loaded  Whether the object should be set to loaded
	 * @chainable
	 * @return  Chargify
	 */
	protected function load_values(array $values, $loaded = FALSE)
	{
		foreach ($values as $key => $value)
		{
			if (is_array($value))
			{
				// Try to set values to an embedded resource
				$this->{$key}->load_values($value, $loaded);
			}
			else
			{
				// Update the column, respects __set()
				$this->$key = $value;
			}
		}

		// Set loaded/saved value
		$this->_loaded = $this->_saved = $loaded;

		if ($loaded === TRUE)
		{
			// Empty changed
			$this->_changed = array();
		}

		return $this;
	}

	/**
	 *
	 */
	public function as_array()
	{
		$result = $this->_attributes;

		foreach ($this->_embedded as $resource => $attributes)
		{
			$result[$resource] = $attributes->as_array();
		}

		return array($this->_resource_name => $result);
	}

	/**
	 *
	 */
	public function as_json()
	{
		return json_encode($this->as_array());
	}

	/**
	 *
	 */
	public function as_xml()
	{
		throw new Kohana_Exception('Chargify module does not currently support XML responses');
	}

	protected function get($uri = NULL, array $query = array())
	{
		return $this->request(Request::GET, $uri, NULL, $query);
	}

	protected function post($body = NULL, $uri = NULL, array $query = array())
	{
		return $this->request(Request::POST, $uri, $body, $query);
	}

	protected function put($body = NULL, $uri = NULL, array $query = array())
	{
		return $this->request(Request::PUT, $uri, $body, $query);
	}

	protected function delete($uri = NULL, array $query = array())
	{
		return $this->request(Request::DELETE, $uri, NULL, $query);
	}

	protected function head($uri = NULL, array $query = array())
	{
		return $this->request(Request::HEAD, $uri, NULL, $query);
	}

	/**
	 * @param  string  $uri
	 * @param  string  $body
	 */
	protected function request($method, $uri = NULL, $body = NULL, array $query = array())
	{
		if ($body instanceof Chargify)
		{
			if (Chargify::$format == 'json')
			{
				$body = $body->as_json();
			}
			elseif (Chargify::$format == 'xml')
			{
				$body = $body->as_xml();
			}
		}

		// Get format mimetype
		$format = File::mime_by_ext(Chargify::$format);

		// Build a new Chargify request object
		$request = Chargify_Request::factory($this->build_uri($uri))
			->method($method)
			->headers('content-type', $format)
			;

		if ($body !== NULL)
		{
			$request->body($body);
		}

		if ($request->method() === Request::GET)
		{
			// Set accept header
			$request->headers('accept', $format);

			foreach ($query as $param => $value)
			{
				// Add query parameters
				$request->query($param, $value);
			}
		}

		return $this->parse_response($request->execute());
	}

	/**
	 * Builds a request URL based on supplied URI.
	 *
	 * @param   string  $uri
	 * @return  string
	 */
	protected function build_uri($uri = NULL)
	{
		// Prefix with resource name
		if ($uri !== NULL OR strpos($uri, '/') === 0)
		{
			$uri = '/'.$this->_resource_plural.'/'.$uri;
		}
		else
		{
			$uri = '/'.$this->_resource_plural;
		}

		return 'https://'.Chargify::$subdomain.'.chargify.com'.$uri.'.'.Chargify::$format;
	}

	/**
	 * Parses API responses and deserializes them into an object.
	 *
	 * @param   Response  $response
	 * @return  Chargify  `FALSE` on failure
	 * @throws  Chargify_Exception
	 */
	protected function parse_response($response)
	{
		switch ($response->status())
		{
			case 200:
			case 201:
				if (strpos($response->headers('content-type'), 'application/json') === 0)
				{
					$body = $this->parse_json_response($response->body());
				}
				elseif (strpos($response->headers('content-type'), 'application/xml') === 0)
				{
					$body = $this->parse_xml_response($response->body());
				}
				else
				{
					throw new Kohana_Exception('Chargify API response returned with unknown Content-Type: :content_type', array(
						':content_type' => $response->headers('content-type'),
					));
				}

				if (Arr::is_assoc($body))
				{
					$results = $this->load_results($body);
				}
				else
				{
					$results = $this->load_results($body, TRUE);
				}

				return $results;
				break;
			case 401:
				throw new Chargify_Exception('Invalid API credentials', NULL, 401);
				break;
			case 404:
				throw new Chargify_Exception('Requested :resource resource does not exist', array(
					':resource' => $this->_resource_name,
				), 404);
				break;
			case 422:
				$errors = $this->parse_json_response($response->body());
				throw new Chargify_Exception('Chargify API responded with error message(s)', NULL, 422, $errors['errors']);
				break;
			case 500:
				throw new Chargify_Exception('Internal Server Error', NULL, 500);
				break;
			default:
				throw new Chargify_Exception('Unknown API response status', NULL, $response->status());
				break;
		}

		return FALSE;
	}

	protected function parse_json_response($body)
	{
		return json_decode($body, TRUE);
	}

	protected function parse_xml_response($body)
	{
		throw new Kohana_Exception('Chargify module does not currently support XML responses');
	}

	public function errors()
	{
		return $this->_errors;
	}

} // End Chargify