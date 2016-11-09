<?php

class ProductsController extends AppController {

    public $uses = array('Journal2Module', 'Product', 'ProductToCategory', 'StockStatus', 'ProductDescription', 'Cart', 'CustomerWishlist');

    public function get_products() {
        $status = 0;
        $errorMsg = '';
        $data = [];
        $conditions = [];
        $order = [];
        $cat_product = array();

        if (isset($_REQUEST['category_id']) && !empty($_REQUEST['category_id'])):
            if (!empty($_REQUEST['category_id'])):
                $cat_product = $this->ProductToCategory->find('list', array('fields' => array('ProductToCategory.product_id'), 'conditions' => array('ProductToCategory.category_id' => $_REQUEST['category_id'])));
                $conditions[] = array('Product.product_id' => $cat_product);
            endif;
            if (!empty($_REQUEST['product_tags'])):
                $tags = explode(',', $_REQUEST['product_tags']);
                foreach ($tags as $tag):
                    $product = $this->ProductDescription->find('list', array('fields' => array('ProductDescription.product_id'), 'conditions' => array('ProductDescription.tag regexp ' => '[[:<:]]' . $tag . '[[:>:]]')));
                    $conditions[] = array('Product.product_id' => $product);
                endforeach;
            endif;

            if (!empty($_REQUEST['status'])):
                //for site
                //5 SOLD OUT
                //6 2 - 3 Days
                //7 In Stock
                //8 PREORDER    
                $stock_status = explode(',', $_REQUEST['status']);
                $conditions[] = array('Product.stock_status_id' => $stock_status);
            endif;

            if (!empty($_REQUEST['price_low'])):
                $conditions[] = array('Product.price >=' => $_REQUEST['price_low']);
            endif;

            if (!empty($_REQUEST['price_high'])):
                $conditions[] = array('Product.price <=' => $_REQUEST['price_high']);
            endif;

            if (empty($order)):
                $order = 'Product.product_id ASC';
            endif;
            $this->Product->bindModel(array(
                'belongsTo' => array(
                    'StockStatus' => array('foreignKey' => FALSE, 'conditions' => array('StockStatus.stock_status_id = Product.stock_status_id')),
                    'ProductDescription' => array('foreignKey' => FALSE, 'conditions' => array('ProductDescription.product_id = Product.product_id')),
                )
            ));
            $page = isset($this->request->query['page']) ? $this->request->query['page'] : 0;
            $limit = 10;
            $offset = ($page) * $limit;

            $product_data = $this->Product->find('all', array('recursive' => 2, 'conditions' => $conditions, 'order' => $order, 'limit' => $limit, 'offset' => $offset, 'group' => 'Product.product_id'));

            $total_product = $this->Product->find('first', array('conditions' => $conditions, 'fields' => array('COUNT(Product.product_id) AS total_product'), 'group by' => 'Product.product_id', 'order' => $order));
            $min_max_total = $this->Product->find('first', array('conditions' => array('Product.product_id' => $cat_product), 'fields' => array('MIN(Product.price) AS min_price', 'MAX(Product.price) AS max_price'), 'group by' => 'Product.product_id', 'order' => $order));


            if (!empty($product_data)):
                $status = 1;
                foreach ($product_data as $k => $pr_data):
                    $data[$k]['id'] = $pr_data['Product']['product_id'];
                    $data[$k]['name'] = $pr_data['ProductDescription']['name'];
                    $data[$k]['description'] = html_entity_decode($pr_data['ProductDescription']['description']);
                    $data[$k]['quantity'] = $pr_data['Product']['quantity'];
                    $data[$k]['price'] = number_format($pr_data['Product']['price'], 2);
                    $data[$k]['sku'] = $pr_data['Product']['sku'];
                    $data[$k]['model'] = $pr_data['Product']['model'];
                    $data[$k]['viewed'] = $pr_data['Product']['viewed'];
                    if (!empty($pr_data['Product']['image'])):
                        $data[$k]['image'] = FULL_BASE_URL . '/image/' . str_replace(' ', '%20', $pr_data['Product']['image']);
                    else:
                        $data[$k]['image'] = '';
                    endif;
                    $data[$k]['minimum'] = $pr_data['Product']['minimum'];
                    $data[$k]['shipping'] = $pr_data['Product']['shipping'];
                    $data[$k]['stock_status'] = $pr_data['StockStatus']['name'];
                endforeach;
            endif;

            $min_price = number_format($min_max_total[0]['min_price'], 2);
            $max_price = number_format($min_max_total[0]['max_price'], 2);
            $total_products = $total_product[0]['total_product'];
            $total_page = floor($total_products / $limit);
            $availability = array('7,In Stock', '5,Out of Stock');
            $tags = array('sweets', 'bhusu');
        else:
            $errorMsg = 'please pass category';
        endif;
        $this->set(compact('status', 'errorMsg', 'min_price', 'max_price', 'total_products', 'total_page', 'data', 'tags', 'availability'));
        $this->set('_serialize', array('status', 'errorMsg', 'min_price', 'max_price', 'tags', 'availability', 'total_products', 'total_page', 'data'));
    }
    
