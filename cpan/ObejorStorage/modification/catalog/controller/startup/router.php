<?php
class ControllerStartupRouter extends Controller {
	public function index() {
		// Route
		if (isset($this->request->get['route']) && $this->request->get['route'] != 'startup/router') {
			$route = $this->request->get['route'];
		} else {
			$route = $this->config->get('action_default');
		}
		
		// Sanitize the call
		$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);
		

                // START: NitroPack.io (https://nitropack.io)
                if (is_file(DIR_CONFIG . 'nitropackio/compatibility.php')) {
                    $this->config->load('nitropackio/compatibility');

                    $nitro_config = $this->config->get('nitropackio');

                    $nitro_action = new Action($nitro_config['route']['module']['nitropack'] . '/postSeoUrl');

                    $nitro_action->execute($this->registry);
                }
                // END: NitroPack.io (https://nitropack.io)
            
		// Trigger the pre events
		$result = $this->event->trigger('controller/' . $route . '/before', array(&$route, &$data));
		
		if (!is_null($result)) {
			return $result;
		}
		
		// We dont want to use the loader class as it would make an controller callable.
		$action = new Action($route);
		
		// Any output needs to be another Action object.
		$output = $action->execute($this->registry); 
		
		// Trigger the post events
		$result = $this->event->trigger('controller/' . $route . '/after', array(&$route, &$data, &$output));
		
		if (!is_null($result)) {
			return $result;
		}
		
		return $output;
	}
}
