<?php

defined('BASEPATH') or exit('No direct script access allowed');

class MY_Model extends CI_Model
{

    /**
     * @var array() For a 'one-to-one' relationship
     *
     *  e.g ["referenced_table" => "foreign_key/referenced_field"]
     *
     */
    protected $_relates = array();

    /**
     * @var array() For a 'one-to-many' relationship
     *                  where there's a pivot table
     *   e.g 'secondary_table' => ['pivot_table', 'primary_table_id'
     *                                 'secondary_table_id']
     */
    protected $_has_multiple = array();

    /**
     * @var null name of database table
     */
    protected $_table = null;

    /**
     * @var array() fields of table in db
     */
    protected $_table_data = array();

    /**
     * @var array() CI form_validation rules
     */
    public $validation_rules = array();

    /**
     * @var null/int primary key of database table
     */
    protected $_primary_key = null;

    /**
     * Constructor of MY_Model class
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get
     *    Fetch record/s from db
     *
     * @param mixed $where criteria for WHERE clause
     *          e.g 5 or ['age >' => 5] or "amount > 100"
     *              NULL for all records
     *
     * @return array()
     */
    public function get($where = null, $relate = true, $order_by = "DESC")
    {
        $single_record = false;
        $data = null;

        if (is_numeric($where)) {
            $single_record = true;
            $this->db->where($this->_primary_key, $where);
        } elseif (is_array($where)) {
            foreach ($where as $_key => $_value) {
                $this->db->where($_key, $_value);
            }
        } elseif (is_string($where)) {
            $this->db->where($where);
        }

        $this->db->order_by($this->_primary_key, $order_by);

        $q = $this->db->get($this->_table);
        $data = $q->result_array();
        $this->db->flush_cache();

        if ($relate) {
            foreach ($data as $key => $data_item) {
                //when relation is one-to-one/many
                foreach ($this->_relates as $referenced_table => $referenced_field) {
                    $model = explode('.', $referenced_table)[0] . "_model";
                    $this->load->model($model);
                    $related_data = null;
                    if (is_array($referenced_field)) {
                        if ($data_item[$referenced_field[0]]) {
                            if (is_numeric($data_item[$referenced_field[0]])) {
                                $related_data = $this->{$model}->get([
                                    $referenced_field[0] => $data_item[$referenced_field[0]],
                                ], false)[0];
                            }
                        }
                    } else {
                        if (isset($data_item[$referenced_field])) {
                            if (!is_null($data_item[$referenced_field])) {
                                $related_data = $this->{$model}->get($data_item[$referenced_field], false);
                            }
                        }
                    }
                    $referenced_table = explode('.', $referenced_table);
                    $referenced_table = isset($referenced_table[1]) ? $referenced_table[1] : $referenced_table[0];
                    if ($related_data) {
                        $data[$key][$referenced_table] = $related_data;
                    } else {
                        $data[$key][$referenced_table] = [];
                    }
                }

                foreach ($this->_has_multiple as $secondary_table => $relation) {
                    $pivot_table = $relation[0];
                    $primary_table_id = $relation[1];
                    $secondary_table_id = $relation[2];

                    if (isset($data_item[$primary_table_id])) {
                        $pivot_model = $pivot_table . "_model";
                        $this->load->model($pivot_model);

                        $secondary_model = $secondary_table . "_model";
                        $this->load->model($secondary_model);

                        $pivot_data = $this->{$pivot_model}->get([
                            "$primary_table_id" => $data_item[$primary_table_id],
                        ], false);

                        if ($pivot_data) {
                            foreach ($pivot_data as $pivot_key => $pivot_row) {
                                $secondary_data = $this->{$secondary_model}->get($pivot_row[$secondary_table_id], false);
                                if ($secondary_data) {
                                    $data[$key][$secondary_table][] = $secondary_data;
                                } else {
                                    $data[$key][$secondary_table][] = [];
                                }
                            }
                        }
                    }
                }
            }
        }

        //reindex array values
        $data = array_values($data);

        if (count($data) == 1 && $single_record) {
            $data = $data[0];
        }

        return $data;
    }

    public function getSum($column, $id = null)
    {
        $this->db->flush_cache();

        $this->db->select_sum($column);
        if (is_numeric($id)) {
            $this->db->where($this->_primary_key, $id);
        }
        if (is_array($id)) {
            foreach ($id as $_key => $_value) {
                $this->db->where($_key, $_value);
            }
        }
        $q = $this->db->get($this->_table)->result_array();
        //reindex array values
        $q = array_values($q);

        if (count($q) == 1) {
            $q = $q[0];
        }

        return $q;
    }

