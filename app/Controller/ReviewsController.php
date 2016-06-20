<?php

class ReviewsController extends AppController {

    public $uses = array('Review');

    public function get_product_reviews() {
        $status = 0;
        $errorMsg = '';
        $data = array();
        if ($this->request->is(array('get', 'post'))):
            if (!empty($_REQUEST['product_id'])):
                $reviews = $this->Review->find('all', array('conditions' => array('Review.product_id' => $_REQUEST['product_id'])));
                if (!empty($reviews)):
                    foreach ($reviews as $k => $review_data):
                        $data[$k]['customer_id'] = $review_data['Review']['customer_id'];
                        $data[$k]['author'] = $review_data['Review']['author'];
                        $data[$k]['text'] = $review_data['Review']['text'];
                        $data[$k]['rating'] = $review_data['Review']['rating'];
                        $data[$k]['date_added'] =  $this->time_elapsed_string(strtotime($review_data['Review']['date_added']));
                    endforeach;
                    $status = 1;
                else:
                    $status = 0;
                    $errorMsg = 'No review found for this product';
                endif;
            else:
                $status = 2;
                $errorMsg = 'Please give product id';
            endif;
        endif;
        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

    public function post_product_review() {
        $status = 0;
        $errorMsg = '';
        if ($this->request->is(array('get', 'post'))):
            if (empty($_REQUEST['product_id']) || empty($_REQUEST['customer_id']) || empty($_REQUEST['author']) || empty($_REQUEST['author']) || empty($_REQUEST['text']) || empty($_REQUEST['rating'])):
                $status = 2;
                $errorMsg = 'Parameter missing';
            else:
                $review['product_id'] = $_REQUEST['product_id'];
                $review['customer_id'] = $_REQUEST['customer_id'];
                $review['author'] = $_REQUEST['author'];
                $review['text'] = $_REQUEST['text'];
                $review['rating'] = $_REQUEST['rating'];
                $review['date_added'] = date('Y-m-d H:m:s');

                $this->Review->set($review);
                $success = $this->Review->save();
                if ($success):
                    $status = 1;
                    $errorMsg = 'Review added successfully';
                else:
                    $status = 0;
                    $errorMsg = 'Review not added successfully';
                endif;

            endif;
        endif;
        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

}
