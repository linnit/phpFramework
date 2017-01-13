<?php

class Admin extends Controller {
	public function __construct($model, $user, $view) {
		$this->model = $model;
		$this->user = $user;
		$this->view = $view;
	}

	/*public function render($vars = null) {
		//var_dump($vars);

		if ($this->user->userLoggedin) {

		} else {
			//$servers = "";
		}

		if (isset($vars["page"])) {

			switch ($vars["page"]) {
				case 'addserver':
					$this->view->render(array(array("admin_addserver")));
					break;
				case 'adduser':
					$this->view->render(array(array("admin_adduser")));
					break;
				case 'addnode':
					$this->view->render(array(array("admin_addnode")));
					break;
				default:
					break;
			}

		} else {
			$this->view->render(array(array("admin")));
		}
	}*/


}
