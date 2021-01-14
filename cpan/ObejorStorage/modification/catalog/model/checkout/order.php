<?php
class ModelCheckoutOrder extends Model {

			public function getProductCategory($productid){
		
		$sql = "SELECT category_id FROM " . DB_PREFIX . "product_to_category where 	product_id = '".(int)$productid."'"; 
		
		  $query = $this->db->query($sql);
		  
		  return $query->rows;  
		}
		public function getTemplateId($product_id) {
			$query = $this->db->query("SELECT pvt.id as id FROM " . DB_PREFIX . "purpletree_vendor_template pvt  WHERE pvt.product_id ='". (int)$product_id ."'");
			 if($query->num_rows){		
				return $query->row['id'];
			 }else{
				 return null;
			 }
		
	}
			public function getVendorOrderProducts($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "purpletree_vendor_orders WHERE order_id = '" . (int)$order_id . "'");
		
		return $query->rows;
	}
			public function getMatrixShippingCharge($address,$totalweight,$seller_id){

			 $sql = "SELECT * FROM " . DB_PREFIX . "purpletree_vendor_shipping pvss WHERE pvss.seller_id =".$seller_id." AND pvss.shipping_country = '".$address['shipping_country_id']."'";
			 if(!is_numeric($address['shipping_postcode'])) {
			 $sql .= " AND pvss.zipcode_from = '".$address['shipping_postcode']."' AND pvss.zipcode_to = '".$address['shipping_postcode']."'";
			 }
			 $shippingqery = $this->db->query($sql)->rows;
			if(!empty($shippingqery)) {
				$shipprice = array();
				foreach($shippingqery as $shipp) {
					if($totalweight >= $shipp['weight_from'] && $totalweight <= $shipp['weight_to']) {
						 if(is_numeric($address['shipping_postcode'])) {
							 if($address['shipping_postcode'] >= $shipp['zipcode_from'] && $address['shipping_postcode'] <= $shipp['zipcode_to']) {
								$shipprice[] = $shipp['shipping_price'];
							 }
						 } else {
							$shipprice[] = $shipp['shipping_price'];
						 }
					}
				}

				if(!empty($shipprice)) {
					return max($shipprice);
				}
					
			}
		}
	public function getoptionsweight($product){
		$productsql = "SELECT weight,weight_class_id FROM ".DB_PREFIX."product WHERE product_id =".$product['product_id']."";
				$productquery = $this->db->query($productsql)->row;
				$totweight = $productquery['weight'];
			if(!empty($product['option'])) {
				foreach($product['option'] as $productsoptin) {
					//echo "c";
					$productsql1 = "SELECT pov.weight,pov.weight_prefix,p.weight_class_id FROM ".DB_PREFIX."product p JOIN ". DB_PREFIX ."product_option_value pov ON(pov.product_id = p.product_id) WHERE pov.product_option_value_id = '".$productsoptin['product_option_value_id']."' AND pov.product_option_id = '".$productsoptin['product_option_id']."' AND pov.product_id = '".$product['product_id']."' AND pov.option_id = '".$productsoptin['option_id']."' AND pov.option_value_id = '".$productsoptin['option_value_id']."'";
					$productquery1 = $this->db->query($productsql1)->row;
					if(!empty($productquery1)){
						if ($productquery1['weight_prefix'] == '+') {
							$totweight += $totweight+($productquery1['weight'] * $product['quantity']);	
						} elseif ($product_option_value_info['weight_prefix'] == '-') {
							$totweight -= $totweight-($productquery1['weight'] * $product['quantity']);
						}
					}
				}
			} else {
					$totweight = $totweight * $product['quantity'];
			}
		$totalweight = $this->weight->convert($totweight, $productquery['weight_class_id'], $this->config->get('config_weight_class_id'));
		return $totalweight;
	}			
	public function getsellershipping($seller_shipping,$product,$address) {

		if($seller_shipping['seller_id'] == '0'){
	
				$shipping_purpletree_shipping_type = (null !== $this->config->get('shipping_purpletree_shipping_type'))? $this->config->get('shipping_purpletree_shipping_type') : 'pts_flat_rate_shipping';
			$shipping_purpletree_shipping_order_type = (null !== $this->config->get('shipping_purpletree_shipping_order_type'))? $this->config->get('shipping_purpletree_shipping_order_type') : 'pts_product_wise';
			$shipping_purpletree_shipping_charge = (null !== $this->config->get('shipping_purpletree_shipping_charge'))? $this->config->get('shipping_purpletree_shipping_charge') : '0';

		} else {
		$shipping_purpletree_shipping_type 			= $seller_shipping['store_shipping_type'] != '' ? $seller_shipping['store_shipping_type'] : 'pts_flat_rate_shipping';
		$shipping_purpletree_shipping_order_type 	= $seller_shipping['store_shipping_order_type'] != '' ? $seller_shipping['store_shipping_order_type'] : 'pts_product_wise';
		$shipping_purpletree_shipping_charge 		= $seller_shipping['store_shipping_charge'] != '' ? $seller_shipping['store_shipping_charge'] : '0';
		}
		$total = 0;
		$totalweight = $this->getoptionsweight($product);
		$getMatrixShippingCharge = $this->getMatrixShippingCharge($address,$totalweight,$seller_shipping['seller_id']);
		// if Matric shipping
		
		if($shipping_purpletree_shipping_type == 'pts_matrix_shipping'){
			if($address['shipping_postcode'] != '') {
				if($shipping_purpletree_shipping_order_type == 'pts_product_wise'){
					if($getMatrixShippingCharge) {
						$total = $getMatrixShippingCharge;
					}
				} 
			}					
		} // if Matric shipping
		// if Flexible shipping
		elseif($shipping_purpletree_shipping_type  == 'pts_flexible_shipping'){
			if($address['shipping_postcode'] != '') {
				if($shipping_purpletree_shipping_order_type == 'pts_product_wise'){
					if($getMatrixShippingCharge) {
						 $total = $getMatrixShippingCharge;
					} else {
						 $total = $shipping_purpletree_shipping_charge;
					}
				}
			} else {
				if($shipping_purpletree_shipping_order_type == 'pts_product_wise'){
					 $total = $shipping_purpletree_shipping_charge;
				}
			}
		} // if Flexible shipping
		// if Flat Rate shipping
			else {
			if($shipping_purpletree_shipping_order_type == 'pts_product_wise'){
				 $total = $shipping_purpletree_shipping_charge;
			}
		}
		
		// if Flat Rate shipping
		return $total;	
	}
	public function getsellershipping1($seller_shipping,$product,$address) {
		if($seller_shipping['seller_id'] == '0'){
			$shipping_purpletree_shipping_type = (null !== $this->config->get('shipping_purpletree_shipping_type'))? $this->config->get('shipping_purpletree_shipping_type') : 'pts_flat_rate_shipping';
			$shipping_purpletree_shipping_order_type = (null !== $this->config->get('shipping_purpletree_shipping_order_type'))? $this->config->get('shipping_purpletree_shipping_order_type') : 'pts_product_wise';
			$shipping_purpletree_shipping_charge = (null !== $this->config->get('shipping_purpletree_shipping_charge'))? $this->config->get('shipping_purpletree_shipping_charge') : '0';

		} else {
		$shipping_purpletree_shipping_type 			= $seller_shipping['store_shipping_type'] != '' ? $seller_shipping['store_shipping_type'] : 'pts_flat_rate_shipping';
		$shipping_purpletree_shipping_order_type 	= $seller_shipping['store_shipping_order_type'] != '' ? $seller_shipping['store_shipping_order_type'] : 'pts_product_wise';
		$shipping_purpletree_shipping_charge 		= $seller_shipping['store_shipping_charge'] != '' ? $seller_shipping['store_shipping_charge'] : '0';
		}
		$weightt = 0;
		// if Matric shipping
		if($shipping_purpletree_shipping_type == 'pts_matrix_shipping'){
			if($address['shipping_postcode'] != '') {
				if($shipping_purpletree_shipping_order_type == 'pts_order_wise'){
					 $weightt = $this->getoptionsweight($product);;
				}
			}					
		}// if Matric shipping
		// if Flexible shipping
		elseif($shipping_purpletree_shipping_type  == 'pts_flexible_shipping'){
			if($address['shipping_postcode'] != '') {
				if($shipping_purpletree_shipping_order_type == 'pts_order_wise'){
					  $weightt = $this->getoptionsweight($product);;
				}
			} else {
				if($shipping_purpletree_shipping_order_type == 'pts_order_wise'){
				 $weightt = $this->getoptionsweight($product);;
				}
			}
		} // if Flexible shipping
		// if Flat Rate shipping
			else {
			if($shipping_purpletree_shipping_order_type == 'pts_order_wise'){
				 
				  $weightt = $this->getoptionsweight($product);
			}
		}
		
		// if Flat Rate shipping
		return $weightt;	
	}
	
	public function addOrder($data) {

			$total = 0;	
			$seller_sub_total = array();
			$seller_final_total = array();
			$seller_tax_data = array();
			$seller_total_tax = array();
			$tax_data = array();
			$seller = array();
			$seller_shipping = array();
			
		$this->db->query("INSERT INTO `" . DB_PREFIX . "order` SET invoice_prefix = '" . $this->db->escape($data['invoice_prefix']) . "', store_id = '" . (int)$data['store_id'] . "', store_name = '" . $this->db->escape($data['store_name']) . "', store_url = '" . $this->db->escape($data['store_url']) . "', customer_id = '" . (int)$data['customer_id'] . "', customer_group_id = '" . (int)$data['customer_group_id'] . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', custom_field = '" . $this->db->escape(isset($data['custom_field']) ? json_encode($data['custom_field']) : '') . "', payment_firstname = '" . $this->db->escape($data['payment_firstname']) . "', payment_lastname = '" . $this->db->escape($data['payment_lastname']) . "', payment_company = '" . $this->db->escape($data['payment_company']) . "', payment_address_1 = '" . $this->db->escape($data['payment_address_1']) . "', payment_address_2 = '" . $this->db->escape($data['payment_address_2']) . "', payment_city = '" . $this->db->escape($data['payment_city']) . "', payment_postcode = '" . $this->db->escape($data['payment_postcode']) . "', payment_country = '" . $this->db->escape($data['payment_country']) . "', payment_country_id = '" . (int)$data['payment_country_id'] . "', payment_zone = '" . $this->db->escape($data['payment_zone']) . "', payment_zone_id = '" . (int)$data['payment_zone_id'] . "', payment_address_format = '" . $this->db->escape($data['payment_address_format']) . "', payment_custom_field = '" . $this->db->escape(isset($data['payment_custom_field']) ? json_encode($data['payment_custom_field']) : '') . "', payment_method = '" . $this->db->escape($data['payment_method']) . "', payment_code = '" . $this->db->escape($data['payment_code']) . "', shipping_firstname = '" . $this->db->escape($data['shipping_firstname']) . "', shipping_lastname = '" . $this->db->escape($data['shipping_lastname']) . "', shipping_company = '" . $this->db->escape($data['shipping_company']) . "', shipping_address_1 = '" . $this->db->escape($data['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape($data['shipping_address_2']) . "', shipping_city = '" . $this->db->escape($data['shipping_city']) . "', shipping_postcode = '" . $this->db->escape($data['shipping_postcode']) . "', shipping_country = '" . $this->db->escape($data['shipping_country']) . "', shipping_country_id = '" . (int)$data['shipping_country_id'] . "', shipping_zone = '" . $this->db->escape($data['shipping_zone']) . "', shipping_zone_id = '" . (int)$data['shipping_zone_id'] . "', shipping_address_format = '" . $this->db->escape($data['shipping_address_format']) . "', shipping_custom_field = '" . $this->db->escape(isset($data['shipping_custom_field']) ? json_encode($data['shipping_custom_field']) : '') . "', shipping_method = '" . $this->db->escape($data['shipping_method']) . "', shipping_code = '" . $this->db->escape($data['shipping_code']) . "', comment = '" . $this->db->escape($data['comment']) . "', total = '" . (float)$data['total'] . "', affiliate_id = '" . (int)$data['affiliate_id'] . "', commission = '" . (float)$data['commission'] . "', marketing_id = '" . (int)$data['marketing_id'] . "', tracking = '" . $this->db->escape($data['tracking']) . "', language_id = '" . (int)$data['language_id'] . "', currency_id = '" . (int)$data['currency_id'] . "', currency_code = '" . $this->db->escape($data['currency_code']) . "', currency_value = '" . (float)$data['currency_value'] . "', ip = '" . $this->db->escape($data['ip']) . "', forwarded_ip = '" .  $this->db->escape($data['forwarded_ip']) . "', user_agent = '" . $this->db->escape($data['user_agent']) . "', accept_language = '" . $this->db->escape($data['accept_language']) . "', date_added = NOW(), date_modified = NOW()");

		$order_id = $this->db->getLastId();

		// Products
		if (isset($data['products'])) {

			$store_shipping_type = array();
			
			foreach ($data['products'] as $product) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_product SET order_id = '" . (int)$order_id . "', product_id = '" . (int)$product['product_id'] . "', name = '" . $this->db->escape($product['name']) . "', model = '" . $this->db->escape($product['model']) . "', quantity = '" . (int)$product['quantity'] . "', price = '" . (float)$product['price'] . "', total = '" . (float)$product['total'] . "', tax = '" . (float)$product['tax'] . "', reward = '" . (int)$product['reward'] . "'");

				$order_product_id = $this->db->getLastId();

		 /*** insert into seller orders ****/
			if ($this->config->get('module_purpletree_multivendor_status')) {	
					
					$seller_id = $this->db->query("SELECT pvp.seller_id, pvs.store_shipping_charge,pvs.store_shipping_order_type,pvs.store_shipping_type,pvs.store_commission, p.tax_class_id FROM " . DB_PREFIX . "purpletree_vendor_products pvp JOIN " . DB_PREFIX . "purpletree_vendor_stores pvs ON(pvs.seller_id=pvp.seller_id) JOIN " . DB_PREFIX . "product p ON(p.product_id=pvp.product_id) WHERE pvp.product_id='".(int)$product['product_id']."' AND pvp.is_approved=1")->row;
					if($this->config->get('module_purpletree_multivendor_seller_product_template')){
					if(empty($seller_id['seller_id'])) {
						$sseller_id = $product['seller_id'];
						$seller_id = $this->db->query("SELECT pvs.seller_id, pvs.store_shipping_charge,pvs.store_shipping_order_type,pvs.store_shipping_type,pvs.store_commission, p.tax_class_id FROM " . DB_PREFIX . "purpletree_vendor_template_products pvtp JOIN " . DB_PREFIX . "purpletree_vendor_stores pvs ON(pvs.seller_id=pvtp.seller_id) JOIN " . DB_PREFIX . "purpletree_vendor_template pvt ON(pvt.id=pvtp.template_id) JOIN " . DB_PREFIX . "product p ON(p.product_id=pvt.product_id) WHERE pvt.product_id='".(int)$product['product_id']."' AND pvs.seller_id='".$sseller_id."'")->row;
					}
					}
					if(!empty($seller_id['seller_id'])) {
						
						$this->db->query("INSERT INTO " . DB_PREFIX . "purpletree_vendor_orders SET order_id ='".(int)$order_id."', seller_id = '".(int)$seller_id['seller_id']."', product_id ='".(int)$product['product_id']."', shipping = '".(float)$seller_id['store_shipping_charge']."', quantity = '" . (int)$product['quantity'] . "', unit_price = '" . (float)$product['price'] . "', total_price = '" . (float)$product['total'] . "', created_at =NOW(), updated_at = NOW()");
						
						
						$this->db->query("INSERT INTO " . DB_PREFIX . "purpletree_vendor_commissions SET order_id = '" . (int)$order_id . "', product_id ='".(int)$product['product_id']."', seller_id = '" . (int)$seller_id['seller_id'] . "', commission_shipping = '0', commission_fixed = '0', commission_percent = '0', commission = '0', status = 'Pending', created_at = NOW(), updated_at = NOW()");
						
						if(!isset($seller_sub_total[$seller_id['seller_id']])){
						$seller_sub_total[$seller_id['seller_id']] = $product['total'];
						} else {
							$seller_sub_total[$seller_id['seller_id']] += $product['total'];
						}
						
						if(!isset($seller_final_total[$seller_id['seller_id']])){
							$seller_final_total[$seller_id['seller_id']] = $this->tax->calculate($product['price'], $seller_id['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'];
						} else {
							$seller_final_total[$seller_id['seller_id']] += $this->tax->calculate($product['price'], $seller_id['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'];
						}
						
						$tax_rates = $this->tax->getRates($product['price'], $seller_id['tax_class_id']);
			
						foreach ($tax_rates as $tax_rate) {
							if (!isset($seller_tax_data[$seller_id['seller_id']][$tax_rate['tax_rate_id']])) {
								$seller_tax_data[$seller_id['seller_id']][$tax_rate['tax_rate_id']] = ($tax_rate['amount'] * $product['quantity']);
							} else {
								$seller_tax_data[$seller_id['seller_id']][$tax_rate['tax_rate_id']] += ($tax_rate['amount'] * $product['quantity']);
							}
						}
				$shipping_purpletree_shipping_order_type 			= $seller_id['store_shipping_order_type'] != '' ? $seller_id['store_shipping_order_type']:'pts_product_wise' ;
				$shipping_purpletree_shipping_type 			= $seller_id['store_shipping_type'] != '' ? $seller_id['store_shipping_type']:'pts_flat_rate_shipping' ;
				$shipping_purpletree_shipping_charge 		= $seller_id['store_shipping_charge'] != '' ? $seller_id['store_shipping_charge'] : '0';
						$getsellershipping = $this->getsellershipping($seller_id,$product,$data);
						$getsellershipping1 = $this->getsellershipping1($seller_id,$product,$data);
						if(!isset($seller_shipping[$seller_id['seller_id']])){
							$seller_shipping[$seller_id['seller_id']] = $getsellershipping;
							$seller_shipping1[$seller_id['seller_id']] = $getsellershipping1;
						} else {
							$seller_shipping[$seller_id['seller_id']] += $getsellershipping;
							$seller_shipping1[$seller_id['seller_id']] += $getsellershipping1;
						}
					} else {
						$seller_id = array();
						$seller_id['seller_id'] = 0;
						$getsellershipping = $this->getsellershipping($seller_id,$product,$data);
						$getsellershipping1 = $this->getsellershipping1($seller_id,$product,$data);
						if(!isset($seller_shipping[$seller_id['seller_id']])){
							$seller_shipping[$seller_id['seller_id']] = $getsellershipping;
							$seller_shipping1[$seller_id['seller_id']] = $getsellershipping1;
						} else {
							$seller_shipping[$seller_id['seller_id']] += $getsellershipping;
							$seller_shipping1[$seller_id['seller_id']] += $getsellershipping1;
						}
				$shipping_purpletree_shipping_order_type = (null !== $this->config->get('shipping_purpletree_shipping_order_type'))? $this->config->get('shipping_purpletree_shipping_order_type') : 'pts_product_wise';
				$shipping_purpletree_shipping_type = (null !== $this->config->get('shipping_purpletree_shipping_type'))? $this->config->get('shipping_purpletree_shipping_type') : 'pts_flat_rate_shipping';
				$shipping_purpletree_shipping_charge = (null !== $this->config->get('shipping_purpletree_shipping_charge'))? $this->config->get('shipping_purpletree_shipping_charge') : '0';
					} 
				$store_shipping_type[$seller_id['seller_id']] = $shipping_purpletree_shipping_type;
				$store_shipping_charge[$seller_id['seller_id']] = $shipping_purpletree_shipping_charge;
				$store_shipping_order_type[$seller_id['seller_id']] = $shipping_purpletree_shipping_order_type;
				}
			

				foreach ($product['option'] as $option) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "order_option SET order_id = '" . (int)$order_id . "', order_product_id = '" . (int)$order_product_id . "', product_option_id = '" . (int)$option['product_option_id'] . "', product_option_value_id = '" . (int)$option['product_option_value_id'] . "', name = '" . $this->db->escape($option['name']) . "', `value` = '" . $this->db->escape($option['value']) . "', `type` = '" . $this->db->escape($option['type']) . "'");
				}
			}
		}

		// Gift Voucher
		$this->load->model('extension/total/voucher');

		// Vouchers

					if(!empty($seller_shipping1)) {
			foreach($seller_shipping1 as $sellerid => $totalweight) {
				if($store_shipping_order_type[$sellerid] == 'pts_order_wise')  {
					$getMatrixShippingCharge1 = $this->getMatrixShippingCharge($data,$totalweight,$sellerid);
					if($store_shipping_type[$sellerid] == 'pts_matrix_shipping') {
						if($data['shipping_postcode'] != '') {
							if($getMatrixShippingCharge1) {
								$seller_shipping[$sellerid] += $getMatrixShippingCharge1;
							}
						} 
					} elseif($store_shipping_type[$sellerid] == 'pts_flexible_shipping') {
						if($getMatrixShippingCharge1) {
							$seller_shipping[$sellerid] += $getMatrixShippingCharge1;
						} else {
							$seller_shipping[$sellerid] += $store_shipping_charge[$sellerid];
						}
					} elseif($store_shipping_type[$sellerid] == 'pts_flat_rate_shipping') {
							$seller_shipping[$sellerid] += $store_shipping_charge[$sellerid];
					}
				}
			}
		}
					$this->load->language('extension/total/total');
			/**************************************** For seller tax*******************************/
		if(! empty($seller_tax_data))
		{
			foreach($seller_tax_data as $key=>$value){
				foreach ($value as $key1 => $value1) {
					if ($value1 > 0) {
						$tax_detail[$key][] = array(
							'code'       => 'tax',
							'title'      => $this->tax->getRateName($key1),
							'value'      => $value1,
							'sort_order' => $this->config->get('total_tax_sort_order')
						);
						if(!isset($seller_total_tax[$key])){
							$seller_total_tax[$key] = $value1;
						} else {
							$seller_total_tax[$key] +=$value1 ;
						}
					}
				}
			} 
			}
			/**************************************** For seller shipping*******************************/
			$this->load->language('account/ptsregister');
			if($this->config->get('shipping_purpletree_shipping_status')){
			if($data['shipping_code'] == 'purpletree_shipping.purpletree_shipping') {
				foreach($seller_shipping as $key=>$value) {
					if ($value > 0) {
						$shippingtitle = $this->language->get('text_seller_shipping_total');
						if($key == 0) {
						$shippingtitle = $this->language->get('text_admin_shipping_total');
						}
						$tax_detail[$key][] = array(
							'code'       => 'seller_shipping',
							'title'      => $shippingtitle,
							'value'      => $value,
							'sort_order' => '2'
						);
					}
				}	
			}
			}
		
			/**************************************** For seller total*******************************/
			
			foreach($seller_final_total as $key=>$value) {
				if(!isset($seller_total_tax[$key])){
					$seller_total_tax[$key]=0;
				}
				if(!$this->config->get('shipping_purpletree_shipping_status')){
						$seller_shipping[$key]=0;
				}
				//echo $data['shipping_code'];
				if($data['shipping_code'] != 'purpletree_shipping.purpletree_shipping') {
						$seller_shipping[$key]=0;
				}
				if ($value > 0) { 
					$tax_detail[$key][] = array(
						'code'       => 'total',
						'title'      => $this->language->get('text_total'),
						'value'      => max(0, ($seller_sub_total[$key]+$seller_total_tax[$key]+$seller_shipping[$key])),
						'sort_order' => $this->config->get('total_total_sort_order')
					);
				}
			}
				
			/**************************************** For seller sub-total*******************************/
			foreach($seller_sub_total as $key=>$value) {
				if ($value > 0) {
					$tax_detail[$key][] = array(
						'code'       => 'sub_total',
						'title'      => $this->language->get('text_sub_total'),
						'value'      => $value,
						'sort_order' => $this->config->get('sub_total_sort_order')
					);
				}
			}
			//echo "<pre>";
			//print_r($tax_detail);
		if (isset($tax_detail)) {
			foreach ($tax_detail as $key=>$value) {
				foreach($value as $data_1){
					$this->db->query("INSERT INTO " . DB_PREFIX . "purpletree_order_total SET order_id = '" . (int)$order_id . "', seller_id = '".(int)$key."', code = '" . $this->db->escape($data_1['code']) . "', title = '" . $this->db->escape($data_1['title']) . "', `value` = '" . (float)$data_1['value'] . "', sort_order = '" . (int)$data_1['sort_order'] . "'");
				}
			}
		}
			
		if (isset($data['vouchers'])) {
			foreach ($data['vouchers'] as $voucher) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_voucher SET order_id = '" . (int)$order_id . "', description = '" . $this->db->escape($voucher['description']) . "', code = '" . $this->db->escape($voucher['code']) . "', from_name = '" . $this->db->escape($voucher['from_name']) . "', from_email = '" . $this->db->escape($voucher['from_email']) . "', to_name = '" . $this->db->escape($voucher['to_name']) . "', to_email = '" . $this->db->escape($voucher['to_email']) . "', voucher_theme_id = '" . (int)$voucher['voucher_theme_id'] . "', message = '" . $this->db->escape($voucher['message']) . "', amount = '" . (float)$voucher['amount'] . "'");

				$order_voucher_id = $this->db->getLastId();

				$voucher_id = $this->model_extension_total_voucher->addVoucher($order_id, $voucher);

				$this->db->query("UPDATE " . DB_PREFIX . "order_voucher SET voucher_id = '" . (int)$voucher_id . "' WHERE order_voucher_id = '" . (int)$order_voucher_id . "'");
			}
		}

		// Totals
		if (isset($data['totals'])) {
			foreach ($data['totals'] as $total) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . (int)$order_id . "', code = '" . $this->db->escape($total['code']) . "', title = '" . $this->db->escape($total['title']) . "', `value` = '" . (float)$total['value'] . "', sort_order = '" . (int)$total['sort_order'] . "'");
			}
		}

		return $order_id;
	}

	public function editOrder($order_id, $data) {

			$total = 0;	
			$seller_sub_total = array();
			$seller_final_total = array();
			$seller_tax_data = array();
			$seller_total_tax = array();
			$tax_data = array();
			$seller = array();
			$seller_shipping = array();
			
		// Void the order first
		$this->addOrderHistory($order_id, 0);

		$this->db->query("UPDATE `" . DB_PREFIX . "order` SET invoice_prefix = '" . $this->db->escape($data['invoice_prefix']) . "', store_id = '" . (int)$data['store_id'] . "', store_name = '" . $this->db->escape($data['store_name']) . "', store_url = '" . $this->db->escape($data['store_url']) . "', customer_id = '" . (int)$data['customer_id'] . "', customer_group_id = '" . (int)$data['customer_group_id'] . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', custom_field = '" . $this->db->escape(json_encode($data['custom_field'])) . "', payment_firstname = '" . $this->db->escape($data['payment_firstname']) . "', payment_lastname = '" . $this->db->escape($data['payment_lastname']) . "', payment_company = '" . $this->db->escape($data['payment_company']) . "', payment_address_1 = '" . $this->db->escape($data['payment_address_1']) . "', payment_address_2 = '" . $this->db->escape($data['payment_address_2']) . "', payment_city = '" . $this->db->escape($data['payment_city']) . "', payment_postcode = '" . $this->db->escape($data['payment_postcode']) . "', payment_country = '" . $this->db->escape($data['payment_country']) . "', payment_country_id = '" . (int)$data['payment_country_id'] . "', payment_zone = '" . $this->db->escape($data['payment_zone']) . "', payment_zone_id = '" . (int)$data['payment_zone_id'] . "', payment_address_format = '" . $this->db->escape($data['payment_address_format']) . "', payment_custom_field = '" . $this->db->escape(json_encode($data['payment_custom_field'])) . "', payment_method = '" . $this->db->escape($data['payment_method']) . "', payment_code = '" . $this->db->escape($data['payment_code']) . "', shipping_firstname = '" . $this->db->escape($data['shipping_firstname']) . "', shipping_lastname = '" . $this->db->escape($data['shipping_lastname']) . "', shipping_company = '" . $this->db->escape($data['shipping_company']) . "', shipping_address_1 = '" . $this->db->escape($data['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape($data['shipping_address_2']) . "', shipping_city = '" . $this->db->escape($data['shipping_city']) . "', shipping_postcode = '" . $this->db->escape($data['shipping_postcode']) . "', shipping_country = '" . $this->db->escape($data['shipping_country']) . "', shipping_country_id = '" . (int)$data['shipping_country_id'] . "', shipping_zone = '" . $this->db->escape($data['shipping_zone']) . "', shipping_zone_id = '" . (int)$data['shipping_zone_id'] . "', shipping_address_format = '" . $this->db->escape($data['shipping_address_format']) . "', shipping_custom_field = '" . $this->db->escape(json_encode($data['shipping_custom_field'])) . "', shipping_method = '" . $this->db->escape($data['shipping_method']) . "', shipping_code = '" . $this->db->escape($data['shipping_code']) . "', comment = '" . $this->db->escape($data['comment']) . "', total = '" . (float)$data['total'] . "', affiliate_id = '" . (int)$data['affiliate_id'] . "', commission = '" . (float)$data['commission'] . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "'");

			$this->db->query("DELETE FROM " . DB_PREFIX . "purpletree_vendor_orders WHERE order_id = '" . (int)$order_id . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "purpletree_vendor_commissions WHERE order_id = '" . (int)$order_id . "'");
			

		// Products
		if (isset($data['products'])) {

			$store_shipping_type = array();
			
			foreach ($data['products'] as $product) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_product SET order_id = '" . (int)$order_id . "', product_id = '" . (int)$product['product_id'] . "', name = '" . $this->db->escape($product['name']) . "', model = '" . $this->db->escape($product['model']) . "', quantity = '" . (int)$product['quantity'] . "', price = '" . (float)$product['price'] . "', total = '" . (float)$product['total'] . "', tax = '" . (float)$product['tax'] . "', reward = '" . (int)$product['reward'] . "'");

				$order_product_id = $this->db->getLastId();

		 /*** insert into seller orders ****/
			if ($this->config->get('module_purpletree_multivendor_status')) {	
					
					$seller_id = $this->db->query("SELECT pvp.seller_id, pvs.store_shipping_charge,pvs.store_shipping_order_type,pvs.store_shipping_type,pvs.store_commission, p.tax_class_id FROM " . DB_PREFIX . "purpletree_vendor_products pvp JOIN " . DB_PREFIX . "purpletree_vendor_stores pvs ON(pvs.seller_id=pvp.seller_id) JOIN " . DB_PREFIX . "product p ON(p.product_id=pvp.product_id) WHERE pvp.product_id='".(int)$product['product_id']."' AND pvp.is_approved=1")->row;
					if($this->config->get('module_purpletree_multivendor_seller_product_template')){
					if(empty($seller_id['seller_id'])) {
						$sseller_id = $product['seller_id'];
						$seller_id = $this->db->query("SELECT pvs.seller_id, pvs.store_shipping_charge,pvs.store_shipping_order_type,pvs.store_shipping_type,pvs.store_commission, p.tax_class_id FROM " . DB_PREFIX . "purpletree_vendor_template_products pvtp JOIN " . DB_PREFIX . "purpletree_vendor_stores pvs ON(pvs.seller_id=pvtp.seller_id) JOIN " . DB_PREFIX . "purpletree_vendor_template pvt ON(pvt.id=pvtp.template_id) JOIN " . DB_PREFIX . "product p ON(p.product_id=pvt.product_id) WHERE pvt.product_id='".(int)$product['product_id']."' AND pvs.seller_id='".$sseller_id."'")->row;
					}
					}
					if(!empty($seller_id['seller_id'])) {
						
						$this->db->query("INSERT INTO " . DB_PREFIX . "purpletree_vendor_orders SET order_id ='".(int)$order_id."', seller_id = '".(int)$seller_id['seller_id']."', product_id ='".(int)$product['product_id']."', shipping = '".(float)$seller_id['store_shipping_charge']."', quantity = '" . (int)$product['quantity'] . "', unit_price = '" . (float)$product['price'] . "', total_price = '" . (float)$product['total'] . "', created_at =NOW(), updated_at = NOW()");
						
						
						$this->db->query("INSERT INTO " . DB_PREFIX . "purpletree_vendor_commissions SET order_id = '" . (int)$order_id . "', product_id ='".(int)$product['product_id']."', seller_id = '" . (int)$seller_id['seller_id'] . "', commission_shipping = '0', commission_fixed = '0', commission_percent = '0', commission = '0', status = 'Pending', created_at = NOW(), updated_at = NOW()");
						
						if(!isset($seller_sub_total[$seller_id['seller_id']])){
						$seller_sub_total[$seller_id['seller_id']] = $product['total'];
						} else {
							$seller_sub_total[$seller_id['seller_id']] += $product['total'];
						}
						
						if(!isset($seller_final_total[$seller_id['seller_id']])){
							$seller_final_total[$seller_id['seller_id']] = $this->tax->calculate($product['price'], $seller_id['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'];
						} else {
							$seller_final_total[$seller_id['seller_id']] += $this->tax->calculate($product['price'], $seller_id['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'];
						}
						
						$tax_rates = $this->tax->getRates($product['price'], $seller_id['tax_class_id']);
			
						foreach ($tax_rates as $tax_rate) {
							if (!isset($seller_tax_data[$seller_id['seller_id']][$tax_rate['tax_rate_id']])) {
								$seller_tax_data[$seller_id['seller_id']][$tax_rate['tax_rate_id']] = ($tax_rate['amount'] * $product['quantity']);
							} else {
								$seller_tax_data[$seller_id['seller_id']][$tax_rate['tax_rate_id']] += ($tax_rate['amount'] * $product['quantity']);
							}
						}
				$shipping_purpletree_shipping_order_type 			= $seller_id['store_shipping_order_type'] != '' ? $seller_id['store_shipping_order_type']:'pts_product_wise' ;
				$shipping_purpletree_shipping_type 			= $seller_id['store_shipping_type'] != '' ? $seller_id['store_shipping_type']:'pts_flat_rate_shipping' ;
				$shipping_purpletree_shipping_charge 		= $seller_id['store_shipping_charge'] != '' ? $seller_id['store_shipping_charge'] : '0';
						$getsellershipping = $this->getsellershipping($seller_id,$product,$data);
						$getsellershipping1 = $this->getsellershipping1($seller_id,$product,$data);
						if(!isset($seller_shipping[$seller_id['seller_id']])){
							$seller_shipping[$seller_id['seller_id']] = $getsellershipping;
							$seller_shipping1[$seller_id['seller_id']] = $getsellershipping1;
						} else {
							$seller_shipping[$seller_id['seller_id']] += $getsellershipping;
							$seller_shipping1[$seller_id['seller_id']] += $getsellershipping1;
						}
					} else {
						$seller_id = array();
						$seller_id['seller_id'] = 0;
						$getsellershipping = $this->getsellershipping($seller_id,$product,$data);
						$getsellershipping1 = $this->getsellershipping1($seller_id,$product,$data);
						if(!isset($seller_shipping[$seller_id['seller_id']])){
							$seller_shipping[$seller_id['seller_id']] = $getsellershipping;
							$seller_shipping1[$seller_id['seller_id']] = $getsellershipping1;
						} else {
							$seller_shipping[$seller_id['seller_id']] += $getsellershipping;
							$seller_shipping1[$seller_id['seller_id']] += $getsellershipping1;
						}
				$shipping_purpletree_shipping_order_type = (null !== $this->config->get('shipping_purpletree_shipping_order_type'))? $this->config->get('shipping_purpletree_shipping_order_type') : 'pts_product_wise';
				$shipping_purpletree_shipping_type = (null !== $this->config->get('shipping_purpletree_shipping_type'))? $this->config->get('shipping_purpletree_shipping_type') : 'pts_flat_rate_shipping';
				$shipping_purpletree_shipping_charge = (null !== $this->config->get('shipping_purpletree_shipping_charge'))? $this->config->get('shipping_purpletree_shipping_charge') : '0';
					} 
				$store_shipping_type[$seller_id['seller_id']] = $shipping_purpletree_shipping_type;
				$store_shipping_charge[$seller_id['seller_id']] = $shipping_purpletree_shipping_charge;
				$store_shipping_order_type[$seller_id['seller_id']] = $shipping_purpletree_shipping_order_type;
				}
			

				foreach ($product['option'] as $option) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "order_option SET order_id = '" . (int)$order_id . "', order_product_id = '" . (int)$order_product_id . "', product_option_id = '" . (int)$option['product_option_id'] . "', product_option_value_id = '" . (int)$option['product_option_value_id'] . "', name = '" . $this->db->escape($option['name']) . "', `value` = '" . $this->db->escape($option['value']) . "', `type` = '" . $this->db->escape($option['type']) . "'");
				}
			}
		}

		// Gift Voucher
		$this->load->model('extension/total/voucher');

		$this->model_extension_total_voucher->disableVoucher($order_id);

		// Vouchers
		$this->db->query("DELETE FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int)$order_id . "'");

			$this->db->query("DELETE FROM " . DB_PREFIX . "purpletree_order_total WHERE order_id = '" . (int)$order_id . "'");
			


					if(!empty($seller_shipping1)) {
			foreach($seller_shipping1 as $sellerid => $totalweight) {
				if($store_shipping_order_type[$sellerid] == 'pts_order_wise')  {
					$getMatrixShippingCharge1 = $this->getMatrixShippingCharge($data,$totalweight,$sellerid);
					if($store_shipping_type[$sellerid] == 'pts_matrix_shipping') {
						if($data['shipping_postcode'] != '') {
							if($getMatrixShippingCharge1) {
								$seller_shipping[$sellerid] += $getMatrixShippingCharge1;
							}
						} 
					} elseif($store_shipping_type[$sellerid] == 'pts_flexible_shipping') {
						if($getMatrixShippingCharge1) {
							$seller_shipping[$sellerid] += $getMatrixShippingCharge1;
						} else {
							$seller_shipping[$sellerid] += $store_shipping_charge[$sellerid];
						}
					} elseif($store_shipping_type[$sellerid] == 'pts_flat_rate_shipping') {
							$seller_shipping[$sellerid] += $store_shipping_charge[$sellerid];
					}
				}
			}
		}
					$this->load->language('extension/total/total');
			/**************************************** For seller tax*******************************/
		if(! empty($seller_tax_data))
		{
			foreach($seller_tax_data as $key=>$value){
				foreach ($value as $key1 => $value1) {
					if ($value1 > 0) {
						$tax_detail[$key][] = array(
							'code'       => 'tax',
							'title'      => $this->tax->getRateName($key1),
							'value'      => $value1,
							'sort_order' => $this->config->get('total_tax_sort_order')
						);
						if(!isset($seller_total_tax[$key])){
							$seller_total_tax[$key] = $value1;
						} else {
							$seller_total_tax[$key] +=$value1 ;
						}
					}
				}
			} 
			}
			/**************************************** For seller shipping*******************************/
			$this->load->language('account/ptsregister');
			if($this->config->get('shipping_purpletree_shipping_status')){
			if($data['shipping_code'] == 'purpletree_shipping.purpletree_shipping') {
				foreach($seller_shipping as $key=>$value) {
					if ($value > 0) {
						$shippingtitle = $this->language->get('text_seller_shipping_total');
						if($key == 0) {
						$shippingtitle = $this->language->get('text_admin_shipping_total');
						}
						$tax_detail[$key][] = array(
							'code'       => 'seller_shipping',
							'title'      => $shippingtitle,
							'value'      => $value,
							'sort_order' => '2'
						);
					}
				}	
			}
			}
		
			/**************************************** For seller total*******************************/
			
			foreach($seller_final_total as $key=>$value) {
				if(!isset($seller_total_tax[$key])){
					$seller_total_tax[$key]=0;
				}
				if(!$this->config->get('shipping_purpletree_shipping_status')){
						$seller_shipping[$key]=0;
				}
				//echo $data['shipping_code'];
				if($data['shipping_code'] != 'purpletree_shipping.purpletree_shipping') {
						$seller_shipping[$key]=0;
				}
				if ($value > 0) { 
					$tax_detail[$key][] = array(
						'code'       => 'total',
						'title'      => $this->language->get('text_total'),
						'value'      => max(0, ($seller_sub_total[$key]+$seller_total_tax[$key]+$seller_shipping[$key])),
						'sort_order' => $this->config->get('total_total_sort_order')
					);
				}
			}
				
			/**************************************** For seller sub-total*******************************/
			foreach($seller_sub_total as $key=>$value) {
				if ($value > 0) {
					$tax_detail[$key][] = array(
						'code'       => 'sub_total',
						'title'      => $this->language->get('text_sub_total'),
						'value'      => $value,
						'sort_order' => $this->config->get('sub_total_sort_order')
					);
				}
			}
			//echo "<pre>";
			//print_r($tax_detail);
		if (isset($tax_detail)) {
			foreach ($tax_detail as $key=>$value) {
				foreach($value as $data_1){
					$this->db->query("INSERT INTO " . DB_PREFIX . "purpletree_order_total SET order_id = '" . (int)$order_id . "', seller_id = '".(int)$key."', code = '" . $this->db->escape($data_1['code']) . "', title = '" . $this->db->escape($data_1['title']) . "', `value` = '" . (float)$data_1['value'] . "', sort_order = '" . (int)$data_1['sort_order'] . "'");
				}
			}
		}
			
		if (isset($data['vouchers'])) {
			foreach ($data['vouchers'] as $voucher) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_voucher SET order_id = '" . (int)$order_id . "', description = '" . $this->db->escape($voucher['description']) . "', code = '" . $this->db->escape($voucher['code']) . "', from_name = '" . $this->db->escape($voucher['from_name']) . "', from_email = '" . $this->db->escape($voucher['from_email']) . "', to_name = '" . $this->db->escape($voucher['to_name']) . "', to_email = '" . $this->db->escape($voucher['to_email']) . "', voucher_theme_id = '" . (int)$voucher['voucher_theme_id'] . "', message = '" . $this->db->escape($voucher['message']) . "', amount = '" . (float)$voucher['amount'] . "'");

				$order_voucher_id = $this->db->getLastId();

				$voucher_id = $this->model_extension_total_voucher->addVoucher($order_id, $voucher);

				$this->db->query("UPDATE " . DB_PREFIX . "order_voucher SET voucher_id = '" . (int)$voucher_id . "' WHERE order_voucher_id = '" . (int)$order_voucher_id . "'");
			}
		}

		// Totals
		$this->db->query("DELETE FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "'");

		if (isset($data['totals'])) {
			foreach ($data['totals'] as $total) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . (int)$order_id . "', code = '" . $this->db->escape($total['code']) . "', title = '" . $this->db->escape($total['title']) . "', `value` = '" . (float)$total['value'] . "', sort_order = '" . (int)$total['sort_order'] . "'");
			}
		}
	}

	public function deleteOrder($order_id) {
		// Void the order first
		$this->addOrderHistory($order_id, 0);

		$this->db->query("DELETE FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "'");

			/*** Delete seller order tables ***/
			$this->db->query("DELETE FROM `" . DB_PREFIX . "purpletree_vendor_orders` WHERE order_id = '" . (int)$order_id . "'");
			$this->db->query("DELETE FROM `" . DB_PREFIX . "purpletree_vendor_commissions` WHERE order_id = '" . (int)$order_id . "'");
			$this->db->query("DELETE FROM `" . DB_PREFIX . "purpletree_order_total` WHERE order_id = '" . (int)$order_id . "'");
			$this->db->query("DELETE FROM `" . DB_PREFIX . "purpletree_vendor_orders_history` WHERE order_id = '" . (int)$order_id . "'");
			
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_product` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_option` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_voucher` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_history` WHERE order_id = '" . (int)$order_id . "'");
		$this->db->query("DELETE `or`, ort FROM `" . DB_PREFIX . "order_recurring` `or`, `" . DB_PREFIX . "order_recurring_transaction` `ort` WHERE order_id = '" . (int)$order_id . "' AND ort.order_recurring_id = `or`.order_recurring_id");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "customer_transaction` WHERE order_id = '" . (int)$order_id . "'");

		// Gift Voucher
		$this->load->model('extension/total/voucher');

		$this->model_extension_total_voucher->disableVoucher($order_id);
	}

	public function getOrder($order_id) {
		$order_query = $this->db->query("SELECT *, (SELECT os.name FROM `" . DB_PREFIX . "order_status` os WHERE os.order_status_id = o.order_status_id AND os.language_id = o.language_id) AS order_status FROM `" . DB_PREFIX . "order` o WHERE o.order_id = '" . (int)$order_id . "'");

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

			$this->load->model('localisation/language');

			$language_info = $this->model_localisation_language->getLanguage($order_query->row['language_id']);

			if ($language_info) {
				$language_code = $language_info['code'];
			} else {
				$language_code = $this->config->get('config_language');
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
				'email'                   => $order_query->row['email'],
				'telephone'               => $order_query->row['telephone'],
				'custom_field'            => json_decode($order_query->row['custom_field'], true),
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
				'payment_custom_field'    => json_decode($order_query->row['payment_custom_field'], true),
				'payment_method'          => $order_query->row['payment_method'],
				'payment_code'            => $order_query->row['payment_code'],
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
				'shipping_custom_field'   => json_decode($order_query->row['shipping_custom_field'], true),
				'shipping_method'         => $order_query->row['shipping_method'],
				'shipping_code'           => $order_query->row['shipping_code'],
				'comment'                 => $order_query->row['comment'],
				'total'                   => $order_query->row['total'],
				'order_status_id'         => $order_query->row['order_status_id'],
				'order_status'            => $order_query->row['order_status'],
				'affiliate_id'            => $order_query->row['affiliate_id'],
				'commission'              => $order_query->row['commission'],
				'language_id'             => $order_query->row['language_id'],
				'language_code'           => $language_code,
				'currency_id'             => $order_query->row['currency_id'],
				'currency_code'           => $order_query->row['currency_code'],
				'currency_value'          => $order_query->row['currency_value'],
				'ip'                      => $order_query->row['ip'],
				'forwarded_ip'            => $order_query->row['forwarded_ip'],
				'user_agent'              => $order_query->row['user_agent'],
				'accept_language'         => $order_query->row['accept_language'],
				'date_added'              => $order_query->row['date_added'],
				'date_modified'           => $order_query->row['date_modified']
			);
		} else {
			return false;
		}
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
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int)$order_id . "'");
	
		return $query->rows;
	}
	
	public function getOrderTotals($order_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order ASC");
		
		return $query->rows;
	}	
			
	public function addOrderHistory($order_id, $order_status_id, $comment = '', $notify = false, $override = false) {
		$order_info = $this->getOrder($order_id);
		
		if ($order_info) {
			// Fraud Detection
			$this->load->model('account/customer');

			$customer_info = $this->model_account_customer->getCustomer($order_info['customer_id']);

			if ($customer_info && $customer_info['safe']) {
				$safe = true;
			} else {
				$safe = false;
			}

			// Only do the fraud check if the customer is not on the safe list and the order status is changing into the complete or process order status
			if (!$safe && !$override && in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
				// Anti-Fraud
				$this->load->model('setting/extension');

				$extensions = $this->model_setting_extension->getExtensions('fraud');

				foreach ($extensions as $extension) {
					if ($this->config->get('fraud_' . $extension['code'] . '_status')) {
						$this->load->model('extension/fraud/' . $extension['code']);

						if (property_exists($this->{'model_extension_fraud_' . $extension['code']}, 'check')) {
							$fraud_status_id = $this->{'model_extension_fraud_' . $extension['code']}->check($order_info);
	
							if ($fraud_status_id) {
								$order_status_id = $fraud_status_id;
							}
						}
					}
				}
			}

			// If current order status is not processing or complete but new status is processing or complete then commence completing the order
			if (!in_array($order_info['order_status_id'], array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status'))) && in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
				// Redeem coupon, vouchers and reward points
				$order_totals = $this->getOrderTotals($order_id);

				foreach ($order_totals as $order_total) {
					$this->load->model('extension/total/' . $order_total['code']);

					if (property_exists($this->{'model_extension_total_' . $order_total['code']}, 'confirm')) {
						// Confirm coupon, vouchers and reward points
						$fraud_status_id = $this->{'model_extension_total_' . $order_total['code']}->confirm($order_info, $order_total);
						
						// If the balance on the coupon, vouchers and reward points is not enough to cover the transaction or has already been used then the fraud order status is returned.
						if ($fraud_status_id) {
							$order_status_id = $fraud_status_id;
						}
					}
				}

				// Stock subtraction
				$order_products = $this->getOrderProducts($order_id);

				foreach ($order_products as $order_product) {
					$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");

					$order_options = $this->getOrderOptions($order_id, $order_product['order_product_id']);

					foreach ($order_options as $order_option) {
						$this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$order_option['product_option_value_id'] . "' AND subtract = '1'");
					}
				}
				
				// Add commission if sale is linked to affiliate referral.
				if ($order_info['affiliate_id'] && $this->config->get('config_affiliate_auto')) {
					$this->load->model('account/customer');

					if (!$this->model_account_customer->getTotalTransactionsByOrderId($order_id)) {
						$this->model_account_customer->addTransaction($order_info['affiliate_id'], $this->language->get('text_order_id') . ' #' . $order_id, $order_info['commission'], $order_id);
					}
				}
			}

			// Update the DB with the new statuses

			// Update the DB with the new statuses
			/*** update seller order and add order history ***/
			//Commission on Order Status Change
			//Commission on Order Status Change
			$order_products = $this->getVendorOrderProducts($order_id);
				foreach ($order_products as $order_product) {
				if($this->config->get('module_purpletree_multivendor_seller_product_template')){
				    $ppproduct_id = $order_product['product_id'];
					$ssseller_id  = $order_product['seller_id'];
					$productqntity  = $order_product['quantity'];
					$template_idd = $this->getTemplateId($ppproduct_id);
					
					$this->db->query("UPDATE " . DB_PREFIX . "purpletree_vendor_template_products SET quantity = (quantity - " . (int)$productqntity . ") WHERE template_id = '" . (int)$template_idd. "'  AND seller_id = '" . (int)$ssseller_id. "' AND subtract = '1'");
					}
						$dsds1 = "SELECT `seller_id`,`order_status_id` FROM `" . DB_PREFIX . "purpletree_vendor_orders_history` WHERE order_id = '" . (int)$order_id . "' && seller_id = '" . (int)$order_product['seller_id'] . "' order by `id` DESC";
						$query1112 = $this->db->query($dsds1);
						$sdsds11 = $query1112->row;
						if(empty($sdsds11)) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "purpletree_vendor_orders_history SET order_id = '" . (int)$order_id . "', seller_id ='". (int)$order_product['seller_id'] ."', order_status_id = '" . (int)$order_status_id . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($comment) . "', created_at = NOW()");
							$ordststid = $order_status_id;
						} else {
							$ordststid = $sdsds11['order_status_id'];
						}
							$this->db->query("UPDATE `" . DB_PREFIX . "purpletree_vendor_orders` SET order_status_id = '" . (int)$ordststid . "', updated_at = NOW() WHERE order_id = '" . (int)$order_id . "' && product_id = '" . (int)$order_product['product_id'] . "'");
			if (null !== $this->config->get('module_purpletree_multivendor_commission_status') && $this->config->get('module_purpletree_multivendor_commission_status') != $order_status_id) {
                    $this->db->query("UPDATE " . DB_PREFIX . "purpletree_vendor_commissions SET commission_shipping = '0', commission_fixed = '0', commission_percent = '0', commission = '0', status = 'Order Cancelled', updated_at = NOW() WHERE order_id = '" . (int)$order_id . "'  AND product_id = '" . (int)$order_product['product_id'] . "'");
                }
			} 
			if (null !== $this->config->get('module_purpletree_multivendor_commission_status') && $this->config->get('module_purpletree_multivendor_commission_status') == $order_status_id) {
				$sellerorders = $this->db->query("SELECT * FROM `" . DB_PREFIX . "purpletree_vendor_orders` WHERE order_id = '" . (int)$order_id . "'");
				
				$shipcommsvirtial = '0';
				$dsdsds = array();
				if(!empty($sellerorders->rows)) {
					foreach($sellerorders->rows as $sellerorder) {
						$sql1111 = "SELECT `store_commission` FROM `" . DB_PREFIX . "purpletree_vendor_stores` WHERE seller_id = '" . (int)$sellerorder['seller_id'] . "'";
						$totalshipingorder = '0';
								$getShippingOrderTotal = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "purpletree_order_total` WHERE order_id = '" . (int)$order_id . "' AND seller_id = '" . (int)$sellerorder['seller_id'] . "' AND code ='seller_shipping'");
						if($getShippingOrderTotal->num_rows){
							$totalshipingorder = $getShippingOrderTotal->row['value'];
						}
				
						$query = $this->db->query($sql1111);
						$seller_commission = $query->row;
						if($sellerorder['order_status_id'] == $this->config->get('module_purpletree_multivendor_commission_status')) {
							 //category_commission
				        $productid = $sellerorder['product_id'];	
						$catids =$this->getProductCategory($productid );
						$commission_cat = array();
						$catttt = array();
						 $shippingcommision = 0;
							 if($totalshipingorder != 0) {
								 if (null !== $this->config->get('module_purpletree_multivendor_shipping_commission')) {
									 if(!array_key_exists($sellerorder['seller_id'],$dsdsds)) {
									 $shippingcommision = (($this->config->get('module_purpletree_multivendor_shipping_commission')*$totalshipingorder)/100);
									 $dsdsds[$sellerorder['seller_id']] = $shippingcommision;
									 }
								 }
							 }
						if(!empty($catids)){
							foreach($catids as $cat) {
								$sql = "SELECT * FROM " . DB_PREFIX . "purpletree_vendor_categories_commission where category_id = '".(int)$cat['category_id']."'";
								$query = $this->db->query($sql);
								$commission_cat[] = $query->rows;
							}
								
						}
						$commission = -1;
						$commission1 = -1;
						$comipercen = 0;
						$comifixs = 0;
						
						if(!empty($commission_cat)) {
						 foreach($commission_cat as $catts) {
						 foreach($catts as $catt) {
								$comifix = 0;
							 if(isset($catt['commison_fixed']) && $catt['commison_fixed'] != '') {
								$comifix = $catt['commison_fixed'];
							 }
								$comiper = 0;
							 if(isset($catt['commission']) && $catt['commission'] != '') {
								$comiper = $catt['commission'];
							 }
							
							 if (null !== $this->config->get('module_purpletree_multivendor_seller_group') && $this->config->get('module_purpletree_multivendor_seller_group') == 1) {
								$sqlgrop = "Select `customer_group_id` from `" . DB_PREFIX . "customer` where customer_id= ".$sellerorder['seller_id']." ";
								$querygrop = $this->db->query($sqlgrop);
								$sellergrp = $querygrop->row;
								if($catt['seller_group'] == $sellergrp['customer_group_id']) {
									$commipercent = (($comiper*$sellerorder['total_price'])/100);
									$commission1 = $comifix + $commipercent + $shippingcommision;
									if($commission1 > $commission) {
										$comipercen 		= $comiper;
										$comifixs 			= $comifix;
										$shippingcommision 	= $shippingcommision;
										$commission 		= $commission1;
									}
								}
							 } else {
								 $commipercent = (($comiper*$sellerorder['total_price'])/100);
									$commission1 = $comifix + $commipercent + $shippingcommision;
									if($commission1 > $commission) {
										$comipercen 		= $comiper;
										$comifixs 			= $comifix;
										$shippingcommision 	= $shippingcommision;
										$commission 		= $commission1;
									} 
							 }
						   }
						 }
						}
						if($commission != -1) {
							$commission = $commission;
						}
						//category_commission
						elseif(isset($seller_commission['store_commission']) && ($seller_commission['store_commission'] != NULL || $seller_commission['store_commission'] != '')){
							$comipercen = $seller_commission['store_commission'];
							$commission = (($sellerorder['total_price']*$seller_commission['store_commission'])/100)+$shippingcommision;
						} else {
							$comipercen = $this->config->get('module_purpletree_multivendor_commission');
							$commission = (($sellerorder['total_price']*$this->config->get('module_purpletree_multivendor_commission'))/100)+$shippingcommision;
						}
						$this->db->query("UPDATE " . DB_PREFIX . "purpletree_vendor_commissions SET commission_shipping = '".$shippingcommision."', commission_fixed = '".$comifixs."', commission_percent = '".$comipercen."', commission = '" . (float)$commission . "', status = 'Complete', updated_at = NOW() WHERE order_id = '" . (int)$order_id . "' && product_id ='".(int)$sellerorder['product_id']."' && seller_id = '" . (int)$sellerorder['seller_id'] . "'");
						} else {
							$this->db->query("UPDATE " . DB_PREFIX . "purpletree_vendor_commissions SET commission_shipping = '0', commission_fixed = '0', commission_percent = '0',  commission = '0', status = 'Pending', updated_at = NOW() WHERE order_id = '" . (int)$order_id . "' && product_id ='".(int)$sellerorder['product_id']."' && seller_id = '" . (int)$sellerorder['seller_id'] . "'");
						}
					}
				}
			}
			
			$this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");

			$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");

			// If old order status is the processing or complete status but new status is not then commence restock, and remove coupon, voucher and reward history
			if (in_array($order_info['order_status_id'], array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status'))) && !in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
				// Restock
				$order_products = $this->getOrderProducts($order_id);

				foreach($order_products as $order_product) {
					$this->db->query("UPDATE `" . DB_PREFIX . "product` SET quantity = (quantity + " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");

					$order_options = $this->getOrderOptions($order_id, $order_product['order_product_id']);

					foreach ($order_options as $order_option) {
						$this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity + " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$order_option['product_option_value_id'] . "' AND subtract = '1'");
					}
				}

				// Remove coupon, vouchers and reward points history
				$order_totals = $this->getOrderTotals($order_id);
				
				foreach ($order_totals as $order_total) {
					$this->load->model('extension/total/' . $order_total['code']);

					if (property_exists($this->{'model_extension_total_' . $order_total['code']}, 'unconfirm')) {
						$this->{'model_extension_total_' . $order_total['code']}->unconfirm($order_id);
					}
				}

				// Remove commission if sale is linked to affiliate referral.
				if ($order_info['affiliate_id']) {
					$this->load->model('account/customer');
					
					$this->model_account_customer->deleteTransactionByOrderId($order_id);
				}
			}

			$this->cache->delete('product');
		}
	}
}