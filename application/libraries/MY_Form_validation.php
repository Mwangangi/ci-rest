<?php

defined("BASEPATH") or exit("No direct script access is allowed");

/**
 * My_Form_validation
 *     Class to extend validation functions of core library
 */
class MY_Form_validation extends CI_Form_validation
{

    /**
     * Constructor of MY_Form_validation class
     */
    public function __construct($config = array())
    {
        parent::__construct($config);
    }

    /**
     * Valid date
     *   Check if the input value is a valid date
     *
     * @param string $str
     * @return bool
     */
    public function valid_date($str)
    {
        if (preg_match("/^((((19|[2-9]\d)\d{2})\-(0[13578]|1[02])\-(0[1-9]|[12]\d|3[01]))|(((19|[2-9]\d)\d{2})\-(0[13456789]|1[012])\-(0[1-9]|[12]\d|30))|(((19|[2-9]\d)\d{2})\-02\-(0[1-9]|1\d|2[0-8]))|(((1[6-9]|[2-9]\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00))\-02\-29))$/", $str)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Valid tel
     *   Check if the input value is a valid telephone/mobile number
     *
     * @param string $str
     * @return bool
     */
    public function valid_tel($str)
    {
        if (preg_match("/^([0|\+[0-9]{1,5})?([0-9]{10})$/", $str)) {
            return true;
        } else {
            return false;
        }
    }
}
