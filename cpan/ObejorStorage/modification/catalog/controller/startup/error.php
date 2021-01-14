<?php
class ControllerStartupError extends Controller {
	public function index() {
		$this->registry->set('log', new Log($this->config->get('config_error_filename')));
		
		set_error_handler(array($this, 'handler'));	
	}
	
	public function handler($code, $message, $file, $line) {
		// error suppressed with @
		if (error_reporting() === 0) {
			return false;
		}
	
		switch ($code) {
			case E_NOTICE:
			case E_USER_NOTICE:
				$error = 'Notice';
				break;
			case E_WARNING:
			case E_USER_WARNING:
				$error = 'Warning';
				break;
			case E_ERROR:
			case E_USER_ERROR:
				$error = 'Fatal Error';
				break;
			default:
				$error = 'Unknown';
				break;
		}
	
		if ($this->config->get('config_error_display')) {

                // START: NitroPack.io (https://nitropack.io)
                if (is_file(DIR_SYSTEM . 'library/nitropackio/hook/error_handler.php')) {
                    require_once(DIR_SYSTEM . 'library/nitropackio/hook/error_handler.php');
                }
                // END: NitroPack.io (https://nitropack.io)
            
			echo '<b>' . $error . '</b>: ' . $message . ' in <b>' . $file . '</b> on line <b>' . $line . '</b>';
		}
	
		if ($this->config->get('config_error_log')) {
			$this->log->write('PHP ' . $error . ':  ' . $message . ' in ' . $file . ' on line ' . $line);
		}
	
		return true;
	} 
} 