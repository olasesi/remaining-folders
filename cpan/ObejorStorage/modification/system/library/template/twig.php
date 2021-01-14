<?php
namespace Template;
final class Twig {
	private $twig;
	private $data = array();
	
	public function __construct() {
		// include and register Twig auto-loader
		include_once(DIR_SYSTEM . 'library/template/Twig/Autoloader.php');
		
		\Twig_Autoloader::register();
	}
	
	public function set($key, $value) {
		$this->data[$key] = $value;
	}
	
	public function render($template, $cache = false) {
$file1 = DIR_MODIFICATION . 'catalog/view/theme/' . $template . '.tpl';
		// specify where to look for templates
				
		$loader = new \Twig_Loader_Filesystem();
		
		if (defined('DIR_CATALOG') && is_dir(DIR_MODIFICATION . 'admin/view/template/')) {	
			$loader->addPath(DIR_MODIFICATION . 'admin/view/template/');
		} elseif (is_dir(DIR_MODIFICATION . 'catalog/view/theme/')) {
			$loader->addPath(DIR_MODIFICATION . 'catalog/view/theme/');
		}
		
		$loader->addPath(DIR_TEMPLATE);

		// initialize Twig environment
		$config = array('autoescape' => false);

		if ($cache) {
			$config['cache'] = DIR_CACHE;
		}

		$this->twig = new \Twig_Environment($loader, $config);

                $this->twig->addFunction('staticCall', new \Twig_Function_Function(function ($class, $function, $args = array()) {
                    if (class_exists($class) && method_exists($class, $function)) {
                        return call_user_func_array(array($class, $function), $args);
                    }

                    return null;
                }));
            
		
		try {
			// load template
			$template = $this->twig->loadTemplate($template . '.twig');
			
			return $template->render($this->data);
		} catch (Exception $e) {
			trigger_error('Error: Could not load template ' . $template . '!');
			exit();	
		}	
	}	
}
