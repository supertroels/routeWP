<?php

class routeWP_route {


	public $method 				= null;
	public $path 				= null;
	public $controller 			= null;
	public $pattern				= null;
	public $name				= null;
	public $vars				= array();
	public $request				= array();
	public $template			= null;
	public $query				= null;
	public $link 				= null;
	public $post_type 			= null;

	private $pattern_var 		= '~:[^\/]+~';


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


		add_action('plugins_loaded', array($this, 'setup'));

	}

	public function setup(){

		if($this->link){

			add_filter('post_link', array($this, 'setup_single_link'), 999, 4);
			add_filter('page_link', array($this, 'setup_single_link'), 999, 4);
			add_filter('post_type_link', array($this, 'setup_single_link'), 999, 4);

		}


	}


	public function setup_single_link($link, $post, $leavename, $sample = false){

		if($post->post_type)
			if($this->post_type != $post->post_type)
				return $link;

		if(!$this->link)
			return $_link;


		if(is_numeric($post))
			$post = get_post($post);


		if(is_callable($this->link))
			$_link = call_user_func_array($this->link, array($this, $post));
		else if(is_string($this->link))
			$_link = $this->link;

		if(!is_string($_link)) // This should not happen
			return $link;


		// Forgive user for %page% and %post% placeholders
		$_link = str_ireplace(array('%post%', '%page%'), array('%postname%', '%pagename%'), $_link);


		// Search for these strings
		if($post->post_type == 'post' or $post->post_type == 'page'){
			$find[] = '%'.$post->post_type.'name%';
		}
		else
			$find[] = '%'.$post->post_type.'%';


		if(!$sample)
			$_link = str_ireplace($find, $post->post_name, $_link);

		// Append URL
		$_link = get_bloginfo('url').$_link;

		return $_link;

	}




	public function set_template($template){

		$this->template = get_stylesheet_directory().'/'.$template.'.php';

		return $this;

	}

	public function set_post_type($post_type){

		$this->post_type = $post_type;
		return $this;

	}

	public function set_link($filter){

		$this->link = $filter;

		return $this;
	}


	public function set_post_query($name, $post_type = 'post'){

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


		return $this;

	}


	public function set_query_var($key, $var){
		
		$this->query[$key] = $var;
		return $this;

	}

	public function set_name($name){
		$this->name = $name;
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