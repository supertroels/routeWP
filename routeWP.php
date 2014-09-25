<?php

/**
* 
*/
class routeWP {
	
	private $routes = array();

	function __construct(){

		if(!is_admin()){
			add_filter('request', array($this, 'filter_request'), 999, 1);
			add_filter('query_vars', array($this, 'setup_query_vars'), 999, 1);
			add_filter('parse_query', array($this, 'parse_query'), 999, 1);
			add_filter('template_include', array($this, 'handle_request_template'), 999, 1);
		}

	}

	function add_route($args){

		if(is_admin())
			return null;

		$defaults = array(
			'template'	 	=> false,
			'query_vars' 	=> array(),
			'support' 		=> array()
			);

		$args = array_merge($defaults, $args);

		if(!$args['pattern'])
			return false;

		$this->routes[$args['pattern']] = array(
			'query_vars' => $args['query_vars'], 
			'template' => $args['template'],
			'support' => $args['supports']
			);

	}

	function get_route(){

		if(is_admin())
			return false;

		if($this->route)
			return $this->route;

		$req = $_SERVER['REDIRECT_URL']; // This should probably be something else
		if(!$req)
			$req = '/';

		foreach($this->routes as $pattern => $route){

			if(preg_match($pattern, $req, $matches)){

				if(is_array($route['query_vars'])){

					foreach($route['query_vars'] as $key => $value){

						if(preg_match('~^\$([\d]{1,3})$~', $value, $num)){
							if($matches[$num[1]])
								$route['query_vars'][$key] = $matches[$num[1]];
							else
								unset($route['query_vars'][$key]);
						}

					}

				}

				if(is_array($route['supports'])){

				}

				$this->route = $route;
				return $this->route;
			}
		}

	}

	function filter_request($request){
		
		if($route = $this->get_route()){
			$request = array();
		}

		return $request;

	}


	function handle_request_template($tmpl){
		if($route = $this->get_route())
			if($route['template'])
				$tmpl = $route['template'];

		return $tmpl;
	}

	function setup_query_vars($query_vars){
		if($route = $this->get_route()){
			foreach($route['query_vars'] as $key => $value){
				$query_vars[] = $key;
			}
		}
		return $query_vars;
	}

	function parse_query($query){
		
		if($route = $this->get_route()){
			foreach($route['query_vars'] as $key => $value){
				$query->set($key, $value);
				$query->query[$key] = $value;
			}
		}

		return $query;
	}

}


?>