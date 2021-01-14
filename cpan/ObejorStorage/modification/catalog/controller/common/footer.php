<?php
class ControllerCommonFooter extends Controller {
	public function index() {

					$data['seller_chat'] = '';
			if(NULL !== $this->config->get('module_purpletree_multivendor_status')){
			if($this->config->get('module_purpletree_multivendor_status')){
				if(NULL !== $this->config->get('module_purpletree_multivendor_allow_live_chat')) {
				if($this->config->get('module_purpletree_multivendor_allow_live_chat')) {	
				if(isset($this->session->data['seller_sto_page'])){
					$seller_store_idd = $this->session->data['seller_sto_page'];
					  $query = $this->db->query("SELECT `store_live_chat_enable` ,`store_live_chat_code` FROM " . DB_PREFIX . "purpletree_vendor_stores WHERE `id` = '" . (int)$seller_store_idd . "'");
					    if ($query->num_rows) {
							if($query->row['store_live_chat_enable']) {
								if($query->row['store_live_chat_code'] != '') {
										unset($this->session->data['seller_sto_page']);
									$data['seller_chat'] = '1';
								}
							}
						}
				}
				}
				}
			}
			}
			
		$this->load->language('common/footer');

		$this->load->model('catalog/information');

		$data['informations'] = array();

		foreach (strpos($this->config->get('config_template'), 'journal2') === 0 ? array() : $this->model_catalog_information->getInformations() as $result) {
			if ($result['bottom']) {
				$data['informations'][] = array(
					'title' => $result['title'],
					'href'  => $this->url->link('information/information', 'information_id=' . $result['information_id'])
				);
			}
		}

		$data['contact'] = $this->url->link('information/contact');
		$data['return'] = $this->url->link('account/return/add', '', true);
		$data['sitemap'] = $this->url->link('information/sitemap');
		$data['tracking'] = $this->url->link('information/tracking');
		$data['manufacturer'] = $this->url->link('product/manufacturer');
		$data['voucher'] = $this->url->link('account/voucher', '', true);
		$data['affiliate'] = $this->url->link('affiliate/login', '', true);
		$data['special'] = $this->url->link('product/special');
		$data['account'] = $this->url->link('account/account', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['newsletter'] = $this->url->link('account/newsletter', '', true);

		$data['powered'] = sprintf($this->language->get('text_powered'), $this->config->get('config_name'), date('Y', time()));

		// Whos Online
		if ($this->config->get('config_customer_online')) {
			$this->load->model('tool/online');

			if (isset($this->request->server['REMOTE_ADDR'])) {
				$ip = $this->request->server['REMOTE_ADDR'];
			} else {
				$ip = '';
			}

			if (isset($this->request->server['HTTP_HOST']) && isset($this->request->server['REQUEST_URI'])) {
				$url = ($this->request->server['HTTPS'] ? 'https://' : 'http://') . $this->request->server['HTTP_HOST'] . $this->request->server['REQUEST_URI'];
			} else {
				$url = '';
			}

			if (isset($this->request->server['HTTP_REFERER'])) {
				$referer = $this->request->server['HTTP_REFERER'];
			} else {
				$referer = '';
			}

			$this->model_tool_online->addOnline($ip, $this->customer->getId(), $url, $referer);
		}

		$data['scripts'] = $this->document->getScripts('footer');
		

			$data['smartSupp'] = $this->load->controller('extension/module/smartsupp');
            
		return $this->load->view('common/footer', $data);
	}
}
