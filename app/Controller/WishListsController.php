<?php

class WishListsController extends AppController {

    public $uses = array('CustomerWishlist');

    public function manage_wishlist() {
        $status = 0;
        $errorMsg = '';
        if ($this->request->is(array('get', 'post'))):
            if (empty($_REQUEST['customer_id']) || empty($_REQUEST['product_id'])):
                $status = 2;
                $errorMsg = 'Parameter missing';
            else:
                $customer_id = $_REQUEST['customer_id'];
                $product_id = $_REQUEST['product_id'];
                $customer_wishlist = $this->CustomerWishlist->find('first', array('conditions' => array('CustomerWishlist.customer_id' => $customer_id, 'CustomerWishlist.product_id' => $product_id)));
                if (!empty($customer_wishlist)):
                    $success = $this->CustomerWishlist->deleteAll(array('customer_id' => $customer_id, 'product_id' => $product_id));
                    if ($success):
                        $status = 1;
                        $errorMsg = 'Product removed from wishlist successfully';
                    else:
                        $status = 0;
                        $errorMsg = 'Product not removed from wishlist successfully';
                    endif;
                else:
                    $this->CustomerWishlist->set(array('customer_id' => $customer_id, 'product_id' => $product_id));
                    $success = $this->CustomerWishlist->save();
                    if ($success):
                        $status = 1;
                        $errorMsg = 'Product added to wishlist successfully';
                    else:
                        $status = 0;
                        $errorMsg = 'Product not added to wishlist successfully';
                    endif;
                endif;
            endif;
        endif;
        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

}
