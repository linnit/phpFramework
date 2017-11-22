<?php

class Pages extends Controller {
	/**
	 * [__construct description]
	 * @param obj $model Main model object
	 * @param obj $view  View controller
	 */
	public function __construct($model, $view, $admin, $account) {
		$this->model = $model;
		$this->view = $view;

		$this->admin = $admin;
		$this->account = $account;
	}

	/**
	 * If / is requested, render the index page
	 *
	 */
	public function index($vars = null) {
		if (isset($vars["pageno"])) {
			$page = $vars["pageno"];
		} else {
			$page = 1;
		}
		if (isset($vars["category"])) {
			$category = $vars["category"];
			$pageURL = "/category/{$category}";
		} else {
			$pageURL = "";
			$category = null;
		}

		if ($this->model->user->userLoggedin) {
			$uid = $this->model->user->uid;
		} else {
			$uid = null;
		}

		$pages = $this->model->post->getPageCount($category);
		$posts = $this->model->post->getRecent($uid, $page, $category);

		if (empty($posts)) {
			$this->view->render("404", $vars);
			return 0;
		}

		$this->view->render("index", array("posts" => $posts,
					"currentPage" => $pageURL,
					"currentPageNo" => $page,
					"pageCount" => $pages));
	}



	public function search($vars) {
		if ($this->model->user->userLoggedin) {
			$uid = $this->model->user->uid;
		} else {
			$uid = null;
		}
		if (isset($vars["category"])) {
			$category = $vars["category"]; // [todo]
		} else {
			$category = null;
		}

		if (isset($vars["pageno"])) {
			$page = $vars["pageno"];
		} else {
			$page = 1;
		}
		$pageURL = "/search/{$vars['search']}";

		$pages = $this->model->post->getSearchPageCount($vars["search"], $category);
		$results = $this->model->post->search($uid, $page, $vars["search"]);

		$this->view->render("search", array("posts" => $results,
							"searchterm" => $vars["search"],
							"currentPage" => $pageURL,
							"currentPageNo" => $page,
							"pageCount" => $pages));
	}

	public function jsonSearch($vars) {
		$results = $this->model->post->json_search($vars["string"]);

		echo json_encode($results);
	}

	/**
	 * If /admin is requested, render the admin page
	 *
	 * @param array $vars Possible actions that can be performed on the admin page
	 *
	 */
	public function admin($vars) {
		if ($this->view->csrf_validate()) {
			$this->admin->handlePostRequest($vars);
		} else {
			$this->admin->handleGetRequest($vars);
		}
	}

	public function register() {
		if ($this->model->user->userLoggedin) {
			// If the user is already logged in, they don't need to see the register page
			if ($this->model->user->getUserLevel() != 0) {
				// If their user level isn't on an admin level, send them to /customer
				header("Location: {$this->model->siteURL}/customer");
			} else {
				// If they're an admin, send them to /admin
				header("Location: {$this->model->siteURL}/admin");
			}
			return true;
		}
		if (!$this->view->csrf_validate()) {
			header("Location: {$this->model->siteURL}/login");
			return false;
		} elseif (isset($_POST["usbcid"])) {
			if ($this->model->user->register()) {
				header("Location: {$this->model->siteURL}/account");
			} else {
				header("Location: {$this->model->siteURL}/login");
			}
			return true;
		}
	}

	/**
	 * Handles all requests to /login
	 *
	 * @return bool
	 */
	public function login($vars) {
		if ($this->model->user->userLoggedin) {
			// If the user is already logged in, they don't need to see the login page ever
			if ($this->model->user->getUserLevel() != 0) {
				// If their user level isn't on an admin level, send them to /customer
				header("Location: {$this->model->siteURL}/account");
			} else {
				// If they're an admin, send them to /admin
				header("Location: {$this->model->siteURL}/admin");
			}
			return true;
		}

		if (!$this->view->csrf_validate()) {
			$loginname = "login_" . mt_rand(0,mt_getrandmax());
			$logintoken = $this->view->csrf_generate_token($loginname);

			$registername = "register_" . mt_rand(0,mt_getrandmax());
			$registertoken = $this->view->csrf_generate_token($registername);

			$this->view->render("login", array("CSRFLoginName" => $loginname, "CSRFLoginToken" => $logintoken, "CSRFRegisterName" => $registername, "CSRFRegisterToken" => $registertoken));
		} elseif (isset($_POST["usbcid"])) {
			if (!$this->model->user->login()) {
				// Login failed, send back to login page
				header("Location: {$this->model->siteURL}/login");
				return true;
			}

			if (is_null($vars["redirect1"])) {
				if ($this->model->user->getUserLevel() != 0) {
					header("Location: {$this->model->siteURL}/account");
				} else {
					header("Location: {$this->model->siteURL}/admin");
				}

				return true;
			} else {
				$uri_redirect = "";
				$uri_redirect .= $vars["redirect1"];
				if (!is_null($vars["redirect2"])) $uri_redirect .= $vars["redirect2"];

				if (!isset($_SESSION["login_redirect"])) {
					header("Location: {$this->model->siteURL}/login");
					return true;
				}

				if ($uri_redirect == str_replace('/', '', $_SESSION["login_redirect"])) {
					// [todo] test this.
					header("Location: " . $this->model->siteURL . $_SESSION["login_redirect"]);
					unset($_SESSION["login_redirect"]);
					return true;
				}
			}
		} else {
			$this->model->setAlert("warning", "Something went wrong");
			header("Location: {$this->model->siteURL}/login");
		}
	}

