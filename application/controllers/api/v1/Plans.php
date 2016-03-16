<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';

class Plans extends REST_Controller
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['index_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['index_put']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['index_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['index_delete']['limit'] = 50;  // 50 requests per hour per user/key
        // load the corresponding model
        $this->load->model("plan_model", "model");
    }

    /*
     * Forces application/json Content-Type header to be present in every request
     */

    private function enforce_json()
    {
        // accepts only JSON encoded in POST, PUT and PATCH requests
        $content_type = $this->input->server('CONTENT_TYPE');
        if ( empty($content_type) === FALSE ) {
            // If a semi-colon exists in the string, then explode by ; and get the value of where
            // the current array pointer resides. This will generally be the first element of the array
            $content_type = (strpos($content_type, ';') !== FALSE ? current(explode(';', $content_type)) : $content_type);
            if ( $content_type !== $this->_supported_formats['json'] ) {
                $this->response([
                    'status' => FALSE,
                    'error' => 'Unsupported Content-Type. JSON expected.'
                        ], REST_Controller::HTTP_UNSUPPORTED_MEDIA_TYPE);
            }
        }
    }

    private function response_array_not_allowed()
    {
        $this->response([
            'status' => FALSE,
            'error' => 'Wrong array or object representation.'
                ], REST_Controller::HTTP_BAD_REQUEST);
    }

    private function response_missing_id()
    {
        $this->response([
            'status' => FALSE,
            'error' => 'Missing or invalid ID.'
                ], REST_Controller::HTTP_BAD_REQUEST);
    }

    private function response_empty_data()
    {
        $this->response([
            'status' => FALSE,
            'error' => 'Empty data.'
                ], REST_Controller::HTTP_BAD_REQUEST);
    }

    private function response_not_found($id = '-')
    {
        $error = ($id === '-') ? 'Not found.' : "Not found (id=$id).";
        $this->response([
            'status' => FALSE,
            'error' => $error
                ], REST_Controller::HTTP_NOT_FOUND);
    }

    private function response_ok($message)
    {
        $this->response($message, REST_Controller::HTTP_OK);
    }

    private function response_validation_failed()
    {
        $this->response([
            'status' => FALSE,
            'error' => 'Validation failed.'
                ], REST_Controller::HTTP_BAD_REQUEST);
    }

    private function response_operation_failed($error = 'Operation failed.')
    {
        $this->response([
            'status' => FALSE,
            'error' => $error
                ], REST_Controller::HTTP_BAD_REQUEST);
    }

    private function response_no_content()
    {
        $this->response(NULL, REST_Controller::HTTP_NO_CONTENT);
    }

    private function response_id_not_required()
    {
        $this->response([
            'status' => FALSE,
            'error' => 'Resource ID not required.'
                ], REST_Controller::HTTP_BAD_REQUEST);
    }

    /**
     * Retrieve one or all items from resource
     * @param integer $id
     */
    public function index_get($id = '-')
    {
        // get requires an ID in URI
        if ( $id !== '-' && $id <= 0 ) {
            $this->response_missing_id();
        }

        $data = NULL;
        if ( $id === '-' ) { // get all items
            $data = $this->model->get_all();
            if ( $data ) {
                // Set the response and exit
                $this->response_ok($data);
            } else { // Set the response and exit
                $this->response_not_found();
            }
        }

        // has id: retrieve single resource item
        $id = (int) $id;

        // id is valid, get single data
        $message = $this->model->get($id);

        if ( !empty($message) ) {
            $this->response_ok($message);
        } else {
            $this->response_not_found($id);
        }
    }

    /**
     * Creates a single record.
     */
    public function index_post($id = '-')
    {

        $this->enforce_json();

        if ( $id !== '-' ) {
            $this->response_id_not_required();
        }

        // load data from post arguments and parse as json
        $data = $this->post();

        // validate the id (post/create operation generally does not requires an id)
        if ( array_key_exists('id', $data) ) {
            // id not needed in post
            $this->response_id_not_required();
        }

        // check data content
        if ( empty($data) ) {
            $this->response_empty_data();
        }

        if ( isset($data[0]) && is_array($data[0]) ) { // multiple items not allowed
            $this->response_array_not_allowed();
        }

        // data format seems to be OK, now proceed to data validation
        $this->form_validation->validation_data = $data;
        $this->form_validation->set_rules($this->model->validation_rules);
        if ( !$this->form_validation->run() ) { // validation failed
            $this->response_validation_failed();
        }

        // validation OK, proceed to insert
        $id = $this->model->insert($data, TRUE); // TRUE to skips validation: already validated at this point
        if ( $id === FALSE ) { // failed to insert
            $this->response_operation_failed('Post operation failed.');
        }

        // everything went fine, return the new resource
        $message = $this->model->get($id);
        $this->response_ok($message);
    }

    /**
     * Updates a single record.
     */
    public function index_put($id = '-')
    {
        $this->enforce_json();

        // put requires an ID in URI
        if ( $id === '-' || ($id !== '-' && $id <= 0) ) {
            $this->response_missing_id();
        }

        // load data from put arguments and parse as json
        $data = $this->put();

        // check data content
        if ( empty($data) ) {
            $this->response_empty_data();
        }

        if ( isset($data[0]) && is_array($data[0]) ) { // multiple items not allowed
            $this->response_array_not_allowed();
        }

        // check if resource id exist
        if ( empty($this->model->get($id)) ) {
            $this->response_not_found($id);
        }

        // data format seems to be OK, now proceed to data validation
        $this->form_validation->validation_data = $data;
        $this->form_validation->set_rules($this->model->validation_rules);
        if ( !$this->form_validation->run() ) { // validation failed
            $this->response_validation_failed();
        }

        // validation OK, proceed to update the record
        $result = $this->model->update($id, $data, TRUE); // TRUE to skips validation: already validated at this point
        if ( $result === FALSE ) { // failed to update (resource does not exist, no permission and several other possible reasons
            $this->response_operation_failed('Put operation failed.');
        }

        // everything went fine, return the new resource
        $message = $this->model->get($id);
        $this->response_ok($message);
    }

    /**
     * Deletes a record
     * @param integer $id
     */
    public function index_delete($id = '-')
    {
        // delete requires an ID in URI
        if ( $id === '-' || ($id !== '-' && $id <= 0) ) {
            $this->response_missing_id();
        }

        // check if resource id exist
        if ( empty($this->model->get($id)) ) {
            $this->response_not_found($id);
        }

        // resource exist, try to delete
        if ( $this->model->delete($id) === FALSE ) {
            $this->response_operation_failed('Delete operation failed.');
        }

        $this->response_no_content();
    }

}
