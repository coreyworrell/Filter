<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Filter
 *
 * Sets up a filtering system where keys from `$_GET` and `$_POST` can be stored
 * in the session to be used to filter data. The advantage to storing these in
 * the session is that the filters will remain after a user leaves the page,
 * so that when they come back they won't have to refilter anything.
 *
 * @package  Filter
 * @author   Corey Worrell
 * @homepage http://coreyworrell.com
 * @version  1.3
 */
class Filter {
	
	/**
	 * @var  string  session key to store filters in
	 */
	public static $session_key = 'filters';
	
	/**
	 * @var  array  holds all the filters
	 */
	protected $_filters = array();
	
	/**
	 * @var  array  holds the defaults for filters
	 */
	protected $_originals = array();
	
	/**
	 * @var  array  holds the keys that have changed since the last page load
	 */
	protected $_changed = array();
	
	/**
	 * @var  string  path to current request
	 */
	protected $_path;
	
	/**
	 * @var  array  `$_GET` and `$_POST` merged
	 */
	protected $_globals;
	
	/**
	 * @var  Filter  current instance of Filter
	 */
	protected static $_instance;
	
	/**
	 * Return an instance of Filter for current request
	 *
	 *     $filters = Filter::instance(array(
	 *         'page'     => 1,
	 *         'per_page' => 20,
	 *         'search'   => '',
	 *         'status'   => 'published',
	 *     ));
	 *
	 * @chainable
	 * @param   array  $filters  filters to keep track of
	 * @return  Filter
	 */
	public static function instance($filters = NULL)
	{
		if (Filter::$_instance === NULL)
		{
			Filter::$_instance = new Filter($filters);
		}
		
		return Filter::$_instance;
	}
	
	/**
	 * Return an instance of Filter for the path given
	 *
	 * Useful for getting the filter values from another controller or action
	 *
	 *     $filters = Filter::path('admin/blog/archives');
	 *
	 * @chainable
	 * @param   string  $path  path to request
	 * @return  Filter
	 */
	public static function path($path)
	{
		return new Filter(NULL, $path);
	}
	
	/**
	 * Create a new instance of Filter
	 *
	 * Sets up globals and adds keys to keep track of
	 *
	 * @param   array   $filters  global keys to keep track of
	 * @param   string  $path     path to request (if not getting current request)
	 * @return  Filter
	 */
	public function __construct($filters = NULL, $path = NULL)
	{
		$this->_globals = array_merge($_POST, $_GET);
		
		if ($path === NULL)
		{
			$directory  = Request::$current->directory();
			$controller = Request::$current->controller();
			$action     = Request::$current->action();
			
			$this->_path = trim("$directory/$controller/$action", '/');
			
			unset($directory, $controller, $action);
		}
		else
		{
			$this->_path = $path;
		}
		
		// Get filters from session
		$this->_filters = Session::instance()->get(Filter::$session_key, array());
		
		// Make sure paths exist
		if ( ! isset($this->_filters[$this->_path]))
		{
			$this->_filters[$this->_path] = array();
		}
		
		if ( ! isset($this->_changed[$this->_path]))
		{
			$this->_changed[$this->_path] = array();
		}
		
		// If filters are passed, keep track of them
		if (is_array($filters))
		{
			$this->track($filters);
		}
	}
	
	/**
	 * Keep track of global keys
	 *
	 * Can be called instead of passing filters to [Filter::instance]
	 *
	 *     $filters = Filter::instance()
	 *         ->track(array(
	 *             'page'     => 1,
	 *             'per_page' => 20,
	 *         ));
	 *
	 * @chainable
	 * @param   array  $filters  global keys to keep track of
	 * @return  Filter
	 */
	public function track(array $filters)
	{
		$this->_originals += $filters;
		
		foreach ($filters as $key => $default)
		{
			if (array_key_exists($key, $this->_globals))
			{
				$value = $this->_globals[$key];
				
				if (array_key_exists($key, $this->_filters[$this->_path]) AND $this->_filters[$this->_path][$key] !== $value)
				{
					$this->_changed[$this->_path][$key] = $key;
				}
				
				$this->_filters[$this->_path][$key] = $value;
			}
			elseif ( ! array_key_exists($key, $this->_filters[$this->_path]))
			{
				// NULL doesn't exist for incoming data, as all params are strings
				if ($default === NULL)
				{
					$default = '';
				}
				
				$this->_filters[$this->_path][$key] = $default;
			}
		}
		
		$this->_save();
		
		return $this;
	}
	
