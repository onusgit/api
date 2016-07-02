<?php

class OrdersController extends AppController {

    public $uses = array('Country', 'ZoneToGeoZone', 'Setting', 'Coupon', 'CouponHistory', 'Cart', 'Product', 'CouponProduct', 'CouponCategory', 'ProductToCategory', 'ProductDescription');

    public function apply_coupon() {
        $status = 1;
        $errorMsg = 'Coupon can be apply';
        $data = array();

        if (empty($_REQUEST['code']) || empty($_REQUEST['customer_id']) || empty($_REQUEST['session_id'])):
            $status = 2;
            $errorMsg = 'Insufficient parameters';
        else:
            $code = $_REQUEST['code'];
            $customer_id = $_REQUEST['customer_id'];
            $session_id = $_REQUEST['session_id'];
            $coupon_query = $this->Coupon->find('first', array('code' => $code, 'date_start' => '< NOW()', 'date_end' => '> NOW()', 'status' => 1));
            $this->Cart->bindModel(array('belongsTo' => array('Product' => array('foriegnKey' => 'product_id'))));
            $cart_data = $this->Cart->find('all', array('conditions' => array('customer_id' => $customer_id)));
            $total = 0;

            foreach ($cart_data as $k => $cart):
                $total += number_format($cart['Product']['price'], 2) * $cart['Cart']['quantity'];
            endforeach;

            if ($coupon_query) {
                if ($coupon_query['Coupon']['total'] > (int) $total) {
                    $status = 0;
                    $errorMsg = 'Amount must be greater then coupon set value';
                }

                $coupon_history_query = $this->CouponHistory->find('all', array('coupon_id' => $coupon_query['Coupon']['coupon_id']));


                //$coupon_history_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` ch WHERE ch.coupon_id = '" . (int) $coupon_query->row['coupon_id'] . "'");

                if ($coupon_query['Coupon']['uses_total'] > 0 && ( count($coupon_history_query) >= $coupon_query['Coupon']['uses_total'])) {
                    $status = 0;
                    $errorMsg = 'Coupon reached to maximum usage';
                }

                if (empty($customer_id)) {
                    $status = 0;
                    $errorMsg = 'Customer id must needed';
                }

                if (!empty($customer_id)) {
                    $coupon_history_query = $this->CouponHistory->find('all', array('coupon_id' => $coupon_query['Coupon']['coupon_id'], 'customer_id' => $customer_id));
                    if ($coupon_query['Coupon']['uses_total'] > 0 && (count($coupon_history_query) >= $coupon_query['Coupon']['uses_customer'])) {
                        $status = 0;
                        $errorMsg = 'You already have used coupon';
                    }
                }

                // Products
                $coupon_product_data = array();

                $coupon_product_query = $this->CouponProduct->find('all', array('conditions' => array('coupon_id' => $coupon_query['Coupon']['coupon_id'])));
                //$coupon_product_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon_product` WHERE coupon_id = '" . (int) $coupon_query->row['coupon_id'] . "'");

                foreach ($coupon_product_query as $product) {
                    $coupon_product_data[] = $product['CouponProduct']['product_id'];
                }

                // Categories
                $coupon_category_data = array();

                $coupon_category_query = $this->CouponCategory->find('all', array('conditions' => array('coupon_id' => $coupon_query['Coupon']['coupon_id'])));

                //$coupon_category_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon_category` cc LEFT JOIN `" . DB_PREFIX . "category_path` cp ON (cc.category_id = cp.path_id) WHERE cc.coupon_id = '" . (int) $coupon_query->row['coupon_id'] . "'");

                foreach ($coupon_category_query as $category) {
                    $coupon_category_data[] = $category['CouponCategory']['category_id'];
                }

                $product_data = array();

                if ($coupon_product_data || $coupon_category_data) {
                    foreach ($cart_data as $product) {
                        if (in_array($product['Cart']['product_id'], $coupon_product_data)) {
                            $product_data[] = $product['Cart']['product_id'];

                            continue;
                        }

                        foreach ($coupon_category_data as $category_id) {
                            $coupon_category_query = $this->ProductToCategory->find('all', array('condition' => array('product_id' => $product['Cart']['product_id'], 'category_id' => $category_id)));
                            //$coupon_category_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = '" . (int) $product['product_id'] . "' AND category_id = '" . (int) $category_id . "'");

                            if ($coupon_category_query) {
                                $product_data[] = $product['Cart']['product_id'];

                                continue;
                            }
                        }
                    }

                    if (!$product_data) {
                        $status = 0;
                        $errorMsg = 'Product in cart not applied to coupon';
                    }
                }
            } else {
                $status = 0;
                $errorMsg = 'Coupon code not match';
            }

            if ($status == 1):

                $this->Product->bindModel(array('belongsTo' => array('ProductDescription' => array('foriegnKey' => 'ProductDescription.product_id'))));
                $this->Cart->bindModel(array('belongsTo' => array('Product' => array('foriegnKey' => 'product_id'))));
                $cart_product_data = $this->Cart->find('all', array('recursive' => 1, 'conditions' => array('Cart.customer_id' => $customer_id, 'Cart.session_id' => $session_id)));
                //pr($cart_product_data);
                if (!empty($cart_product_data)):
                    if ($status != 3):
                        $status = 1;
                    endif;
                    $total_cost = 0;
                    foreach ($cart_product_data as $k => $c_data):
                        $product_description = $this->ProductDescription->find('first', array('conditions' => array('ProductDescription.product_id' => $c_data['Cart']['product_id'])));
                        $data[$k]['product_id'] = $c_data['Cart']['product_id'];
                        $data[$k]['product_image'] = FULL_BASE_URL . '/image/' . $c_data['Product']['image'];
                        $data[$k]['product_price'] = number_format($c_data['Product']['price'], 2);
                        $total_cost += $c_data['Product']['price'] * $c_data['Cart']['quantity'];
                        $data[$k]['product_name'] = $product_description['ProductDescription']['name'];
                        $data[$k]['quantity'] = $c_data['Cart']['quantity'];
                    endforeach;
                else:
                    $errorMsg = 'No product found in cart';
                endif;
            endif;
            if (!empty($data)):
                $total_item = count($data);
                $total_cost = round($total_cost, 2);
                $after_total_cost = 0;
                $discount_amount = 0;             
                if ($coupon_query['Coupon']['type'] == 'P'):
                    $discount_amount = ($total_cost * (int) $coupon_query['Coupon']['discount'])  / 100;
                    //$discount_amount = (int) $discount_amount / 100;
                else:
                    $discount_amount = $coupon_query['Coupon']['discount'];
                endif;
                $after_discount_total_cost = $total_cost - (int) $discount_amount;
            else:
                $status = 0;
            endif;
        endif;

        $this->set(compact('status', 'errorMsg', 'data', 'total_item', 'total_cost', 'after_discount_total_cost', 'discount_amount'));
        $this->set('_serialize', array('status', 'errorMsg', 'data', 'total_item', 'total_cost', 'after_discount_total_cost', 'discount_amount'));
    }

