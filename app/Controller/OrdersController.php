<?php

class OrdersController extends AppController {

    public $uses = array('Country', 'ZoneToGeoZone', 'Setting', 'Coupon', 'CouponHistory', 'Cart', 'Product', 'CouponProduct', 'CouponCategory', 'ProductToCategory', 'ProductDescription', 'Currency', 'Customer', 'Order', 'OrderProduct', 'OrderTotal');

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
                        $data[$k]['product_image'] = FULL_BASE_URL . '/image/' . str_replace(' ', '%20', $c_data['Product']['image']);
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
                    $discount_amount = ($total_cost * (int) $coupon_query['Coupon']['discount']) / 100;
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
            if ($weight_status['Setting']['value'] == 1):
                $rate_str = explode(",", $weight_rate['Setting']['value']);
                foreach ($rate_str as $rs):
                    $rate = explode(":", $rs);
                    if (isset($rate[0]) && $rate[0] == $weight):
                        $data['Shipping_method_code'] = 'weight.weight_' . $zones['ZoneToGeoZone']['geo_zone_id'];
                        $data['Shipping_method_name'] = $country['Country']['name'] . "(Weight:" . $weight . "g) - Rs." . $rate[1];
                    endif;
                endforeach;
            else:
                $data['Shipping_method_code'] = 'free.free';
                $data['Shipping_method_name'] = 'Free Shipping - Rs.0.00';
            endif;
        endif;
        if (!empty($data)):
            $status = 1;
            $errorMsg = 'success';
        endif;
        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

    /*
      $value = value to be translate
      $from = org current 2 = inr, 5=dollar
     * * 
     * 
     * */

    public function convert_currency($value = null, $from = 2, $to = 5) {
        $status = 0;
        $errorMsg = '';
        $price = '';
        if ($value):
            $from_val = $this->Currency->find('first', array('fields' => array('value'), 'conditions' => array('currency_id' => $from)));
            $to_val = $this->Currency->find('first', array('fields' => array('value'), 'conditions' => array('currency_id' => $to)));
            $converted_value = $value * ( $to_val['Currency']['value'] / $from_val['Currency']['value']);
            $status = 1;
            $errorMsg = 'Price converted successfully';
            $price = $converted_value;
        else:
            $status = 2;
            $errorMsg = 'Parameters are not sufficient';
        endif;
        $this->set(compact('status', 'errorMsg', 'price'));
        $this->set('_serialize', array('status', 'errorMsg', 'price'));
    }

