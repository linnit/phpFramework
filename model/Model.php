<?php

require 'model/User.php';
require 'model/Payment.php';

Class Model {
	var $endAlerts;

	public function __construct() {
		$dotenv = new Dotenv\Dotenv(dirname(dirname(__DIR__)));
		$dotenv->load();

		$this->siteURL = $this->getSiteUrl();
		$this->siteDomain = $this->getDomain();
		$this->siteName = getenv('SITENAME');

		$dbhost = getenv('DB_HOST');
		$dbname = getenv('DB_NAME');
		$dbuser = getenv('DB_USER');
		$dbpass = getenv('DB_PASS');

		$this->stripeTestSecKey = getenv('STRIPE_TEST_SEC_KEY');
		$this->stripeTestPubKey = getenv('STRIPE_TEST_PUB_KEY');
		$this->stripeLiveSecKey = getenv('STRIPE_LIVE_SEC_KEY');
		$this->stripeLivePubKey = getenv('STRIPE_LIVE_PUB_KEY');

		$this->mail = new PHPMailer;
		if (filter_var(getenv('SMTPDEBUG'), FILTER_VALIDATE_BOOLEAN)) $this->mail->SMTPDebug = 3;
		$this->mail->isSMTP();
		$this->mail->Host =		getenv('SMTPHOST');
		$this->mail->SMTPAuth =		getenv('SMTPAUTH');
		$this->mail->Username =		getenv('SMTPUSER');
		$this->mail->Password =		getenv('SMTPPASS');
		$this->mail->SMTPSecure =	filter_var(getenv('SMTPSECURE'), FILTER_VALIDATE_BOOLEAN);
		$this->mail->Port =		getenv('SMTPPORT');

		try {
			$this->db = new PDO("mysql:host={$dbhost};dbname={$dbname}", $dbuser, $dbpass);
			$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		} catch (PDOException $e) {
			throw new PDOException("Error connecting to database.");
		}

		$this->user = new User($this);
		$this->payment = new Payment($this);

		// danger, warning, success
		$this->endAlerts = array();
	}

	function setAlert($type, $alert) {
		if (!isset($_SESSION)) {
			session_start();
		}

		if (!isset($_SESSION['alerts'])) $_SESSION['alerts'] = array();
		array_push($_SESSION['alerts'], array($type, $alert));
	}

	function getSiteUrl() {
		return preg_replace('/\/$/', '', getenv('SITEURL'));
	}

	function getDomain() {
		return str_replace(array('http://', 'https://'), '', $this->siteURL);
	}
}
