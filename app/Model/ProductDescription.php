<?php

class ProductDescription extends AppModel{
    public $primaryKey = 'product_id'; 
    public $useTable = 'product_description'; // This model does not use a database table
}