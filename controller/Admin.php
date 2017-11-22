<?php

class Admin extends Controller {
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
	 * Handle POST requests to /addproduct page
	 *
	 * @return bool Status of action
	 */
	function postAddProduct() {
		$productId = $this->model->product->addNewProduct($_POST["product"], $_POST["description"], $_POST["price"], $_POST["stripe_sub_plan"]);

		for ($i = 0; $i <= $_POST["no_features"]; $i++) {
			if (isset($_POST["feature".$i])) {
				if ($_POST["feature".$i] == "") continue;
				$this->model->product->addProductFeature($productId, $_POST["feature".$i]);
			}
		}
		$this->model->setAlert("success", "Added new product!");
		header('Location: /admin/addproduct');
	}

	/**
	 * Handle POST requests to /manageusers page
	 *
	 * @return bool Status of action
	 */
	function postManageUsers() {
		switch ($_POST["action"]) {
			case 'lockuser':
				if ($this->model->user->lockUser($_POST["user"])) {
					$this->model->setAlert("sucess", "Locked User");
					header('Location: /admin/manageusers');
					return true;
				} else {
					$this->model->setAlert("warning", "Problem locking user");
					header('Location: /admin/manageusers');
					return false;
				}
			break;
			case 'edituser':
				if ($this->model->user->userIdExists($_POST["user"])) {
			  	header("Location: /admin/edituser/".$_POST["user"]);
					return true;
				} else {
					$this->model->setAlert("warning", "User does not exist");
					header("Location: /admin/manageusers");
					return false;
				}
			  break;
			default:
			  echo "Default case for manageusers";
			  break;
		}
	}

	/**
	 * Handle POST requests to /manageproducts
	 *
	 * @return bool Status of action
	 */
	function postManageProducts() {
		switch ($_POST["action"]) {
			case 'deleteproduct':
				if ($this->model->product->deleteProduct($_POST["product"])) {
					$this->model->setAlert("sucess", "Deleted Product");
					header('Location: /admin/manageproducts');
					return true;
				} else {
					$this->model->setAlert("warning", "Problem deleting product");
					header('Location: /admin/manageproducts');
					return false;
				}
			break;
			case 'editproduct':
				if ($this->model->product->productIdExists($_POST["product"])) {
					header("Location: /admin/editproduct/".$_POST["product"]);
					return true;
				} else {
					$this->model->setAlert("warning", "Product does not exist");
					header("Location: /admin/manageproducts");
					return false;
				}
				break;
			default:
				echo "Default case for manageproducts";
				break;
		}
	}

	/**
	 * Direct all POST requests to relevant function
	 *
	 * @param  arr $vars Array of URL values
	 *
	 * @return bool Return false if hit the default case
	 */
	function handlePostRequest($vars) {
		switch ($vars["action"]) {
				case NULL:
					$this->view->render("admin");
					break;
				case 'addproduct':
					$this->postAddProduct();
					break;
				case 'manageusers':
					$this->postManageUsers();
					break;
				case 'manageproducts':
					break;
				default:
					return false;
		}
	}

	/**
	 * Direct all GET requests to relevant function
	 *
	 * @param  arr $vars Array of URL values
	 *
	 * @return bool Status of action
	 */
	function handleGetRequest($vars) {
			switch ($vars["action"]) {
				case NULL:
					$this->view->render("admin");
					break;
				case 'addproduct':
					$name = "admin-addproduct_" . mt_rand(0,mt_getrandmax());
					$token = $this->view->csrf_generate_token($name);

					// Get all Stripe plans
					$plans = \Stripe\Plan::all();
					$form_plans = array();
					foreach ( $plans["data"] as $plan ) {
						$form_plans[$plan["id"]] = array("name" => $plan["name"], "price" => $plan["amount"]);
					}

					$this->view->render("admin-addproduct", array("CSRFName" => $name, "CSRFToken" => $token, "plans" => $form_plans));
					break;
				case 'edituser':
					if (!isset($vars["variable"])) {
						$this->model->setAlert("warning", "No user ID!");
						header("Location: /admin/manageusers");
						return false;
					}

					if (!$this->model->user->userIdExists($vars["variable"])) {
						$this->model->setAlert("warning", "User does not exist");
						header("Location: /admin/manageusers");
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
