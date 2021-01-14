<?php

		if (! function_exists('array_column')) {
			function array_column(array $input, $columnKey, $indexKey = null) {
				$array = array();
				foreach ($input as $value) {
					if ( !array_key_exists($columnKey, $value)) {
						trigger_error("Key \"$columnKey\" does not exist in array");
						return false;
					}
					if (is_null($indexKey)) {
						$array[] = $value[$columnKey];
					}
					else {
						if ( !array_key_exists($indexKey, $value)) {
							trigger_error("Key \"$indexKey\" does not exist in array");
							return false;
						}
						if ( ! is_scalar($value[$indexKey])) {
							trigger_error("Key \"$indexKey\" does not contain scalar value");
							return false;
						}
						$array[$value[$indexKey]] = $value[$columnKey];
					}
				}
				return $array;
			}
		}
class ControllerProductCategory extends Controller {
	public function index() {
 $resultsid = array(); 
$this->document->addStyle('catalog/view/javascript/purpletree/jquery/owl-carousel/owl.carousel.css');
	        $this->document->addScript('catalog/view/javascript/purpletree/jquery/owl-carousel/owl.carousel.min.js');  
		$this->load->language('product/category');

		$this->load->model('catalog/category');

		$this->load->model('catalog/product');

                $this->load->model('journal2/product');
            

		$this->load->model('tool/image');

		if (isset($this->request->get['filter'])) {
			$filter = $this->request->get['filter'];
		} else {
			$filter = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'p.sort_order';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		if (isset($this->request->get['limit'])) {
			$limit = (int)$this->request->get['limit'];
		} else {
			$limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		if (isset($this->request->get['path'])) {
			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$path = '';

			$parts = explode('_', (string)$this->request->get['path']);

			$category_id = (int)array_pop($parts);

			foreach ($parts as $path_id) {
				if (!$path) {
					$path = (int)$path_id;
				} else {
					$path .= '_' . (int)$path_id;
				}

				$category_info = $this->model_catalog_category->getCategory($path_id);

				if ($category_info) {
					$data['breadcrumbs'][] = array(
						'text' => $category_info['name'],
						'href' => $this->url->link('product/category', 'path=' . $path . $url)
					);
				}
			}
		} else {
			$category_id = 0;
		}

		$category_info = $this->model_catalog_category->getCategory($category_id);
			
            $this->config->load('isenselabs/isenselabs_seo');
            $isense_module_path = $this->config->get('isenselabs_seo_path');
            $isense_call_model  = $this->config->get('isenselabs_seo_model');
            $this->load->model($isense_module_path);
            
            $breadcrumb_check = $this->{$isense_call_model}->getSetting('breadcrumb_categories');
            
            if ($category_info && $breadcrumb_check) {
                $cats = $this->{$isense_call_model}->getPathCategoryPage($category_id);
                $path = '';
                $parts = explode('_', (string)$cats);
                foreach ($parts as $path_id) {
                    $cat_info = $this->model_catalog_category->getCategory($path_id);
                    if (isset($cat_info) && $cat_info['category_id']!= $category_id) {
                        $data['breadcrumbs'][] = array(
                            'text'      => $cat_info['name'],
                            'href'      => $this->url->link('product/category', 'path=' . $cat_info['category_id']),
                            'separator' => $this->language->get('text_separator')
                        );
                    }
                }
            }
			

		if ($category_info) {
			$this->document->setTitle($category_info['meta_title']);
			$this->document->setDescription($category_info['meta_description']);
			$this->document->setKeywords($category_info['meta_keyword']);

			$data['heading_title'] = $category_info['name'];

			$data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));

			// Set the last category breadcrumb
			$data['breadcrumbs'][] = array(
				'text' => $category_info['name'],
				'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'])
			);

			if ($category_info['image']) {
				$data['thumb'] = $this->model_tool_image->resize($category_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height'));
			} else {
				$data['thumb'] = '';
			}

			$data['description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');
			$data['compare'] = $this->url->link('product/compare');

			$url = '';

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['categories'] = array();

			$results = $this->model_catalog_category->getCategories($category_id);

			foreach ($results as $result) {
				$filter_data = array(
					'filter_category_id'  => $result['category_id'],
					'filter_sub_category' => true
				);

				$data['categories'][] = array(
					'name' => $result['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
					'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '_' . $result['category_id'] . $url)
				);
			}

			$data['products'] = array();

			$filter_data = array(
				'filter_category_id' => $category_id,
				'filter_filter'      => $filter,
				'sort'               => $sort,
				'order'              => $order,
				'start'              => ($page - 1) * $limit,
				'limit'              => $limit
			);

			$product_total = $this->model_catalog_product->getTotalProducts($filter_data);

			$results = $this->model_catalog_product->getProducts($filter_data);
 $resultsid = array(); 

			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$result['special']) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}


