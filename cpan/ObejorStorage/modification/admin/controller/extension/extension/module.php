<?php
class ControllerExtensionExtensionModule extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/extension/module');

		$this->load->model('setting/extension');

		$this->load->model('setting/module');

		$this->getList();
	}

	public function install() {
		$this->load->language('extension/extension/module');

		$this->load->model('setting/extension');

		$this->load->model('setting/module');

		if ($this->validate()) {
			$this->model_setting_extension->install('module', $this->request->get['extension']);

			/***********    Create Tables For Multivendor Extension when module installs     ***********/
			if($this->request->get['extension']=="purpletree_multivendor"){
			$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "purpletree_vendor_stores'");	
				if($query->num_rows==0) 
		{
			$seller_layout = $this->db->query("SELECT layout_id FROM " . DB_PREFIX . "layout WHERE name='Account'");
			
			if($seller_layout->num_rows > 0){
				$data = $seller_layout->row;
					$this->db->query("INSERT into " . DB_PREFIX . "layout_route SET layout_id='".$data['layout_id']."', store_id='0', route='extension/account/%'");	
			}
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_stores` (
  							`id` int(11) NOT NULL AUTO_INCREMENT,
  							`seller_id` int(11) NOT NULL,
  							`store_name` varchar(256) NOT NULL,
  							`store_logo` varchar(150) NOT NULL,
  							`store_email` varchar(200) NOT NULL,
  							`store_phone` varchar(14) NOT NULL,
  							`store_banner` varchar(150) NOT NULL,
  							`document` varchar(150) NOT NULL,
  							`store_description` text NOT NULL,
  							`store_address` text NOT NULL,
  							`store_city` varchar(200) NOT NULL,
  							`store_country` int(11) NOT NULL,
  							`store_state` int(11) NOT NULL,
  							`store_zipcode` varchar(11) NOT NULL,
  							`store_shipping_policy` text NOT NULL,
							`store_return_policy` text NOT NULL,
							`store_meta_keywords` text NOT NULL,
							`store_meta_descriptions` text NOT NULL,
							`store_bank_details` varchar(356) NOT NULL,
  							`store_tin` varchar(50) NOT NULL,
							`store_shipping_type` varchar(50) NOT NULL,
							`store_shipping_order_type` varchar(50) NOT NULL,
  							`store_shipping_charge` decimal(6,2) NOT NULL,
							`store_live_chat_enable` tinyint(1) NOT NULL,
							`store_live_chat_code` text NOT NULL,
  							`store_status` tinyint(1) NOT NULL,
  							`store_commission` float(6,4) NULL DEFAULT NULL,
  							`is_removed` tinyint(1) NOT NULL,
  							`store_created_at` date NOT NULL,
  							`store_updated_at` date NOT NULL,
							`seller_paypal_id` varchar(50) NOT NULL,
							`multi_store_id` int(11) NOT NULL,
  							PRIMARY KEY (`id`)  ) CHARACTER SET utf8 COLLATE utf8_unicode_ci
						");
						$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_social_links` (
								`id` int(11) NOT NULL AUTO_INCREMENT,
								`store_id` int(11) NOT NULL,
								`facebook_link` varchar(255) NOT NULL,
								`google_link` varchar(255) NOT NULL,
								`instagram_link` varchar(255) NOT NULL,
								`twitter_link` varchar(255) NOT NULL,
								`pinterest_link` varchar(255) NOT NULL,
								`wesbsite_link` varchar(255) NOT NULL,	
								`whatsapp_link` varchar(255) NOT NULL,	
								PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
			            ");
				
				$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_products` (
  							`id` int(11) NOT NULL AUTO_INCREMENT,
  							`seller_id` int(11) NOT NULL,
  							`product_id` int(11) NOT NULL,
							`is_featured` int(11) NOT NULL,
							`is_category_featured` int(11) NOT NULL,
  							`is_approved` tinyint(1) NOT NULL,
  							`created_at` date NOT NULL,
  							`updated_at` date NOT NULL,
  							PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
						");
				$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_orders` (
  							`id` int(11) NOT NULL AUTO_INCREMENT,
  							`seller_id` int(11) NOT NULL,
  							`product_id` int(11) NOT NULL,
  							`order_id` int(11) NOT NULL,
  							`shipping` decimal(6,2) NOT NULL,
  							`quantity` int(11) NOT NULL,
  							`unit_price` decimal(10,2) NOT NULL,
  							`total_price` decimal(10,2) NOT NULL,
  							`order_status_id` int(3) NOT NULL,
  							`created_at` date NOT NULL,
  							`updated_at` date NOT NULL,
							`seen` int(11) NOT NULL DEFAULT '1',
  							PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
						");
				$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_order_total` (
  							`order_total_id` int(11) NOT NULL AUTO_INCREMENT,
  							`order_id` int(11) NOT NULL,
  							`seller_id` int(11) NOT NULL,
  							`code` varchar(32) NOT NULL,
  							`title` varchar(255) NOT NULL,
  							`value` decimal(15,4) NOT NULL,
  							`sort_order` int(3) NOT NULL,
  							PRIMARY KEY (`order_total_id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
						");
				$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_orders_history` (
  							`id` int(11) NOT NULL AUTO_INCREMENT,
  							`seller_id` int(11) NOT NULL,
  							`order_id` int(11) NOT NULL,
  							`order_status_id` int(11) NOT NULL,
  							`comment` varchar(250) NOT NULL,
							`notify` tinyint(1) NOT NULL,
  							`created_at` date NOT NULL,
  							PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
						");
				$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_reviews` (
  							`id` int(11) NOT NULL AUTO_INCREMENT,
  							`seller_id` int(11) NOT NULL,
  							`customer_id` int(11) NOT NULL,
  							`review_title` varchar(150) NOT NULL,
  							`review_description` text NOT NULL,
  							`rating` int(11) NOT NULL,
  							`status` tinyint(1) NOT NULL,
  							`created_at` date NOT NULL,
  							`updated_at` date NOT NULL,
  							PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
						");
				$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_commissions` (
  							`id` int(11) NOT NULL AUTO_INCREMENT,
  							`seller_id` int(11) NOT NULL,
  							`order_id` int(11) NOT NULL,
  							`product_id` int(11) NOT NULL,
							`commission_fixed` int(50) NOT NULL DEFAULT '0',
							`commission_percent` decimal(4,2) NOT NULL DEFAULT '0.00',
							`commission_shipping` float(8,2) NOT NULL DEFAULT '0.00',
							`invoice_status` int(50) NOT NULL DEFAULT '0',
  							`commission` decimal(10,2) NOT NULL,
  							`status` varchar(50) NOT NULL,
  							`created_at` date NOT NULL,
  							`updated_at` date NOT NULL,
  							PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
						");
				$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_payments` (
  							`id` int(11) NOT NULL AUTO_INCREMENT,
  							`invoice_id` int(11) NOT NULL,
  							`seller_id` int(11) NOT NULL,
  							`transaction_id` varchar(20) NOT NULL,
  							`amount` decimal(10,2) NOT NULL,
  							`payment_mode` varchar(50) NOT NULL,
  							`status` varchar(50) NOT NULL,
  							`created_at` date NOT NULL,
  							`updated_at` date NOT NULL,
  							PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
						");
				       $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_contact` (
							  `id` int(10) NOT NULL AUTO_INCREMENT,
							  `seller_id` int(10) NOT NULL,
							  `customer_id` int(10) NOT NULL,
							  `contact_from` int(10) NOT NULL,
							  `customer_name` varchar(150) NOT NULL,
							  `customer_email` varchar(150) NOT NULL,
							  `customer_message` text NOT NULL,
							  `created_at` datetime NOT NULL,
							  `updated_at` datetime NOT NULL,
							  `seen` int(11) NOT NULL DEFAULT '1',
							  PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
					");
					$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_categories_commission` (
  							`id` int(11) NOT NULL AUTO_INCREMENT,
  							`category_id` int(11) NOT NULL,
  							`commission` decimal(4,2) NOT NULL,
							`commison_fixed` double NOT NULL,
  							`seller_group` int(50) NOT NULL DEFAULT '1',
  							PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
						");	
						$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_shipping` (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`seller_id` int(11) NOT NULL,
						`shipping_country` int(11) NOT NULL,
						`zipcode_from` varchar(11) NOT NULL,
						`zipcode_to` varchar(11) NOT NULL,
						`shipping_price` decimal(15,2) NOT NULL,
						`weight_from` decimal(15,2) NOT NULL,
						`weight_to` decimal(15,2) NOT NULL,
						`max_days` int(11) NOT NULL,
						PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
					");
						$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_downloads` (
  							`id` int(11) NOT NULL AUTO_INCREMENT,
  							`download_id` int(11) NOT NULL,
  							`seller_id` int(11) NOT NULL,
  							PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
						");	
						// Subscription Plan
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_plan_invoice_status` (
				`status_id` int(11) NOT NULL AUTO_INCREMENT,
				`created_date` datetime NOT NULL,
				`modified_date` datetime NOT NULL,
				PRIMARY KEY (`status_id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
			");
			$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_plan_invoice_status_languge` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`status_id` int(11) NOT NULL,
				`language_id` int(11) NOT NULL,
				`status` varchar(30) NOT NULL,
				PRIMARY KEY (`id`),FOREIGN KEY (`status_id`) REFERENCES " . DB_PREFIX . "purpletree_vendor_plan_invoice_status(`status_id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
			");
			
			$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_plan` (
				`plan_id` int(11) NOT NULL AUTO_INCREMENT,
				`no_of_product` int(11) NOT NULL,
				`no_of_featured_product` int(10) NOT NULL,
				`no_of_category_featured_product` int(10) NOT NULL,
				`featured_store` int(10) NOT NULL,
				`joining_fee` decimal(15,4) NOT NULL,
				`subscription_price` decimal(15,4) NOT NULL,
				`validity` int(11) NOT NULL,
				`default_subscription_plan` int(1) NOT NULL,
				`status` int(1) NOT NULL,
				`created_date` datetime NOT NULL,
				`modified_date` datetime NOT NULL,
				PRIMARY KEY (`plan_id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
			");	
			
			$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_plan_description` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`plan_id` int(11) NOT NULL,
				`language_id` int(11) NOT NULL,
				`plan_name` varchar(30) NOT NULL,
				`plan_description` TEXT NOT NULL,
				`plan_short_description` TEXT NOT NULL,
				PRIMARY KEY (`id`),FOREIGN KEY (`plan_id`) REFERENCES " . DB_PREFIX . "purpletree_vendor_plan(`plan_id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
			");	
				
			$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_seller_plan` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`invoice_id` int(11) NOT NULL,
				`plan_id` int(11) NOT NULL,
				`seller_id` int(11) NOT NULL,
				`reminder` int(1) NOT NULL,
				`reminder1` int(1) NOT NULL,
				`reminder2` int(1) NOT NULL,
				`status` int(1) NOT NULL,
				`new_status` int(1) NOT NULL,
				`start_date` datetime NOT NULL,
				`end_date` datetime NOT NULL,
				`new_end_date` datetime NOT NULL,
				`created_date` datetime NOT NULL,
				`modified_date` datetime NOT NULL,
				PRIMARY KEY (`id`),FOREIGN KEY (`plan_id`) REFERENCES " . DB_PREFIX . "purpletree_vendor_plan(`plan_id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
			");	
			
			$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_plan_invoice` (
				`invoice_id` int(11) NOT NULL AUTO_INCREMENT,
				`seller_id` int(11) NOT NULL,
				`plan_id` int(11) NOT NULL,
				`payment_mode` varchar(30) NOT NULL,
				`status_id` int(11) NOT NULL,
				`created_date` datetime NOT NULL,
				PRIMARY KEY (`invoice_id`),FOREIGN KEY (`plan_id`) REFERENCES " . DB_PREFIX . "purpletree_vendor_plan(`plan_id`),FOREIGN KEY (`status_id`) REFERENCES " . DB_PREFIX . "purpletree_vendor_plan_invoice_status(`status_id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
			");	
			
			$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_plan_invoice_item` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`invoice_id` int(11) NOT NULL,
				`code` varchar(30) NOT NULL,
				`title` varchar(30) NOT NULL,
				`price` decimal(15,4) NOT NULL,
				`sort_order` int(11) NOT NULL,
				PRIMARY KEY (`id`),FOREIGN KEY (`invoice_id`) REFERENCES " . DB_PREFIX . "purpletree_vendor_plan_invoice(`invoice_id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
			");	

				$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_plan_invoice_history` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`invoice_id` int(11) NOT NULL,
				`status_id` int(11) NOT NULL,
				`payment_mode` varchar(30) NOT NULL,
				`transaction_id` varchar(30) NOT NULL,
				`comment` text NOT NULL,
				`created_date` datetime NOT NULL,
				`modified_date` datetime NOT NULL,
				PRIMARY KEY (`id`),FOREIGN KEY (`invoice_id`) REFERENCES " . DB_PREFIX . "purpletree_vendor_plan_invoice(`invoice_id`),FOREIGN KEY (`status_id`) REFERENCES " . DB_PREFIX . "purpletree_vendor_plan_invoice_status(`status_id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
			");
			$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_plan_subscription` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`seller_id` int(11) NOT NULL,
				`status_id` int(1) NOT NULL,
				`created_date` datetime NOT NULL,
				`modified_date` datetime NOT NULL,
				PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
			");
			$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_commission_invoice_items` (
  							`id` int(11) NOT NULL AUTO_INCREMENT,
							`link_id` int(50) NOT NULL,
  							`order_id` int(50) NOT NULL,
  							`product_id` int(50) NOT NULL,
  							`seller_id` int(50) NOT NULL,
  							`commission_fixed` int(50) NOT NULL DEFAULT '0',
  							`commission_percent` decimal(4,2) NOT NULL DEFAULT '0.00',
  							`commission_shipping` decimal(4,2) NOT NULL DEFAULT '0.00',
  							`total_commission` float(8,2) NOT NULL,
  							PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
						");
						$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_commission_invoice` (
  							`id` int(11) NOT NULL AUTO_INCREMENT,
							`total_amount` decimal(11,2) NOT NULL DEFAULT '0.00',
							`total_commission` decimal(11,2) NOT NULL DEFAULT '0.00',
							`total_pay_amount` decimal(11,2) NOT NULL DEFAULT '0.00',
  							`created_at` date NOT NULL,
  							PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
						");
					// Pending
			$querry = $this->db->query("SELECT * FROM " . DB_PREFIX . "purpletree_vendor_plan_invoice_status WHERE status_id = 1");
			if($querry->num_rows){} else {
			$this->db->query("INSERT INTO " . DB_PREFIX . "purpletree_vendor_plan_invoice_status SET status_id = '1', created_date = NOW(), modified_date = NOW()");
			
			$status_id = $this->db->getLastId();   
			
			$this->load->model('localisation/language');            
		    $languages = $this->model_localisation_language->getLanguages();
			
			foreach ($languages as $language_id ) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "purpletree_vendor_plan_invoice_status_languge SET status_id = '" . (int)$status_id . "', language_id = '" . (int)$language_id['language_id'] . "', status = 'Pending'");
			}
			}
			// Pending
			// Complete
			$querry1 = $this->db->query("SELECT * FROM " . DB_PREFIX . "purpletree_vendor_plan_invoice_status WHERE status_id = 2");
			if($querry1->num_rows){} else {
			$this->db->query("INSERT INTO " . DB_PREFIX . "purpletree_vendor_plan_invoice_status SET status_id = '2', created_date = NOW(), modified_date = NOW()");
			
			$status_id = $this->db->getLastId();   
			
			$this->load->model('localisation/language');            
		    $languages = $this->model_localisation_language->getLanguages();
			
			foreach ($languages as $language_id ) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "purpletree_vendor_plan_invoice_status_languge SET status_id = '" . (int)$status_id . "', language_id = '" . (int)$language_id['language_id'] . "', status = 'Complete'");
		}
			}
			// Complete
		// Subscription Plan
		       // Start seller blog
					$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_blog_post` (
  							`blog_post_id` int(11) NOT NULL AUTO_INCREMENT,
							`seller_id` int(11) NOT NULL,
  							`image` varchar(255) NOT NULL,  							
  							`sort_order` int(11) NOT NULL,
							`status` tinyint(1) NOT NULL,
							`created_at` datetime NOT NULL,
  							`updated_at` datetime NOT NULL, 							
							PRIMARY KEY (`blog_post_id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
						");
						
                  $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_blog_post_comment` (
  							`blog_comment_id` int(11) NOT NULL AUTO_INCREMENT,
  							`blog_post_id` int(11) NOT NULL,
  							`name` varchar(150) NOT NULL,
  							`email_id` varchar(150) NOT NULL,
  							`text` text NOT NULL,
							`status` tinyint(1) NOT NULL,
							`created_at` datetime NOT NULL,
  							`updated_at` datetime NOT NULL,
  							PRIMARY KEY (`blog_comment_id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
						");
						
				$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_blog_post_description` (
  							`blog_post_id` int(11) NOT NULL,
  							`language_id` int(11) NOT NULL,
  							`title` varchar(255) NOT NULL,
  							`description` text NOT NULL,
							`meta_title` varchar(255) NOT NULL,
  							`meta_description` varchar(255) NOT NULL,
  							`meta_keyword` varchar(255) NOT NULL,
  							`post_tags` varchar(255) NOT NULL)
							CHARACTER SET utf8 COLLATE utf8_unicode_ci
						");
						// End seller blog
				$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_payment_settlement_history` (
  							`id` int(11) NOT NULL AUTO_INCREMENT,
  							`invoice_id` int(11) NOT NULL,
  							`status_id` int(11) NOT NULL,
  							`payment_mode` varchar(50) NOT NULL,
  							`transaction_id` varchar(50) NOT NULL,
  							`comment` text NOT NULL,
							`created_date` datetime NOT NULL,
  							`modified_date` datetime NOT NULL, 	
							PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
						");	
				$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_enquiries` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`seller_id` int(11) NOT NULL,
					`contact_from` int(11) NOT NULL,
					`message` varchar(250) NOT NULL,
					`created_at` datetime NOT NULL,
					`updated_at` datetime NOT NULL, 	
					PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
				");
									

	$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_subscription_products` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`product_id` int(11) NOT NULL,
				`product_plan_id` int(11) NOT NULL,
				`featured_product_plan_id` int(11) NOT NULL,
				`category_featured_product_plan_id` varchar(255) NOT NULL,
				PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
		");	
    $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_template` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`product_id` int(11) NOT NULL,
				`status` tinyint(1) NOT NULL DEFAULT '0',		
				PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
		");   
/* 	$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_allowed_category` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`seller_id` int(11) NOT NULL,
				`category_id` varchar(255) NOT NULL,
				`type` tinyint(1) NOT NULL DEFAULT '1',				
				PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
		"); */	
    $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_template_products` (				
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`template_id` int(11) NOT NULL,
				`seller_id` int(11) NOT NULL,
				`quantity` int(4) NOT NULL DEFAULT '0',
				`price` decimal(15,4) NOT NULL DEFAULT '0.0000',
				`stock_status_id` int(11) NOT NULL,
				`subtract` tinyint(1) NOT NULL DEFAULT '1',
				`status` tinyint(1) NOT NULL DEFAULT '0',				
				PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
		");	
      $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_cart` (				
				`id` int(11) NOT NULL AUTO_INCREMENT,
                `cart_id` int(11) NOT NULL,
                `seller_id` int(11) NOT NULL,				
				PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
		");		

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "purpletree_vendor_adaptive_paykey` (
							`id` int(11) NOT NULL AUTO_INCREMENT,
							`order_id` int(11) NOT NULL,
							`payment_key` VARCHAR(50) NOT NULL,
							PRIMARY KEY (`id`)) CHARACTER SET utf8 COLLATE utf8_unicode_ci
						");			
			
	
			}
		}
			

			$this->load->model('user/user_group');

			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/' . $this->request->get['extension']);
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/' . $this->request->get['extension']);

			// Call install method if it exsits
			$this->load->controller('extension/module/' . $this->request->get['extension'] . '/install');

                $this->load->controller('module/' . $this->request->get['extension'] . '/install');
            

			$this->session->data['success'] = $this->language->get('text_success');
		} else {
			$this->session->data['error'] = $this->error['warning'];
		}
	
		$this->getList();
	}

	public function uninstall() {
		$this->load->language('extension/extension/module');

		$this->load->model('setting/extension');

		$this->load->model('setting/module');

		if ($this->validate()) {
			$this->model_setting_extension->uninstall('module', $this->request->get['extension']);

			/*******  Drop Tables Of Multivendor Extension when module un-installs  *******/
			if($this->request->get['extension']=="purpletree_multivendor"){
				$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "purpletree_vendor_stores'");	
				if($query->num_rows==1) 
				{
				$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_cart");
				$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_template");
				/* $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_allowed_category");*/
				$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_template_products");
				$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_downloads");
				$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_shipping");
				$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_stores");
				$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_products");
				$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_orders");
				$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_reviews");
				$this->db->query("DROP TABLE IF EXISTS ".DB_PREFIX."purpletree_vendor_commissions");
				$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_payments");
				$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_order_total");
		        $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_orders_history");
		
				$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_contact");
					
					//subscription plan_description
				
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_plan_description");
					
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_seller_plan");
					
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_plan_invoice_item");
					
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_plan_invoice_history");
					
					$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query LIKE '%seller_store_id%'");
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_categories_commission");
						
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_plan_invoice");
					
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_plan");
					
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_plan_invoice_status_languge");
					
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_plan_invoice_status");
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_plan_subscription");
					//Start Seller blog
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_blog_post_description");
                     $this->db->query("DELETE FROM ". DB_PREFIX ."seo_url WHERE query LIKE 'blog_post%'");	
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_blog_post_comment");
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_blog_post");                				
				//End seller blog
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_commission_invoice_items");
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_commission_invoice");
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_payment_settlement_history");
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_subscription_products");
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."pts_vendor_shipping");
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_social_links");
					$this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."purpletree_vendor_adaptive_paykey");
					
					
					
				}
			}
			

			$this->model_setting_module->deleteModulesByCode($this->request->get['extension']);

			// Call uninstall method if it exsits
			$this->load->controller('extension/module/' . $this->request->get['extension'] . '/uninstall');

                $this->load->controller('module/' . $this->request->get['extension'] . '/uninstall');
            

			$this->session->data['success'] = $this->language->get('text_success');
		}

		$this->getList();
	}
	
	public function add() {
		$this->load->language('extension/extension/module');

		$this->load->model('setting/extension');

		$this->load->model('setting/module');

		if ($this->validate()) {
			$this->load->language('module' . '/' . $this->request->get['extension']);
			
			$this->model_setting_module->addModule($this->request->get['extension'], $this->language->get('heading_title'));

			$this->session->data['success'] = $this->language->get('text_success');
		}

		$this->getList();
	}

	public function delete() {
		$this->load->language('extension/extension/module');

		$this->load->model('setting/extension');

		$this->load->model('setting/module');

		if (isset($this->request->get['module_id']) && $this->validate()) {
			$this->model_setting_module->deleteModule($this->request->get['module_id']);

			$this->session->data['success'] = $this->language->get('text_success');
		}
		
		$this->getList();
	}

	protected function getList() {
		$data['text_layout'] = sprintf($this->language->get('text_layout'), $this->url->link('design/layout', 'user_token=' . $this->session->data['user_token'], true));

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$extensions = $this->model_setting_extension->getInstalled('module');

		foreach ($extensions as $key => $value) {
			if (!is_file(DIR_APPLICATION . 'controller/extension/module/' . $value . '.php') && !is_file(DIR_APPLICATION . 'controller/module/' . $value . '.php')) {
				$this->model_setting_extension->uninstall('module', $value);

				unset($extensions[$key]);
				
				$this->model_setting_module->deleteModulesByCode($value);
			}
		}

		$data['extensions'] = array();

		// Create a new language container so we don't pollute the current one
		$language = new Language($this->config->get('config_language'));
		
		// Compatibility code for old extension folders
		$files = glob(DIR_APPLICATION . 'controller/extension/module/*.php');

		if ($files) {
			foreach ($files as $file) {
				$extension = basename($file, '.php');

				$this->load->language('extension/module/' . $extension, 'extension');

				$module_data = array();

				$modules = $this->model_setting_module->getModulesByCode($extension);

				foreach ($modules as $module) {
					if ($module['setting']) {
						$setting_info = json_decode($module['setting'], true);
					} else {
						$setting_info = array();
					}
					
					$module_data[] = array(
						'module_id' => $module['module_id'],
						'name'      => $module['name'],
						'status'    => (isset($setting_info['status']) && $setting_info['status']) ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
						'edit'      => $this->url->link('extension/module/' . $extension, 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $module['module_id'], true),
						'delete'    => $this->url->link('extension/extension/module/delete', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $module['module_id'], true)
					);
				}

				$data['extensions'][] = array(
					'name'      => $this->language->get('extension')->get('heading_title'),
					'status'    => $this->config->get('module_' . $extension . '_status') ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
					'module'    => $module_data,
					'install'   => $this->url->link('extension/extension/module/install', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $extension, true),
					'uninstall' => $this->url->link('extension/extension/module/uninstall', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $extension, true),
					'installed' => in_array($extension, $extensions),
					'edit'      => $this->url->link('extension/module/' . $extension, 'user_token=' . $this->session->data['user_token'], true)
				);
			}
		}

		$sort_order = array();

		foreach ($data['extensions'] as $key => $value) {
			$sort_order[$key] = $value['name'];
		}

		array_multisort($sort_order, SORT_ASC, $data['extensions']);

		$data['promotion'] = $this->load->controller('extension/extension/promotion');

		$this->response->setOutput($this->load->view('extension/extension/module', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/extension/module')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
