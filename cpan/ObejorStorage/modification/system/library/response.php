<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Response class
*/
class Response {
	private $headers = array();
	private $level = 0;
	private $output;

	/**
	 * Constructor
	 *
	 * @param	string	$header
	 *
 	*/
	public function addHeader($header) {
		$this->headers[] = $header;
	}
	
	/**
	 * 
	 *
	 * @param	string	$url
	 * @param	int		$status
	 *
 	*/
	public function redirect($url, $status = 302) {
		header('Location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $url), true, $status);
		exit();
	}
	
	/**
	 * 
	 *
	 * @param	int		$level
 	*/
	public function setCompression($level) {
		$this->level = $level;
	}
	
	/**
	 * 
	 *
	 * @return	array
 	*/
	public function getOutput() {
		return $this->output;
	}
	
	/**
	 * 
	 *
	 * @param	string	$output
 	*/	
	public function setOutput($output) {
		$this->output = $output;
	}
	
	/**
	 * 
	 *
	 * @param	string	$data
	 * @param	int		$level
	 * 
	 * @return	string
 	*/
	private function compress($data, $level = 0) {
		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)) {
			$encoding = 'gzip';
		}

		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false)) {
			$encoding = 'x-gzip';
		}

		if (!isset($encoding) || ($level < -1 || $level > 9)) {
			return $data;
		}

		if (!extension_loaded('zlib') || ini_get('zlib.output_compression')) {
			return $data;
		}

		if (headers_sent()) {
			return $data;
		}

		if (connection_status()) {
			return $data;
		}

		$this->addHeader('Content-Encoding: ' . $encoding);

		return gzencode($data, (int)$level);
	}
	
	/**
	 * 
 	*/
	public function output() {
		if ($this->output) {

                if (defined('JOURNAL_INSTALLED')) {
                    global $registry;
                    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                    $is_get = !empty($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'get';
                    $ignored_routes = array(
                        'journal2/assets/css',
                        'journal2/assets/js',
                        'feed/google_sitemap',
                        'feed/google_product_feed'
                    );
                    $request = $registry->get('request');
                    $current_route = isset($request->get['route']) ? $request->get['route'] : null;
                    $ignored_route = $current_route !== null && in_array($current_route, $ignored_routes);
                    $journal2 = $registry->get('journal2');
                    if (!$ignored_route && !$is_ajax && $is_get && !$journal2->settings->get('config_system_settings.developer_mode') && $journal2->settings->get('config_system_settings.minify_html')) {
                        try {
							$this->output = Minify_HTML::minify($this->output, array(
                            'xhtml' => false,
                            'jsMinifier' => 'j2_js_minify'
                        ));
						} catch (Exception $e) { }

                        if (Journal2Cache::$page_cache_file) {
                            file_put_contents(Journal2Cache::$page_cache_file, $this->output);
                        }
                    }
                }
            
			$output = $this->level ? $this->compress($this->output, $this->level) : $this->output;
			
			if (!headers_sent()) {
				foreach ($this->headers as $header) {
					header($header, true);
				}
			}
			
			echo $output;
		}
	}
}
