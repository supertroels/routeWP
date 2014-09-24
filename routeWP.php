<?php

/**
* 
*/
class routeWP {
	
	private $routes = array();

	function __construct(){
		add_filter('request', array($this, 'filter_request'), 999, 1);
	}

	function add_route($pattern, $args = array(), $template){
		$this->routes[$pattern] = array('args' => $args, 'template' => $template);
	}

	function filter_request($request){
		
		$req = $_SERVER['REDIRECT_URL'];

		foreach($this->routes as $pattern => $route){
			if(preg_match($pattern, $req, $matches)){
				$request = array();
				foreach($matches as $k => $match){
					
					if($k === 0) // Skip the first match as it contains the whole string
						continue;

					$index = $k -1;
					/*
					Now we'll save the regex matches in the request vars
					named via the $route['args'] array;
					*/
					if($route['args'][$index])
						$request[$route['args'][$index]] = $match;
				}

				$this->template = $route['template'];
				add_action('template_include', array($this, 'handle_request_template'));

				break;
			}
		}

		return $request;

	}


	function handle_request_template($tmpl){
		return $this->template;
	}

}


?>