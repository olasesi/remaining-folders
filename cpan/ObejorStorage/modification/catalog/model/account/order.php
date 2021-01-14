<?php
class ModelAccountOrder extends Model {
	public function getOrder($order_id) {
		$order_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND customer_id != '0' AND order_status_id > '0'");

		if ($order_query->num_rows) {
			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['payment_country_id'] . "'");

			if ($country_query->num_rows) {
				$payment_iso_code_2 = $country_query->row['iso_code_2'];
				$payment_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$payment_iso_code_2 = '';
				$payment_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$payment_zone_code = $zone_query->row['code'];
			} else {
				$payment_zone_code = '';
			}

			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

			if ($country_query->num_rows) {
				$shipping_iso_code_2 = $country_query->row['iso_code_2'];
				$shipping_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$shipping_iso_code_2 = '';
				$shipping_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['shipping_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$shipping_zone_code = $zone_query->row['code'];
			} else {
				$shipping_zone_code = '';
			}

			return array(
				'order_id'                => $order_query->row['order_id'],
				'invoice_no'              => $order_query->row['invoice_no'],
				'invoice_prefix'          => $order_query->row['invoice_prefix'],
				'store_id'                => $order_query->row['store_id'],
				'store_name'              => $order_query->row['store_name'],
				'store_url'               => $order_query->row['store_url'],
				'customer_id'             => $order_query->row['customer_id'],
				'firstname'               => $order_query->row['firstname'],
				'lastname'                => $order_query->row['lastname'],
				'telephone'               => $order_query->row['telephone'],
				'email'                   => $order_query->row['email'],
				'payment_firstname'       => $order_query->row['payment_firstname'],
				'payment_lastname'        => $order_query->row['payment_lastname'],
				'payment_company'         => $order_query->row['payment_company'],
				'payment_address_1'       => $order_query->row['payment_address_1'],
				'payment_address_2'       => $order_query->row['payment_address_2'],
				'payment_postcode'        => $order_query->row['payment_postcode'],
				'payment_city'            => $order_query->row['payment_city'],
				'payment_zone_id'         => $order_query->row['payment_zone_id'],
				'payment_zone'            => $order_query->row['payment_zone'],
				'payment_zone_code'       => $payment_zone_code,
				'payment_country_id'      => $order_query->row['payment_country_id'],
				'payment_country'         => $order_query->row['payment_country'],
				'payment_iso_code_2'      => $payment_iso_code_2,
				'payment_iso_code_3'      => $payment_iso_code_3,
				'payment_address_format'  => $order_query->row['payment_address_format'],
				'payment_method'          => $order_query->row['payment_method'],
				'shipping_firstname'      => $order_query->row['shipping_firstname'],
				'shipping_lastname'       => $order_query->row['shipping_lastname'],
				'shipping_company'        => $order_query->row['shipping_company'],
				'shipping_address_1'      => $order_query->row['shipping_address_1'],
				'shipping_address_2'      => $order_query->row['shipping_address_2'],
				'shipping_postcode'       => $order_query->row['shipping_postcode'],
				'shipping_city'           => $order_query->row['shipping_city'],
				'shipping_zone_id'        => $order_query->row['shipping_zone_id'],
				'shipping_zone'           => $order_query->row['shipping_zone'],
				'shipping_zone_code'      => $shipping_zone_code,
				'shipping_country_id'     => $order_query->row['shipping_country_id'],
				'shipping_country'        => $order_query->row['shipping_country'],
				'shipping_iso_code_2'     => $shipping_iso_code_2,
				'shipping_iso_code_3'     => $shipping_iso_code_3,
				'shipping_address_format' => $order_query->row['shipping_address_format'],
				'shipping_method'         => $order_query->row['shipping_method'],
				'comment'                 => $order_query->row['comment'],
				'total'                   => $order_query->row['total'],
				'order_status_id'         => $order_query->row['order_status_id'],
				'language_id'             => $order_query->row['language_id'],
				'currency_id'             => $order_query->row['currency_id'],
				'currency_code'           => $order_query->row['currency_code'],
				'currency_value'          => $order_query->row['currency_value'],
				'date_modified'           => $order_query->row['date_modified'],
				'date_added'              => $order_query->row['date_added'],
				'ip'                      => $order_query->row['ip']
			);
		} else {
			return false;
		}
	}

