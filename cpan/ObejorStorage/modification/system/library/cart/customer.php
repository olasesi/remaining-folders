<?php
namespace Cart;

			if( !function_exists('apache_request_headers') ) {
///
function apache_request_headers() {
  $arh = array();
  $rx_http = '/\AHTTP_/';
  foreach($_SERVER as $key => $val) {
    if( preg_match($rx_http, $key) ) {
      $arh_key = preg_replace($rx_http, '', $key);
      $rx_matches = array();
      // do some nasty string manipulations to restore the original letter case
      // this should work in most cases
      $rx_matches = explode('_', $arh_key);
      if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
        foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
        $arh_key = implode('-', $rx_matches);
      }
      $arh[$arh_key] = $val;
    }
  }
  return( $arh );
}
} 
class Customer {
	private $customer_id;
	private $firstname;
	private $lastname;
	private $customer_group_id;
	private $email;
	private $telephone;
	private $newsletter;
	private $address_id;

	public function __construct($registry) {
		$this->config = $registry->get('config');
		$this->db = $registry->get('db');
		$this->request = $registry->get('request');
		$this->session = $registry->get('session');

		if (isset($this->session->data['customer_id'])) {
			$customer_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE customer_id = '" . (int)$this->session->data['customer_id'] . "' AND status = '1'");

			if ($customer_query->num_rows) {
				$this->customer_id = $customer_query->row['customer_id'];
				$this->firstname = $customer_query->row['firstname'];
				$this->lastname = $customer_query->row['lastname'];
				$this->customer_group_id = $customer_query->row['customer_group_id'];
				$this->email = $customer_query->row['email'];
				$this->telephone = $customer_query->row['telephone'];
				$this->newsletter = $customer_query->row['newsletter'];
				$this->address_id = $customer_query->row['address_id'];

				$this->db->query("UPDATE " . DB_PREFIX . "customer SET language_id = '" . (int)$this->config->get('config_language_id') . "', ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "' WHERE customer_id = '" . (int)$this->customer_id . "'");

				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_ip WHERE customer_id = '" . (int)$this->session->data['customer_id'] . "' AND ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "'");

				if (!$query->num_rows) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "customer_ip SET customer_id = '" . (int)$this->session->data['customer_id'] . "', ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "', date_added = NOW()");
				}
			} else {
				$this->logout();
			}
		}
	}

  public function login($email, $password, $override = false) {
		if ($override) {
			$customer_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE LOWER(email) = '" . $this->db->escape(utf8_strtolower($email)) . "' AND status = '1'");
		} else {
			$customer_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE LOWER(email) = '" . $this->db->escape(utf8_strtolower($email)) . "' AND (password = SHA1(CONCAT(salt, SHA1(CONCAT(salt, SHA1('" . $this->db->escape($password) . "'))))) OR password = '" . $this->db->escape(md5($password)) . "') AND status = '1'");
		}

		if ($customer_query->num_rows) {
			$this->session->data['customer_id'] = $customer_query->row['customer_id'];

			$this->customer_id = $customer_query->row['customer_id'];
			$this->firstname = $customer_query->row['firstname'];
			$this->lastname = $customer_query->row['lastname'];
			$this->customer_group_id = $customer_query->row['customer_group_id'];
			$this->email = $customer_query->row['email'];
			$this->telephone = $customer_query->row['telephone'];
			$this->newsletter = $customer_query->row['newsletter'];
			$this->address_id = $customer_query->row['address_id'];
		
			$this->db->query("UPDATE " . DB_PREFIX . "customer SET language_id = '" . (int)$this->config->get('config_language_id') . "', ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "' WHERE customer_id = '" . (int)$this->customer_id . "'");

			return true;
		} else {
			return false;
		}
	}

	public function logout() {
		unset($this->session->data['customer_id']);

		$this->customer_id = '';
		$this->firstname = '';
		$this->lastname = '';
		$this->customer_group_id = '';
		$this->email = '';
		$this->telephone = '';
		$this->newsletter = '';
		$this->address_id = '';
	}

	public function isLogged() {
		return $this->customer_id;
	}

	public function getId() {
		return $this->customer_id;
	}

	public function getFirstName() {
		return $this->firstname;
	}

	public function getLastName() {
		return $this->lastname;
	}

	public function getGroupId() {
		return $this->customer_group_id;
	}

	public function getEmail() {
		return $this->email;
	}

	public function getTelephone() {
		return $this->telephone;
	}

	public function getNewsletter() {
		return $this->newsletter;
	}

	public function getAddressId() {
		return $this->address_id;
	}

	public function getBalance() {
		$query = $this->db->query("SELECT SUM(amount) AS total FROM " . DB_PREFIX . "customer_transaction WHERE customer_id = '" . (int)$this->customer_id . "'");

		return $query->row['total'];
	}


			/********* Check if customer is a seller also ***********/
			public function isSeller(){
			if ($this->config->get('module_purpletree_multivendor_status')) {
		$query = $this->db->query("SELECT id, store_status, multi_store_id, is_removed FROM " . DB_PREFIX . "purpletree_vendor_stores where seller_id IN (SELECT seller_id FROM " . DB_PREFIX . "purpletree_vendor_stores WHERE customer_id = '" . $this->customer_id . "') AND role LIKE 'ADMIN'");
		return $query->row;
	}
	}
	/**** Validate seller with licence id *******/
		public function isMobileApiCall() {
						header('Access-Control-Allow-Origin:*');
		header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
		header('Access-Control-Max-Age: 286400');
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Allow-Headers: xocmerchantid,XOCMERCHANTID,XOCSESSION,xocsession,purpletreemultivendor,Purpletreemultivendor,PURPLETREEMULTIVENDOR');
		if(NULL !== apache_request_headers()){
			foreach(apache_request_headers() as $key =>$value) {
				if($key == 'purpletreemultivendor' || $key == 'Purpletreemultivendor' || $key == 'PURPLETREEMULTIVENDOR') {
			$key = strtolower($key);
					if ($this->config->get('module_purpletree_multivendor_status')) {
						$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "api` WHERE `username` = '" . $key . "' AND `key` ='" . $value . "'");
						if($query->num_rows) {
							return true;
						}
					}
				}
			}
		}
		return false;
	}
	public function validateSeller($skipcondition = null) {
		if($_SERVER['HTTP_HOST'] == 'localhost') {
			$domain = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
		} else {
			$domain = 'http://'.$_SERVER['HTTP_HOST'];
		}
		
		$ip_a = $_SERVER['HTTP_HOST'];
		if(isset($skipcondition)){
		      $domain1 = md5($domain);
			$text = $this->config->get('module_purpletree_multivendor_encypt_text');
			$data_live = $this->config->get('module_purpletree_multivendor_live_validate_text');
			if(($domain1 == $text) && $data_live == 1) {
				return true;
			} else {
				$module	= 'purpletree_multivendor_oc';
				$valuee = $this->config->get('module_purpletree_multivendor_process_data');
				$ip_address = $this->request->server['REMOTE_ADDR'];
				$url = "https://www.process.purpletreesoftware.com/occheckdata.php";
				$handle=curl_init($url);
				curl_setopt($handle, CURLOPT_VERBOSE, true);
				curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($handle, CURLOPT_POSTFIELDS,
							"process_data=$valuee&domain_name=$domain&ip_address=$ip_address&module_name=$module");
				$result = curl_exec($handle);
				$result1 = json_decode($result);
				if(curl_error($handle))
				{
					echo 'error';
					die;
				}
				if ($result1->status == 'success') {
					$query = $this->db->query("UPDATE " . DB_PREFIX . "setting SET value = '1' WHERE " . DB_PREFIX . "setting.key = 'module_purpletree_multivendor_live_validate_text'");
					
				$query1 = $this->db->query("UPDATE " . DB_PREFIX . "setting SET value = '" .$domain1. "' WHERE " . DB_PREFIX . "setting.key = 'module_purpletree_multivendor_encypt_text'");
				
				return true;
				 } else {
					 return false;
				} 
			}
		}else{
		     //$ip_a = '103.111.47.26:123';
		   	$ip_a = str_replace(array(':', '.'), '', $ip_a);
				if (is_numeric($ip_a)){
					return true;
			}
		
		if (preg_match('(localhost|demo|test)',$domain)) {
			return true;
		} else {
			$domain1 = md5($domain);
			$text = $this->config->get('module_purpletree_multivendor_encypt_text');
			$data_live = $this->config->get('module_purpletree_multivendor_live_validate_text');
			if(($domain1 == $text) && $data_live == 1) {
				return true;
			} else {
				$module	= 'purpletree_multivendor_oc';
				$valuee = $this->config->get('module_purpletree_multivendor_process_data');
				$ip_address = $this->request->server['REMOTE_ADDR'];
				$url = "https://www.process.purpletreesoftware.com/occheckdata.php";
				$handle=curl_init($url);
				curl_setopt($handle, CURLOPT_VERBOSE, true);
				curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($handle, CURLOPT_POSTFIELDS,
							"process_data=$valuee&domain_name=$domain&ip_address=$ip_address&module_name=$module");
				$result = curl_exec($handle);
				$result1 = json_decode($result);
				if(curl_error($handle))
				{
					echo 'error';
					die;
				}
				if ($result1->status == 'success') {
					$query = $this->db->query("UPDATE " . DB_PREFIX . "setting SET value = '1' WHERE " . DB_PREFIX . "setting.key = 'module_purpletree_multivendor_live_validate_text'");
					
				$query1 = $this->db->query("UPDATE " . DB_PREFIX . "setting SET value = '" .$domain1. "' WHERE " . DB_PREFIX . "setting.key = 'module_purpletree_multivendor_encypt_text'");
				
				return true;
				 } else {
					 return false;
				} 
			}
		}	
		}
		
	}
	
	public function getRewardPoints() {
		$query = $this->db->query("SELECT SUM(points) AS total FROM " . DB_PREFIX . "customer_reward WHERE customer_id = '" . (int)$this->customer_id . "'");

		return $query->row['total'];
	}
}
