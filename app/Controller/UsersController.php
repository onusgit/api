<?php

class UsersController extends AppController {

    public $uses = array('Customer', 'Address');
    public $components = array('RequestHandler');

    function beforeFilter() {
        parent::beforeFilter();
    }

    function login() {
        $status = 0;
        $errorMsg = '';
        $data = [];
        if ($this->request->is(array('post', 'put'))):
            $email = $_REQUEST['email'];
            $password = $_REQUEST['password'];
            $customer_data = $this->Customer->find('first', array('conditions' => array('email' => $email)));
            if (!empty($customer_data)):
                $salt = $customer_data['Customer']['salt'];
                $enc_password = sha1($salt . sha1($salt . sha1($password)));
                if ($customer_data['Customer']['password'] == $enc_password):
                    $status = 1;
                    $errorMsg = 'User found';
                    $data['id'] = $customer_data['Customer']['customer_id'];
                    $data['first_name'] = $customer_data['Customer']['firstname'];
                    $data['last_name'] = $customer_data['Customer']['lastname'];
                    $data['email'] = $customer_data['Customer']['email'];
                    $data['telephone'] = $customer_data['Customer']['telephone'];
                    $data['token'] = $customer_data['Customer']['token'];
                else:
                    $status = 0;
                    $errorMsg = 'Email id or password is wrong';
                endif;
            else:
                $status = 0;
                $errorMsg = 'Email id or password is wrong';
            endif;
        endif;
        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

    function register() {
        $status = 0;
        $errorMsg = '';
        $data = [];

        if ($this->request->is(array('post', 'put'))):
            $email = $_REQUEST['email'];
            $first_name = $_REQUEST['first_name'];
            $last_name = $_REQUEST['last_name'];
            $telephone = $_REQUEST['telephone'];
            $password = $_REQUEST['password'];

            $customer_data = $this->Customer->find('first', array('conditions' => array('email' => $email)));
            if (empty($customer_data)):
                $customer['Customer']['email'] = $email;
                $customer['Customer']['firstname'] = $first_name;
                $customer['Customer']['lastname'] = $last_name;
                $customer['Customer']['telephone'] = $telephone;
                $salt = $this->token(9);
                $customer['Customer']['salt'] = $salt;
                $customer['Customer']['password'] = sha1($salt . sha1($salt . sha1($password)));
                $customer['Customer']['customer_group_id'] = Configure::read('customer_group_id');
                $customer['Customer']['date_added'] = date('Y-m-d H:m:s');
                $this->Customer->set($customer);
                $success = $this->Customer->save($customer);
                if ($success):
                    $status = 1;
                    $errorMsg = 'Registration successfull';
                else:
                    $status = 0;
                    $errorMsg = 'Registration failed';
                endif;
            else:
                $status = 2;
                $errorMsg = 'This email id already registered';
            endif;
        endif;


        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

    public function get_address($customer_id) {
        $status = 0;
        $errorMsg = '';
        $data = array();
        $address = $this->Address->find('all', array('conditions' => array('customer_id' => $customer_id)));
        if (!empty($address)):
            foreach ($address as $k => $o):
                $data[$k]['address_id'] = $o['Address']['address_id'];
                $data[$k]['customer_id'] = $o['Address']['customer_id'];
                $data[$k]['firstname'] = $o['Address']['firstname'];
                $data[$k]['lastname'] = $o['Address']['lastname'];
                $data[$k]['company'] = $o['Address']['company'];
                $data[$k]['address_1'] = $o['Address']['address_1'];
                $data[$k]['address_2'] = $o['Address']['address_2'];
                $data[$k]['city'] = $o['Address']['city'];
                $data[$k]['postcode'] = $o['Address']['postcode'];
                $data[$k]['country_id'] = $o['Address']['country_id'];
                $data[$k]['zone_id'] = $o['Address']['zone_id'];
                $data[$k]['full_address'] = $o['Address']['firstname'].", ".$o['Address']['lastname'].", ". $o['Address']['city'].", ". $o['Address']['zone_id'].", ". $o['Address']['country_id'];
            endforeach;
        endif;
        if (!empty($data)):
            $status = 1;
        endif;
        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

}