	/**
	 * Handle requests to /logout
	 *
	 * @return bool
	 */
	public function logout() {
		$this->model->user->logout();

		header("Location: {$this->model->siteURL}/");
		return true;
	}

	/**
	 * If /account is requested render the customer pages
	 * The view controller checks if the user is logged in
	 * and if not re-direct them to /login
	 *
	 */
	public function account($vars) {
		if ($this->view->csrf_validate()) {
			$this->account->handlePostRequest($vars);
		} else {
			$this->account->handleGetRequest($vars);
		}
	}


	public function post($vars) {
		if ($this->model->user->userLoggedin) {
			$uid = $this->model->user->uid;
		} else {
			$uid = null;
		}

		$post = $this->model->post->getItem($vars["item"], $uid);

		// Add to history
		if ($this->model->user->userLoggedin) {
			$this->model->post->addToHistory($post["id"], $uid);
		}

		if (!empty($post)) {
			$this->view->render("post", $post);
		} else {
			$this->view->render("404", $vars);
		}
	}

	public function save($vars) {
		if (!$this->model->user->userLoggedin) {
			$this->model->setAlert("warning", "You need to be logged in to save items.");
			header("Location: {$this->model->siteURL}/login");
			return false;
		}

		$pid = $this->model->post->getItemId($vars["item"]);
		$uid = $this->model->user->uid;

		$this->model->post->saveItem($pid, $uid);

		header("Location: " . $this->model->siteURL . $_SERVER['HTTP_REFERER']);

		return true;
	}

	public function unsave($vars) {
		if (!$this->model->user->userLoggedin) {
			$this->model->setAlert("warning", "You need to be logged in to unsave items.");
			header("Location: {$this->model->siteURL}/login");
			return false;
		}

		$pid = $this->model->post->getItemId($vars["item"]);
		$uid = $this->model->user->uid;

		$this->model->post->deleteSavedItem($pid, $uid);

		header("Location: " . $this->model->siteURL . $_SERVER['HTTP_REFERER']);

		return true;
	}

	/**
	 * [resetPassword description]
	 */
	public function resetPassword() {
		if (!$this->view->csrf_validate()) {
			$name = "reset_" . mt_rand(0,mt_getrandmax());
			$token = $this->view->csrf_generate_token($name);

			$this->view->render("resetpassword", array("CSRFName" => $name, "CSRFToken" => $token));
		} elseif (isset($_POST["password1"])) {
			if ($_POST["password1"] === $_POST["password2"]) {
				$this->model->user->resetPassword($this->model->user->uid, $_POST["password1"]);
				$this->model->setAlert("success", "Successfully changed password");
				header("Location: {$this->model->siteURL}/account"); // [TODO] Will we always redirect to /account?
				return true;
			} else {
				$this->model->setAlert("warning", "Passwords do not match");
				header("Location: {$this->model->siteURL}/resetpassword");
				return true;
			}
		}
	}

	public function enterevent() {
		if (!$this->view->csrf_validate()) {
			$eventname = "event_" . mt_rand(0,mt_getrandmax());
			$eventtoken = $this->view->csrf_generate_token($loginname);

			$this->view->render("enterevent", array("CSRFEventName" => $eventname, "CSRFEventToken" => $eventtoken));
		} elseif (isset($_POST["HID"])) {
			echo "You have entered an event..";
			return true;
		}
	}

	public function winnerscircle() {
		$this->view->render("winnerscircle");
	}

	public function abuse() {
		$this->view->render("abuse");
	}

	public function faq() {
		$this->view->render("faq");
	}

	public function termsOfService() {
		$this->view->render("termsofservice");
	}

	public function privacyPolicy() {
		$this->view->render("privacypolicy");
	}

	public function fourOhFour($vars) {
		$this->view->render("404", $vars);
	}
}
