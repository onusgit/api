<?php

class FunctionsController extends AppController {

    public $uses = array('Journal2Module');

    public function beforeFilter() {
        parent::beforeFilter();
    }

    public function do_curl_request($url, $params = array()) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/apicookie.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/apicookie.txt');

        $params_string = '';
        if (is_array($params) && count($params)) {
            foreach ($params as $key => $value) {
                $params_string .= $key . '=' . $value . '&';
            }
            rtrim($params_string, '&');

            curl_setopt($ch, CURLOPT_POST, count($params));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);
        }

        //execute post
        $result = curl_exec($ch);

        //close connection
        curl_close($ch);

        return $result;
    }

    public function option() {
        $options = $this->Journal2Module->find('all', array('conditions' => array('module_type' => 'journal2_product_tabs')));
        $i = 0;
        foreach ($options as $k => $o):
            $pdata = json_decode($o['Journal2Module']['module_data'], true);
            if($pdata['status'] == '1' && $i < 2):
                $data[$k]['name'] = @$pdata['name']['value'][1];
                $data[$k]['value'] = @$pdata['content'][1];
                $i++;
            endif;       
        endforeach;
        pr($data);
        die;
    }

}
