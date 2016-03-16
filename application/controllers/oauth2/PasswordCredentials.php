<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';

class PasswordCredentials extends REST_Controller
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['index_get']['limit']    = 5;  // 500 requests per hour per user/key
        $this->methods['index_post']['limit']   = 2;  // 100 requests per hour per user/key
        $this->methods['index_delete']['limit'] = 1;  // 50 requests per hour per user/key
        
        $this->load->library("Server");
    }

    function index_post(){
        $this->server->password_credentials();
    }
}
