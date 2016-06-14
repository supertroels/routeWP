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
				
				if($route->on_match){

					$return = call_user_func_array($route->on_match, array($route));

					if($route->status != 200){
						add_action('wp', array($route, 'do_status'));
						break;
					}

					if($return === false)
						continue;

				}

				if(!preg_match('~\/$~', $request['path'])){
					wp_redirect($request['path'].'/');
					die();
				}

				$this->route = $route;

				$this->setup_route_hooks();

				break;
			}

		}

		if(!$match)
			return false; // No match - bail


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



	public function request($method = 'GET', $path){
		
		include_once 'models/routeWP_route.php';
		$route = new routeWP_route($method, $path);
		$this->routes[$route->method][] = $route;

		return $route;

	}


	public function get($path){

		return $this->request('GET', $path);

	}


	public function post($path){

		return $this->request('POST', $path);

	}


	public function get_routes(){
		return $this->routes;
	}

	public function get_link($name, $vars = array()){

		$found = false;
		foreach($this->routes as $method => $routes){
			foreach($routes as $route){
				if($route->name == $name){
					$found = true;
					break 2;
				}
			}
		}


		if(!$found)
			return false;

		return $route->get_link($vars);

	}


}


?>