<?php

class CategoriesController extends AppController {

    public $uses = array('Category', 'CategoryDescription');
    public function beforeFilter() {
        parent::beforeFilter();
    }
    
    public function get_categories() {
        $status = 0;
        $errorMsg = '';  
//        $order = 'Category.category_id ASC';
        $conditions = array('Category.parent_id' => 0);
        $this->Category->bindModel(array('belongsTo' => array('CategoryDescription' => array('foreignKey' => FALSE, 'conditions' => array('CategoryDescription.category_id = Category.category_id')))));
//        $category_data = $this->Category->find('all', array('recursive' => 2, 'conditions' => $conditions ,'order' => $order));
        $category_data = $this->Category->find('all', array('recursive' => 2));
//        pr($category_data);die;
        if(!empty($category_data)):
            $i = 0;
            foreach($category_data as $k => $cat):
                //pr($cat);
                $data[$k]['id'] = $cat['Category']['category_id'];                
                $data[$k]['name'] = $cat['CategoryDescription']['name'];                
                $data[$k]['description'] = $cat['CategoryDescription']['description'];   
                if(!empty($cat['Category']['image'])):
                    $data[$k]['image'] = FULL_BASE_URL .'/image/'.str_replace(' ', '%20', $cat['Category']['image']);     
                else:
                    $data[$k]['image'] = "";                         
                endif;                
                $data[$k]['child_category'] = $this->get_child_category($cat['Category']['category_id']);                
                $i++;
            endforeach;
            $status = 1;            
        endif;
        $this->set(compact('status', 'errorMsg', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'data'));
    }
    // link to get_categories
    
    public function get_child_category($category_id) {
        $status = 0;
        $errorMsg = '';  
        $data = [];
        $order = 'Category.category_id ASC';
        $conditions = array('Category.parent_id' => $category_id);
        $this->Category->bindModel(array('belongsTo' => array('CategoryDescription' => array('foreignKey' => FALSE, 'conditions' => array('CategoryDescription.category_id = Category.category_id')))));
        $category_data = $this->Category->find('all', array('recursive' => 2, 'conditions' => $conditions ,'order' => $order));
        
        if(!empty($category_data)):
            $i = 0;
            foreach($category_data as $k => $cat):
                //pr($cat);
                $data[$k]['id'] = $cat['Category']['category_id'];                
                $data[$k]['name'] = $cat['CategoryDescription']['name'];                
                $data[$k]['description'] = $cat['CategoryDescription']['description'];                
                if(!empty($cat['Category']['image'])):
                    $data[$k]['image'] = FULL_BASE_URL .'/image/'.str_replace(' ', '%20', $cat['Category']['image']);     
                else:
                    $data[$k]['image'] = "";                         
                endif;
                $i++;
            endforeach;
            $status = 1;            
        endif;
       return $data;

    }

}