                $date_end = false;
                if (strpos($this->config->get('config_template'), 'journal2') === 0 && $special && $this->journal2->settings->get('show_countdown', 'never') !== 'never') {
                    $this->load->model('journal2/product');
                    $date_end = $this->model_journal2_product->getSpecialCountdown($result['product_id']);
                    if ($date_end === '0000-00-00') {
                        $date_end = false;
                    }
                }
            

                $additional_images = $this->model_catalog_product->getProductImages($result['product_id']);

                $image2 = false;

                if (count($additional_images) > 0) {
                    $image2 = $this->model_tool_image->resize($additional_images[0]['image'], $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height'));
                }
            
 $resultsid[] = $result['product_id']; 
				$data['products'][] = array(
					'product_id'  => $result['product_id'],
					'thumb'       => $image,

                'thumb2'       => $image2,
            

                'labels'        => $this->model_journal2_product->getLabels($result['product_id']),
            
					'name'        => $result['name'],
					'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,

                'date_end'       => $date_end,
            
					'tax'         => $tax,
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $result['rating'],
					'href'        => $this->url->link('product/product', 'path=' . $this->request->get['path'] . '&product_id=' . $result['product_id'] . $url),
					'from_abroad' => $result['from_abroad']
				);
			}

			$url = '';

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['sorts'] = array();

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_default'),
				'value' => 'p.sort_order-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.sort_order&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_asc'),
				'value' => 'pd.name-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=pd.name&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_desc'),
				'value' => 'pd.name-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=pd.name&order=DESC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_asc'),
				'value' => 'p.price-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.price&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_desc'),
				'value' => 'p.price-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.price&order=DESC' . $url)
			);

			if ($this->config->get('config_review_status')) {
				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_desc'),
					'value' => 'rating-DESC',
					'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=rating&order=DESC' . $url)
				);

				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_asc'),
					'value' => 'rating-ASC',
					'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=rating&order=ASC' . $url)
				);
			}

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_asc'),
				'value' => 'p.model-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.model&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_desc'),
				'value' => 'p.model-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.model&order=DESC' . $url)
			);

			$url = '';

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			$data['limits'] = array();

			$limits = array_unique(array($this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit'), 25, 50, 75, 100));

			sort($limits);

			foreach($limits as $value) {
				$data['limits'][] = array(
					'text'  => $value,
					'value' => $value,
					'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url . '&limit=' . $value)
				);
			}

			$url = '';

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$pagination = new Pagination();
			$pagination->total = $product_total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url . '&page={page}');

			$data['pagination'] = $pagination->render();

			$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

			// http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html
			if ($page == 1) {
			    $this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id']), 'canonical');
			} else {
				$this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . '&page='. $page), 'canonical');
			}
			
			if ($page > 1) {
			    $this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . (($page - 2) ? '&page='. ($page - 1) : '')), 'prev');
			}

			if ($limit && ceil($product_total / $limit) > $page) {
			    $this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . '&page='. ($page + 1)), 'next');
			}

			$data['sort'] = $sort;
			$data['order'] = $order;
			$data['limit'] = $limit;

			$data['continue'] = $this->url->link('common/home');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

 //  Start Seller category featured  products
				$this->load->language('account/ptsregister');
            $data['text_seller_featured_products'] = $this->language->get('text_seller_featured_products');
	        $this->load->model('setting/extension');
            $installed_modules = $this->model_setting_extension->getExtensions('module');
			  
		/* get active skin */
		$data['no_Of_product'] = 4;
		if (strpos($this->config->get('config_template'), 'journal2') === 0){
				
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "journal2_config WHERE store_id = '0' AND `key` = 'active_skin'");
			$theme['journal2_config']['active_skin'] = $query->num_rows ? $query->row['value'] : 1;		
			if($theme['journal2_config']['active_skin']==1){
				$data['no_Of_product'] = 3;
			}elseif($theme['journal2_config']['active_skin']==2 || $theme['journal2_config']['active_skin']==3 || $theme['journal2_config']['active_skin']==4 || $theme['journal2_config']['active_skin']==5 || $theme['journal2_config']['active_skin']== 6|| $theme['journal2_config']['active_skin']==7 || $theme['journal2_config']['active_skin']==8 || $theme['journal2_config']['active_skin']==9 || $theme['journal2_config']['active_skin']== 12 || $theme['journal2_config']['active_skin']==14 || $theme['journal2_config']['active_skin']==15){
				$data['no_Of_product'] = 4;
			}elseif($theme['journal2_config']['active_skin']==10 || $theme['journal2_config']['active_skin']==11 || $theme['journal2_config']['active_skin']==13){
				$data['no_Of_product'] = 5;
			}
		}
		$data['text_tax'] = $this->language->get('text_tax');
		$data['button_cart'] = $this->language->get('button_cart');
		$data['button_wishlist'] = $this->language->get('button_wishlist');
		$data['button_compare'] = $this->language->get('button_compare');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');

		$data['productss'] = array();			
			
			$productss = $this->model_catalog_category->getCategoryFeatured();			
		

			 if(!empty($productss)) {
			 $prodssarray = array();
			 foreach ($productss as $product) {
				 if(in_array($product['product_id'],$resultsid)){
				  if(!in_array($product['product_id'],$prodssarray)) {
				  $prodssarray[] = $product['product_id'];
				$product_infoo = $this->model_catalog_product->getProduct($product['product_id']);
				$imagee2 = false;
				if (strpos($this->config->get('config_template'), 'journal2') === 0){ 
				    $this->load->model('journal2/product');
				    $additional_imagess = $this->model_catalog_product->getProductImages($product['product_id']);               
					if (count($additional_imagess) > 0) {
                    $imagee2 = $this->model_tool_image->resize($additional_imagess[0]['image'], $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height'));
                }
			   }

				if ($product_infoo) {
					if ($product_infoo['image']) {
						$imagee = $this->model_tool_image->resize($product_infoo['image'], '200' , '200');
						
					} else {
						$imagee = $this->model_tool_image->resize('placeholder.png', '200' , '200');
					}

					if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
						$pricee = $this->currency->format($this->tax->calculate($product_infoo['price'], $product_infoo['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					} else {
						$pricee = false;
					}

					if ((float)$product_infoo['special']) {
						$speciall = $this->currency->format($this->tax->calculate($product_infoo['special'], $product_infoo['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					} else {
						$speciall = false;
					}

					if ($this->config->get('config_tax')) {
						$tax = $this->currency->format((float)$product_infoo['special'] ? $product_infoo['special'] : $product_infoo['price'], $this->session->data['currency']);
					} else {
						$tax = false;
					}

					if ($this->config->get('config_review_status')) {
						$rating = $product_infoo['rating'];
					} else {
						$rating = false;
					}
				$date_end = false;
				$labelss ='';
				if (strpos($this->config->get('config_template'), 'journal2') === 0){
				 $this->load->model('journal2/product');
				 $labelss = (array_search('journal2', array_column($installed_modules, 'code')) !== False)?$this->model_journal2_product->getLabels($product['product_id']):'';
				 }
                if (strpos($this->config->get('config_template'), 'journal2') === 0 && $special && $this->journal2->settings->get('show_countdown', 'never') !== 'never') {
                   // $this->load->model('journal2/product');
                    $date_end = $this->model_journal2_product->getSpecialCountdown($result['product_id']);
                    if ($date_end === '0000-00-00') {
                        $date_end = false;
                    }
                }
            
					 $shortdescription = utf8_substr(strip_tags(html_entity_decode($product_infoo['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get($this->config->get('config_theme') . '_product_description_length' )) . '..';
					 
						 if(strlen($shortdescription) > 25){
						   $shortdescription =  substr($shortdescription, 0, 25).'...';
						}
				
					$data['productss'][] = array(
						'product_id'  => $product_infoo['product_id'],
						'thumb'       => $imagee,
						'thumb2'       => $imagee2,
						'name'        => $product_infoo['name'],
						'labels'        => $labelss,
						'description' => $shortdescription,
						'price'       => $pricee,
						'special'     => $speciall,
						'date_end'       => $date_end,
						'tax'         => $tax,
						'rating'      => $rating,
						'href'        => $this->url->link('product/product', 'product_id=' .      $product_infoo['product_id'])
					);
				}
				}
			}
		 }
	}
//  End Seller category featured  products 
			$this->response->setOutput($this->load->view('product/category', $data));
		} else {
			$url = '';

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('product/category', $url)
			);

$this->load->controller('extension/module/isenselabs_seo/notFoundPageHandler');
			$this->document->setTitle($this->language->get('text_error'));

			$data['continue'] = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}
}
