<?php
final class Front {
	protected $registry;
	protected $pre_action = array();
	protected $error;

	/* Journal2 Theme modification */
	public static $IS_INSTALLER = false;
	public static $IS_JOURNAL   = false;
	public static $IS_OC2       = false;
	public static $IS_ADMIN     = false;
	/* End of Journal2 Theme modification */

	public function __construct($registry) {
		$this->registry = $registry;
	}

	public function addPreAction($pre_action) {
		$this->pre_action[] = $pre_action;
	}

	public function dispatch($action, $error) {
		$this->error = $error;

		foreach ($this->pre_action as $pre_action) {
			$result = $this->execute($pre_action);

			if ($result) {
				$action = $result;

				break;
			}
		}

		/* Journal2 Theme modification */
		if (defined('HTTP_OPENCART')) {
			self::$IS_INSTALLER = true;
		} else if (defined('VERSION')) {
			global $config;
			self::$IS_OC2 = version_compare(VERSION, '2', '>=');
			if (file_exists(DIR_APPLICATION . 'model/journal2/journal2.php')) {
				require_once DIR_APPLICATION . 'model/journal2/journal2.php';
			}
			self::$IS_ADMIN = defined('JOURNAL_IS_ADMIN');
			self::$IS_JOURNAL = $config->get('config_template') === 'journal2' || $config->get('theme_default_directory') === 'journal2';
		}

		require_once(DIR_SYSTEM . 'journal2/startup.php');
		/* End of Journal2 Theme modification */


                // START: NitroPack.io (https://nitropack.io)
                if (basename(DIR_APPLICATION) == "catalog" && is_file(DIR_CONFIG . 'nitropackio/compatibility.php')) {
                    $this->registry->get('config')->load('nitropackio/compatibility');

                    $nitro_config = $this->registry->get('config')->get('nitropackio');

                    $nitro_action = new \Action($nitro_config['route']['module']['nitropack'] . '/postSeoUrl');

                    $nitro_action->execute($this->registry);
                }
                // END: NitroPack.io (https://nitropack.io)
            
		while ($action) {
			$action = $this->execute($action);
		}
	}

	private function execute($action) {
	    // oc22 fix
        if (defined('VERSION') && version_compare(VERSION, '2.2', '>=')) {
            $result = $action->execute($this->registry);

            if ($result instanceof Action) {
                return $result;
            }

            if ($result instanceof Exception) {
                $action = $this->error;

                $this->error = null;

                return $action;
            }

            return;
        }

		if (method_exists($action, 'getFile')) {
			if (file_exists($action->getFile())) {
				require_once($action->getFile());

				$class = $action->getClass();

				$controller = new $class($this->registry);

				if (is_callable(array($controller, $action->getMethod()))) {
					$action = call_user_func_array(array($controller, $action->getMethod()), $action->getArgs());
				} else {
					$action = $this->error;

					$this->error = '';
				}
			} else {
				$action = $this->error;

				$this->error = '';
			}
		} else {
			$result = $action->execute($this->registry);

			if (is_object($result)) {
				$action = $result;
			} elseif ($result === false) {
				$action = $this->error;

				$this->error = '';
			} else {
				$action = false;
			}
		}

		return $action;
	}
}
?>