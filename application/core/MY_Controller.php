<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Base Controller with general methods
 *  Extends default CI Controller
 *
 */
class MY_Controller extends CI_Controller
{
    /**
     * Constructor for MY_Controller
     *    Checks if user is logged in
     */
    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set('Africa/Nairobi');

        $method = $this->router->method;

        // check if method is inherited or within class
        if (!in_array($method, $this->_class_methods)) {
            $this->response(404, ['success' => false,
                'data' => ['Not found']]);
        }

        //input class properties
        $this->get_params = $this->input->get();
        $this->post_params = $this->input->post();
        //decode json php://input into an associative array
        $this->raw_input = json_decode($this->input->raw_input_stream, true);
        $this->raw_input = is_array($this->raw_input) ? $this->raw_input : [];
    }

    /**
     * Array of all inherited methods in a Controller
     *
     * @var array $_class_methods
     */
    protected $_class_methods = array();

    /**
     * get params $this->input->get()
     *
     * @var null $get_params
     */
    protected $get_params = null;

    /**
     * post params $this->input->post()
     *
     * @var null $post_params
     */
    protected $post_params = null;

    /**
     * php://input params $this->input->raw_input_stream
     *
     * @var null $raw_input
     */
    protected $raw_input = null;

    /**
     * Default GET function
     *
     * @access public
     * @return bool
     */
    public function get($id = null)
    {
        $entity = strtolower($this->router->class);
        $model_name = $entity . "_model";
        $this->load->model($model_name);

        if ($id == null) {
            //filters
            $filters = $this->read_filters($model_name);
            $data = $this->{$model_name}->get($filters);
        } else {
            $data = $this->{$model_name}->get($id);
        }

        if ($data) {
            $this->response(200, ['success' => true, 'data' => $data]);
        }

        $this->response(200, ['success' => false,
            'data' => ['No records found']]);
    }

    protected function read_filters($model)
    {
        $params = $this->get_params;
        $filters = [];

        foreach ($params as $filter_name => $filter_value) {
            if (in_array($filter_name, $this->{$model}->_table_data)) {
                $filters[$filter_name] = $filter_value;
            }
        }
        return array_filter($filters);
    }

    /**
     * Default CREATE function
     *
     * @access public
     */
    public function create()
    {
        $entity = strtolower($this->router->class);
        $data = $this->raw_input;

        $model = $entity . "_model";
        $this->load->model($model);

        //run validation
        $this->validate($data);

        $object_id = $this->{$model}->insert($data);

        if ($object_id) {
            $this->audit_log($object_id);
            //TODO: log user activity
            $this->response(200, ['success' => true, 'message' => 'Success adding record']);
        }
        $this->response(200, ['success' => false,
            'data' => ['Failed adding record']]);
    }

    /**
     * Default UPDATE function
     *
     * @access public
     * @return mixed
     */
    public function update($id)
    {
        $entity = strtolower($this->router->class);
        $data = $this->raw_input;

        $model_name = $entity . "_model";
        $this->load->model($model_name);

        //run validation
        $this->validate($data);

        $updated = $this->{$model_name}->update($data, $id);

        if ($updated) {
            $this->audit_log($id);
            $this->response(200, ['success' => true, 'message' => 'Success updating record']);
        }
        $this->response(200, ['success' => false,
            'data' => ['No change made to record']]);
    }

    /**
     * Default DELETE function
     *
     * @access public
     * @return bool
     */
    public function delete($id)
    {
        $entity = strtolower($this->router->class);
        $model_name = $entity . "_model";
        $this->load->model($model_name);

        $deleted = $this->{$model_name}->delete($id);

        if ($deleted) {
            $this->audit_log($id);
            $this->response(200, ['success' => true, 'message' => 'Success deleting record']);
        }
        $this->response(200, ['success' => false,
            'data' => ['Failed deleting record']]);
    }

    /**
     * Validate
     *     Runs validation for the create methods of child classes
     *
     * @access protected
     */
    protected function validate($data = null, $model_name = null)
    {
        $data = is_null($data) ? $this->raw_input : $data;

        if (is_null($model_name)) {
            $model = strtolower($this->router->class) . "_model";
        } else {
            $model = $model_name;
        }

        $this->load->model($model);
        $validation_rules = $this->{$model}->validation_rules;

        $this->form_validation->reset_validation();
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules($validation_rules);
        if ($this->form_validation->run() === false) {
            $this->response(200, ['success' => false,
                'data' => $this->form_validation->error_array()]);
        }
    }

    /**
     * Upload image
     *   Handles all image upload requests of child classes
     *
     *@access protected
     *@param string $_upload_path Folder name where file should be stored
     */
    protected function do_upload($_config = array(), $_upload_path = null)
    {
        $upload_dir = is_null($_upload_path) ? './uploads/' : './uploads/' . $_upload_path . '/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $config['upload_path'] = $upload_dir;
        $config['file_name'] = uniqid();
        $config['file_ext_tolower'] = true;
        $config['max_size'] = '10240'; //10MB

        $config = array_merge($_config, $config);

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('user_file')) {
            //NOOO
            $this->response(200, ['success' => false, 'data' => $this->upload->error_msg]);
        } else {
            //YEEESSS
            return true;
        }
    }

    /**
     * Set Response
     *
     * Generates output to client request with a status code and data.
     *
     * @access protected
     * @param  NULL\int $http_code
     * @param  array  $data
     */
    protected function response($http_code = null, $data = null)
    {
        if ($data !== null && is_numeric($http_code)) {
            set_status_header($http_code);
            $output = $data;
        } else {
            set_status_header(404);
            $output = ['success' => false, 'message' => ['No data found']];
        }
        header('Content-Type: application/json;charset=UTF-8');
        exit(json_encode($output));
    }
}
