<?php

class OrdersController extends AppController {

    public $uses = array('ZoneToGeoZone', 'Setting');

    public function apply_coupon() {
        $status = 0;
        $errorMsg = '';
        $data = array();
        if ($this->request->is(array('post', 'put'))):
            $email = $_REQUEST['email'];
            $password = $_REQUEST['password'];
        endif;
    }

    public function use_gift_voucher() {
        
    }

    public function payment_options($country_id = null, $weight = null) {
        $status = 0;
        $errorMsg = '';
        $data = array();
        $zones = $this->ZoneToGeoZone->find('first', array('conditions' => array('country_id' => $country_id)));
        pr($zones);
        if (!empty($zones)):
            $setting = $this->Setting->find('all', array('conditions' => array('key' => 'weight_'.$zones['ZoneToGeoZone']['geo_zone_id'].'_status'))); 
        pr($setting);die;
        endif;
        if (!empty($data)):
            $status = 1;
            $errorMsg = 'success';
        endif;
        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

}
