<?php
class ControllerStartupSeoUrl extends Controller {
	public function index() {
		
			try {
				$subfolder_check = $this->db->query("SELECT `id` FROM `" . DB_PREFIX . "seo_module_settings` WHERE `key` = 'subfolder_prefixes' AND `value` = '1' AND `store_id` = '" . (int)$this->config->get('config_store_id') . "' LIMIT 1")->num_rows;
			} catch (Exception $e) {
                $subfolder_check = false;
            }
            try { 
                $default_lang_prefix = $this->db->query("SELECT `id` FROM `" . DB_PREFIX . "seo_module_settings` WHERE `key` = 'default_lang_prefix' AND `value` = '1' AND `store_id` = '" . (int)$this->config->get('config_store_id') . "' LIMIT 1")->num_rows;
            } catch (Exception $e) {
                $default_lang_prefix = false;
            }
			
		// Add rewrite to url class
		if ($this->config->get('config_seo_url')) {
			$this->url->addRewrite($this);
		}

		// Decode URL

			//ocmultivendor SEO
			if ($this->config->get('config_seo_url')) {
				if(isset($this->request->get['route']) && $this->request->get['route'] != 'extension/account/purpletree_multivendor/sellerstore/storeview') {
		$routeee = (explode("extension/account/purpletree_multivendor/",$this->request->get['route']));
				 if (array_key_exists("1",$routeee)) {
					 if(!empty($this->request->get) && $this->request->server['REQUEST_METHOD'] != 'POST') {
							unset($this->request->get['route']);
							$urlappend = '';
							$ccc = 0;
						foreach($this->request->get as $keyy => $valuee) {
							if($ccc == 0) {
								$urlappend .= '?';
							} else {
								$urlappend .= '&';
							}
							$urlappend .= $keyy.'='.$valuee;
							$ccc++;
						}
							header('Location: '.$this->config->get('config_url').'ocmultivendor/'.$routeee[1].$urlappend, true, 301);
							exit;
						}
				 }
		} 
		}
		//ocmultivendor SEO
		

                if ($subfolder_check) {
                    $lquery = $this->db->query("SELECT * FROM " . DB_PREFIX . "language;");			
                    $lparts = isset($this->request->get['_route_']) ? explode('/', $this->request->get['_route_']) : array();
                    $lcode  = isset($lparts[0]) ? $lparts[0] : '';

                    $default_lang_code  = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "setting` WHERE `key` = 'config_language'")->row['value'];
                    $active_lang_prefix = $active_lang_code = isset($this->session->data['language']) ? $this->session->data['language'] : $default_lang_code;
                    $subfolder_prefixes_alias = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "seo_module_settings` WHERE `key` = '" . $this->db->escape('subfolder_prefixes_alias'). "' AND `store_id` = '" . (int)$this->config->get('config_store_id') . "' LIMIT 1");
                    $subfolder_prefixes_alias = json_decode($subfolder_prefixes_alias->row['value'], true);

                    if (in_array($lcode, $subfolder_prefixes_alias)) {
                        $active_lang_prefix = $active_lang_code = array_search($lcode, $subfolder_prefixes_alias);
                    }
                    if (isset($subfolder_prefixes_alias[$active_lang_prefix])) {
                        $active_lang_prefix = $subfolder_prefixes_alias[$active_lang_prefix];
                    }

                    if ($default_lang_prefix) {
                        $table_check = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX ."seo_module_settings'")->num_rows;

