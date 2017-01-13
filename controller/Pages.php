<?php

class Pages extends Controller {
	public function __construct($model, $view) {
		$this->model = $model;
		$this->view = $view;
	}

	public function index($vars = null) {
		$this->view->render("index");
	}

	public function login() {
		if ($this->model->user->userLoggedin) header('Location: /customer');

		if (!$this->view->csrf_validate()) {
			$name = "login_" . mt_rand(0,mt_getrandmax());
			$token = $this->view->csrf_generate_token($name);

			if (!$token) {
				$this->model->setAlert("danger", "Cannot generate CSRF token. Requires function random_bytes() or openssl_random_pseudo_bytes()");
			}

			$this->view->render("login", array("CSRFName" => $name, "CSRFToken" => $token));
		} elseif (isset($_POST["login"])) {
			if (!$this->model->user->login()) {
				header('Location: /login');
				return true;
			}
			header('Location: /customer');
			return true;
		}
	}

	public function logout() {
		$this->model->user->logout();

		header('Location: /');
		return true;
	}

	public function customer() {
		$this->model->setAlert("success", "We are attempting to view the customer portal");
		$this->view->render("customer");
	}

	public function resetPassword() {
		if (!$this->model->user->userLoggedin) header('Location: /login');

		if (!$this->view->csrf_validate()) {
			$name = "reset_" . mt_rand(0,mt_getrandmax());
			$token = $this->view->csrf_generate_token($name);

			if (!$token) {
				$this->model->setAlert("danger", "Cannot generate CSRF token. Requires function random_bytes() or openssl_random_pseudo_bytes()");
			}
			$this->view->render("resetpassword", array("CSRFName" => $name, "CSRFToken" => $token));
		} elseif (isset($_POST["password1"])) {
			if ($_POST["password1"] === $_POST["password2"]) {
				$this->model->user->resetPassword($this->model->user->uid, $_POST["password1"]);
				$this->model->setAlert("success", "Successfully changed password");
				header('Location: /customer');
				return true;
			} else {
				$this->model->setAlert("warning", "Passwords do not match");
				header('Location: /resetpassword');
				return true;
			}
		}
	}

	public function purchase() {
		if (!$this->view->csrf_validate()) {
			$name = "purchase_" . mt_rand(0,mt_getrandmax());
			$token = $this->view->csrf_generate_token($name);

			if (!$token) {
				$this->model->setAlert("danger", "Cannot generate CSRF token. Requires function random_bytes() or openssl_random_pseudo_bytes()");
			}

			$this->view->render("purchase", array("CSRFName" => $name, "CSRFToken" => $token, "stripePubKey" => $this->model->stripeTestPubKey));
		} else {
			// csrf is valid, process the transaction

			if (isset($_POST["stripeToken"])) {
				\Stripe\Stripe::setApiKey($this->model->stripeTestSecKey);

				$token = $_POST["stripeToken"];
				$customerEmail = $_POST["stripeEmail"];

				// Check if customer has tried fudging the prices client side
				if (!$this->model->payment->checkPrice()) header('Location: /purchase');


				// [TODO] if the customer is logged in, we don't need to create a customer and register them
				// Check if the customer is logged in and using their own email address
				if ($this->model->user->userLoggedin) {
					if ($this->model->user->getUserEmail() !== $customerEmail) {
						$this->model->setAlert("danger", "Problem with email address");
						header('Location: /');
						return false;
					}
				// If they're not logged in, but trying to use an email that has an account
				//	Redirect them to login first
				} elseif ($this->model->user->userExists($customerEmail)) {
					$this->model->setAlert('danger', "Please log in to purchase a VPS");
					header('Location: /login');
					return false;
				}

				// [TODO]
				// Create customer on stripe
				$customer = \Stripe\Customer::create(array(
					//"description" => "Customer for noah.robinson@example.com",
					"email" =>	$customerEmail,
					"source" =>	$token,
					"plan" =>	"kvm_tiny",
					"metadata" =>	array('created'=> time())
				));

				// [TODO]
				// Charge customer's card
				try {
					$charge = \Stripe\Charge::create(array(
						"amount" => $customerPaid,
						"currency" => "gbp",
						"customer" => $customer->id,
						"description" => "KVM VPS"
					));
				} catch(\Stripe\Error\Card $e) {
					$this->model->setAlert('danger', 'Card has been declined');
				}

				// Create customer account on our side
				$password = $this->model->user->random_str(16);
				$this->model->user->register($customer->id, $customerEmail, $password, $password);

				// E-mail customer with a password
				$this->model->user->email_new_user($customerEmail, $password);

			} else {
				$this->model->setAlert("danger", "Invalid Strike Token");
				header('Location: /purchase');
				return true;
			}

		}

	}

	public function abuse() {
		$this->view->render("abuse");
	}

	public function faq() {
		$this->view->render("faq");
	}

	public function terms_of_service() {
		$this->view->render("terms-of-service");
	}

	public function privacy_policy() {
		$this->view->render("privacy-policy");
	}

	public function fourohfour($vars) {
		$this->view->render("404", $vars);
	}
}
