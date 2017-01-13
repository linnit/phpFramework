<?php

class Payment extends Model {
	public $payment;

	public function __construct($parent) {
		$this->parent	= $parent;
		$this->db	= $this->parent->db;
	}

	public function checkPrice() {
		$finalPrice =  0;
		$customerVPS = $_POST["vpsChosen"];
		$customerPaid = $_POST["stripeAmount"];

		// [TODO] Maybe set these in the database?
		$prices = array(
			'tinyVps' => 2,
			'smallVps' => 3,
			'mediumVps' => 6
		);

		parse_str($customerVPS, $customerVPSArray);

		if (count($customerVPSArray) == 0) {
			$this->model->setAlert("danger", "No VPS package selected");
			return false;
		}

		foreach ($customerVPSArray as $key => $value) {
			$finalPrice += $customerVPSArray[$key] * $prices[$key];
		}

		if (($finalPrice*100) == $customerPaid) {
			return true;
		} else {
			return false;
		}
	}

}

