<?php

require 'model/Model.php';
require 'controller/View.php';
require 'controller/Pages.php';
require 'controller/Admin.php';

Class Controller {
	public function __construct() {
		$this->model		= new Model();
		$this->view		= new View($this->model);
		$this->pages		= new Pages($this->model, $this->view);

		$this->actioned		= false;
	}

	/**
	 *
	 * All possible URI requests reside in this function
	 *
	 */
	public function invoke() {
		/**
		 *
		 *	$this->request("/search/{required}/?{optional}", "");
		 *
		 *
		 */

		// Index
		$this->request("/", "pages@index");

		// Login page
		$this->request("/login", "pages@login");
		// Logout
		$this->request("/logout", "pages@logout");

		$this->request("/purchase", "pages@purchase");
		$this->request("/customer", "pages@customer");
		$this->request("/resetpassword", "pages@resetPassword");
		$this->request("/abuse", "pages@abuse");
		$this->request("/faq", "pages@faq");
		$this->request("/terms-of-service", "pages@terms_of_service");
		$this->request("/privacy-policy", "pages@privacy_policy");

		$this->request("/{404}", "pages@fourohfour");
	}

	/**
	 * Parse request and run desired function
	 * [TODO] Maybe allow query strings in the URI?
	 *
	 * @param  string $request [description]
	 * @param  string $action  [description]
	 * @param  [type] $args    [description]
	 *
	 * @return [type]          [description]
	 */
	public function request($request, $action) {
		$definedURI = explode('/', $request);
		$requestURI = explode('/', $_SERVER["REQUEST_URI"]);

		// Remove the empty element
		array_shift($definedURI);
		array_shift($requestURI);

		// Make sure the request meets the minimum number of stuff
		$notRequired = substr_count($request, '?');
		$required = count($definedURI) - $notRequired;
		if (count($requestURI) < $required) {
			return false;
		}

		// Check if the URI matches
		list($actions, $vars) = $this->uriMatch($definedURI, $requestURI);

		if (!$actions && is_null($vars)) {
			//echo "no actions";
			return false;
		}

		$split_action = explode('@', $action);

		$obj = $split_action[0];
		$func = $split_action[1];

		if (count($vars) >= 1) {
			$this->$obj->$func($vars);
			exit;
		} else {
			$this->$obj->$func();
			exit;
		}
	}

	function uriMatch($definedURI, $requestURI) {
		$actions = array();
		$vars = array();

		for ($i = 0; $i < count($definedURI); $i++) {
			// Check if a variable

			if (preg_match('/(\?)?{(.*)}/', $definedURI[$i], $match)) {

				// Is the arg. required
				if ($match[1] == "?") {
					if (isset($requestURI[$i])) {
						$vars[$match[2]] = $requestURI[$i];
					} else {
						$vars[$match[2]] = null;
					}
				} else {
					// arg. is required, return false if isn't set
					if (!isset($requestURI[$i])) {
						//echo "arg. is required, return false if isn't set";
						return false;
					}

					$vars[$match[2]] = $requestURI[$i];
				}

			} elseif ($definedURI[$i] != $requestURI[$i]) {
				return false;
			} else {
				// Not a variable, add to actions
				array_push($actions, $definedURI[$i]);
			}
		}

		return array($actions, $vars);
	}

}
