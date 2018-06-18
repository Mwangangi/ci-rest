<?php

defined("BASEPATH") or exit("No direct script access allowed");

/**
 * Customer
 *  Handles all tasks related to Customer
 *
 * @extends MY_Controller Core CI Class
 */
class Customer extends MY_Controller
{

    protected $_class_methods = array('get', 'create', 'update', 'delete');

    /**
     * Constructor for Customer Class
     */
    public function __construct()
    {
        parent::__construct();
    }
}
