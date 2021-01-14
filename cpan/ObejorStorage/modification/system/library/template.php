<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Template class
*/
class Template {
	private $adaptor;
	
	/**
	 * Constructor
	 *
	 * @param	string	$adaptor
	 *
 	*/
  	public function __construct($adaptor) {
	    $class = 'Template\\' . $adaptor;

		if (class_exists($class)) {
			$this->adaptor = new $class();

                global $journal2, $config, $registry;
			    $this->adaptor->journal2 = $journal2;
			    $this->adaptor->url = $registry->get('url');
			    $this->adaptor->config = $config;

			    if (version_compare(VERSION, '3', '>=')) {
			        $this->adaptor->set('journal2', $journal2);
			        $this->adaptor->set('url', $registry->get('url'));
			        $this->adaptor->set('config', $config);
			    }
            
		} else {
			throw new \Exception('Error: Could not load template adaptor ' . $adaptor . '!');
		}
	}
	
	/**
	 * 
	 *
	 * @param	string	$key
	 * @param	mixed	$value
 	*/	
	public function set($key, $value) {
		$this->adaptor->set($key, $value);
	}
	
	/**
	 * 
	 *
	 * @param	string	$template
	 * @param	bool	$cache
	 *
	 * @return	string
 	*/	
	public function render($template, $cache = false) {
		return $this->adaptor->render($template, $cache);
	}
}
