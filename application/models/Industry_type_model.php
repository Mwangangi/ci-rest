<?php

defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Model for industry_type table in database
 */
class Industry_type_model extends MY_Model
{

    /**
     * Inherited properties
     */
    protected $_table = 'industry_type';

    protected $_primary_key = 'industry_type_id';

    public $_table_data = [
        'reference_number', 'name',
    ];

    public $validation_rules = [
        [
            'field' => 'name',
            'label' => 'name',
            'rules' => 'trim|required|is_unique[industry_type.name]',
        ],
    ];

    public function __construct()
    {
        parent::__construct();
    }
}