    public function place_order() {
        $status = 0;
        $errorMsg = '';
        $data = array();
        if (!empty($_REQUEST['session_id'])):

            $customer_data = $this->Customer->find('first', array('conditions' => array('customer_id' => @$_REQUEST['customer_id'])));

            $this->Product->bindModel(array('belongsTo' => array('ProductDescription' => array('foriegnKey' => 'product_id'))));
            $this->Cart->bindModel(array('belongsTo' => array('Product' => array('foriegnKey' => 'product_id'))));
            $cart_data = $this->Cart->find('all', array('recursive' => 2, 'conditions' => array('Cart.customer_id' => $_REQUEST['customer_id'], 'Cart.session_id' => $_REQUEST['session_id'])));
//            pr($cart_data);die;
//            pr($cart_data);
//            die;

            $order_data['invoice_no'] = 0; //need to make dynamic
            $order_data['invoice_prefix'] = 'INV-2016-00'; //need to make dynamic
            $order_data['store_id'] = '0'; //need to make dynamic  // fix 0
            $order_data['store_name'] = 'Snacks'; //need to make dynamic
            $order_data['store_url'] = 'http://mysnacky.com/'; //need to make dynamic
            $order_data['customer_id'] = $_REQUEST['customer_id'];
            $order_data['customer_group_id'] = '1'; //need to make dynamic
            if (isset($_REQUEST['guest']) && $_REQUEST['guest'] == '1'):
                $order_data['firstname'] = $_REQUEST['payment_firstname'];
                $order_data['lastname'] = $_REQUEST['payment_lastname'];
                $order_data['email'] = $_REQUEST['payment_email'];
                $order_data['telephone'] = $_REQUEST['payment_email'];
                $order_data['fax'] = $_REQUEST['fax'];
            else:
                $order_data['firstname'] = $customer_data['Customer']['firstname'];
                $order_data['lastname'] = $customer_data['Customer']['lastname'];
                $order_data['email'] = $customer_data['Customer']['email'];
                $order_data['telephone'] = $customer_data['Customer']['telephone'];
            endif;


            //payment field
            $order_data['payment_firstname'] = $_REQUEST['payment_firstname'];
            $order_data['payment_lastname'] = $_REQUEST['payment_lastname'];
            $order_data['payment_company'] = $_REQUEST['payment_company'];
            $order_data['payment_address_1'] = $_REQUEST['payment_address_1'];
            $order_data['payment_address_2'] = $_REQUEST['payment_address_2'];
            $order_data['payment_city'] = $_REQUEST['payment_city'];
            $order_data['payment_postcode'] = $_REQUEST['payment_postcode'];
            $order_data['payment_country'] = $_REQUEST['payment_country'];
            $order_data['payment_country_id'] = $_REQUEST['payment_country_id'];
            $order_data['payment_zone'] = $_REQUEST['payment_zone'];
            $order_data['payment_zone_id'] = $_REQUEST['payment_zone_id'];
            $order_data['payment_method'] = $_REQUEST['payment_method'];
            $order_data['payment_code'] = $_REQUEST['payment_code'];


            //shipping field
            $order_data['shipping_firstname'] = $_REQUEST['shipping_firstname'];
            $order_data['shipping_lastname'] = $_REQUEST['shipping_lastname'];
            $order_data['shipping_company'] = $_REQUEST['shipping_company'];
            $order_data['shipping_address_1'] = $_REQUEST['shipping_address_1'];
            $order_data['shipping_address_2'] = $_REQUEST['shipping_address_2'];
            $order_data['shipping_city'] = $_REQUEST['shipping_city'];
            $order_data['shipping_postcode'] = $_REQUEST['shipping_postcode'];
            $order_data['shipping_country'] = $_REQUEST['shipping_country'];
            $order_data['shipping_country_id'] = $_REQUEST['shipping_country_id'];
            $order_data['shipping_zone'] = $_REQUEST['shipping_zone'];
            $order_data['shipping_zone_id'] = $_REQUEST['shipping_zone_id'];
            $order_data['shipping_method'] = $_REQUEST['shipping_method'];
            $order_data['shipping_code'] = $_REQUEST['shipping_code'];

            //other field
            $order_data['comment'] = $_REQUEST['comment'];
            $order_data['total'] = $_REQUEST['total'];
            $order_data['order_status_id'] = 5; //need to make dynamic
            $order_data['language_id'] = 1; //need to make dynamic
            $order_data['currency_id'] = 2; //need to make dynamic
            $order_data['currency_code'] = 'INR'; //need to make dynamic
            $order_data['currency_value'] = 1.0000000; //need to make dynamic

            $this->Order->set($order_data);
            $this->Order->save();
            $order_id = $this->Order->getLastInsertId();
            foreach ($cart_data as $k => $cart):
                $order_product_data['order_id'] = $order_id;
                $order_product_data['product_id'] = $cart['Product']['product_id'];
                $order_product_data['name'] = isset($cart['ProductDescription']['name']) ? $cart['ProductDescription']['name'] : '&nbsp;';
                $order_product_data['model'] = $cart['Product']['model'];
                $order_product_data['quantity'] = $cart['Cart']['quantity'];
                $order_product_data['price'] = $cart['Product']['price'];
                $order_product_data['total'] = $cart['Cart']['quantity'] * $cart['Product']['price'];
                $order_product_data['tax'] = 0; //need to make dynamic
                $order_product_data['reward'] = 0; //need to make dynamic
                $this->OrderProduct->set($order_product_data);
                $this->OrderProduct->save();

                //order totle table update
                $order_total['OrderTotal'][0]['order_id'] = $order_id;
                $order_total['OrderTotal'][0]['code'] = 'total';
                $order_total['OrderTotal'][0]['title'] = 'Total';
                $order_total['OrderTotal'][0]['sort_order'] = '9';
                $order_total['OrderTotal'][0]['value'] = $_REQUEST['total'];

                $order_total['OrderTotal'][1]['order_id'] = $order_id;
                $order_total['OrderTotal'][1]['code'] = 'sub_total';
                $order_total['OrderTotal'][1]['title'] = 'Sub-Total';
                $order_total['OrderTotal'][1]['value'] = $_REQUEST['total'];
                $order_total['OrderTotal'][1]['sort_order'] = '1';

                $order_total['OrderTotal'][2]['order_id'] = $order_id;
                $order_total['OrderTotal'][2]['code'] = 'shipping';
                $order_total['OrderTotal'][2]['title'] = 'Free Shipping';
                $order_total['OrderTotal'][2]['value'] = '0.0000';
                $order_total['OrderTotal'][2]['sort_order'] = '1';
                $this->OrderTotal->saveAll($order_total);
            endforeach;
            $status = 1;
            $errorMsg = 'Order saved successfully';
            $data['order_id'] = $order_id;
        else:
            $status = 2;
            $errorMsg = 'Parameter missing';
        endif;
        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

    public function order_history($customer_id = null) {
        $status = 0;
        $errorMsg = '';
        $data = array();

        $this->Order->bindModel(array('belongsTo' => array('OrderStatus' => array('foreignKey' => 'order_status_id'))));
        $this->Order->bindModel(array('hasMany' => array('OrderProduct' => array('foreignKey' => 'order_id'))));
        $orders = $this->Order->find('all', array('conditions' => array('customer_id' => $customer_id)));
//        pr($orders);die;
        if (!empty($orders)):
            foreach ($orders as $k => $o):
                $data[$k]['order_id'] = $o['Order']['order_id'];
                $data[$k]['status'] = !empty($o['OrderStatus']['name']) ? $o['OrderStatus']['name'] : '';
                $data[$k]['no_of_product'] = count($o['OrderProduct']);
                $data[$k]['date'] = date('d/m/Y', strtotime($o['Order']['date_added']));
                $data[$k]['customer'] = $o['Order']['payment_firstname'] . " " . $o['Order']['payment_lastname'];
                $data[$k]['total'] = number_format($o['Order']['total'], 2);
            endforeach;
        endif;
        if (!empty($data)):
            $status = 1;
        endif;
        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

    public function payatm_generateChecksum() {
        App::import('Vendor', 'paytm', array('file' => 'paytm' . DS . 'lib' . DS . 'encdec_paytm.php'));
        $checkSum = getChecksumFromArray($_POST, 'O!Pyeu%UB3yp6_#I');
        echo json_encode(array("CHECKSUMHASH" => $checkSum, "ORDER_ID" => $_POST["ORDER_ID"], "payt_STATUS" => "1"));
        die();
        //Sample response return to SDK
        //  {"CHECKSUMHASH":"GhAJV057opOCD3KJuVWesQ9pUxMtyUGLPAiIRtkEQXBeSws2hYvxaj7jRn33rTYGRLx2TosFkgReyCslu4OUj\/A85AvNC6E4wUP+CZnrBGM=","ORDER_ID":"asgasfgasfsdfhl7","payt_STATUS":"1"} 
    }

    public function payatm_verifyChecksum() {
        App::import('Vendor', 'paytm', array('file' => 'paytm' . DS . 'lib' . DS . 'encdec_paytm.php'));        
        $paytmChecksum = "";
        $paramList = array();
        $isValidChecksum = FALSE;

        $paramList = $_POST;
        $return_array = $_POST;
        $paytmChecksum = isset($_POST["CHECKSUMHASH"]) ? $_POST["CHECKSUMHASH"] : ""; //Sent by Paytm pg
//Verify all parameters received from Paytm pg to your application. Like MID received from paytm pg is same as your application’s MID, TXN_AMOUNT and ORDER_ID are same as what was sent by you to Paytm PG for initiating transaction etc.
        $isValidChecksum = verifychecksum_e($paramList, 'O!Pyeu%UB3yp6_#I', $paytmChecksum); //will return TRUE or FALSE string.
// if ($isValidChecksum===TRUE)
// 	$return_array["IS_CHECKSUM_VALID"] = "Y";
// else
// 	$return_array["IS_CHECKSUM_VALID"] = "N";

        $return_array["IS_CHECKSUM_VALID"] = $isValidChecksum ? "Y" : "N";
//$return_array["TXNTYPE"] = "";
//$return_array["REFUNDAMT"] = "";
        unset($return_array["CHECKSUMHASH"]);

        echo $encoded_json = htmlentities(json_encode($return_array));
        die();
    }

}