    public function search_product() {
        $status = 0;
        $errorMsg = '';
        $data = [];        
        $order = [];        
        if (isset($_REQUEST['q']) && !empty($_REQUEST['q'])):                       
            $this->Product->bindModel(array(
                'belongsTo' => array( 
                    'StockStatus' => array('foreignKey' => FALSE, 'conditions' => array('StockStatus.stock_status_id = Product.stock_status_id')),                    
                )
            ));
            $this->ProductDescription->bindModel(array(
                'belongsTo' => array(                     
                    'Product' => array('foreignKey' => FALSE, 'conditions' => array('ProductDescription.product_id = Product.product_id')),                    
                )
            ));
            $page = isset($this->request->query['page']) ? $this->request->query['page'] : 0;
            $limit = 10;
            $offset = ($page) * $limit;
            $product_data = $this->ProductDescription->find('all', array('recursive' => 2, 'conditions' => array('ProductDescription.name like' => "%".$_REQUEST['q']."%"), 'order' => $order, 'limit' => $limit, 'offset' => $offset));           
            $total_product = $this->ProductDescription->find('first', array('fields' => array('COUNT(ProductDescription.product_id) AS total_product'), 'conditions' => array('ProductDescription.name like' => "%".$_REQUEST['q']."%")));           
            if (!empty($product_data)):
                $status = 1;
                foreach ($product_data as $k => $pr_data):
                    $data[$k]['id'] = $pr_data['Product']['product_id'];
                    $data[$k]['name'] = $pr_data['ProductDescription']['name'];
                    $data[$k]['description'] = html_entity_decode($pr_data['ProductDescription']['description']);
                    $data[$k]['quantity'] = $pr_data['Product']['quantity'];
                    $data[$k]['price'] = number_format($pr_data['Product']['price'], 2);
                    $data[$k]['sku'] = $pr_data['Product']['sku'];
                    $data[$k]['model'] = $pr_data['Product']['model'];
                    $data[$k]['viewed'] = $pr_data['Product']['viewed'];
                    if (!empty($pr_data['Product']['image'])):
                        $data[$k]['image'] = FULL_BASE_URL . '/image/' . str_replace(' ', '%20', $pr_data['Product']['image']);
                    else:
                        $data[$k]['image'] = '';
                    endif;
                    $data[$k]['minimum'] = $pr_data['Product']['minimum'];
                    $data[$k]['shipping'] = $pr_data['Product']['shipping'];
                    $data[$k]['stock_status'] ="";
                    if(isset($pr_data['Product']['StockStatus']) && !empty($pr_data['Product']['StockStatus'])):
                        $data[$k]['stock_status'] = $pr_data['Product']['StockStatus']['name'];
                    endif;
                endforeach;
            endif;

           
            $total_products = $total_product[0]['total_product'];
            $total_page = ceil($total_products / $limit);           
        else:
            $errorMsg = 'please pass category';
        endif;
        $this->set(compact('status', 'errorMsg', 'total_products', 'total_page', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'total_products', 'total_page', 'data'));
    }