                        if ($table_check) {
                            $redirect_active_lang_prefix = $this->db->query("SELECT `id` FROM `" . DB_PREFIX . "seo_module_settings` WHERE `key` = 'redirect_default_lang_prefix' AND `value` = '1' AND `store_id` = '" . (int)$this->config->get('config_store_id') . "' LIMIT 1")->num_rows;

                            if ($redirect_active_lang_prefix) {
                                if (isset($this->request->get['_route_']) || empty($this->request->get)) {
                                    $missing_lang_code = true;
                                    if ($lcode == $active_lang_prefix) {
                                        $missing_lang_code = false;
                                    }

                                    if ($missing_lang_code) {
                                        $base = HTTP_SERVER;
                                        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
                                            $base = HTTPS_SERVER;
                                        }

                                        if (isset($this->request->get['_route_'])) {
                                            $this->response->redirect($base . $active_lang_prefix . '/' . $this->request->get['_route_']);
                                        } else {
                                            $this->response->redirect($base . $active_lang_prefix . '/');
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (isset($this->request->get['_route_'])) {
                        foreach ($lquery->rows as $language) {
                            if ($language['code'] == $active_lang_code) {
                                $this->session->data['language'] = $language['code']; 
                                
                                $this->language = new Language($language['code']);
                                $this->language->load($language['code']); 

                                $this->registry->set('language', $this->language); 
                                $this->config->set('config_language_id', $language['language_id']); 	

                                if ($default_lang_prefix || $default_lang_code != $active_lang_code) {
                                    $this->request->get['_route_'] = substr($this->request->get['_route_'], strlen($active_lang_prefix . '/'));
                                }
                            }
                        }
                    }
                }
            
		if (isset($this->request->get['_route_'])) {
			$parts = explode('/', $this->request->get['_route_']);

			// remove any empty arrays from trailing
			if (utf8_strlen(end($parts)) == 0) {
				array_pop($parts);
			}

			foreach ($parts as $part) {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($part) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

				if ($query->num_rows) {
					$url = explode('=', $query->row['query']);

					if ($url[0] == 'product_id') {
						$this->request->get['product_id'] = $url[1];
					}

if ($url[0] == 'seller_store_id') {
						$this->request->get['seller_store_id'] = $url[1];
					}
			
					if ($url[0] == 'category_id') {
						if (!isset($this->request->get['path'])) {
							$this->request->get['path'] = $url[1];
						} else {
							$this->request->get['path'] .= '_' . $url[1];
						}
					}

					if ($url[0] == 'manufacturer_id') {
						$this->request->get['manufacturer_id'] = $url[1];
					}

					if ($url[0] == 'information_id') {
						$this->request->get['information_id'] = $url[1];
					}

					if ($query->row['query'] && $url[0] != 'information_id' && $url[0] != 'manufacturer_id' && $url[0] != 'category_id' && $url[0] != 'product_id' && $url[0] != 'seller_store_id') {
			
						$this->request->get['route'] = $query->row['query'];
					}
				} else {
					$this->request->get['route'] = 'error/not_found';

					break;
				}
			}


			//ocmultivendor SEO
			$routeee = (explode("ocmultivendor/",$this->request->get['_route_']));
				 if (array_key_exists("1",$routeee)) {
					 $this->request->get['route'] = 'extension/account/purpletree_multivendor/'.$routeee[1];
				 }
				 //ocmultivendor SEO
			
			if (!isset($this->request->get['route'])) {
				if (isset($this->request->get['product_id'])) {
					$this->request->get['route'] = 'product/product';
				} elseif (isset($this->request->get['path'])) {
					$this->request->get['route'] = 'product/category';
				} elseif (isset($this->request->get['manufacturer_id'])) {
					$this->request->get['route'] = 'product/manufacturer/info';
				} elseif (isset($this->request->get['information_id'])) {
					$this->request->get['route'] = 'information/information';
				}elseif (isset($this->request->get['seller_store_id'])) {
					$this->request->get['route'] = 'extension/account/purpletree_multivendor/sellerstore/storeview';
			
				} else {
			        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "url_alias WHERE keyword = '" . $this->db->escape($this->request->get['_route_']) . "'");
                    if ($query->num_rows) {
                        $this->request->get['route'] = $query->row['query'];
                    }
				}
			}
		}

			else {

            $table_check = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX ."seo_module_settings'")->num_rows;
            
                if ($table_check) {
                    $redirect_to_seo_links_check = $this->db->query("SELECT `id` FROM `" . DB_PREFIX . "seo_module_settings` WHERE `key` = 'redirect_to_seo_links' AND `value` = '1' AND `store_id` = '" . (int)$this->config->get('config_store_id') . "' LIMIT 1")->num_rows;
                
                    if ($redirect_to_seo_links_check) {
                        $redirect_to_seo_links_check = true;
                    } else {
                        $redirect_to_seo_links_check = false;
                    }
                } else {
                    $redirect_to_seo_links_check = false;
                }

            if (isset($this->request->get['route']) && !empty($this->request->get['route']) && $redirect_to_seo_links_check) {
                $request_uri = isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
                $request_uri_segment = '';
                $request_uri_segment_pos = strpos($request_uri, '&');

                if ($request_uri_segment_pos) {
                    $request_uri_segment = substr($request_uri, $request_uri_segment_pos + 1);
                }
                
                $SEO_link = $this->url->link($this->request->get['route'], $request_uri_segment, 'SSL');
                $isSEOFriendy = strpos($SEO_link, 'route=') ? false : true;
                
                if ($isSEOFriendy){
                    $this->response->redirect($SEO_link);
                }
            }
        }
			
	}

