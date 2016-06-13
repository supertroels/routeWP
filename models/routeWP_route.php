<?php

class routeWP_route {


	public $method 			= null;
	public $path 			= null;
	public $controller 		= null;
	public $pattern			= null;
	public $vars			= array();
	public $request			= array();
	public $template		= null;
	public $query			= null;

	private $pattern_var 	= '~:[^\/]+~';


	public function __construct($method, $path, $controller){


		// Method
		$this->method = strtoupper($method);

		// Path
		$path = preg_replace('~[\/]{1,}$~', '', $path);
		$this->path = $path;

		// Method
		$this->pattern 		= $this->get_pattern();

		// Controller
		$this->controller 	= $controller;

		// Request
		$this->request 		= $this->get_request();

		// Vars
		$this->vars 		= $this->get_request_vars();

	}


	public function set_template($template){

		$this->template = get_stylesheet_directory().'/'.$template.'.php';

	}


	public function set_query($name, $post_type = 'post'){

		if($post_type == 'post'){
			
			$this->query = array(
				'name'			=> $name,
				);
			
		}
		else {

			$this->query = array(
				'post_type'			=> $post_type,
				'pagename'			=> $name,
				$post_type			=> $name,
				);

		}

	}


	public function set_query_var($key, $var){
		$this->query[$key] = $var;
	}

	public function get_request_var($key){
		if(isset($this->vars[$key]))
			return $this->vars[$key];
		return false;
	}

	// Util functions
	public function get_request(){

		$req = array();

		// Path
		$path = explode('?', $_SERVER['REQUEST_URI']);
		$path = $path[0];

		$req['path'] = $path;

		$req['method'] = strtoupper($_SERVER['REQUEST_METHOD']);

		return $req;

	}



	private function get_path_keys(){

		if(!preg_match_all('~:([^\/]+)~', $this->path, $keys))
			return false;

		$keys = $keys[1];

		return $keys;

	}

	private function get_request_vars(){

		$vars = array();

		$req_parts = explode('/', $this->request['path']);
		unset($req_parts[0]);
		$req_parts = array_values($req_parts);

		$path_parts = explode('/', $this->path);
		unset($path_parts[0]);
		$path_parts = array_values($path_parts);

		foreach($path_parts as $key => $part){
			
			if(!preg_match('~^:~', $part))
				continue;

			$var_key = preg_replace('~^:~', '', $part);
			$vars[$var_key] = $req_parts[$key];

		}


		return $vars;


	}

	private function get_pattern(){

		$pattern = '~^'.preg_replace($this->pattern_var, '([^\/]+)', $this->path).'/?$~';
		return $pattern;

	}


}

?>