    /**
     *  Insert
     *      Insert single record to table
     *
     * @param array() $data Array of data
     *                  e.g ['name'=>'John']
     *
     * @return mixed ID of inserted row or FALSE on failure
     */
    public function insert($data)
    {
        $this->db->trans_begin();

        $primary_data = array_filter(elements($this->_table_data, $data), 'strlen');
        if ($data == null || count($primary_data) == 0) {
            return false;
        }

        //set reference number if field has one
        if (in_array('reference_number', $this->_table_data)) {
            $primary_data['reference_number'] = $this->getRefNo();
        }

        $this->db->insert($this->_table, $primary_data);
        $insert_id = $this->db->insert_id();

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return $insert_id;
        }
    }

    /**
     * Insert unique
     *    Inserts multiple records to the same table
     *
     * @param array() $data Associative array of data to be inserted
     *                  e.g [['name'=>'John'],['name' => 'Jane']]
     *
     * @return mixed FALSE or Id of inserted record / existing record
     */
    public function insert_unique($data)
    {
        $data = array_filter(elements($this->_table_data, $data), 'strlen');
        if ($data == null || count($data) == 0) {
            return false;
        }

        //check if record exists
        foreach ($data as $key => $value) {
            $this->db->where($key, $value);
        }

        $old_record = $this->db->get($this->_table)->result_array();

        if ($old_record) {
            return false;
        }

        // set reference_number if field has one
        if (in_array('reference_number', $this->_table_data)) {
            $data['reference_number'] = $this->getRefNo();
        }

        $this->db->insert($this->_table, $data);
        return $this->db->insert_id();
    }

    /**
     *  Update
     *      Update table record with new data
     *
     * @param array $new_data New data to be SET
     *              e.g ['username'=>'New Username']
     * @param mixed $where WHERE params for SQL query
     *                  e.g 5 or ['name' => "John"] or "id = 10"
     *
     * @return bool TRUE on success, FALSE on failure
     */
    public function update($new_data, $where, $relate = true)
    {
        if (is_numeric($where)) {
            $this->db->where($this->_primary_key, $where);
        } elseif (is_array($where)) {
            foreach ($where as $_key => $_value) {
                $this->db->where($_key, $_value);
            }
        } elseif (is_string($where)) {
            $this->db->where($where);
        } else {
            //must provide a valid param
            return false;
        }
        $new_data = array_filter(elements($this->_table_data, $new_data), 'strlen');
        $this->db->update($this->_table, $new_data);
        return ($this->db->affected_rows() > 0) ? true : false;
    }

    /**
     *  Delete
     *     Permanently deletes a record/s
     *
     * @param mixed $id Primary key of table or an Array of criteria
     *                e.g 5 or ["name" => "John", "age >" => 10]
     * @return mixed TRUE on success FALSE on failure
     */
    public function delete($id)
    {
        if (is_numeric($id)) {
            $this->db->where($this->_primary_key, $id);
        } elseif (is_array($id)) {
            foreach ($id as $_key => $_value) {
                $this->db->where($_key, $_value);
            }
            //don't delete more than 10 records at a go
            $this->db->limit(5);
        } else {
            //must provide a valid param
            return false;
        }

        $this->db->delete($this->_table);

        return ($this->db->affected_rows() > 0) ? true : false;
    }

    /**
     * Get reference number
     *  Return an autogenerated ref number from primary_key
     */
    public function getRefNo()
    {
        $this->db->flush_cache();
        $query = $this->db->query("SELECT * FROM `$this->_table` ORDER BY `$this->_primary_key` DESC LIMIT 1");

        $data = $query->result_array();
        if ($data) {
            $id = (int) $data[0][$this->_primary_key] + 1;
        } else {
            $id = 1;
        }

        $ref_number = strtoupper($this->_table) . "/" . sprintf("%03d", $id);
        return $ref_number;
    }

    public function getCount($id = null)
    {
        $this->db->flush_cache();

        if (is_numeric($id)) {
            $this->db->where($this->_primary_key, $id);
        }
        if (is_array($id)) {
            foreach ($id as $_key => $_value) {
                $this->db->where($_key, $_value);
            }
        }

        $this->db->from($this->_table);
        return $this->db->count_all_results();
    }

    /**
     * Get Id
     *  Fetch the Id of record specified in params
     *
     * @param string $where filter field for query
     *
     */
    public function getId($where)
    {
        $this->db->flush_cache();
        if (is_array($where)) {
            foreach ($where as $key => $value) {
                $this->db->where($key, $value);
            }
        } elseif (is_string($where)) {
            $this->db->where($where);
        } else {
            die('Provide a valid argument');
        }

        $query = $this->db->get($this->_table)->result_array();

        if ($query) {
            return $query[0][$this->_primary_key];
        }
    }
}