	public function rewrite($link) {

            try {
                $subfolder_check = $this->db->query("SELECT `id` FROM `" . DB_PREFIX . "seo_module_settings` WHERE `key` = 'subfolder_prefixes' AND `value` = '1' AND `store_id` = '" . (int)$this->config->get('config_store_id') . "' LIMIT 1")->num_rows;
            } catch (Exception $e) {
                $subfolder_check = false;
            }
			try { 
                $default_lang_prefix = $this->db->query("SELECT `id` FROM `" . DB_PREFIX . "seo_module_settings` WHERE `key` = 'default_lang_prefix' AND `value` = '1' AND `store_id` = '" . (int)$this->config->get('config_store_id') . "' LIMIT 1")->num_rows;
            } catch (Exception $e) {
                $default_lang_prefix = false;
            }

            if ($subfolder_check) {
                $active_lang_code = isset($this->session->data['language']) ? $this->session->data['language'] : $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "setting` WHERE `key` = 'config_language'")->row['value'];
                $subfolder_prefixes_alias = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "seo_module_settings` WHERE `key` = '" . $this->db->escape('subfolder_prefixes_alias'). "' AND `store_id` = '" . (int)$this->config->get('config_store_id') . "' LIMIT 1");
                $subfolder_prefixes_alias = json_decode($subfolder_prefixes_alias->row['value'], true);
            }
			

			try { // SEO Backpack Unify Script
				$unify_check = $this->db->query("SELECT `id` FROM `" . DB_PREFIX . "seo_module_settings` WHERE `key` = 'unify_urls' AND `value` = '1' AND `store_id` = '" . (int)$this->config->get('config_store_id') . "' LIMIT 1")->num_rows;
			} catch (Exception $e) {
				$unify_check = false;
			}
			
		$url_info = parse_url(str_replace('&amp;', '&', $link));

		$url = '';

		$data = array();

		parse_str($url_info['query'], $data);
			
            if (isset($data['route']) && $data['route'] == 'common/home') { //Common Home Fix
            	$is_common_home = true;
			} else {
				$is_common_home = false;
			}

			if ($is_common_home) {
				unset($data['route']);

				$query = '';

				if ($data) {
					foreach ($data as $key => $value) {
						$query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
					}

					if ($query) {
						$query = '?' . str_replace('&', '&amp;', trim($query, '&'));
					}
				}

				if ($subfolder_check) {
                    $language_string = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "setting` WHERE `key` = 'config_language'")->row['value'];

                    if (isset($this->session->data['language'])) {
                        if($this->session->data['language'] <> $language_string || $default_lang_prefix){
                            $url_prefix = '/' . $this->session->data['language']. '/';

                            if (!empty($subfolder_prefixes_alias) && isset($subfolder_prefixes_alias[$active_lang_code])) {
                                $url_prefix = '/' . $subfolder_prefixes_alias[$active_lang_code] . '/';
                            }
                        } else {
                        	$url_prefix = '';
                        }
                    } else {
                        $url_prefix = '';
                    }

                   return $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url_prefix . $url . $query;
               } else {
                   $new_link = $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url . $query;
                   return $new_link;
               }
			}
			
		//start of custom code
		if ($data['route'] == 'checkout/cart'){
			$url .= '/checkout_cart';
		}


		//end of custom code
		foreach ($data as $key => $value) {
			if (isset($data['route'])) {

			//ocmultivendor SEO
			$route11 = '';
				 $routeee = (explode("extension/account/purpletree_multivendor",$data['route']));
				 if (array_key_exists("1",$routeee)) {
					  $route11 = $routeee[1]; 
				 }
				 //ocmultivendor SEO
			
			    $q=$this->db->query("SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE query='$value'");
                if($q->row){
                    $url .= '/' . $q->row['keyword'];
                }
				if (($data['route'] == 'product/product' && $key == 'product_id') || (($data['route'] == 'product/manufacturer/info' || $data['route'] == 'product/product') && $key == 'manufacturer_id') || ($data['route'] == 'information/information' && $key == 'information_id')) {
					$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = '" . $this->db->escape($key . '=' . (int)$value) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

					if ($query->num_rows && $query->row['keyword']) {
						
			if ($unify_check) { // SEO Backpack Unify Script
                $url = '/' . $query->row['keyword'];
            } else {
                $url .= '/' . $query->row['keyword'];
            }
			

						unset($data[$key]);
				    } elseif ($data['route'] != 'information/information/agree' && $data['route'] != 'common/home') { // new code start
					    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE (`query` = '" . $this->db->escape($key . '=' . (int)$value) . "' OR `query` = '" . filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH) . "') AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");
				
    					if ($query->num_rows) {
    						
			if ($unify_check) { // SEO Backpack Unify Script
                $url = '/' . $query->row['keyword'];
            } else {
                $url .= '/' . $query->row['keyword'];
            }
			
    						
    						unset($data[$key]);
    					} 
				    } elseif ($data['route'] == 'common/home') {
    					$url = '/';
    					
    					unset($data[$key]);
    					}
				    } elseif ($data['route'] == 'extension/account/purpletree_multivendor/sellerstore/storeview' && $key == 'seller_store_id') {
                    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = '" . $this->db->escape($key . '=' . (int)$value) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

                    if ( $query->num_rows && $query->row['keyword'] ) {
                        $url .=  '/' . $query->row['keyword'];
 
                        unset( $data[$key] );
                    }
					//ocmultivendor SEO
					} elseif ($route11 != '' && $data['route'] != 'extension/account/purpletree_multivendor/sellerstore/storeview') {
						$url .=  '/ocmultivendor' . $route11;
 
                        unset( $data[$key] );
						//ocmultivendor SEO
                } elseif ($key == 'path') {
					$categories = explode('_', $value);

					foreach ($categories as $category) {
						$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = 'category_id=" . (int)$category . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

						if ($query->num_rows && $query->row['keyword']) {
							
			if ($unify_check) { // SEO Backpack Unify Script
                $url = '/' . $query->row['keyword'];
            } else {
                $url .= '/' . $query->row['keyword'];
            }
			
						} else {
							$url = '';

							break;
						}
					}

					unset($data[$key]);
				    } else {
				        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "url_alias WHERE `query` = '" . $this->db->escape($data['route']) . "'");
                        if ($query->num_rows) {
                            
			if ($unify_check) { // SEO Backpack Unify Script
                $url = '/' . $query->row['keyword'];
            } else {
                $url .= '/' . $query->row['keyword'];
            }
			
                            unset($data[$key]);
                        }
				    }
			}
		}

		if (($url) && ($url <> '/'.$this->session->data['language'])){
			unset($data['route']);

			$query = '';

			if ($data) {
				foreach ($data as $key => $value) {
					$query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
				}

				if ($query) {
					$query = '?' . str_replace('&', '&amp;', trim($query, '&'));
				}
			}

			
				if ($subfolder_check) {
                    $language_string = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "setting` WHERE `key` = 'config_language'")->row['value'];

                    if (isset($this->session->data['language'])) {
                        if($this->session->data['language'] <> $language_string || $default_lang_prefix){
                            $url_prefix = '/' . $this->session->data['language'];

                            if (!empty($subfolder_prefixes_alias) && isset($subfolder_prefixes_alias[$active_lang_code])) {
                                $url_prefix = '/' . $subfolder_prefixes_alias[$active_lang_code];
                            }
                        } else {
                        	$url_prefix = '';
                        }
                    } else {
                        $url_prefix = '';
                    }

                   return $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url_prefix . $url . $query;
               } else {
                   return $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url . $query;
               }
            
		} else {
			return $link;
		}
	}
}
