<?php

class routeWP {


	private $routes = array();
	private $route 	= null;


	public function __construct(){

		$this->routes['GET'] = array();
		$this->routes['POST'] = array();

		add_action('plugins_loaded', array($this, 'prepare_route'));

	}


	public function prepare_route(){

		$request = $this->get_request();

		$match = false;
		foreach($this->routes[$request['method']] as $key => $route){

			if(preg_match($route->pattern, $request['path'])){
				$match = true;
				break;
			}

		}

		if(!$match)
			return false; // No match - bail

		call_user_func_array($route->controller, array($route));

		$this->route = $route;

		$this->setup_route_hooks();

	}



	public function setup_route_hooks(){

		remove_filter('template_redirect', 'redirect_canonical');

		if(!is_admin()){
			add_filter('request', array($this, 'handle_request'), 100, 1);
			add_filter('template_include', array($this, 'handle_request_template'), 100, 1);			
		}


	}


	public function handle_request($request){

		if($this->route->query)
			$request = $this->route->query;

		return $request;
	}


	public function handle_request_template($template){

		if($this->route and $this->route->template){
			status_header(200);
			$template = $this->route->template;
		}

		return $template;
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



	public function request($method = 'GET', $path, $controller){
		
		include_once 'models/routeWP_route.php';
		$route = new routeWP_route($method, $path, $controller);
		$this->routes[$route->method][] = $route;

		return $route;

	}


	public function get($path, $controller){

		return $this->request('GET', $path, $controller);

	}


	public function post($path, $controller){

		return $this->request('POST', $path, $controller);

	}


	public function get_routes(){
		return $this->routes;
	}


}


?>