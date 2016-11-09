<?php

App::import('Controller', 'Products');

class HomesController extends AppController {

    public $uses = array('Journal2Module', 'Category', 'Country', 'Zone', 'Product', 'ProductToCategory', 'StockStatus', 'ProductDescription', 'Cart', 'CustomerWishlist');

    public function index() {
        $status = 0;
        $errorMsg = '';
        $data = array();

        // get slider 
        $slider = $this->Journal2Module->find('first', array('conditions' => array('module_type' => 'journal2_slider')));
        if (isset($slider['Journal2Module']['module_data']) && !empty($slider['Journal2Module']['module_data'])):
            $slider_arr = json_decode($slider['Journal2Module']['module_data'], true);
            if (!empty($slider_arr['slides'])):
                foreach ($slider_arr['slides'] as $k => $s):
                    $data['slides'][$k]['image'] = FULL_BASE_URL . '/image/' . str_replace(' ', '%20', reset($s['image']));
                    $data['slides'][$k]['caption'] = "";
                    if (!empty($s['captions'])):
                        $data['slides'][$k]['caption'] = $s['captions'][0]['caption_name'];
                    endif;
                endforeach;
            endif;
        endif;

        //product options
        $poptions = $this->Journal2Module->find('all', array('conditions' => array('module_type' => 'journal2_product_tabs')));
        $i = 0;
        foreach ($poptions as $k => $o):
            $pdata = json_decode($o['Journal2Module']['module_data'], true);
            if ($pdata['status'] == '1' && $i < 2):
                $options[$i]['name'] = @$pdata['name']['value'][1];
                if ($i == 0):
                    $options[$i]['value'] = strip_tags(@$pdata['content'][1]);
                else:
                    $options[$i]['value'] = @$pdata['content'][1];
                endif;
                $i++;
            endif;
        endforeach;

        //get home catedories        
        $this->Category->bindModel(array('belongsTo' => array('CategoryDescription' => array('foreignKey' => FALSE, 'conditions' => array('CategoryDescription.category_id = Category.category_id')))));
        $category_data = $this->Category->find('all', array('conditions' => array('Category.parent_id' => '0')));

        if (!empty($category_data)):
            foreach ($category_data as $k => $cat):
                $data['Categories'][$k]['id'] = $cat['Category']['category_id'];
                $data['Categories'][$k]['name'] = $cat['CategoryDescription']['name'];
                if (!empty($cat['Category']['image'])):
                    $data['Categories'][$k]['image'] = FULL_BASE_URL . '/image/' . str_replace(' ', '%20', $cat['Category']['image']);
                else:
                    $data['Categories'][$k]['image'] = "";
                endif;
            endforeach;
        endif;

        //get homes tag and its product
        $products = $this->Journal2Module->find('all', array('conditions' => array('module_type' => 'journal2_carousel')));
//        $products = $this->Journal2Module->find('all', array('conditions' => array('module_type' => 'journal2_carousel', 'module_id' => array('250', '88', '244'))));
        $products = $this->Journal2Module->find('all', array('conditions' => array('module_type' => 'journal2_carousel', 'module_id' => array('255', '257', '258'))));

        if (!empty($products)):
            for ($i = 0; $i < 15; $i++):
                if (isset($products[$i]['Journal2Module']['module_data']) && !empty($products[$i]['Journal2Module']['module_data'])):
                    $product_arr = json_decode($products[$i]['Journal2Module']['module_data'], true);
//                     if(isset($product_arr['module_name'])):
//                         $data['product'][$i]['tag_name'] = $product_arr['module_name'];                     
//                     endif;  
//                    pr($product_arr);
//                    die;
                    if (isset($product_arr['product_sections'][0]['section_title']['value'][1])):
//                        $data['product'][$i]['tag_name'] = $product_arr['module_name'];
                        $data['product'][$i]['tag_name'] = $product_arr['product_sections'][0]['section_title']['value'][1];
                        //for random prodcut type
                        if (isset($product_arr['product_sections'][0]['section_type']) && $product_arr['product_sections'][0]['section_type'] == 'random'):
//                      if (isset($product_arr['product_sections'][0]['category']['data']['id'])):
//                            $cat_product = $this->ProductToCategory->find('list', array('fields' => array('ProductToCategory.product_id'), 'conditions' => array('ProductToCategory.category_id' => $product_arr['product_sections'][0]['category']['data']['id'])));
                            $this->Product->bindModel(array(
                                'belongsTo' => array(
                                    'StockStatus' => array('foreignKey' => FALSE, 'conditions' => array('StockStatus.stock_status_id = Product.stock_status_id')),
                                    'ProductDescription' => array('foreignKey' => FALSE, 'conditions' => array('ProductDescription.product_id = Product.product_id')),
                                )
                            ));
//                            $product_data = $this->Product->find('all', array('recursive' => 2, 'conditions' => array('Product.product_id' => $cat_product), 'limit' => 8, 'group' => 'Product.product_id'));
                            $product_data = $this->Product->find('all', array('recursive' => 2, 'order' => 'rand()', 'limit' => 8, 'group' => 'Product.product_id'));
                            if (!empty($product_data)):
                                foreach ($product_data as $k => $p):
                                    $data['product'][$i]['products'][$k]['id'] = $p['Product']['product_id'];
                                    $data['product'][$i]['products'][$k]['name'] = $p['ProductDescription']['name'];
                                    $data['product'][$i]['products'][$k]['description'] = html_entity_decode($p['ProductDescription']['description']);
                                    $data['product'][$i]['products'][$k]['quantity'] = $p['Product']['quantity'];
                                    $data['product'][$i]['products'][$k]['price'] = number_format($p['Product']['price'], 2);
                                    $data['product'][$i]['products'][$k]['sku'] = $p['Product']['sku'];
                                    $data['product'][$i]['products'][$k]['model'] = $p['Product']['model'];
                                    $data['product'][$i]['products'][$k]['viewed'] = $p['Product']['viewed'];
                                    if (!empty($p['Product']['image'])):
                                        $data['product'][$i]['products'][$k]['image'] = FULL_BASE_URL . '/image/' . str_replace(' ', '%20', $p['Product']['image']);
                                    else:
                                        $data['product'][$i]['products'][$k]['image'] = '';
                                    endif;
                                    $data['product'][$i]['products'][$k]['minimum'] = $p['Product']['minimum'];
                                    $data['product'][$i]['products'][$k]['shipping'] = $p['Product']['shipping'];
                                    $data['product'][$i]['products'][$k]['stock_status'] = $p['StockStatus']['name'];
                                endforeach;
                            else:
                                $data['product'][$i]['products'] = array();
                            endif;
                        elseif (isset($product_arr['product_sections'][0]['module_type']) && $product_arr['product_sections'][0]['module_type'] == 'latest'):
                            $this->Product->bindModel(array(
                                'belongsTo' => array(
                                    'StockStatus' => array('foreignKey' => FALSE, 'conditions' => array('StockStatus.stock_status_id = Product.stock_status_id')),
                                    'ProductDescription' => array('foreignKey' => FALSE, 'conditions' => array('ProductDescription.product_id = Product.product_id')),
                                )
                            ));
                            $product_data = $this->Product->find('all', array('recursive' => 2, 'order' => 'Product.product_id DESC', 'limit' => 8, 'group' => 'Product.product_id'));
                            if (!empty($product_data)):
                                foreach ($product_data as $k => $p):
                                    $data['product'][$i]['products'][$k]['id'] = $p['Product']['product_id'];
                                    $data['product'][$i]['products'][$k]['name'] = $p['ProductDescription']['name'];
                                    $data['product'][$i]['products'][$k]['description'] = html_entity_decode($p['ProductDescription']['description']);
                                    $data['product'][$i]['products'][$k]['quantity'] = $p['Product']['quantity'];
                                    $data['product'][$i]['products'][$k]['price'] = number_format($p['Product']['price'], 2);
                                    $data['product'][$i]['products'][$k]['sku'] = $p['Product']['sku'];
                                    $data['product'][$i]['products'][$k]['model'] = $p['Product']['model'];
                                    $data['product'][$i]['products'][$k]['viewed'] = $p['Product']['viewed'];
                                    if (!empty($p['Product']['image'])):
                                        $data['product'][$i]['products'][$k]['image'] = FULL_BASE_URL . '/image/' . str_replace(' ', '%20', $p['Product']['image']);
                                    else:
                                        $data['product'][$i]['products'][$k]['image'] = '';
                                    endif;
                                    $data['product'][$i]['products'][$k]['minimum'] = $p['Product']['minimum'];
                                    $data['product'][$i]['products'][$k]['shipping'] = $p['Product']['shipping'];
                                    $data['product'][$i]['products'][$k]['stock_status'] = $p['StockStatus']['name'];
                                endforeach;
                            else:
                                $data['product'][$i]['products'] = array();
                            endif;
                        else:
                            $data['product'][$i]['products'] = array();
                        endif;
                    endif;
                endif;
            endfor;
        endif;
        if (!empty($data)):
            $status = 1;
            $errorMsg = 'success';
        endif;
        $this->set(compact('status', 'errorMsg', 'options', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'options', 'data'));
    }

