<?php

defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Model for customer_group table in database
 */
class Customer_group_model extends MY_Model
{

    /**
     * Inherited properties
     */
    protected $_table = 'customer_group';

    protected $_primary_key = 'customer_group_id';

    public $_table_data = [
        'reference_number', 'name',
    ];

    public $validation_rules = [
        [
            'field' => 'name',
            'label' => 'name',
            'rules' => 'trim|required|is_unique[customer_group.name]',
        ],
    ];

    public function __construct()
    {
        parent::__construct();
    }
}