	/**
	 * Set a filter manually, instead of letting Filter get it from the globals
	 *
	 *     $filters->set('page', 10);
	 *     
	 *     $filters->set(array(
	 *         'status' => 'draft',
	 *         'order'  => 'date',
	 *     ));
	 *
	 * @chainable
	 * @param   mixed  $filters  array of key => values, or just the key
	 * @param   mixed  $value    value of key if first param is a string
	 * @return  Filter
	 */
	public function set($filters, $value = NULL)
	{
		if ( ! is_array($filters))
		{
			$filters = array($filters => $value);
		}
		
		foreach ($filters as $key => $value)
		{
			$this->_filters[$this->_path][$key] = $value;
		}
		
		$this->_save();
		
		return $this;
	}
	
	/**
	 * Get the value of a stored filter, or all filters
	 *
	 *     $page = $filters->get('page', 1);
	 *     
	 *     $as_array = $filters->get();
	 *
	 * @param   string  $key      filter to return
	 * @param   mixed   $default  default value if key is empty or doesn't exist
	 * @return  mixed   filter value(s)
	 */
	public function get($key = NULL, $default = NULL)
	{
		if ($key === NULL)
		{
			return $this->_filters[$this->_path];
		}
		
		return Arr::get($this->_filters[$this->_path], $key, $default);
	}
	
	/**
	 * Removes a filter entirely
	 *
	 * [!!] Note: This only affects the current page request
	 *
	 *     $filters->delete('page');
	 *     
	 *     $filters->delete(array('page', 'per_page', 'search'));
	 *
	 * @chainable
	 * @param   mixed  $keys  key or array of keys to delete
	 * @return  Filter
	 */
	public function delete($keys = NULL)
	{
		if ($keys === NULL)
		{
			$this->_filters[$this->_path] = array();
		}
		else
		{
			foreach ((array) $keys as $key)
			{
				unset($this->_filters[$this->_path][$key]);
			}
		}
		
		$this->_save();
		
		return $this;
	}
	
	/**
	 * Resets a key, or all keys, to their defaults
	 *
	 *     // Set the page back to its default (1)
	 *     $filters->reset('page');
	 *     
	 *     // Reset a group of filters
	 *     $filters->reset(array('page', 'per_page', 'search'));
	 *     
	 *     // Reset all the filters
	 *     $filters->reset();
	 *
	 * @chainable
	 * @param   mixed  $keys  key or array of keys to reset
	 * @return  Filter
	 */
	public function reset($keys = NULL)
	{
		if ($keys === NULL)
		{
			$keys = array_keys($this->_originals);
		}
		
		foreach ((array) $keys as $key)
		{
			$this->_filters[$this->_path][$key] = $this->_originals[$key];
		}
		
		$this->_save();
		
		return $this;
	}
	
	/**
	 * Check whether a filter has changed since the last page load
	 *
	 *      if ($filters->changed('search'))
	 *      {
	 *          $filters->reset('page');
	 *      }
	 *
	 * @param   string  $key  key to check for, or NULL to return array of changed keys
	 * @return  mixed   boolean or array depending on first param passed
	 */
	public function changed($key = NULL)
	{
		if ($key === NULL)
		{
			return $this->_changed[$this->_path];
		}
		
		return isset($this->_changed[$this->_path][$key]);
	}
	
	/**
	 * Sets a filter
	 *
	 *     $filters->page = 10;
	 *
	 * @param   string  $key    filter key
	 * @param   string  $value  filter value
	 * @return  void
	 */
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}
	
	/**
	 * Gets a filter value
	 *
	 *     $page = $filters->page
	 *
	 * @param   string  $key  filter key
	 * @return  mixed   filter value
	 */
	public function __get($key)
	{
		return $this->get($key);
	}
	
	/**
	 * Check if a filter is set
	 *
	 *     if (isset($filters->page)) ...
	 *
	 * @param   string  $key  filter key
	 * @return  boolean
	 */
	public function __isset($key)
	{
		return isset($this->_filters[$this->_path][$key]);
	}
	
	/**
	 * Unset/remove a filter
	 *
	 * [!!] Note: This only affects the current request
	 *
	 *     unset($filters->page);
	 *
	 * @param   string  $key  filter key
	 * @return  void
	 */
	public function __unset($key)
	{
		$this->delete($key);
	}
	
	/**
	 * Writes the filters to the session
	 *
	 * @return  Session
	 */
	protected function _save()
	{
		return Session::instance()->set(Filter::$session_key, $this->_filters);
	}

}