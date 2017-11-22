<?php

class Account extends Controller {
	/**
	 * Construct Admin object
	 *
	 * @param obj $model Load the model object
	 * @param obj $view  Load the view object
	 */
	public function __construct($model, $view) {
		$this->model = $model;
		$this->view = $view;
	}

	/**
	 * Handle POST requests to /account/submit page
	 *
	 * @return bool Status of action
	 */
	function postAddNewItem() {
		$productId = $this->model->post->addNewItem($_POST["product"], $_POST["description"], $_POST["price"], $_POST["stripe_sub_plan"]);

		for ($i = 0; $i <= $_POST["no_features"]; $i++) {
			if (isset($_POST["feature".$i])) {
				if ($_POST["feature".$i] == "") continue;
				$this->model->product->addProductFeature($productId, $_POST["feature".$i]);
			}
		}
		$this->model->setAlert("success", "Added new product!");
		header("Location: {$this->model->siteURL}/admin/addproduct");
	}

	/**
	 * Direct all POST requests to relevant function
	 *
	 * @param  arr $vars Array of URL values
	 *
	 * @return bool Return false if hit the default case
	 */
	function handlePostRequest($vars) {
		echo "Post request";
		//switch ($vars["action"]) {
		//		case NULL:
		//			$this->view->render("admin");
		//			break;
		//		case 'addproduct':
		//			$this->postAddProduct();
		//			break;
		//		case 'manageusers':
		//			$this->postManageUsers();
		//			break;
		//		case 'manageproducts':
		//			break;
		//		default:
		//			return false;
		//}
	}

	/**
	 * Direct all GET requests to relevant function
	 *
	 * @param  arr $vars Array of URL values
	 *
	 * @return bool Status of action
	 */
	public function handleGetRequest($vars) {
		$uid = $this->model->user->uid;
		switch ($vars["action"]) {
			case NULL:
				$this->view->render("account", array("uid" => $uid));
				break;
			case 'saved':
				if (isset($vars["pageno"])) {
					$page = $vars["pageno"];
				} else {
					$page = 1;
				}

				$pages = $this->model->post->getSavedPageCount($uid);

				if ($page > 1 && $pages == 1) {
					header("Location: {$this->model->siteURL}/account/saved");
					exit;
				}

				$posts = $this->model->post->getSaved($uid, $page);
				$this->view->render("account-saved", array("posts" => $posts,
							"currentPage" => "/account/saved",
							"currentPageNo" => $page,
							"pageCount" => $pages));
				break;
			case 'history':
				$posts = $this->model->post->getHistory($uid);
				$this->view->render("account-history", array("posts" => $posts));
				break;
			case 'submitted':
				$this->view->render("account-submitted");
				break;
			case 'settings':
				$this->view->render("account-settings");
				break;
			case 'edituser':
				if (!isset($vars["variable"])) {
					$this->model->setAlert("warning", "No user ID!");
					header("Location: {$this->model->siteURL}/admin/manageusers");
					return false;
				}

				if (!$this->model->user->userIdExists($vars["variable"])) {
					$this->model->setAlert("warning", "User does not exist");
					header("Location: {$this->model->siteURL}/admin/manageusers");
					return false;
				}

				$name = "admin-edituser_" . mt_rand(0,mt_getrandmax());
				$token = $this->view->csrf_generate_token($name);

				$user = $this->model->user->getUser($vars["variable"]);

				$this->view->render("admin-edituser", array("CSRFName" => $name, "CSRFToken" => $token, "user" => $user));
				break;
			case 'manageproducts':
				$name = "admin-manageproducts_" . mt_rand(0,mt_getrandmax());
				$token = $this->view->csrf_generate_token($name);

				$products = $this->model->product->getProducts();
				$features = $this->model->product->getAllFeatures($products);

				$this->view->render("admin-manageproducts", array("CSRFName" => $name, "CSRFToken" => $token, "products" => $products, "features" => $features));
				break;
			case 'manageusers':
				$name = "admin-manageusers_" . mt_rand(0,mt_getrandmax());
				$token = $this->view->csrf_generate_token($name);

				$users = $this->model->user->getAllUsers();
				$this->view->render("admin-manageusers", array("CSRFName" => $name, "CSRFToken" => $token, "users" => $users));
				break;
			case 'settings':
				$this->view->render("admin-settings");
				break;
			default:
				echo "Hit default case";
		}
	}

}
