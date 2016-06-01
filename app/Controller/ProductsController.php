<?php

class ProductsController extends AppController {

    public $uses = array('Product', 'ProductToCategory', 'StockStatus', 'ProductDescription', 'Cart');

    public function get_all_products() {
        $status = 0;
        $errorMsg = '';
        $data = [];
        $order = 'Product.product_id ASC';
        $this->Product->bindModel(array('belongsTo' => array('ProductDescription' => array('foreignKey' => FALSE, 'conditions' => array('ProductDescription.product_id = ProductDescription.product_id')))));
        $this->Product->bindModel(array('belongsTo' => array('StockStatus' => array('foreignKey' => FALSE, 'conditions' => array('StockStatus.stock_status_id = Product.stock_status_id')))));
        $product_data = $this->Product->find('all', array('recursive' => 2, 'order' => $order));

        if (!empty($product_data)):
            foreach ($product_data as $k => $pr_data):
                $data[$k]['id'] = $pr_data['Product']['product_id'];
                $data[$k]['name'] = $pr_data['ProductDescription']['name'];
                $data[$k]['description'] = $pr_data['ProductDescription']['description'];
                $data[$k]['quantity'] = $pr_data['Product']['quantity'];
                $data[$k]['price'] = $pr_data['Product']['price'];
                $data[$k]['sku'] = $pr_data['Product']['sku'];
                $data[$k]['image'] = $pr_data['Product']['image'];
                $data[$k]['minimum'] = $pr_data['Product']['minimum'];
                $data[$k]['shipping'] = $pr_data['Product']['shipping'];
                $data[$k]['stock_status'] = $pr_data['StockStatus']['name'];
            endforeach;
        endif;

        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

    public function get_category_products() {
        $category_array = array('18');
        $status = 0;
        $errorMsg = '';
        $data = [];
        $conditions = [];
        //$conditions[] = array('ProductToCategory.category_id' => $category_array);
        $order = 'Product.product_id ASC';

        $this->Product->bindModel(array(
            'belongsTo' => array(
                'StockStatus' => array('foreignKey' => FALSE, 'conditions' => array('StockStatus.stock_status_id = Product.stock_status_id')),
                'ProductDescription' => array('foreignKey' => FALSE, 'conditions' => array('ProductDescription.product_id = Product.product_id')),
            ),
            'hasMany' => array(
                'ProductToCategory' => array('foreignKey' => FALSE, 'conditions' => array('ProductToCategory.product_id = Product.product_id')),
            )
        ));
        $product_data = $this->Product->find('all', array('recursive' => 2, 'conditions' => $conditions, 'order' => $order));

        if (!empty($product_data)):
            foreach ($product_data as $k => $pr_data):
                $data[$k]['id'] = $pr_data['Product']['product_id'];
                $data[$k]['name'] = $pr_data['ProductDescription']['name'];
                $data[$k]['description'] = $pr_data['ProductDescription']['description'];
                $data[$k]['quantity'] = $pr_data['Product']['quantity'];
                $data[$k]['price'] = $pr_data['Product']['price'];
                $data[$k]['sku'] = $pr_data['Product']['sku'];
                $data[$k]['image'] = $pr_data['Product']['image'];
                $data[$k]['minimum'] = $pr_data['Product']['minimum'];
                $data[$k]['shipping'] = $pr_data['Product']['shipping'];
                $data[$k]['stock_status'] = $pr_data['StockStatus']['name'];
            endforeach;
        endif;

        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }
    
    public function get_products() {
        $category_array = array('18');
        $status = 0;
        $errorMsg = '';
        $data = [];
        $conditions = [];
        $order = [];
        
        if(!empty($_REQUEST['price_low'])):
            $conditions[] = array('Product.price >=' => $_REQUEST['price_low']);
        endif;
        
        if(!empty($_REQUEST['price_high'])):
            $conditions[] = array('Product.price <=' => $_REQUEST['price_high']);
        endif;
        //$conditions[] = array('ProductToCategory.category_id' => $category_array);
        
        if(empty($order)):
            $order = 'Product.product_id ASC';
        endif;
        $this->Product->bindModel(array(
            'belongsTo' => array(
                'StockStatus' => array('foreignKey' => FALSE, 'conditions' => array('StockStatus.stock_status_id = Product.stock_status_id')),
                'ProductDescription' => array('foreignKey' => FALSE, 'conditions' => array('ProductDescription.product_id = Product.product_id')),
            ),
//            'hasMany' => array(
//                'ProductToCategory' => array('foreignKey' => FALSE, 'conditions' => array('ProductToCategory.product_id = Product.product_id')),
//            )
        ));
        $product_data = $this->Product->find('all', array('recursive' => 2, 'conditions' => $conditions, 'order' => $order));

        if (!empty($product_data)):
            foreach ($product_data as $k => $pr_data):
                $data[$k]['id'] = $pr_data['Product']['product_id'];
                $data[$k]['name'] = $pr_data['ProductDescription']['name'];
                $data[$k]['description'] = $pr_data['ProductDescription']['description'];
                $data[$k]['quantity'] = $pr_data['Product']['quantity'];
                $data[$k]['price'] = $pr_data['Product']['price'];
                $data[$k]['sku'] = $pr_data['Product']['sku'];
                $data[$k]['image'] = $pr_data['Product']['image'];
                $data[$k]['minimum'] = $pr_data['Product']['minimum'];
                $data[$k]['shipping'] = $pr_data['Product']['shipping'];
                $data[$k]['stock_status'] = $pr_data['StockStatus']['name'];
            endforeach;
        endif;
        pr($data);
        die;
        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

    public function get_product($product_id) {
        $status = 0;
        $errorMsg = '';
        if (!empty($product_id)):
            $data = [];
            $conditions = array('Product.product_id' => $product_id);
            $this->Product->bindModel(array('belongsTo' => array('ProductDescription' => array('foreignKey' => FALSE, 'conditions' => array('ProductDescription.product_id = ProductDescription.product_id')))));
            $this->Product->bindModel(array('belongsTo' => array('StockStatus' => array('foreignKey' => FALSE, 'conditions' => array('StockStatus.stock_status_id = Product.stock_status_id')))));
            $product_data = $this->Product->find('first', array('recursive' => 2, 'conditions' => $conditions));
            if (!empty($product_data)):
                $data['id'] = $product_data['Product']['product_id'];
                $data['name'] = $product_data['ProductDescription']['name'];
                $data['description'] = $product_data['ProductDescription']['description'];
                $data['quantity'] = $product_data['Product']['quantity'];
                $data['price'] = $product_data['Product']['price'];
                $data['sku'] = $product_data['Product']['sku'];
                $data['image'] = $product_data['Product']['image'];
                $data['minimum'] = $product_data['Product']['minimum'];
                $data['shipping'] = $product_data['Product']['shipping'];
                $data['stock_status'] = $product_data['StockStatus']['name'];
            else:
                $status = 0;
                $errorMsg = 'No Product Found';
            endif;
        else:
            $status = 0;
            $errorMsg = 'Please provide product id';
        endif;
        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

    public function add_to_cart() {
        $status = 0;
        $errorMsg = '';
        $cart['customer_id'] = $_REQUEST['customer_id'];
        $cart['session_id'] = $_REQUEST['session_id'];
        $cart['product_id'] = $_REQUEST['product_id'];
        $cart['quantity'] = $_REQUEST['product_quantity'];
        $product = $this->Product->findById($cart['product_id']);
        if (empty($product) || $product['Product']['quantity'] > $cart['quantity']):
            $status = 2;
            $errorMsg = 'Product quanitity is more then available ';
        else:
            $this->Cart->set($cart);
            $success = $this->Cart->save($cart);
            if ($success):
                $status = 1;
                $errorMsg = 'Product added sucessfully to cart';
            else:
                $status = 0;
                $errorMsg = 'Product not added sucessfully to cart';
            endif;
        endif;
        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

    public function edit_cart() {
        $status = 0;
        $errorMsg = '';
        $cart['customer_id'] = $_REQUEST['customer_id'];
        $cart['session_id'] = $_REQUEST['session_id'];
        $cart['product_id'] = $_REQUEST['product_id'];
        $cart['quantity'] = $_REQUEST['product_quantity'];
        
        $product = $this->Product->findById($cart['product_id']);
        if (empty($product) || $product['Product']['quantity'] > $cart['quantity']):
            $status = 2;
            $errorMsg = 'Product quanitity is more then available ';
        else:
            $cart_data = $this->Cart->find('first', array('conditions' => array('customer_id' => $cart['customer_id'], 'session_id' => $cart['session_id'], 'product_id' => $cart['product_id'])));
            if (!empty($cart_data)):
                $success = $this->Cart->updateAll(array('quantity' => $cart['quantity']), array('cart_id' => $cart_data['Cart']['cart_id']));
                if ($success):
                    $status = 1;
                    $errorMsg = 'Cart updated successfully';
                else:
                    $status = 0;
                    $errorMsg = 'Cart not updated successfully';
                endif;
            else:
                $status = 2;
                $errorMsg = 'No item found in cart';
            endif;
        endif;

        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

}
