<?php

class Product extends AppModel{
    public $useTable = 'product'; 
    public $primaryKey = 'product_id'; 
    //public $belongsTo = array('ProductDescription' => array('foriegnKey' => false, 'conditions' => array('ProductDescription.product_id = Product.product_id')));
}