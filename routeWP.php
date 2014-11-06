<?php

/**
* 
*/
class routeWP {
	
	private $routes = array();

	function __construct(){

		$this->dir 				= dirname(__FILE__);
		$this->status_templates = array();

		if(!is_admin()){
			add_filter('request', array($this, 'filter_request'), 999, 1);
			// add_filter('query_vars', array($this, 'setup_query_vars'), 999, 1);
			// add_filter('parse_query', array($this, 'parse_query'), 999, 1);
			add_filter('template_include', array($this, 'handle_request_template'), 999, 1);
		}

		$this->set_status_template(404, $this->dir.'/templates/404.php');

	}

	function add_route($args){

		if(is_admin())
			return null;

		$defaults = array(
			'template'	 	=> $this->dir.'/templates/index.php',
			'query_vars' 	=> array(),
			'type' 			=> ''
			);

		$args = array_merge($defaults, $args);

		if(!$args['handle']){
			die('Please provide a handle for your route');
			return false;
		}

		if(!$args['pattern']){
			die('Please provide a pattern for your route');
			return false;
		}

		$this->routes[$args['pattern']] = array(
			'handle' 		=> $args['handle'],
			'query_vars' 	=> $args['query_vars'], 
			'template' 		=> $args['template'],
			'callback'		=> $args['callback']
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
						if(preg_match_all('~\$([\d]{1,3})~', $value, $ph)){
							if($placeholders = $ph[1]){
								foreach($placeholders as $num){
									if($matches[$num]){
										$route['query_vars'][$key] = str_ireplace('$'.$num, $matches[$num], $route['query_vars'][$key]);
									}
								}
							}
						}
					}
				}

				$this->route = $route;
				return $this->route;
			}
		}

		return false;

	}


	function save_route($route){
		$this->route = $route;
	}


	function filter_request($request){

		if($route = $this->get_route()){
			$request = $route['query_vars'];
		}

		return $request;

	}


	function handle_request_template($tmpl){

		if($tmpl == get_stylesheet_directory().'/404.php'){
			return $this->status_templates[404];
		}

		if($route = $this->get_route()){
			
			$route['status'] = 200;

			if(is_callable($route['callback'])){
				$has_assigned_cb = true;
				add_filter('routeWP/handler', $route['callback'], 10, 1);
			}
	
			$route = apply_filters('routeWP/handler', $route);

			if($has_assigned_cb)
				remove_filter('routeWP/handler', $route['callback']);


			if($route['status'] !== 200)
				return $this->status_templates[$route['status']];
			
			if($route['template'])
				$tmpl = $route['template'];
		}

		return $tmpl;
	}

	// function setup_query_vars($query_vars){
	// 	if($route = $this->get_route()){
	// 		foreach($route['query_vars'] as $key => $value){
	// 			$query_vars[] = $key;
	// 		}
	// 	}
	// 	return $query_vars;
	// }

	// function parse_query($query){
		
	// 	if($route = $this->get_route()){
	// 		foreach($route['query_vars'] as $key => $value){
	// 			$query->set($key, $value);
	// 			$query->query[$key] = $value;
	// 		}
	// 	}

	// 	return $query;

	// }

	function set_status_template($status, $tmpl){
		$this->status_templates[$status] = $tmpl;
	}

}


?>