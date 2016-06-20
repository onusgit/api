<?php

App::import('Controller', 'Products');

class HomesController extends AppController {

    public $uses = array('Journal2Module', 'Category', 'Product', 'ProductToCategory', 'StockStatus', 'ProductDescription', 'Cart', 'CustomerWishlist');

    public function index() {
        $status = 0;
        $errorMsg = '';
        $data = array();

        // get slider 
        $slider = $this->Journal2Module->find('first', array('conditions' => array('module_type' => 'journal2_slider')));
        if (isset($slider['Journal2Module']['module_data']) && !empty($slider['Journal2Module']['module_data'])):
            $slider_arr = json_decode($slider['Journal2Module']['module_data'], true);
            if (!empty($slider_arr['slides'])):
                foreach ($slider_arr['slides'] as $k => $s):
                    $data['slides'][$k]['image'] = FULL_BASE_URL . '/image/' . reset($s['image']);
                    if (!empty($s['captions'])):
                        $data['slides'][$k]['caption'] = $s['captions'][0]['caption_name'];
                    endif;
                endforeach;
            endif;
        endif;

        //get home catedories        
        $this->Category->bindModel(array('belongsTo' => array('CategoryDescription' => array('foreignKey' => FALSE, 'conditions' => array('CategoryDescription.category_id = Category.category_id')))));
        $category_data = $this->Category->find('all');
        if (!empty($category_data)):
            foreach ($category_data as $k => $cat):
                $data['Categories'][$k]['id'] = $cat['Category']['category_id'];
                $data['Categories'][$k]['name'] = $cat['CategoryDescription']['name'];
                if (!empty($cat['Category']['image'])):
                    $data['Categories'][$k]['image'] = FULL_BASE_URL . '/image/' . $cat['Category']['image'];
                else:
                    $data['Categories'][$k]['image'] = "";
                endif;
            endforeach;
        endif;

        //get homes tag and its product
        $products = $this->Journal2Module->find('all', array('conditions' => array('module_type' => 'journal2_carousel')));

        if (!empty($products)):
            for ($i = 0; $i < 3; $i++):
                if (isset($products[$i]['Journal2Module']['module_data']) && !empty($products[$i]['Journal2Module']['module_data'])):
                    $product_arr = json_decode($products[$i]['Journal2Module']['module_data'], true);
//                     if(isset($product_arr['module_name'])):
//                         $data['product'][$i]['tag_name'] = $product_arr['module_name'];                     
//                     endif;  
                    if (isset($product_arr['product_sections'][0]['section_title']['value'][1])):
                        $data['product'][$i]['tag_name'] = $product_arr['product_sections'][0]['section_title']['value'][1];
                        if (isset($product_arr['product_sections'][0]['category']['data']['id'])):
                            $cat_product = $this->ProductToCategory->find('list', array('fields' => array('ProductToCategory.product_id'), 'conditions' => array('ProductToCategory.category_id' => $product_arr['product_sections'][0]['category']['data']['id'])));
                            $conditions[] = array('Product.product_id' => $cat_product);
                            $this->Product->bindModel(array(
                                'belongsTo' => array(
                                    'StockStatus' => array('foreignKey' => FALSE, 'conditions' => array('StockStatus.stock_status_id = Product.stock_status_id')),
                                    'ProductDescription' => array('foreignKey' => FALSE, 'conditions' => array('ProductDescription.product_id = Product.product_id')),
                                )
                            ));
                            $product_data = $this->Product->find('all', array('recursive' => 2, 'conditions' => $conditions, 'limit' => 8, 'group' => 'Product.product_id'));
                            if(!empty($product_data)):
                                foreach ($product_data as $k => $p):                                    
                                    $data['product'][$i]['products'][$k]['id'] = $p['Product']['product_id'];
                                    $data['product'][$i]['products'][$k]['name'] = $p['ProductDescription']['name'];
                                    $data['product'][$i]['products'][$k]['description'] = html_entity_decode($p['ProductDescription']['description']);
                                    $data['product'][$i]['products'][$k]['quantity'] = $p['Product']['quantity'];
                                    $data['product'][$i]['products'][$k]['price'] = $p['Product']['price'];
                                    $data['product'][$i]['products'][$k]['sku'] = $p['Product']['sku'];
                                    $data['product'][$i]['products'][$k]['model'] = $p['Product']['model'];
                                    $data['product'][$i]['products'][$k]['viewed'] = $p['Product']['viewed'];
                                    if(!empty($p['Product']['image'])):
                                        $data['product'][$i]['products'][$k]['image'] =  FULL_BASE_URL .'/image/'.$p['Product']['image'];
                                    else:
                                        $data['product'][$i]['products'][$k]['image'] =  '';
                                    endif;
                                    $data['product'][$i]['products'][$k]['minimum'] = $p['Product']['minimum'];
                                    $data['product'][$i]['products'][$k]['shipping'] = $p['Product']['shipping'];
                                    $data['product'][$i]['products'][$k]['stock_status'] = $p['StockStatus']['name'];
                                endforeach;
                            else:
                                $data['product'][$i]['products'] = array();                                
                            endif;
                        else:
                             $data['product'][$i]['products'] = array();                                
                        endif;
                    endif;
                endif;
            endfor;
        endif;
        if (!empty($data)):
            $status = 1;
            $errorMsg = 'success';
        endif;
        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

}