    public function use_gift_voucher() {
        
    }

    public function payment_options($country_id = null, $weight = null) {
        $status = 0;
        $errorMsg = '';
        $data = array();
        $country = $this->Country->find('first', array('conditions' => array('country_id' => $country_id)));
        $zones = $this->ZoneToGeoZone->find('first', array('conditions' => array('country_id' => $country_id)));  
        if (!empty($zones)):
            $weight_rate = $this->Setting->find('first', array('conditions' => array('key' => 'weight_' . $zones['ZoneToGeoZone']['geo_zone_id'] . '_rate')));
            $weight_status = $this->Setting->find('first', array('conditions' => array('key' => 'weight_' . $zones['ZoneToGeoZone']['geo_zone_id'] . '_status')));
            if($weight_status['Setting']['value'] == 1):
                $rate_str = explode(",", $weight_rate['Setting']['value']);                  
                foreach ($rate_str as $rs):
                    $rate = explode(":", $rs);
                    if(isset($rate[0]) && $rate[0] == $weight):
                        $data['Shipping_method']['weight.weight_'.$zones['ZoneToGeoZone']['geo_zone_id']] = $country['Country']['name']."(Weight:".$weight."g) - Rs.". $rate[1];
                    endif;
                endforeach;
            else:
                $data['Shipping_method']['free.free'] = 'Free Shipping - Rs.0.00';
            endif;            
        endif;
        if (!empty($data)):
            $status = 1;
            $errorMsg = 'success';
        endif;        
        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

}
