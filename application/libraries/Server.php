<?php

//date_default_timezone_set('America/Sao_Paulo');

class Server{

	function __construct($config=array()){
        // Is the config file in the environment folder?
        if ( ! file_exists($file_path = APPPATH.'config/'.ENVIRONMENT.'/database.php')
			&& ! file_exists($file_path = APPPATH.'config/database.php'))
		{
			show_error('The configuration file database.php does not exist.');
		}

		include($file_path);

        $config = $db['oauth'];

		OAuth2\Autoloader::register();
		$pdo = array('dsn' => $config["dsn"], 'port' => $config["port"], 'dbname' => $config["database"], 'username' => $config["username"], 'password' => $config["password"]);
        $this->storage = new OAuth2\Storage\Pdo($pdo);
		$this->server = new OAuth2\Server($this->storage, array(
            'allow_implicit' => true,
            'always_issue_new_refresh_token' => true,
            'refresh_token_lifetime' => 2592000, // 30 days
        ));
		$this->request = OAuth2\Request::createFromGlobals();
		$this->response = new OAuth2\Response();
	}

	/**
	* client_credentials, for more see: http://tools.ietf.org/html/rfc6749#section-4.3
	*/
	public function client_credentials(){
		$this->server->addGrantType(new OAuth2\GrantType\ClientCredentials($this->storage, array(
    		"allow_credentials_in_request_body" => true
		)));
		$this->server->handleTokenRequest($this->request)->send();
	}

	/**
	* password_credentials, for more see: http://tools.ietf.org/html/rfc6749#section-4.3
	*/
	public function password_credentials(){
        //$this->storage->setUser('bgeneto', '123123', 'Bernhard', 'Georg');
        $this->server->addGrantType(new OAuth2\GrantType\UserCredentials($this->storage));
        $this->server->handleTokenRequest($this->request)->send();
	}

	/**
	* refresh_token, for more see: http://tools.ietf.org/html/rfc6749#page-74
	*/
	public function refresh_token(){
		$this->server->addGrantType(new OAuth2\GrantType\RefreshToken($this->storage, array(
			"always_issue_new_refresh_token" => true,
			"unset_refresh_token_after_use" => true,
			"refresh_token_lifetime" => 2592000,
		)));
		$this->server->handleTokenRequest($this->request)->send();
	}

	/**
	* limit scpoe here
	* @param $scope = "node file userinfo"
	*/
	public function require_scope($scope=""){
		if (!$this->server->verifyResourceRequest($this->request, $this->response, $scope)) {
    		$this->server->getResponse()->send();
    		die;
		}
	}

	public function check_client_id(){
		if (!$this->server->validateAuthorizeRequest($this->request, $this->response)) {
    		$this->response->send();
    		die;
		}
	}

	public function authorize($is_authorized){
		$this->server->addGrantType(new OAuth2\GrantType\AuthorizationCode($this->storage));
		$this->server->handleAuthorizeRequest($this->request, $this->response, $is_authorized);
		if ($is_authorized) {
	  		$code = substr($this->response->getHttpHeader('Location'), strpos($this->response->getHttpHeader('Location'), 'code=')+5, 40);
	  		header("Location: ".$this->response->getHttpHeader('Location'));
	  	}
		$this->response->send();
	}

	public function authorization_code(){
		$this->server->addGrantType(new OAuth2\GrantType\AuthorizationCode($this->storage));
		$this->server->handleTokenRequest($this->request)->send();
	}
}