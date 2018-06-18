<?php

defined("BASEPATH") or exit("No direct script access allowed");

/**
 * Customer_group
 *  Handles all tasks related to Customer_group
 *
 * @extends MY_Controller Core CI Class
 */
class Customer_group extends MY_Controller
{

    protected $_class_methods = array('get', 'create', 'update', 'delete');

    /**
     * Constructor for Customer_group Class
     */
    public function __construct()
    {
        parent::__construct();
    }
}
