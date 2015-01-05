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
			add_filter('request', array($this, 'filter_request'), 100, 1);
			add_filter('template_include', array($this, 'handle_request_template'), 100, 1);			
		}

		add_action('admin_bar_menu', array($this, 'add_route_to_toolbar'), 999);

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


		$insert = array(
				'handle' 		=> $args['handle'],
				'pattern' 		=> $args['pattern'],
				'query_vars' 	=> $args['query_vars'],
				'template' 		=> $args['template'],
				'callback'		=> $args['callback'],
				);

		if($args['prepend']){
			$this->routes = array_merge(array($args['handle'] => $args), $this->routes);
		}
		else{
			$this->routes[$args['handle']] = $insert;

		}

		if($args['permalink_filter']){
			add_filter('post_link', $args['permalink_filter'], 999, 4);
			add_filter('page_link', $args['permalink_filter'], 999, 4);
			add_filter('post_type_link', $args['permalink_filter'], 999, 4);
		}

	}


	function get_request(){

		$req = explode('?', $_SERVER['REQUEST_URI']);
		$req = $req[0];
		
		return $req;

	}


	function get_route(){

		if(is_admin())
			return false;

		if($this->route)
			return $this->route;

		$req = $this->get_request(); // This should probably be something else

		if(!$req)
			$req = '/';

		foreach($this->routes as $handle => $route){

			if(preg_match($route['pattern'], $req, $matches)){

				if(is_array($route['query_vars'])){
					foreach($route['query_vars'] as $key => $value){
						if(preg_match_all('~\$([\d]{1,3})~', $value, $ph)){
							if($placeholders = $ph[1]){
								foreach($placeholders as $num){
									if($matches[$num]){
										$route['query_vars'][$key] = str_ireplace('$'.$num, $matches[$num], $route['query_vars'][$key]);
									}
									else{
										$route['query_vars'][$key] = str_ireplace('$'.$num, '', $route['query_vars'][$key]);
									}

									if(empty($route['query_vars'][$key]))
										unset($route['query_vars'][$key]);
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

		if($route = $this->get_route()){
			
			$route['status'] = 200;

			if(is_404())
				$route['status'] = '404';

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
		else{
			if(is_404())
				return $this->status_templates[404];
		}

		return $tmpl;
	}


	function add_route_to_toolbar($wp_admin_bar){
		if($route = $this->get_route()){
	
			$args = array(
				'id'    => 'my_page',
				'title' => 'Route: '.$route['handle']
			);
	
			$wp_admin_bar->add_node($args);

		}
	}


	function set_status_template($status, $tmpl){
		$this->status_templates[$status] = $tmpl;
	}

}


?>