	public function getOrders($start = 0, $limit = 20) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 1;
		}

		$query = $this->db->query("SELECT o.order_id, o.firstname, o.lastname, os.name as status, o.date_added, o.total, o.currency_code, o.currency_value FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id) WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND o.order_status_id > '0' AND o.store_id = '" . (int)$this->config->get('config_store_id') . "' AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.order_id DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	public function getOrderProduct($order_id, $order_product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");

		return $query->row;
	}

	public function getOrderProducts($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

		return $query->rows;
	}

	public function getOrderOptions($order_id, $order_product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");

		return $query->rows;
	}

	public function getOrderVouchers($order_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_voucher` WHERE order_id = '" . (int)$order_id . "'");

		return $query->rows;
	}

	public function getOrderTotals($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order");

		return $query->rows;
	}


						public function getSellerOrderHistories($order_id) {
		$query = $this->db->query("SELECT pvh.order_id, pvh.seller_id, pvh.created_at, os.name AS status, pvh.comment, pvh.notify FROM " . DB_PREFIX . "purpletree_vendor_orders_history pvh LEFT JOIN " . DB_PREFIX . "order_status os ON pvh.order_status_id = os.order_status_id WHERE pvh.order_id = '" . (int)$order_id . "' AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY pvh.id DESC");
		return $query->rows;
	}
	
	public function getUniqueSeller($order_id){
		$query = $this->db->query("SELECT DISTINCT(seller_id) FROM " . DB_PREFIX . "purpletree_vendor_orders_history WHERE order_id='".(int)$order_id."' order by id desc");
		$result = $query->rows;
		$aa = array();
		$aa = $this->getSellerOrderStatus($result, $order_id);
		$dd = array();
		if(!empty($aa)) {
			foreach($aa as $bb) {
			foreach($bb['product'] as $cc) {
				$dd[] = $cc['product_id'];
			}
		}
		}
		
		$query11 = $this->db->query("SELECT '0' AS seller_id,op.product_id,op.name as product_name, os.name AS status FROM " . DB_PREFIX . "order_product op LEFT JOIN " . DB_PREFIX . "order o ON o.order_id = op.order_id LEFT JOIN " . DB_PREFIX . "order_status os ON o.order_status_id = os.order_status_id WHERE op.order_id='" . $order_id . "' GROUP BY op.order_product_id");
		$resul11t = $query11->rows;
			$fdfd = array();
		foreach($resul11t as $ree) {
			if(!empty($aa)) {
				if(!in_array($ree['product_id'],$dd)) {
		$fdfd[$ree['product_id']]['product_name'] = $ree['product_name'];
					foreach($aa as $gg) {
						$aa[0] = array("order_status" =>  $ree['status'],
										"product" => $fdfd
						);
					}
				}
			} else {
		$fdfd[$ree['product_id']]['product_name'] = $ree['product_name'];
				$aa[0] = array("order_status" =>  $ree['status'],
								"seller_id" => '0',
										"product" => $fdfd
						);
			}
		}
		return $aa;
	}
	
	public function getSellerOrderStatus($result, $order_id){
		$order_status = array();
		foreach($result as $result){
			$query = $this->db->query("SELECT pvh.order_id, pvh.seller_id, pvh.created_at, os.name AS status FROM " . DB_PREFIX . "purpletree_vendor_orders_history pvh LEFT JOIN " . DB_PREFIX . "order_status os ON pvh.order_status_id = os.order_status_id WHERE pvh.seller_id='".(int)$result['seller_id']."' AND pvh.order_id='".(int)$order_id."' AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY pvh.id DESC limit 1");
			$result1 = $query->row;
			$order_status[$result['seller_id']] = array(
				'order_status' => $result1['status'],
				'seller_id' => $result['seller_id'],
				'product' => $this->getSellerOrderProducts($order_id, $result['seller_id'])
			);
		}
	
		return $order_status;
	}
	
	public function getSellerOrderProducts($order_id, $seller_id){
		$query = $this->db->query("SELECT pvo.product_id,(SELECT op.name FROM " . DB_PREFIX . "order_product op where op.product_id = pvo.product_id AND op.order_id = pvo.order_id) as product_name FROM " . DB_PREFIX . "purpletree_vendor_orders pvo WHERE pvo.seller_id='".(int)$seller_id."' AND pvo.order_id = '".(int)$order_id."'");
    
		return $query->rows;
	}
		public function getStoreName($seller_id) {
		$query = $this->db->query("SELECT store_name FROM " . DB_PREFIX . "purpletree_vendor_stores WHERE seller_id = " . (int)$seller_id );

		if ($query->num_rows > 0) {
			return $query->row['store_name'];
		} else {
			return null;	
		}
		
	}	
			
	public function getOrderHistories($order_id) {
		$query = $this->db->query("SELECT date_added, os.name AS status, oh.comment, oh.notify FROM " . DB_PREFIX . "order_history oh LEFT JOIN " . DB_PREFIX . "order_status os ON oh.order_status_id = os.order_status_id WHERE oh.order_id = '" . (int)$order_id . "' AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY oh.date_added");

		return $query->rows;
	}

	public function getTotalOrders() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` o WHERE customer_id = '" . (int)$this->customer->getId() . "' AND o.order_status_id > '0' AND o.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		return $query->row['total'];
	}

	public function getTotalOrderProductsByOrderId($order_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

		return $query->row['total'];
	}

	public function getTotalOrderVouchersByOrderId($order_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order_voucher` WHERE order_id = '" . (int)$order_id . "'");

		return $query->row['total'];
	}
}