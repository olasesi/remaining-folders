<?php
class ControllerJournal2Settings extends Controller {

    private static $CACHEABLE = null;

    private $css_settings = array();
    private $js_settings = array();

    protected $data = array();

    protected function render() {
        if (version_compare(VERSION, '2.2', '<')) {
            $this->template = $this->config->get('config_template') . '/template/' . $this->template;
        }

        $this->template = str_replace($this->config->get('config_template') . '/template/' . $this->config->get('config_template') . '/template/', $this->config->get('config_template') . '/template/', $this->template);

        if (version_compare(VERSION, '3', '>=')) {
            return $this->load->view(str_replace('.tpl', '', $this->template), $this->data);
        }

        return Front::$IS_OC2 ? $this->load->view($this->template, $this->data) : parent::render();
    }

    public function index() {
        $this->load->model('journal2/db');
        $this->load->model('tool/image');

        // admin mode
        if (version_compare(VERSION, '2.1', '<')) {
            $this->load->library('user');
        }
        if (version_compare(VERSION, '2.2', '>=')) {
            $this->user = new Cart\User($this->registry);
        } else {
            $this->user = new User($this->registry);
        }
        if ($this->user->isLogged()) {
            $this->journal2->html_classes->addClass('is-admin');
        }

        // customer
        if (version_compare(VERSION, '2.2', '>=')) {
            $this->customer = new Cart\Customer($this->registry);
        }
        if ($this->customer->isLogged()) {
            $this->journal2->html_classes->addClass('is-customer');
        } else {
            $this->journal2->html_classes->addClass('is-guest');
        }

        // get current store config settings
        $db_config_settings = $this->model_journal2_db->getConfigSettings();
        foreach ($db_config_settings as $key => $value) {
            $this->journal2->settings->set('config_' . $key, $value);
        }

        // get active skin
        $skin_id = $this->journal2->settings->get('config_active_skin', 1);

        if (!$this->model_journal2_db->skinExists($skin_id)) {
            $skin_id = 1;
        }

        $developer_mode = $this->journal2->settings->get('config_system_settings.developer_mode', '1');
        if (!$developer_mode) {
            self::$CACHEABLE = true;
        }

        $this->journal2->cache->setDeveloperMode($developer_mode);

                // START: NitroPack.io (https://nitropack.io)
                if (is_file(DIR_CONFIG . 'nitropackio/compatibility.php')) {
                    $nitropack_is_worker_request = false;

                    nitropackio\hook\Init::executeNitroPackIfConnected(function($nitropack) use (&$nitropack_is_worker_request) {
                        $nitropack_is_worker_request = $nitropack->isWorkerRequest();
                    });

                    if ($nitropack_is_worker_request) {
                        $this->journal2->settings->set('config_system_settings.minify_css', false);
                        $this->journal2->settings->set('config_system_settings.minify_js', false);
                        $this->journal2->settings->set('config_system_settings.minify_html', false);
                    }
                }
                // END: NitroPack.io (https://nitropack.io)
            
        if (!$this->journal2->html_classes->hasClass('ie9')) {
            $this->journal2->minifier->setMinifyCss((bool)$this->journal2->settings->get('config_system_settings.minify_css'));
        }

        $route = isset($this->request->get['route']) ? $this->request->get['route'] : null;
        if ($route !== null && strpos($route, 'seller/') === 0) {
            $this->journal2->minifier->setMinifyJs(false);
        } else {
            $this->journal2->minifier->setMinifyJs((bool)$this->journal2->settings->get('config_system_settings.minify_js'));
        }

        $this->journal2->cache->setSkinId($skin_id);

        $this->journal2->html_classes->addClass('skin-' . $skin_id);

        $cache_property = 'settings';

        $cache = $this->journal2->cache->get($cache_property);

        if ($cache === null || self::$CACHEABLE !== true) {
            // load current skin settings
            $db_skin_settings = $this->model_journal2_db->getSkinSettings($skin_id);

            // all settings
            $all_settings = $this->journal2->settings->getAll();

            // parse settings
            foreach ($db_skin_settings as $key => $value) {
                if (!isset($all_settings[$key])) {
                    trigger_error('Journal Error: Could not parse setting ' . $key . '!');
                    exit();
                }
                if (is_scalar($value)) {
                    $value = array(
                        'value' => $value
                    );
                }
                $value['name'] = $key;
                $value['type'] = $all_settings[$key]['type'];
                if (isset($all_settings[$key]['selector'])) {
                    $value['css'] = array(
                        'selector' => $all_settings[$key]['selector'],
                        'property' => $all_settings[$key]['property']
                    );
                    $this->addCssSettings($value);
                }
                $this->addCpSettings($value);
            }

            $cached = array(
                'settings'  => $this->journal2->settings->getAllSettings(),
                'fonts'     => $this->journal2->google_fonts->getAllFonts(),
                'css'       => $this->css_settings
            );

            if (self::$CACHEABLE === true) {
                $this->journal2->cache->set($cache_property, json_encode($cached));
            }
        } else {
            $cache = json_decode($cache, true);
            $this->css_settings = $cache['css'];
            $this->journal2->settings->setAllSettings($cache['settings']);
            $this->journal2->google_fonts->setAllFonts($cache['fonts']);
        }

        foreach ($db_config_settings as $key => $value) {
            $this->journal2->settings->set('config_' . $key, $value);
        }

        // process some settings
        $this->processSettings();

        // assign css settings
        $this->journal2->css_settings = $this->css_settings;

        // rtl
        $this->journal2->settings->set('rtl', $this->language->get('direction') === 'rtl');

        // LazyLoad dummy image
        $this->journal2->settings->set('product_dummy_image', Journal2Utils::resizeImage($this->model_tool_image, 'data/journal2/transparent.png', $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height'), 'crop'));
        $this->journal2->settings->set('product_no_image'   , Journal2Utils::resizeImage($this->model_tool_image, 'data/journal2/no_image_large.png', $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height')));

        // modernizr
        $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/modernizr/modernizr.min.js', 'header');

        // add jquery + jquery ui
        $this->journal2->minifier->addStyle('catalog/view/theme/journal2/css/j-strap.css');
        if (version_compare(VERSION, '2', '>=')) {
            //$this->journal2->minifier->addStyle('catalog/view/javascript/bootstrap/css/bootstrap.min.css');
            $this->journal2->minifier->addStyle('catalog/view/javascript/font-awesome/css/font-awesome.min.css');
            $this->journal2->minifier->addScript('catalog/view/javascript/jquery/jquery-2.1.1.min.js', 'header');
            $this->journal2->minifier->addScript('catalog/view/javascript/bootstrap/js/bootstrap.min.js', 'header');
            $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/jquery/jquery-migrate-1.2.1.min.js', 'header');
            $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/jquery.ui/jquery-ui-slider.min.js', 'header');
            $this->journal2->minifier->addStyle('catalog/view/theme/journal2/lib/jquery.ui/jquery-ui-slider.min.css');
        } else {
            $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/jquery/jquery-1.8.3.min.js', 'header');
            $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/jquery.ui/jquery-ui-1.8.24.min.js', 'header');
            $this->journal2->minifier->addStyle('catalog/view/javascript/jquery/ui/themes/ui-lightness/jquery-ui-1.8.16.custom.css');
        }

        // opencart scripts
        $this->journal2->minifier->addScript('catalog/view/javascript/common.js', 'header');
        $this->journal2->minifier->addScript('catalog/view/javascript/jquery/jquery.total-storage.min.js', 'header');

        // v1541 compatibility
        if (VERSION === '1.5.4' || VERSION === '1.5.4.1') {
            $this->journal2->minifier->addStyle('catalog/view/javascript/jquery/colorbox/colorbox.css');
            $this->journal2->minifier->addScript('catalog/view/javascript/jquery/colorbox/jquery.colorbox.js', 'header');
        }

        // add jquery tabs
        $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/jquery.tabs/tabs.js', 'header');

        // add swiper
        $this->journal2->minifier->addStyle('catalog/view/theme/journal2/lib/swiper/css/swiper.css');
        $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/swiper/js/swiper.jquery.js');

        // infinite scroll
        $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/ias/jquery-ias.min.js');

        // intense
        $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/intense/intense.min.js', 'header');

        // add lightgallery
        $this->journal2->minifier->addStyle('catalog/view/theme/journal2/lib/lightgallery/css/lightgallery.min.css');
        $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/lightgallery/js/lightgallery.js', 'header');
        $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/lightgallery/js/lg-thumbnail.min.js', 'footer');

        // add magnific popup
        $this->journal2->minifier->addStyle('catalog/view/theme/journal2/lib/magnific-popup/magnific-popup.css');
        $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/magnific-popup/jquery.magnific-popup.js', 'header');

        // add other plugins
        $this->document->addScript('catalog/view/theme/journal2/lib/lazy/jquery.lazy.1.6.min.js');
        $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/actual/jquery.actual.min.js', 'header');
        $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/hover-intent/jquery.hoverIntent.min.js', 'footer');
        $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/countdown/jquery.countdown.js', 'header');
        $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/pnotify/jquery.pnotify.min.js', 'footer');
        $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/vide/jquery.vide.min.js', 'footer');
        if (!$this->journal2->html_classes->hasClass('mobile') && !$this->journal2->html_classes->hasClass('tablet')) {
            $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/respond/respond.js', 'footer');
            $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/image-zoom/jquery.imagezoom.min.js', 'header');
        }

        // autocomplete
        if ((Journal2Utils::getDevice() === 'desktop' && $this->journal2->settings->get('search_autocomplete', '1') == '1') ||
            (Journal2Utils::getDevice() === 'tablet' && $this->journal2->settings->get('search_autocomplete_tablet', '1') == '1') ||
            (Journal2Utils::getDevice() === 'phone' && $this->journal2->settings->get('search_autocomplete_phone', '1') == '1')) {
            $this->journal2->minifier->addScript('catalog/view/theme/journal2/lib/autocomplete2/jquery.autocomplete2.min.js', 'footer');
        }

        // category image width/height
        $this->journal2->settings->set('config_image_width', $this->config->get('config_image_category_width'), 250);
        $this->journal2->settings->set('config_image_height', $this->config->get('config_image_category_height'), 250);

        // checkout
        if ($this->journal2->settings->get('one_page_status', 'default') === 'one-page') {
            $this->journal2->settings->set('journal_checkout', true);
        }

        // notification buttons
        if (version_compare(VERSION, '2', '>=')) {
            $this->load->language('common/cart');
        } else {
            $this->language->load('module/cart');
        }
        $html = '';
        $html .= '<div class="notification-buttons">';
        $html .= '<a class="button notification-cart" href="' . Journal2Utils::link('checkout/cart') . '">' . trim(preg_replace('/\s+/', ' ', $this->language->get('text_cart'))) . '</a>';
        $html .= '<a class="button notification-checkout" href="' . Journal2Utils::link('checkout/checkout', '', 'SSL') . '">' . trim(preg_replace('/\s+/', ' ', $this->language->get('text_checkout'))) . '</a>';
        $html .= '</div>';
        $this->journal2->settings->set('notification_buttons', $html);
    }

    public function columns() {
        $cols = 0;
        if ($this->journal2->page->hasModules('column_left')) {
            $this->journal2->settings->set('config_columns_left', 1);
            $cols ++;
        } else {
            $this->journal2->settings->set('config_columns_left', 0);
        }
        if ($this->journal2->page->hasModules('column_right')) {
            $this->journal2->settings->set('config_columns_right', 1);
            $cols ++;
        } else {
            $this->journal2->settings->set('config_columns_right', 0);
        }
        if ($cols == 1){
            $this->journal2->html_classes->addClass('one-column');
        }
        if ($cols == 2){
            $this->journal2->html_classes->addClass('two-columns');
        }
        $this->journal2->settings->set('config_columns_count', $cols);
        $this->journal2->settings->set('product_grid_classes', Journal2Utils::getProductGridClasses($this->journal2->settings->get('category_page_products_per_row'), $this->journal2->settings->get('site_width', 1024), $cols));
        $this->journal2->settings->set('related_products_grid_classes', Journal2Utils::getProductGridClasses($this->journal2->settings->get('related_products_items_per_row'), $this->journal2->settings->get('site_width', 1024), $cols));

        // product views
        if (($this->journal2->page->getType() === 'product' || $this->journal2->page->getType() === 'quickview')) {
            $this->load->model('journal2/product');
            if ($this->journal2->settings->get('product_page_options_views')) {
                $this->journal2->settings->set('product_views', $this->model_journal2_product->getProductViews($this->journal2->page->getId()));
            }
            if ($this->journal2->settings->get('product_page_options_sold')) {
                $text = $this->journal2->settings->get('product_page_options_sold_text', ' Product(s) Sold');
                $count = '<span>' . $this->model_journal2_product->getProductSoldCount($this->journal2->page->getId()) . '</span>';
                if (strpos($text, '%s') !== FALSE) {
                    $text = sprintf($text, $count);
                } else {
                    $text = $count . $text;
                }
                $this->journal2->settings->set('product_sold', $text);
            }
        }
    }

    private function addCssSettings($setting) {
        /* selector */
        $md5_selector = md5($setting['css']['selector']);
        if (!isset($this->css_settings[$md5_selector])) {
            $this->css_settings[$md5_selector] = array(
                'selector'      => $setting['css']['selector'],
                'properties'    => array()
            );
        }

        /* hover selector */
        $hover_selector = isset($setting['css']['hover_selector']) ? $setting['css']['hover_selector'] : $setting['css']['selector'] . ':hover';
        $md5_hover_selector = md5($hover_selector);
        if (!isset($this->css_settings[$md5_hover_selector])) {
            $this->css_settings[$md5_hover_selector] = array(
                'selector'      => $hover_selector,
                'properties'    => array()
            );
        }

        /* expand values */
        switch($setting['type']) {
            case 'j-opt-color':
            case 'j-opt-color-gradient':
                if (Journal2Utils::getProperty($setting, 'value.gradient') !== null) {
                    $this->css_settings[$md5_selector]['properties'][] = preg_replace( '/\s*(?!<\")\/\*[^\*]+\*\/(?!\")\s*/' , '' , Journal2Utils::getProperty($setting, 'value.gradient'));
                } elseif (Journal2Utils::getProperty($setting, 'value.color')) {
                    $this->css_settings[$md5_selector]['properties'][] = $this->parseCssValue($setting['css']['property'], Journal2Utils::getColor(Journal2Utils::getProperty($setting, 'value.color')));
                }
                break;
            case 'j-opt-text':
                if (Journal2Utils::getProperty($setting, 'value.text') !== null) {
                    $this->css_settings[$md5_selector]['properties'][] = $this->parseCssValue($setting['css']['property'], Journal2Utils::getProperty($setting, 'value.text'));
                }
                break;
            case 'j-opt-icon':

                switch (Journal2Utils::getProperty($setting, 'value.icon_type')) {
                    case 'icon':
                        if (Journal2Utils::getProperty($setting, 'value.icon.icon')) {
                            $this->css_settings[$md5_selector]['properties'][] = $this->parseCssValue("content: '%s'", str_replace(array('&#x', ';'), array('\\', ''), Journal2Utils::getProperty($setting, 'value.icon.icon')));
                        }
                        if ($fs = Journal2Utils::getProperty($setting, 'value.options.font_size')) {
                            if ($fs !== '---') {
                                $this->css_settings[$md5_selector]['properties'][] = $this->parseCssValue('font-size', $fs);
                            }
                        }
                        if (Journal2Utils::getProperty($setting, 'value.options.color.value.color')) {
                            $color = Journal2Utils::getColor(Journal2Utils::getProperty($setting, 'value.options.color.value.color'));
                            $this->css_settings[$md5_selector]['properties'][] = $this->parseCssValue('color', $color);
                            $this->journal2->settings->set($setting['name'] . ':color', $color);
                        }
                        if (Journal2Utils::getProperty($setting, 'value.options.top')) {
                            $this->css_settings[$md5_selector]['properties'][] = $this->parseCssValue('top', Journal2Utils::getProperty($setting, 'value.options.top') . 'px');
                        }
                        if (Journal2Utils::getProperty($setting, 'value.options.left')) {
                            $this->css_settings[$md5_selector]['properties'][] = $this->parseCssValue('left', Journal2Utils::getProperty($setting, 'value.options.left') . 'px');
                        }
                        break;
                    case 'image':
                        if (Journal2Utils::getProperty($setting, 'value.image')) {
                            $this->css_settings[$md5_selector]['properties'][] = $this->parseCssValue('content', 'url("image/' . Journal2Utils::getProperty($setting, 'value.image') . '")');
                        }
                        if (Journal2Utils::getProperty($setting, 'value.options.font_size')) {
                            $this->css_settings[$md5_selector]['properties'][] = $this->parseCssValue('font-size', Journal2Utils::getProperty($setting, 'value.options.font_size'));
                        }
                        if (Journal2Utils::getProperty($setting, 'value.options.color.value.color')) {
                            $this->css_settings[$md5_selector]['properties'][] = $this->parseCssValue('color', Journal2Utils::getColor(Journal2Utils::getProperty($setting, 'value.options.color.value.color')));
                        }
                        if (Journal2Utils::getProperty($setting, 'value.options.top')) {
                            $this->css_settings[$md5_selector]['properties'][] = $this->parseCssValue('top', Journal2Utils::getProperty($setting, 'value.options.top') . 'px');
                        }
                        if (Journal2Utils::getProperty($setting, 'value.options.left')) {
                            $this->css_settings[$md5_selector]['properties'][] = $this->parseCssValue('left', Journal2Utils::getProperty($setting, 'value.options.left') . 'px');
                        }
                        if (Journal2Utils::getProperty($setting, 'value.options.width')) {
                            $this->css_settings[$md5_selector]['properties'][] = $this->parseCssValue('width', Journal2Utils::getProperty($setting, 'value.options.width') . 'px');
                        }
                        if (Journal2Utils::getProperty($setting, 'value.options.height')) {
                            $this->css_settings[$md5_selector]['properties'][] = $this->parseCssValue('height', Journal2Utils::getProperty($setting, 'value.options.height') . 'px');
                        }
                        break;
                }
                break;
            case 'j-opt-image':
                if (Journal2Utils::getProperty($setting, 'value.image') !== null) {
                    $this->css_settings[$md5_selector]['properties'][] = $this->parseCssValue($setting['css']['property'], 'image/' . Journal2Utils::getProperty($setting, 'value.image'));
                }
                break;
            case 'j-opt-select':
                if (Journal2Utils::getProperty($setting, 'value') !== null) {
                    $this->css_settings[$md5_selector]['properties'][] = $this->parseCssValue($setting['css']['property'], Journal2Utils::getProperty($setting, 'value'));
                }
                break;
            case 'j-opt-font':
                $fv = Journal2Utils::getProperty($setting, 'value.v');
                $fg = false;
                if (Journal2Utils::getProperty($setting, 'value.font_type') === 'google') {
                    $fg = true;
                    $this->journal2->google_fonts->add(Journal2Utils::getProperty($setting, 'value.font_name'), Journal2Utils::getProperty($setting, 'value.font_subset'), Journal2Utils::getProperty($setting, 'value.font_weight'));
                    $weight = filter_var(Journal2Utils::getProperty($setting, 'value.font_weight'), FILTER_SANITIZE_NUMBER_INT);
                    $this->css_settings[$md5_selector]['properties'][] = 'font-weight: ' . ($weight ? $weight : 400) . $setting['css']['property'];
                    $this->css_settings[$md5_selector]['properties'][] = 'font-family: "' . Journal2Utils::getProperty($setting, 'value.font_name') . '"' . $setting['css']['property'];
                }
                if (Journal2Utils::getProperty($setting, 'value.font_type') === 'system') {
                    if ($fv !== '2') {
                        $this->css_settings[$md5_selector]['properties'][] = 'font-weight: ' . Journal2Utils::getProperty($setting, 'value.font_weight') . $setting['css']['property'];
                    }
                    $this->css_settings[$md5_selector]['properties'][] = 'font-family: ' . Journal2Utils::getProperty($setting, 'value.font_family') . $setting['css']['property'];
                }
                if ($fv === '2') {
                    if (!$fg && ($value = Journal2Utils::getProperty($setting, 'value.font_weight'))) {
                        $this->css_settings[$md5_selector]['properties'][] = 'font-weight: ' . $value . $setting['css']['property'];
                    }
                    if ($value = Journal2Utils::getProperty($setting, 'value.font_style')) {
                        $this->css_settings[$md5_selector]['properties'][] = 'font-style: ' . $value . $setting['css']['property'];
                    }

                    $value = Journal2Utils::getProperty($setting, 'value.font_size');

                    if (Journal2Utils::getDevice() === 'phone') {
                        $value2 = Journal2Utils::getProperty($setting, 'value.font_size_mobile');
                        if ($value2 && $value2 !== '---') {
                            $value = $value2;
                        }
                    }

                    if ($value && $value !== '---') {
                        $this->css_settings[$md5_selector]['properties'][] = 'font-size: ' . $value . $setting['css']['property'];
                    }

                    if ($value = Journal2Utils::getProperty($setting, 'value.text_transform')) {
                        $this->css_settings[$md5_selector]['properties'][] = 'text-transform: ' . $value . $setting['css']['property'];
                    }
                    if ($value = Journal2Utils::getProperty($setting, 'value.letter_spacing')) {
                        $this->css_settings[$md5_selector]['properties'][] = 'letter-spacing: ' . $value . 'px' . $setting['css']['property'];
                    }
                } else {
                    if (Journal2Utils::getProperty($setting, 'value.font_type') !== 'none') {
                        $this->css_settings[$md5_selector]['properties'][] = 'font-style: ' . Journal2Utils::getProperty($setting, 'value.font_style') . $setting['css']['property'];
                        $this->css_settings[$md5_selector]['properties'][] = 'font-size: ' . Journal2Utils::getProperty($setting, 'value.font_size') . $setting['css']['property'];
                        $this->css_settings[$md5_selector]['properties'][] = 'text-transform: ' . Journal2Utils::getProperty($setting, 'value.text_transform') . $setting['css']['property'];
                        if (Journal2Utils::getProperty($setting, 'value.letter_spacing')) {
                            $this->css_settings[$md5_selector]['properties'][] = 'letter-spacing: ' . Journal2Utils::getProperty($setting, 'value.letter_spacing') . 'px' . $setting['css']['property'];
                        }
                    }
                }
                if (Journal2Utils::getProperty($setting, 'value.color.value.color')) {
                    $color = Journal2Utils::getColor(Journal2Utils::getProperty($setting, 'value.color.value.color'));
                    $this->css_settings[$md5_selector]['properties'][] = 'color: ' . $color . $setting['css']['property'];
                    $this->journal2->settings->set($setting['name'] . ':color', $color);
                }
                break;
            case 'j-opt-background':
                foreach (Journal2Utils::getBackgroundCssProperties($setting) as $sett) {
                    $this->css_settings[$md5_selector]['properties'][] = $sett;
                    $parts = explode(':', $sett, 2);
                    if (count($parts) > 1 && strlen(trim($parts[0])) && strlen(trim($parts[1]))) {
                        $this->journal2->settings->set($setting['name'] . ':' . trim($parts[0]), trim($parts[1]));
                    }
                }
                break;
            case 'j-opt-border':
                foreach (Journal2Utils::getBorderCssProperties($setting) as $sett) {
                    $this->css_settings[$md5_selector]['properties'][] = $sett;
                    $parts = explode(':', $sett);
                    if (count($parts) > 1 && strlen(trim($parts[0])) && strlen(trim($parts[1]))) {
                        $this->journal2->settings->set($setting['name'] . ':' . trim($parts[0]), trim($parts[1]));
                    }
                }
                break;
            case 'j-opt-shadow':
                foreach (Journal2Utils::getShadowCssProperties($setting) as $sett) {
                    $this->css_settings[$md5_selector]['properties'][] = $sett;
                    $parts = explode(':', $sett);
                    if (count($parts) > 1 && strlen(trim($parts[0])) && strlen(trim($parts[1]))) {
                        $this->journal2->settings->set($setting['name'] . ':' . trim($parts[0]), trim($parts[1]));
                    }
                }
                break;
        }
    }

    private function parseCssValue($property, $value) {
        return strpos($property, '%s') === FALSE ? $property . ': ' . $value : str_replace('%s',$value, $property);
    }

    private function addCpSettings($setting) {
        switch($setting['type']) {
            case 'j-opt-color':
            case 'j-opt-color-gradient':
                if (Journal2Utils::getProperty($setting, 'value.color') !== null) {
                    $this->journal2->settings->set($setting['name'], Journal2Utils::getColor(Journal2Utils::getProperty($setting, 'value.color')));
                }
                break;
            case 'j-opt-text':
                if (Journal2Utils::getProperty($setting, 'value.text') !== null) {
                    $this->journal2->settings->set($setting['name'], Journal2Utils::getProperty($setting, 'value.text'));
                }
                break;
            case 'j-opt-textarea':
                if (Journal2Utils::getProperty($setting, 'value.text') !== null) {
                    $this->journal2->settings->set($setting['name'], Journal2Utils::getProperty($setting, 'value.text'));
                }
                break;
            case 'j-opt-text-lang':
                if (Journal2Utils::getProperty($setting, 'value') !== null) {
                    $this->journal2->settings->set($setting['name'], Journal2Utils::getProperty($setting, 'value.' . $this->config->get('config_language_id')));
                }
                break;
            case 'j-opt-image':
                if (Journal2Utils::getProperty($setting, 'value.image') !== null) {
                    $this->journal2->settings->set($setting['name'], Journal2Utils::getProperty($setting, 'value.image'));
                }
                break;
            case 'j-opt-select':
                if (Journal2Utils::getProperty($setting, 'value') !== null) {
                    $this->journal2->settings->set($setting['name'], Journal2Utils::getProperty($setting, 'value'));
                }
                break;
            case 'j-opt-font':
            case 'j-opt-border':
            break;
            case 'j-opt-background':
                foreach (Journal2Utils::getBackgroundCssProperties($setting) as $sett) {
                    $parts = explode(':', $sett, 2);
                    $this->journal2->settings->set($setting['name'] . ':' . $parts[0], $parts[1]);
                }
            break;
            case 'j-opt-shadow':
                foreach (Journal2Utils::getShadowCssProperties($setting) as $sett) {
                    $parts = explode(':', $sett);
                    $this->journal2->settings->set($setting['name'], $parts[1]);
                }
                break;
            case 'j-opt-icon':
                $icon = Journal2Utils::getIconOptions2(Journal2Utils::getProperty($setting, 'value'));
                $this->journal2->settings->set($setting['name'], $icon);
                break;
            case 'j-opt-items-per-row':
                $this->journal2->settings->set($setting['name'], Journal2Utils::getProperty($setting, 'value'));
                break;
            case 'j-opt-slider':
                $this->journal2->settings->set($setting['name'], Journal2Utils::getProperty($setting, 'value'));
                break;
            case 'j-opt-sharethis':
                $share_this_data = json_decode(file_get_contents(DIR_SYSTEM . 'journal2/data/share_this.json'), true);
                $items = array();
                foreach ($setting as $k => $v) {
                    if (is_numeric($k)) {
                        $items[] = array(
                            'class' => 'st_' . str_replace('st_li_', '', $v['id']),
                            'name'  => $share_this_data[$v['id']]['name']
                        );
                    }
                }
                $this->journal2->settings->set('config_share_buttons', $items);
                break;
			case 'j-opt-search':
				$this->journal2->settings->set($setting['name'], Journal2Utils::getProperty($setting, 'id'));
				break;
            default:
                trigger_error($setting['type'] . ' not parsed!');
        }
        return false;
    }

    private function processSettings() {
        if (version_compare(VERSION, '2.1', '<')) {
            $this->load->library('user');
        }
        if (version_compare(VERSION, '2.2', '>=')) {
            $this->user = new \Cart\User($this->registry);
        } else {
            $this->user = new User($this->registry);
        }
        if ($this->config->get('config_maintenance') && !$this->user->isLogged()) {
            $this->journal2->html_classes->addClass('maintenance-mode');
        }
        if ($this->journal2->settings->get('responsive_design')) {
            $this->journal2->html_classes->addClass('responsive-layout');
        }
        if($this->journal2->settings->get('sticky_bottom_bar', 'top') === 'bottom' && $this->journal2->settings->get('hide_menus_on_phone', 'on') === 'on'){
            $this->journal2->html_classes->addClass('bottom-menu-bar');
        }
        if($this->journal2->settings->get('header_type', 'default') === 'default'){
            $this->journal2->html_classes->addClass('default-header');
        }
        if($this->journal2->settings->get('infinite_scroll', '1') === '1'){
            $this->journal2->html_classes->addClass('infinite-scroll');
        }
        if($this->journal2->settings->get('header_type', 'default') === 'compact'){
            $this->journal2->html_classes->addClass('default-header compact-header');
        }
        if($this->journal2->settings->get('header_type', 'default') === 'extended'){
            $this->journal2->html_classes->addClass('default-header slim-header');
        }
        if($this->journal2->settings->get('header_type', 'default') === 'center'){
            $this->journal2->html_classes->addClass('center-header');
        }
        if($this->journal2->settings->get('header_type', 'default') === 'mega'){
            $this->journal2->html_classes->addClass('center-header mega-header');
        }
        if($this->journal2->settings->get('catalog_header_search', 'block') === 'none'){
            $this->journal2->html_classes->addClass('catalog-search');
        }
        if($this->journal2->settings->get('catalog_header_lang2', 'visible') === 'hidden'){
            $this->journal2->html_classes->addClass('catalog-language');
        }
        if($this->journal2->settings->get('catalog_header_curr2', 'visible') === 'hidden'){
            $this->journal2->html_classes->addClass('catalog-currency');
        }
        if($this->journal2->settings->get('catalog_header_cart2', 'visible') === 'hidden'){
            $this->journal2->html_classes->addClass('catalog-cart');
        }

        if($this->journal2->settings->get('language_display', 'flag') === 'full'){
            $this->journal2->html_classes->addClass('lang-full');
        }
        if($this->journal2->settings->get('language_display_mobile', 'flag') === 'full'){
            $this->journal2->html_classes->addClass('lang-full-mobile');
        }
        if($this->journal2->settings->get('currency_display', 'symbol') === 'full'){
            $this->journal2->html_classes->addClass('currency-full');
        }
        if($this->journal2->settings->get('currency_display_mobile', 'symbol') === 'full'){
            $this->journal2->html_classes->addClass('currency-full-mobile');
        }

        if($this->journal2->settings->get('language_display', 'flag') === 'flag'){
            $this->journal2->html_classes->addClass('lang-flag');
        }
        if($this->journal2->settings->get('language_display_mobile', 'flag') === 'flag'){
            $this->journal2->html_classes->addClass('lang-flag-mobile');
        }
        if($this->journal2->settings->get('currency_display', 'symbol') === 'symbol'){
            $this->journal2->html_classes->addClass('currency-symbol');
        }
        if($this->journal2->settings->get('currency_display_mobile', 'symbol') === 'symbol'){
            $this->journal2->html_classes->addClass('currency-symbol-mobile');
        }

        if($this->journal2->settings->get('hide_menus_on_phone', 'on') === 'off'){
            $this->journal2->html_classes->addClass('no-top-on-mobile');
        }
        if($this->journal2->settings->get('hide_secondary_on_phone', 'on') === 'off'){
            $this->journal2->html_classes->addClass('no-secondary-on-mobile');
        }

        if($this->journal2->settings->get('footer_collapse_column', 'on') === 'on'){
            $this->journal2->html_classes->addClass('collapse-footer-columns');
        }
        if($this->journal2->settings->get('filter_columns_mobile', '1') === '0'){
            $this->journal2->html_classes->addClass('filter-columns-mobile');
        }
        if($this->journal2->settings->get('product_grid_soft_shadow', 'none') === '1px 1px 0px rgba(0,0,0,.04)' || $this->journal2->settings->get('cs_product_grid_soft_shadow', 'none') === '1px 1px 0px rgba(0,0,0,.04)' || $this->journal2->settings->get('carousel_product_grid_soft_shadow', 'none') === '1px 1px 0px rgba(0,0,0,.04)'){
            $this->journal2->html_classes->addClass('soft-shadow');
        }
        if ($this->journal2->settings->get('product_grid_description', 'none') === 'block') {
            $this->journal2->html_classes->addClass('product-grid-description');
        }
        if ($this->journal2->settings->get('mobile_menu_on', 'phone') === 'tablet') {
            $this->journal2->html_classes->addClass('mobile-menu-on-tablet');
        }
        if($this->journal2->settings->get('extended_layout', '0') === '1'){
            $this->journal2->html_classes->addClass('extended-layout');
        }
        if($this->journal2->settings->get('boxed_header', '0') === '1'){
            $this->journal2->html_classes->addClass('boxed-header');
        }
        if($this->journal2->settings->get('header_type', 'default') === 'center' || $this->journal2->settings->get('header_type', 'default') === 'mega'){
            $this->journal2->html_classes->addClass('header-center');
        }

        if($this->journal2->settings->get('sticky_header', '0') === '1') {
            $this->journal2->html_classes->addClass('header-sticky');
        }
        if($this->journal2->settings->get('sticky_header_style', 'default') === 'default') {
            $this->journal2->html_classes->addClass('sticky-default');
        }
        if($this->journal2->settings->get('sticky_header_style', 'default') === 'full') {
            $this->journal2->html_classes->addClass('sticky-full');
        }
        if($this->journal2->settings->get('sticky_header_style', 'default') === 'menu') {
            $this->journal2->html_classes->addClass('sticky-menu');
        }
        $this->journal2->html_classes->addClass('backface');

        /* second images */
        if (!Journal2Cache::$mobile_detect->isMobile() && $this->journal2->settings->get('product_grid_second_image') === '1') {
            $this->journal2->html_classes->addClass('product-grid-second-image');
        } else {
            $this->journal2->html_classes->addClass('product-grid-no-second-image');
        }

        if (!Journal2Cache::$mobile_detect->isMobile() && $this->journal2->settings->get('product_list_second_image') === '1') {
            $this->journal2->html_classes->addClass('product-list-second-image');
        } else {
            $this->journal2->html_classes->addClass('product-list-no-second-image');
        }

        // push options
        $classes = array();
        if ($this->journal2->settings->get('product_page_options_push_select') == '1') {
            $classes[] = 'push-select';
        }
        if ($this->journal2->settings->get('product_page_options_push_image') == '1') {
            $classes[] = 'push-image';
        }
        if ($this->journal2->settings->get('product_page_options_push_checkbox') == '1') {
            $classes[] = 'push-checkbox';
        }
        if ($this->journal2->settings->get('product_page_options_push_radio') == '1') {
            $classes[] = 'push-radio';
        }
        $this->journal2->settings->set('product_page_options_push_classes', implode(' ', $classes));

        // disable add to cart
        if ($this->journal2->settings->get('out_of_stock_disable_button') === '1') {
            $this->journal2->html_classes->addClass('hide-cart');
        }

        // cache
		define('JOURNAL_CACHE_CG_ID', $this->journal2->settings->get('cache_by_cg_id', '0') == '1');

        $this->processFooter();
    }

    private function processFooter() {
        /* copyright text */
        $copyright = $this->journal2->settings->get('config_copyright', array());
        $copyright_text = Journal2Utils::getProperty($copyright, 'value.' . $this->config->get('config_language_id'));
        $this->journal2->settings->set('config_copyright', $copyright_text);

        /* payment methods */
        $payments = $this->journal2->settings->get('config_payments.payments', array());
        $payments = Journal2Utils::sortArray($payments);
        $payment_methods = array();
        $width = '';
        $height = '';
        foreach ($payments as $payment) {
            $image = Journal2Utils::getProperty($payment, 'image');
            if (!$image || !file_exists(DIR_IMAGE . $image)) {
                $image = version_compare(VERSION, '2', '>=') ? 'no_image.png' : 'no_image.jpg';
            }
            list($width, $height) = getimagesize(DIR_IMAGE . $image);
            $payment_methods[] = array(
                'image'     => Journal2Utils::resizeImage($this->model_tool_image, $image),
                'name'      => Journal2Utils::getProperty($payment, 'name.value.' . $this->config->get('config_language_id')),
                'url'       => Journal2Utils::getProperty($payment, 'link.value.text'),
                'target'    => Journal2Utils::getProperty($payment, 'new_window') ? ' target="_blank"' : '',
                'width'     => $width,
                'height'    => $height
            );
        }
        $this->journal2->settings->set('config_payments', $payment_methods);
        if ($payment_methods) {
            $this->journal2->settings->set('config_payments_dummy', $this->model_tool_image->resize('data/journal2/transparent.png', $width, $height));
        }

        /* custom classes */
        $classes = array();
        if (!$copyright_text) $classes[] = 'no-copyright';
        if (!$payment_methods) $classes[] = 'no-payments';
        $this->journal2->settings->set('config_footer_classes', implode(' ', $classes));
    }

    public function sitemap() {
        $this->load->model('journal2/blog');

        if (!$this->model_journal2_blog->isEnabled()) {
            return;
        }

        $blog_categories = array();
        $categories = $this->model_journal2_blog->getCategories();
        foreach ($categories as $category) {
            $blog_categories[] = array(
                'name'  => $category['name'],
                'href'  => Journal2Utils::link('journal2/blog', 'journal_blog_category_id=' . $category['category_id'])
            );
        }

        $this->journal2->settings->set('blog_sitemap', '1');
        $this->journal2->settings->set('blog_name', $this->journal2->settings->get('config_blog_settings.title.value.' . $this->config->get('config_language_id'), 'Journal Blog'));
        $this->journal2->settings->set('blog_href', Journal2Utils::link('journal2/blog'));
        $this->journal2->settings->set('blog_categories', $blog_categories);
    }

}
?>