    public function product_detail() {
        $status = 0;
        $errorMsg = '';
        $data = array();
        if ($this->request->is(array('get', 'post'))):
            if (!empty($_REQUEST['product_id'])):
                $product_id = $_REQUEST['product_id'];

                $this->ProductToCategory->bindModel(
                        array(
                            'belongsTo' => array('CategoryDescription' => array('foreignKey' => 'category_id'))
                        )
                );

                $this->Product->bindModel(array(
                    'belongsTo' => array(
                        'StockStatus' => array('foreignKey' => FALSE, 'conditions' => array('StockStatus.stock_status_id = Product.stock_status_id')),
                        'ProductDescription' => array('foreignKey' => FALSE, 'conditions' => array('ProductDescription.product_id = Product.product_id')),
                    ),
                    'hasMany' => array(
                        'ProductToCategory' => array('foriegnKey' => 'product_id')
                    ),
                ));
                $product_data = $this->Product->find('first', array('recursive' => 2, 'conditions' => array('Product.product_id' => $product_id)));
                if (!empty($product_data)):
                    $status = 1;
                    $data['id'] = $product_data['Product']['product_id'];
                    $data['name'] = $product_data['ProductDescription']['name'];
                    $data['description'] = $product_data['ProductDescription']['description'];
                    $data['quantity'] = $product_data['Product']['quantity'];
                    $data['price'] = $product_data['Product']['price'];
                    $data['sku'] = $product_data['Product']['sku'];
                    $data['model'] = $product_data['Product']['model'];
                    $data['viewed'] = $product_data['Product']['viewed'];
                    if (!empty($product_data['Product']['image'])):
                        $data['image'] = FULL_BASE_URL . '/image/' . str_replace(' ', '%20', $product_data['Product']['image']);
                    else:
                        $data['image'] = '';
                    endif;
                    $data['minimum'] = $product_data['Product']['minimum'];
                    $data['shipping'] = $product_data['Product']['shipping'];
                    $data['stock_status'] = $product_data['StockStatus']['name'];
                    foreach ($product_data['ProductToCategory'] as $k => $cat):
                        $cat_data[$k]['id'] = $cat['CategoryDescription']['category_id'];
                        $cat_data[$k]['name'] = $cat['CategoryDescription']['name'];
                        $cat_data[$k]['description'] = $cat['CategoryDescription']['description'];
                    endforeach;
                    $data['category'] = $cat_data;
//                $cat = $this->ProductToCategory->find('all', array('conditions' => array('ProductToCategory.product_id' => $product_data['Product']['product_id'])));
                else:
                    $status = 0;
                    $errorMsg = 'No product found';
                endif;
            else:
                $status = 2;
                $errorMsg = 'Please add product id';
            endif;
        endif;

        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

    public function get_product_by_id($id = null) {
        $status = 0;
        $errorMsg = '';
        $data = array();
        if ($id):
            $this->Product->bindModel(array(
                'belongsTo' => array(
                    'StockStatus' => array('foreignKey' => FALSE, 'conditions' => array('StockStatus.stock_status_id = Product.stock_status_id')),
                    'ProductDescription' => array('foreignKey' => FALSE, 'conditions' => array('ProductDescription.product_id = Product.product_id')),
                )
            ));
            $product_data = $this->Product->find('first', array('recursive' => 2, 'conditions' => array('Product.product_id' => $id), 'group' => 'Product.product_id'));

            if (!empty($product_data)):
                $data['id'] = $product_data['Product']['product_id'];
                $data['name'] = $product_data['ProductDescription']['name'];
                $data['description'] = html_entity_decode($product_data['ProductDescription']['description']);
                $data['quantity'] = $product_data['Product']['quantity'];
                $data['price'] = number_format($product_data['Product']['price'], 2);
                $data['sku'] = $product_data['Product']['sku'];
                $data['model'] = $product_data['Product']['model'];
                $data['viewed'] = $product_data['Product']['viewed'];
                if (!empty($product_data['Product']['image'])):
                    $data['image'] = FULL_BASE_URL . '/image/' . str_replace(' ', '%20', $product_data['Product']['image']);
                else:
                    $data['image'] = '';
                endif;
                $data['minimum'] = $product_data['Product']['minimum'];
                $data['shipping'] = $product_data['Product']['shipping'];
                $data['stock_status'] = $product_data['StockStatus']['name'];
            endif;
            if(!empty($data)):
                $status = 1;
            endif;
        endif;
        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

    public function manage_cart() {
        $status = $total_item = $total_cost = 0;
        $errorMsg = '';
        $data = array();

        if ($this->request->is(array('post', 'get'))):
//            if (empty($_REQUEST['customer_id']) || empty($_REQUEST['session_id'])):
            if (empty($_REQUEST['session_id'])):
                $status = 2;
                $errorMsg = 'Parameter missing';
            else:
                if (!empty($_REQUEST['product_id']) && $_REQUEST['product_quantity'] == 0):
                    $cart_data = $this->Cart->find('first', array('conditions' => array('Cart.session_id' => $_REQUEST['session_id'], 'Cart.product_id' => $_REQUEST['product_id'])));
                    if (!empty($cart_data)):
                        $this->Cart->delete($cart_data['Cart']['cart_id']);
                        $status = 3; // remove from cart
                    endif;
                elseif (!empty($_REQUEST['product_id']) && !empty($_REQUEST['product_quantity'])):
                    $cart['customer_id'] = isset($_REQUEST['customer_id']) ? $_REQUEST['customer_id'] : '0';
                    $cart['session_id'] = $_REQUEST['session_id'];
                    $cart['product_id'] = $_REQUEST['product_id'];
                    $cart['quantity'] = $_REQUEST['product_quantity'];
                    $product = $this->Product->findByProductId($cart['product_id']);
                    if (empty($product) || $cart['quantity'] > $product['Product']['quantity']):
                        $status = 2;
                        $errorMsg = 'Product quanitity is not in stock';
                    else:
                        $cart_data = $this->Cart->find('first', array('conditions' => array('Cart.session_id' => $cart['session_id'], 'Cart.product_id' => $cart['product_id'])));
                        if (!empty($cart_data)):
                            $success = $this->Cart->updateAll(array('quantity' => $cart['quantity']), array('cart_id' => $cart_data['Cart']['cart_id']));
                            if ($success):
                                $status = 1;
                                $errorMsg = 'Product updated sucessfully to cart';
                            else:
                                $status = 0;
                                $errorMsg = 'Product not updated sucessfully to cart';
                            endif;
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
                    endif;
                endif;
                $this->Product->bindModel(array('belongsTo' => array('ProductDescription' => array('foriegnKey' => 'ProductDescription.product_id'))));
                $this->Cart->bindModel(array('belongsTo' => array('Product' => array('foriegnKey' => 'product_id'))));
                $cart_product_data = $this->Cart->find('all', array('recursive' => 1, 'conditions' => array('Cart.session_id' => $_REQUEST['session_id'])));
//                pr($cart_product_data);
//                die;
                if (!empty($cart_product_data)):
                    if ($status != 3):
                        $status = 1;
                    endif;
                    $weight = 0;
                    foreach ($cart_product_data as $k => $c_data):
                        $product_description = $this->ProductDescription->find('first', array('conditions' => array('ProductDescription.product_id' => $c_data['Cart']['product_id'])));
                        $data[$k]['product_id'] = $c_data['Cart']['product_id'];
                        $data[$k]['product_image'] = FULL_BASE_URL . '/image/' . str_replace(' ', '%20', $c_data['Product']['image']);
                        $data[$k]['product_price'] = number_format($c_data['Product']['price'], 2);
                        $total_cost += $c_data['Product']['price'] * $c_data['Cart']['quantity'];
                        $data[$k]['product_name'] = $product_description['ProductDescription']['name'];
                        $data[$k]['quantity'] = $c_data['Cart']['quantity'];
                        $weight += $c_data['Cart']['quantity'] * $c_data['Product']['weight'];
                    endforeach;

                else:
                    $errorMsg = 'No product found in cart';
                endif;
            endif;
        endif;
        if (!empty($data)):
            $total_item = count($data);
            $total_cost = number_format($total_cost, 2);
            $total_weight = $weight;
        else:
            $status = 0;
        endif;
        $this->set(compact('status', 'errorMsg', 'total_item', 'total_weight', 'total_cost', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'total_item', 'total_weight', 'total_cost', 'data'));
    }

}
