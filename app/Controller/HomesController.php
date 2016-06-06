<?php

class HomesController extends AppController {

    public $uses = array('Journal2Module', 'Category');

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
                    $data['slides'][$k]['image'] = FULL_BASE_URL .'/image/'.reset($s['image']);
                    if (!empty($s['captions'])):
                        $data['slides'][$k]['caption'] = $s['captions'][0]['caption_name'];
                    endif;
                endforeach;
            endif;
        endif;
        
        //get home catedories        
        $this->Category->bindModel(array('belongsTo' => array('CategoryDescription' => array('foreignKey' => FALSE, 'conditions' => array('CategoryDescription.category_id = Category.category_id')))));
        $category_data = $this->Category->find('all');
        if(!empty($category_data)):            
            foreach($category_data as $k => $cat):                
                $data['Categories'][$k]['id'] = $cat['Category']['category_id'];                
                $data['Categories'][$k]['name'] = $cat['CategoryDescription']['name'];                
                if(!empty($cat['Category']['image'])):
                    $data['Categories'][$k]['image'] = FULL_BASE_URL .'/image/'.$cat['Category']['image'];     
                else:
                    $data['Categories'][$k]['image'] = "";                         
                endif;                                
            endforeach;            
        endif;
        
        //get homes tag and its product
        $products = $this->Journal2Module->find('all', array('conditions' => array('module_type' => 'journal2_carousel')));
        if(!empty($products)):
            foreach ($products as $k => $p):
                 if (isset($p['Journal2Module']['module_data']) && !empty($p['Journal2Module']['module_data'])):
                     $product_arr = json_decode($p['Journal2Module']['module_data'], true); 
                     if(isset($product_arr['module_name'])):
                         $data['product'][$k]['tag'] = $product_arr['module_name'];                     
                     endif;                     
                 endif;
            endforeach;
        endif;
        if (!empty($data)):
            $status = 1;
            $errorMsg = 'success';
        endif;
        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }

}
