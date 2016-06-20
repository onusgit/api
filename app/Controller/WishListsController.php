<?php

class WishListsController extends AppController {

    public $uses = array('CustomerWishlist', 'ProductDescription', 'Product', 'ProductToCategory');

    public function manage_wishlist() {
        $status = 0;
        $errorMsg = '';
        $data = array();
        if ($this->request->is(array('get', 'post'))):
            if (empty($_REQUEST['customer_id'])):
                $status = 2;
                $errorMsg = 'Parameter missing';
            else:
                $customer_id = $_REQUEST['customer_id'];
                if(isset($_REQUEST['product_id'])):
                    $product_id = $_REQUEST['product_id'];
                    $customer_wishlist = $this->CustomerWishlist->find('first', array('conditions' => array('CustomerWishlist.customer_id' => $customer_id, 'CustomerWishlist.product_id' => $product_id)));
                    if (!empty($customer_wishlist)):                              
//                        $success = $this->CustomerWishlist->deleteAll(array('customer_ids' => $customer_id, 'product_id' => $product_id));
                        $success =$this->CustomerWishlist->query("DELETE FROM  `ocjn_customer_wishlist` WHERE  `customer_id` = ".$customer_id." &&  `product_id` =".$product_id);
                        if ($success):                            
                            $errorMsg = 'Product removed from wishlist successfully';
                        else:                           
                            $errorMsg = 'Product not removed from wishlist successfully';
                        endif;
                        $status = 3;
                    else:
                        $add_data['customer_id'] = $customer_id;
                        $add_data['product_id'] = $product_id;
                        $this->CustomerWishlist->create();
                        $success = $this->CustomerWishlist->save($add_data, false);
                        if ($success):                            
                            $errorMsg = 'Product added to wishlist successfully';
                        else:                            
                            $errorMsg = 'Product not added to wishlist successfully';
                        endif;
                        $status = 3;
                    endif;
                endif;
                
                $product_ids = $this->CustomerWishlist->find('all', array('fields' => array('customer_id', 'product_id'),'conditions' => array('CustomerWishlist.customer_id' => $customer_id)));
                $product_arr = array();
                foreach ($product_ids as $id):
                    $product_arr[] = $id['CustomerWishlist']['product_id']; 
                endforeach;
                $this->Product->bindModel(array(
                    'belongsTo' => array(
                        'StockStatus' => array('foreignKey' => FALSE, 'conditions' => array('StockStatus.stock_status_id = Product.stock_status_id')),
                        'ProductDescription' => array('foreignKey' => FALSE, 'conditions' => array('ProductDescription.product_id = Product.product_id')),
                    ),
                    'hasMany' => array(
                        'ProductToCategory' => array('foriegnKey' => 'product_id')
                    ),
                ));
                $product_data = $this->Product->find('all', array('recursive' => 2, 'conditions' => array('Product.product_id' => $product_arr), 'group' => 'Product.product_id'));
               
                if (!empty($product_data)):  
                    if($status != 3):
                        $status = 1;
                    endif;
                    foreach ($product_data as $k => $c_data):                        
                        $data[$k]['id'] = $c_data['Product']['product_id'];
                        $data[$k]['name'] = $c_data['ProductDescription']['name'];
                        $data[$k]['description'] = $c_data['ProductDescription']['description'];
                        $data[$k]['quantity'] = $c_data['Product']['quantity'];
                        $data[$k]['price'] = number_format($c_data['Product']['price'], 2);  
                        $data[$k]['sku'] = $c_data['Product']['sku'];
                        $data[$k]['model'] = $c_data['Product']['model'];
                        $data[$k]['viewed'] = $c_data['Product']['viewed'];
                        if (!empty($c_data['Product']['image'])):
                            $data[$k]['image'] = FULL_BASE_URL . '/image/' .$c_data['Product']['image'];
                        else:
                            $data[$k]['image'] = '';
                        endif;
                        $data[$k]['minimum'] = $c_data['Product']['minimum'];
                        $data[$k]['shipping'] = $c_data['Product']['shipping'];
                        $data[$k]['stock_status'] = $c_data['StockStatus']['name'];                                                
                    endforeach;
                else:
                    $errorMsg = 'No product found in wishlist';
                endif;
            endif;
        endif;
        if(empty($data)):
            $status = 0;
        else:
            $total_product = count($data);
        endif;
        
        $this->set(compact('status', 'errorMsg', 'total_product', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'total_product', 'data'));
    }

}
