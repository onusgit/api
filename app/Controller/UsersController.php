<?php

class UsersController extends AppController {

    public $uses = array('Customer');
    public $components = array('RequestHandler');

    function beforeFilter() {
        parent::beforeFilter();
    }

    function login() {
        $status = 0;
        $errorMsg = '';
        $data = [];

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


        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

    function register() {
        $status = 0;
        $errorMsg = '';
        $data = [];

        $email = $_REQUEST['email'];
        $first_name = $_REQUEST['first_name'];
        $last_name = $_REQUEST['last_name'];
        $password = $_REQUEST['password'];

        $customer_data = $this->Customer->find('first', array('conditions' => array('email' => $email)));
        if (empty($customer_data)):
            $customer['Customer']['email'] = $email;
            $customer['Customer']['firstname'] = $first_name;
            $customer['Customer']['lastname'] = $last_name;
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


        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

}
