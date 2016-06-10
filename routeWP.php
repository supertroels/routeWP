<?php

class routeWP {


	private $routes = array();

	private $pattern_var = '~:[^\/]+~';



	public function __construct(){

		$this->routes['GET'] = array();
		$this->routes['POST'] = array();

		add_action('plugins_loaded', array($this, 'prepare_route'));

		// if(!is_admin()){
		// 	add_filter('request', array($this, 'handle_request'), 100, 1);
		// 	add_filter('template_include', array($this, 'handle_request_template'), 100, 1);			
		// }

	}


	public function prepare_route(){

		$request = $this->get_request();

		$match = false;
		foreach($this->routes[$request['method']] as $key => $route){
			
			if(preg_match($route['pattern'], $request['path'])){
				$match = true;
				break;
			}

		}

		if(!$match)
			return false; // No match bail

		echo '<pre>';
		print_r($route);
		echo '</pre>';

		die();


	}


	public function handle_request($request){



	}


	public function handle_request_template(){
		
		add_filter('template_include', array($this, 'handle_request_template'), 100, 1);			

	}


	public function get_request(){

		$req = array();

		// Path
		$path = explode('?', $_SERVER['REQUEST_URI']);
		$path = $path[0];

		$req['path'] = $path;

		$req['method'] = strtoupper($_SERVER['REQUEST_METHOD']);

		return $req;

	}


	public function get_path_keys(){


		return $keys;

	}



	public function path_to_pattern($path){

		$pattern = '~^'.preg_replace($this->pattern_var, '([^\/]+)', $path).'/?$~';
		return $pattern;

	}


	public function request($method = 'GET', $path, $controller){

		$route = array();
		
		$method = strtoupper($method);

		$path = preg_replace('~[\/]{1,}$~', '', $path);

		$route['path'] 			= $path;
		$route['pattern'] 		= $this->path_to_pattern($path);
		$route['controller'] 	= $controller;

		$this->routes[$method][] = $route;

	}


	public function get_routes(){
		return $this->routes;
	}


}


?>