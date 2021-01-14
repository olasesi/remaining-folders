<?php
class ControllerCommonHeader extends Controller {
	public function index() {

			$direction = $this->language->get('direction');
			if ($direction=='rtl'){
			$this->document->addStyle('catalog/view/javascript/purpletree/bootstrap/css/bootstrap.min-a.css');
			$this->document->addStyle('catalog/view/theme/default/stylesheet/purpletree/custom-a.css'); 
			}else{
			$this->document->addStyle('catalog/view/javascript/purpletree/bootstrap/css/bootstrap.min.css'); 
			$this->document->addStyle('catalog/view/theme/default/stylesheet/purpletree/custom.css'); 
			}
			//$this->document->addScript('catalog/view/javascript/purpletree/common.js'); 
			
			//count notification 
			$this->load->model('extension/purpletree_multivendor/dashboard');
			$totalorders1 = $this->model_extension_purpletree_multivendor_dashboard->getCountSeen($this->customer->getId());
			$totalenqures1 = $this->model_extension_purpletree_multivendor_dashboard->getCountSeen1($this->customer->getId());
			$data['totalnotification'] = $totalorders1 + $totalenqures1;
			
       
		// Analytics
		$this->load->model('setting/extension');

		$data['analytics'] = array();

		$analytics = $this->model_setting_extension->getExtensions('analytics');

		foreach ($analytics as $analytic) {
			if ($this->config->get('analytics_' . $analytic['code'] . '_status')) {
				$data['analytics'][] = $this->load->controller('extension/analytics/' . $analytic['code'], $this->config->get('analytics_' . $analytic['code'] . '_status'));
			}
		}

		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

		if (is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
			$this->document->addLink($server . 'image/' . $this->config->get('config_icon'), 'icon');
		}

		$data['title'] = $this->document->getTitle();

		$data['base'] = $server;
		$data['description'] = $this->document->getDescription();
		$data['keywords'] = $this->document->getKeywords();
		$data['links'] = $this->document->getLinks();
		$data['styles'] = $this->document->getStyles();
		$data['scripts'] = $this->document->getScripts('header');

			if($this->config->get('module_purpletree_multivendor_include_jquery')){
			}else{
			array_unshift($data['scripts'] , "catalog/view/javascript/purpletree/jquery/jquery-2.1.1.min.js");
			}
			
		$data['lang'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');

		$data['name'] = $this->config->get('config_name');

		if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$data['logo'] = $server . 'image/' . $this->config->get('config_logo');
		} else {
			$data['logo'] = '';
		}

		$this->load->language('common/header');

		// Wishlist
		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');

			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), $this->model_account_wishlist->getTotalWishlist());
		} else {
			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), (isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0));
		}

		$data['text_logged'] = sprintf($this->language->get('text_logged'), $this->url->link('account/account', '', true), $this->customer->getFirstName(), $this->url->link('account/logout', '', true));
		
		$data['home'] = $this->url->link('common/home');
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['logged'] = $this->customer->isLogged();
		$data['account'] = $this->url->link('account/account', '', true);
		$data['register'] = $this->url->link('account/register', '', true);
		$data['login'] = $this->url->link('account/login', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['transaction'] = $this->url->link('account/transaction', '', true);
		$data['download'] = $this->url->link('account/download', '', true);
		$data['logout'] = $this->url->link('account/logout', '', true);
		$data['shopping_cart'] = $this->url->link('checkout/cart');
		$data['checkout'] = $this->url->link('checkout/checkout', '', true);
		$data['contact'] = $this->url->link('information/contact');
		$data['telephone'] = $this->config->get('config_telephone');
		
		$data['language'] = $this->load->controller('common/language');
		$data['currency'] = $this->load->controller('common/currency');
		$data['search'] = $this->load->controller('common/search');
		$data['cart'] = $this->load->controller('common/cart');

			/***** Show browse seller link in header ******/
			$this->load->language('account/ptsregister');
			 $data['direction'] = $this->language->get('direction');
			 $data['module_purpletree_multivendor_browse_sellers'] = $this->config->get('module_purpletree_multivendor_browse_sellers');
			 $data['module_purpletree_multivendor_include_jquery'] = $this->config->get('module_purpletree_multivendor_include_jquery');
			$data['text_browse_sellers'] = $this->language->get('text_browse_sellers');
			$data['text_seller_register'] = $this->language->get('text_seller_register');
			$data['seller_panel_link'] = '';
			$data['text_seller_panel'] = $this->language->get('text_seller_panel');
			$data['browse_seller_link'] = $this->url->link('extension/account/purpletree_multivendor/sellers');
			$data['seller_register_link'] = $this->url->link('extension/account/purpletree_multivendor/sellerlogin');
			///// seller panel/////
			$this->load->model('extension/purpletree_multivendor/vendor');

			   $store_detail = $this->model_extension_purpletree_multivendor_vendor->isSeller($this->customer->getId());	
				if($store_detail){
						if($store_detail['is_removed']==1){
							$data['seller_panel_link'] = $this->url->link('extension/account/purpletree_multivendor/sellerstore/becomeseller', '', true);
						} else {
						if($store_detail['store_status']==1 && $store_detail[  'multi_store_id'] == $this->config->get('config_store_id')){
							$data['seller_panel_link'] = $this->url->link('extension/account/purpletree_multivendor/dashboardicons', '', true);
						}elseif($store_detail[  'multi_store_id'] != $this->config->get('config_store_id')){
						  $data['seller_panel_link'] = $this->url->link('extension/account/purpletree_multivendor/sellerlogin');
						   $data['text_seller_panel'] = $this->language->get('text_seller_register');
						} else {
							$data['seller_panel_link'] = $this->url->link('extension/account/purpletree_multivendor/sellerstore/becomeseller', '', true);
						}
						}
				}
			//////End seller panel///
			if ($this->customer->isLogged()) {
			$this->load->model('extension/purpletree_multivendor/vendor');
			   $store_detail = $this->model_extension_purpletree_multivendor_vendor->isSeller($this->customer->getId());	
				if($store_detail){
					$data['sellerlogged'] = 'seller';
				}
		    }
			
		$data['menu'] = $this->load->controller('common/menu');

		return $this->load->view('common/header', $data);
	}
}
