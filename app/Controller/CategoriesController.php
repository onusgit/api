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
        $cat_id_name = array();
        if (!empty($category_data)):
            $i = 0;
            foreach ($category_data as $k => $cat):
                //pr($cat);
                $data[$k]['id'] = $cat['Category']['category_id'];
                $data[$k]['name'] = $cat['CategoryDescription']['name'];
                $data[$k]['description'] = $cat['CategoryDescription']['description'];
                if (!empty($cat['Category']['image'])):
                    $data[$k]['image'] = FULL_BASE_URL . '/image/' . str_replace(' ', '%20', $cat['Category']['image']);
                else:
                    $data[$k]['image'] = "";
                endif;
                $data[$k]['child_category_count'] = count($this->get_child_category($cat['Category']['category_id']));
                $data[$k]['child_category'] = $this->get_child_category($cat['Category']['category_id']);
                $i++;
            endforeach;
            $j = 0;
            foreach ($data as $d):
                $cat_id_name[$j]['id'] = $d['id'];
                $cat_id_name[$j]['name'] = $d['name'];
                $j++;
                if (!empty($d['child_category'])):
                    foreach ($d['child_category'] as $c):
                        $cat_id_name[$j]['id'] = $c['id'];
                        $cat_id_name[$j]['name'] = $c['name'];
                        $j++;
                    endforeach;
                endif;

            endforeach;
            $status = 1;
        endif;

        $this->set(compact('status', 'errorMsg', 'cat_id_name', 'data'));
        $this->set('_serialize', array('status', 'errorMsg', 'cat_id_name', 'data'));
    }

    // link to get_categories

    public function get_child_category($category_id) {
        $status = 0;
        $errorMsg = '';
        $data = [];
        $order = 'Category.category_id ASC';
        $conditions = array('Category.parent_id' => $category_id);
        $this->Category->bindModel(array('belongsTo' => array('CategoryDescription' => array('foreignKey' => FALSE, 'conditions' => array('CategoryDescription.category_id = Category.category_id')))));
        $category_data = $this->Category->find('all', array('recursive' => 2, 'conditions' => $conditions, 'order' => $order));

        if (!empty($category_data)):
            $i = 0;
            foreach ($category_data as $k => $cat):
                //pr($cat);
                $data[$k]['id'] = $cat['Category']['category_id'];
                $data[$k]['name'] = $cat['CategoryDescription']['name'];
                $data[$k]['description'] = $cat['CategoryDescription']['description'];
                if (!empty($cat['Category']['image'])):
                    $data[$k]['image'] = FULL_BASE_URL . '/image/' . str_replace(' ', '%20', $cat['Category']['image']);
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
