<?php

class FunctionsController extends AppController {

    public $uses = array('Journal2Module');

    public function beforeFilter() {
        parent::beforeFilter();
    }
public function check_ios_push() {
        $tBadge = 2;
        $tSound = "default";
        /*ku*/
        $tBody['aps'] = array(
            'alert' => 'test push message',
            'badge' => $tBadge,
            'sound' => $tSound,
        );
        
        
        $tBody ['payload'] = 'test';
        echo $payload = json_encode($tBody);
        $device_token = 'a61024723bcd671c68a99786fc49c5d6ce02f82aea2a029c1a3f80cf1f6df10c';
        $ctx = stream_context_create();
        $ios_certificate_path = WWW_ROOT . "certi/MySnacky_dist.pem";
        stream_context_set_option($ctx, 'ssl', 'local_cert', $ios_certificate_path);
        stream_context_set_option($ctx, 'ssl', 'passphrase', 'Onus2016');
//        $fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
        $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
        $streamContext = stream_context_create(array('ssl' => array(
                'cafile' => $this->_sRootCertificationAuthorityFile,
                'local_cert' => $this->_sProviderCertificateFile
        )));
        if (!$fp):
            $msg = "Failed to connect : $err $errstr" . PHP_EOL;
        endif;
        $msg = chr(0) . pack('n', 32) . pack('H*', $device_token) . pack('n', strlen($payload)) . $payload;
        echo $result = fwrite($fp, $msg, strlen($msg));
        fclose($fp);
        die();
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
