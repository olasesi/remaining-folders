<?php
class ControllerCommonHome extends Controller {
	public function index() {

                // iSense SEO Meta
                $meta_title = $this->config->get('config_meta_title');
                $meta_description = $this->config->get('config_meta_description');
                $meta_keywords = $this->config->get('config_meta_keyword');

                $meta_title = is_array($meta_title) && isset($meta_title[$this->config->get('config_language_id')]) ? $meta_title[$this->config->get('config_language_id')] : $meta_title;
                $meta_description = is_array($meta_description) && isset($meta_description[$this->config->get('config_language_id')]) ? $meta_description[$this->config->get('config_language_id')] : $meta_description;
        		$meta_keywords = is_array($meta_keywords) && isset($meta_keywords[$this->config->get('config_language_id')]) ? $meta_keywords[$this->config->get('config_language_id')] : $meta_keywords;
			
		
                $this->document->setTitle($meta_title);
			
		
                $this->document->setDescription($meta_description);
			
		
                $this->document->setKeywords($meta_keywords);
			

		if (isset($this->request->get['route'])) {
			$this->document->addLink($this->config->get('config_url'), 'canonical');
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/home', $data));
	}
}
