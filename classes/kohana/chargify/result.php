<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * A near exact copy of [Database_Result], modified to iterate through Chargify
 * API results with an additional method for converting to JSON.
 *
 * @package    Chargify
 * @category   Request/Result
 * @author     Kohana Team, Gabriel Evans <gabriel@codeconcoction.com>
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Kohana_Chargify_Result implements Countable, Iterator, SeekableIterator, ArrayAccess {

	protected $_results;

	protected $_current_result = 0;

	protected $_internal_result = 0;

	protected $_total_results = 0;

	public function __construct(array $results = array())
	{
		$this->_results = $results;

		$this->_total_results = count($results);
	}

	public function as_array($key = NULL, $value = NULL)
	{
		// ...
	}

	public function as_json($key = NULL, $value = NULL)
	{
		return json_encode($this->as_array($key, $value));
	}

	public function seek($offset)
	{
		if ($this->offsetExists($offset))
		{
			// Set the current result to the offset
			$this->_current_result = $this->_internal_result = $offset;

			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	public function current()
	{
		if ($this->_current_result !== $this->_internal_result AND ! $this->seek($this->_current_result))
		{
			return NULL;
		}

		// Increment internal result for optimization assuming results are fetched in order
		$this->_internal_result++;

		return $this->_results[$this->_current_result];
	}

	public function count()
	{
		return $this->_total_results;
	}

	public function key()
	{
		return $this->_current_result;
	}

	public function offsetExists($offset)
	{
		return ($offset >= 0 AND $offset < $this->_total_results);
	}

	public function offsetGet($offset)
	{
		if ( ! $this->seek($offset))
		{
			return NULL;
		}

		return $this->current();
	}

	final public function offsetSet($offset, $value)
	{
		throw new Chargify_Exception('Request results are read-only');
	}

	final public function offsetUnset($offset)
	{
		throw new Chargify_Exception('Request results are read-only');
	}

	public function prev()
	{
		--$this->_current_result;
		return $this;
	}

	public function next()
	{
		++$this->_current_result;
		return $this;
	}

	public function rewind()
	{
		$this->_current_result = 0;
		return $this;
	}

	public function valid()
	{
		return $this->offsetExists($this->_current_result);
	}

} // End Chargify_Result