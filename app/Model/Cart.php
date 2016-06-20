<?php

class Cart extends AppModel{
    public $useTable = 'cart';
     public $primaryKey = 'cart_id'; 
    //public $belongsTo = array('Product' => array('foriegnKey' => 'product_id'));
}