<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';

class Customers extends REST_Controller
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['index_get']['limit']    = 500; // 500 requests per hour per user/key
        $this->methods['index_post']['limit']   = 100; // 100 requests per hour per user/key
        $this->methods['index_delete']['limit'] = 50;  // 50 requests per hour per user/key
    }

    public function index_get()
    {
        $this->load->model("subscriber_model","model");
        $data = $this->model->get_all();

        $id = $this->get('id');

        // If the id parameter doesn't exist return all the data

        if ($id === NULL)
        {
            // Check if the data store contains real data (in case the database result returns NULL)
            if ($data)
            {
                // Set the response and exit
                $this->response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
            }
            else
            {
                // Set the response and exit
                $this->response([
                    'status' => FALSE,
                    'message' => 'No data'
                ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
            }
        }

        // Find and return a single record for a particular data.
        $id = (int) $id;

        // Validate the id.
        if ($id <= 0)
        {
            // Invalid id, set the response and exit.
            $this->response(NULL, REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        // Get the data from the array, using the id as key for retreival.
        // Usually a model is to be used for this.

        $single = NULL;

        if (!empty($data))
        {
            $single = $this->model->get($id);
        }

        if (!empty($single))
        {
            $this->set_response($single, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        }
        else
        {
            $this->set_response([
                'status' => FALSE,
                'message' => 'Data could not be found'
            ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }
}