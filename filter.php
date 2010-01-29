<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Filter
 *
 * Sets up a filtering system where keys from $_GET and $_POST can be stored
 * in the session to be used to filter data. The advantage to storing these in
 * the session is that the filters will remain after a user leaves the page,
 * so that when they come back they won't have to refilter anything.
 *
 * @author   Corey Worrell
 * @homepage http://coreyworrell.com
 * @version  1.2
 */
class Filter {
	
	// Holds all filters available across site
	protected $_filters = array();
	
	// Holds the filters 'local' to the active controller and action
	protected $_local = array();
	
	// References to the defaults
	protected $_keys = array();
	
	// Reference to globals
	protected $_globals = array();
	
	// Session key to store filters
	protected $_sk;
	
	// Instance of class
	protected static $_instance;
	
	/**
	 * Creates an instance of Filter
	 *
	 * Add keys to grab from $_GET and $_POST to be used as filters
	 *
	 * @param   array   key => default to grab from globals
	 * @param   string  Session key to store filters in
	 * @return  Filter
	 */
	public static function instance(array $keys, $session_key = 'filters')
	{
		if (self::$_instance === NULL)
		{
			self::$_instance = new Filter($keys, $session_key);
		}
		
		return self::$_instance;
	}
	
	/**
	 * Sets up the filters environment in the Session
	 *
	 * @param   array   key => default to grab from globals
	 * @param   string  Session key to store filters in
	 * @return  void
	 */
	public function __construct(array $keys, $session_key = 'filters')
	{
		$this->_sk      = $session_key;
		$this->_keys    = $keys;
		$this->_filters = Session::instance()->get($this->_sk, array());
		$this->_globals = Arr::merge($_POST, $_GET);
		
		$controller = Request::instance()->controller;
		$action     = Request::instance()->action;
		
		if ( ! isset($this->_filters[$controller]))
		{
			$this->_filters[$controller] = array();
		}
		
		if ( ! isset($this->_filters[$controller][$action]))
		{
			$this->_filters[$controller][$action] = array();
		}
		
		$this->_local = & $this->_filters[$controller][$action];
		
		$this->add($keys);
	}
	
	/**
	 * Add filters
	 * $keys can be an array containing keys => defaults
	 *
	 * @param   string   Filter key
	 * @param   mixed    Default
	 * @return  Filter
	 */
	public function add($keys, $value = NULL)
	{
		if ( ! is_array($keys))
		{
			$keys = array($keys => $value);
		}
		
		$this->_keys += $keys;
		
		foreach ($keys as $key => $default)
		{
			if (array_key_exists($key, $this->_globals))
			{
				$this->_local[$key] = Arr::get($this->_globals, $key, $default);
			}
			else
			{
				$this->_local[$key] = $default;
			}
		}
		
		$this->_session_set();
		
		return $this;
	}
	
	/**
	 * Set a key manually. Rather than getting from $_GET or $_POST
	 *
	 * $key can be an array containing keys => values to set multiple keys at once
	 *
	 * @param   string   Filter name
	 * @param   mixed    Filter value
	 * @return  Filter
	 */
	public function set($keys, $value = NULL)
	{
		if ( ! is_array($keys))
		{
			$keys = array($keys => $value);
		}
		
		foreach ($keys as $key => $value)
		{
			$this->_local[$key] = $value;
		}
		
		$this->_session_set();
		
		return $this;
	}
	
	/**
	 * Get a filter, or if no params are given return all local filters
	 *
	 * @param   string   Filter key
	 * @param   mixed    Default value if key does not exist
	 * @return  mixed    Filter value
	 */
	public function get($key = NULL, $default = NULL)
	{
		if (empty($key))
		{
			$data = $this->_local;
		}
		else
		{
			$data = Arr::get($this->_local, $key, $default);
		}
		
		return $data;
	}
	
	/**
	 * Get all global filters as an array
	 *
	 * @return   array  All filters
	 */
	public function get_global()
	{
		return $this->_filters;
	}
	
	/**
	 * Delete filters
	 * If no keys are given, it will delete all local filters
	 *
	 * @param   array   One key or an array of keys to delete
	 * @return  Filter
	 */
	public function delete($keys = NULL)
	{
		if (empty($keys))
		{
			$this->_local = array();
		}
		else
		{
			if ( ! is_array($keys))
			{
				$keys = array($keys);
			}
			
			foreach ($keys as $key)
			{
				if (array_key_exists($key, $this->_local))
				{
					unset($this->_local[$key]);
				}
			}
		}
		
		$this->_session_set();
		
		return $this;
	}
	
	/**
	 * Reset filters to defaults
	 * If no keys are given, all keys will be reset
	 *
	 * @param   array   One key or an array of keys to reset
	 * @return  Filter
	 */
	public function reset($keys = NULL)
	{
		if (empty($keys))
		{
			foreach ($this->_keys as $key => $default)
			{
				$this->_local[$key] = $default;
			}
		}
		else
		{
			if ( ! is_array($keys))
			{
				$keys = array($keys);
			}
			
			foreach ($keys as $key)
			{
				$this->_local[$key] = $this->_keys[$key];
			}
		}
		
		$this->_session_set();
		
		return $this;
	}
	
	/**
	 * Magic function to set a local filter
	 *
	 * @param   string   Filter name
	 * @param   mixed    Filter value
	 * @return  void
	 */
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}
	
	/**
	 * Magic function to get a local filter
	 *
	 * @param   string   Filter name
	 * @return  mixed    Filter value
	 */
	public function __get($key)
	{
		return $this->get($key);
	}
	
	/**
	 * Writes the filters to the session
	 *
	 * @return  void
	 */
	protected function _session_set()
	{
		Session::instance()->set($this->_sk, $this->_filters);
	}

}