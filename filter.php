<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Filter
 *
 * Used to keep $_GET, $_POST, or any data across multiple page loads,
 * so users can see filtered results w/out having to re-filter.
 * Can also be used to repopulate form fields when redirecting to avoid
 * submitting a form twice for example. 
 *
 * @author   Corey Worrell
 * @homepage http://coreyworrell.com
 * @version  1.0
 */
class Filter {
	
	// Holds all filters available across site
	protected $_filters = array();
	
	// Reference to the session
	protected $_session;
	
	// The active controller name
	protected $_controller;
	
	// The active controller action name
	protected $_action;
	
	// Holds the filters 'local' to the active controller and action
	protected $_local = array();
	
	/**
	 * Creates a singleton instance
	 */
	public static function instance()
	{
		static $instance;
		
		! $instance AND $instance = new Filter;
		
		return $instance;
	}
	
	/**
	 * Sets up the filters environment in the Session
	 */
	public function __construct()
	{
		$this->_session = Session::instance();
		$this->_controller = Request::instance()->controller;
		$this->_action = Request::instance()->action;
		$this->_filters = $this->_session->get('filters', array());
		
		if ( ! isset($this->_filters[$this->_controller]))
		{
			$this->_filters[$this->_controller] = array();
		}
		if ( ! isset($this->_filters[$this->_controller][$this->_action]))
		{
			$this->_filters[$this->_controller][$this->_action] = array();
		}
		
		$this->_local = & $this->_filters[$this->_controller][$this->_action];
	}
	
	/**
	 * Add any number of keys to grab from $_GET and $_POST to be used as filters
	 *
	 * For example, if you want to store the page number and ordering for this page,
	 * you'd do this:
	 *     Filter::instance()->add('page', 'ordering');
	 * And now it will get the 'page' and 'ordering' values from $_GET or $_POST and
	 * put them in the filters Session array.
	 *
	 * @chainable
	 * @param   string   Key to get from $_GET or $_POST
	 * ...
	 * @return  Filter
	 */
	public function add()
	{
		$keys = func_get_args();
		
		if (count($keys) < 1)
			return $this;
			
		$globals = Arr::merge($_POST, $_GET)
			
		$vals = array();
		
		foreach ($keys as $key)
		{
			$vals[$key] = Arr::get($globals, $key);
		}
		
		$this->_local += $vals;
		
		$this->_session->set('filters', $this->_filters);
		
		return $this;
	}
	
	/**
	 * Set a key manually. Rather than getting from $_GET or $_POST
	 *
	 * @param   string   Filter name
	 * @param   mixed    Value of the filter
	 * @return  Filter
	 */
	public function set($key, $value = NULL)
	{
		if ( ! is_array($key))
		{
			$key = array($key => $value);
		}
		
		foreach ($key as $k => $v)
		{
			$this->_local[$k] = $v;
		}
		
		$this->_session->set('filters', $this->_filters);
		
		return $this;
	}
	
	/**
	 * Get all filters as array if no params are given, or return a specific key
	 *
	 * @param   string   Filter name
	 * @param   mixed    Default value if key does not exist
	 * @return  mixed    Value of filter
	 */
	public function get($key = NULL, $default = NULL)
	{
		if (empty($key))
		{
			$ret = isset($this->_local) ? $this->_local : array();
		}
		else
		{
			$ret = Arr::get($this->_local, $key, $default);
		}
		
		return $ret;
	}
	
	/**
	 * Delete filters
	 * If no parameters are given, it will delete all local filters
	 *
	 * @param   string   Filter name
	 * ...
	 * @return  Filter
	 */
	public function delete()
	{
		$keys = func_get_args();
		
		if (count($keys) < 1)
		{
			$this->_local = array();
			$this->_session->set('filters', $this->_filters);
		}
		else
		{
			foreach ($keys as $key)
			{
				if (array_key_exists($key, $this->_local))
				{
					unset($this->_local[$key]);
				}
			}
			
			$this->_session->set('filters', $this->_filters);
		}
		
		return $this;
	}
	
	/**
	 * Magic function to set a local filter
	 *
	 * @param   string   Filter name
	 * @param   mixed    Value of the filter
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
	 * @return  void
	 */
	public function __get($key)
	{
		$this->get($key);
	}

}