    public function get_countries() {
        $status = 0;
        $errorMsg = '';
        $data = array();
        $country = $this->Country->find('all', array('conditions' => array('status' => '1')));
        if (!empty($country)):
            foreach ($country as $k => $c):
                $data[$k]['country_id'] = $c['Country']['country_id'];
                $data[$k]['name'] = $c['Country']['name'];
                $zone = $this->Zone->find('all', array('conditions' => array('country_id' => $c['Country']['country_id'])));
                if (!empty($zone)):
                    foreach ($zone as $kz => $z):
                        $data[$k]['zone'][$kz]['zone_id'] = $z['Zone']['zone_id'];
                        $data[$k]['zone'][$kz]['name'] = $z['Zone']['name'];
                    endforeach;
                endif;
            endforeach;
        endif;
        if (!empty($data)):
            $status = 1;
            $errorMsg = 'success';
        endif;
        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

//    echo ip_info("173.252.110.27", "Country"); // United States
//echo ip_info("173.252.110.27", "Country Code"); // US
//echo ip_info("173.252.110.27", "State"); // California
//echo ip_info("173.252.110.27", "City"); // Menlo Park
//echo ip_info("173.252.110.27", "Address"); // Menlo Park, California, United Stat
    function check_country($ip = NULL, $purpose = "location", $deep_detect = TRUE) {
        $output = NULL;
        if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
            $ip = $_SERVER["REMOTE_ADDR"];
            if ($deep_detect) {
                if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
            }
        }
        $purpose = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));
        $support = array("country", "countrycode", "state", "region", "city", "location", "address");
        $continents = array(
            "AF" => "Africa",
            "AN" => "Antarctica",
            "AS" => "Asia",
            "EU" => "Europe",
            "OC" => "Australia (Oceania)",
            "NA" => "North America",
            "SA" => "South America"
        );
        if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
            $ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));
            if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
                switch ($purpose) {
                    case "location":
                        $output = array(
//                            "city" => @$ipdat->geoplugin_city,
//                            "state" => @$ipdat->geoplugin_regionName,
                            "country" => @$ipdat->geoplugin_countryName,
                            "country_code" => @$ipdat->geoplugin_countryCode,
                            "continent" => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
                            "continent_code" => @$ipdat->geoplugin_continentCode
                        );
                        break;
                    case "address":
                        $address = array($ipdat->geoplugin_countryName);
                        if (@strlen($ipdat->geoplugin_regionName) >= 1)
                            $address[] = $ipdat->geoplugin_regionName;
                        if (@strlen($ipdat->geoplugin_city) >= 1)
                            $address[] = $ipdat->geoplugin_city;
                        $output = implode(", ", array_reverse($address));
                        break;
                    case "city":
                        $output = @$ipdat->geoplugin_city;
                        break;
                    case "state":
                        $output = @$ipdat->geoplugin_regionName;
                        break;
                    case "region":
                        $output = @$ipdat->geoplugin_regionName;
                        break;
                    case "country":
                        $output = @$ipdat->geoplugin_countryName;
                        break;
                    case "countrycode":
                        $output = @$ipdat->geoplugin_countryCode;
                        break;
                }
            }
        }
        $data = $output;
        $status = 1;
        if (!empty($data)):
            $status = 1;
        endif;
        $this->set(compact('status', 'data'));
        $this->set('_serialize', array('status', 'data'));
    }

}
