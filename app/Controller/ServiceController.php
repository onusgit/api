<?php

class ServiceController extends AppController{
    
    public $useTable = array('User', 'Category');
    public $uses = array('User', 'Category');

    public function beforeFilter() {
        parent::beforeFilter();
    }
    public function cat() {
        $c = $this->Category->find('all');
        //pr($c);
        $salt = 'fjuJGrU7Q';
        pr(sha1($salt . sha1($salt . sha1('admin@123'))));
    }
    public function login() {
        $c = $this->User->find('first', array('conditions' => array('password' => sha1($salt . sha1($salt . sha1($data['password']))))));
        pr($c);
    }
}