<?php

defined("BASEPATH") or exit("No direct script access allowed");
/**
 * Model for activity table in database
 */
class Customer_model extends MY_Model
{

    /**
     * Inherited properties
     */
    protected $_table = "customer";

    protected $_primary_key = "customer_id";

    public $_table_data = [
        'reference_number', 'name', 'customer_type', 'email_address', 'telephone_number',
        'customer_details', 'industry_type_id', 'customer_group_id',
    ];

    public $_relates = [
        'industry_type' => 'industry_type_id',
        'customer_group' => 'customer_group_id',
    ];

    public $validation_rules = [
        [
            'field' => 'name',
            'label' => 'name',
            'rules' => 'trim|required|is_unique[customer.name]',
        ],
        [
            'field' => 'customer_details',
            'label' => 'customer details',
            'rules' => 'trim',
        ],
        [
            'field' => 'customer_type',
            'label' => 'customer type',
            'rules' => 'trim|required',
        ],
        [
            'field' => 'email_address',
            'label' => 'email address',
            'rules' => 'trim|required|valid_email',
        ],
        [
            'field' => 'telephone_number',
            'label' => 'telephone number',
            'rules' => 'trim|required|valid_tel',
        ],
        [
            'field' => 'industry_type_id',
            'label' => 'industry type',
            'rules' => 'trim|required|is_natural',
        ],
        [
            'field' => 'customer_group_id',
            'label' => 'customer group',
            'rules' => 'trim|required|is_natural',
        ],
    ];

    public function __construct()
    {
        parent::__construct();
    }
}
