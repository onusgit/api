<?php

class Product extends AppModel{
    public $useTable = 'Product'; 
    public $primaryKey = 'product_id'; 
    //public $belongsTo = array('ProductDescription' => array('foriegnKey' => false, 'conditions' => array('ProductDescription.product_id = Product.product_id')));
}