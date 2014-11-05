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
			add_filter('query_vars', array($this, 'setup_query_vars'), 999, 1);
			add_filter('parse_query', array($this, 'parse_query'), 999, 1);
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

		if(!$args['pattern'])
			return false;

		$this->routes[$args['pattern']] = array(
			'handle' 		=> $args['handle'],
			'query_vars' 	=> $args['query_vars'], 
			'template' 		=> $args['template'],
			'type' 			=> $args['type']
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


	function route_status($route = false){

		if(!$route){
			$route = $this->get_route();
		}

		if(isset($this->route_status))
			return $this->route_status;

		$status = 200;
		switch ($route['type']) {
			case 'single':
				global $post;
				if(!isset($post->ID))
					$status = 404;
				break;
			
			default:
				# code...
				break;
		}

		$this->route_status = $status;
		return $this->route_status;

	}

	function filter_request($request){
		
		if($route = $this->get_route()){
			$request = array();
		}

		return $request;

	}


	function handle_request_template($tmpl){

		if($tmpl == get_stylesheet_directory().'/404.php'){
			return $this->status_templates[404];
		}

		if($route = $this->get_route()){
			
			if($this->route_status() !== 200)
				return $this->status_templates[$this->route_status()];
			
			if($route['template'])
				$tmpl = $route['template'];
		}

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

	function set_status_template($status, $tmpl){
		$this->status_templates[$status] = $tmpl;
	}

}


?>