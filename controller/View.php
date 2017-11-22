<?php

class View extends Controller {
	public function __construct($model) {
		$loader = new Twig_Loader_Filesystem('view');
		$this->twig = new Twig_Environment($loader, array(
			'debug' => true,
			//'cache'	=> 'cache'
		));
		$this->twig->addExtension(new Twig_Extension_Debug());

		$this->model = $model;
	}

	public function render($page, $stuff = "") {
		if (!isset($_SESSION)) {
			session_start();
		}

		if (isset($_SESSION['alerts'])) {
			$this->model->endAlerts = $_SESSION['alerts'];
			unset($_SESSION['alerts']);
		}

		$pagePerm = $this->model->user->getPagePerms($page);
		$userLevel = $this->model->user->getUserLevel();

		if ($userLevel <= $pagePerm) {
			echo $this->twig->render("$page.html", array(
				'siteUrl' => $this->model->siteURL,
				'siteName' => $this->model->siteName,
				'page' => $page,
				'alerts' => $this->model->endAlerts,
				'stuff' => $stuff,
				'userlevel' => $userLevel
			));

		} elseif ($this->model->user->userLoggedin) {
			$this->model->setAlert("danger", "You do not have permission to access that page");
			header("Location: {$this->model->siteURL}/");
		} else {
			$this->model->setAlert("danger", "Please login to access that page");
			$_SESSION["login_redirect"] = $_SERVER["REQUEST_URI"];

			header("Location: {$this->model->siteURL}/login" . $_SERVER["REQUEST_URI"]);
		}
	}

	// The view controller handles all CSRF stuff
	// <input type="hidden" name="CSRFName" value="{{ stuff.CSRFName }}">
	// <input type="hidden" name="CSRFToken" value="{{ stuff.CSRFToken }}">
	// [TODO] if you refresh a form multiple times, multiplpe tokens will be stored in sessions
	// 				need to clear them out..
	function store_in_session($key,$value) {
		if (!isset($_SESSION)) {
			session_start();
		}

		$_SESSION[$key] = $value;
	}

	function unset_session($key) {
		if (!isset($_SESSION)) {
			session_start();
		}

		$this->csrf_generate_token($key);
		unset($_SESSION[$key]);
	}

	function get_from_session($key) {
		if (!isset($_SESSION)) {
			session_start();
		}

		if (isset($_SESSION[$key])) {
			return $_SESSION[$key];
		} else {
			return false;
		}
	}

	function csrf_validate_token($unique_form_name, $token_value) {
		$token = $this->get_from_session($unique_form_name);
		//echo "Token from session: '{$token}'";
		//echo "<br>";
		//echo "Token from Web Page: '{$token_value}'";

		if (!is_string($token_value)) {
			return false;
		}
		// [todo] Check errors when token doesn't exist (f5 a form submit and tail logs)
		$result = hash_equals($token, $token_value);
		$this->unset_session($unique_form_name);
		return $result;
	}

	function csrf_generate_token($unique_form_name) {
		if (function_exists('random_bytes')) {
			$token = base64_encode(random_bytes(64)); // PHP 7
		} elseif (function_exists('openssl_random_pseudo_bytes')) {
			$token = openssl_random_pseudo_bytes(64); // openSSL
		} else {
			$this->model->setAlert("danger", "Cannot generate CSRF token. Requires function random_bytes() or openssl_random_pseudo_bytes()");
			return false;
		}

		$this->store_in_session($unique_form_name,$token);
		return $token;
	}

	function csrf_validate() {
		if ("POST" == $_SERVER["REQUEST_METHOD"]) {
			// CloudFlare doesn't support HTTP_ORIGIN?
			//if (isset($_SERVER["HTTP_ORIGIN"])) {
				// This website only allows https but we could also get the protocol dynamically
				//$address = "https://".$_SERVER["SERVER_NAME"];
				//if (strpos($address, $_SERVER["HTTP_ORIGIN"]) !== 0) {
				//	//echo "CSRF protection in POST request: detected invalid Origin header: ".$_SERVER["HTTP_ORIGIN"];
				//
				//	$this->model->setAlert('danger', 'Invalid origin header');
				//	return false;
				//}
			//} else {
			//	//echo "No Origin header set.\n";
			//	$this->model->setAlert('danger', 'Invalid origin header');
			//	return false;
			//}

			if ($this->csrf_validate_token($_POST["CSRFName"], $_POST["CSRFToken"])) {
				return true;
			} else {
				$this->model->setAlert('danger', 'CSRF Token invalid');
				return false;
			}
		} else {
			return false;
		}
	}


}
