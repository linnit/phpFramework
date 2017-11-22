<?php

require 'model/User.php';
require 'model/Post.php';
require 'model/Table.php';

Class Model {
	var $endAlerts;

	public function __construct() {
		try {
			$dotenv = new \Dotenv\Dotenv(dirname(dirname(__DIR__)));
			$dotenv->load();
		} catch(Exception $e) {
			echo 'Error: ' .$e->getMessage();
			throw new Exception("Error opening .env file");
		}

		$this->siteURL = $this->getSiteUrl();
		$this->siteDomain = $this->getDomain();
		$this->siteName = getenv('SITENAME');

		$dbhost = getenv('DB_HOST');
		$dbname = getenv('DB_NAME');
		$dbuser = getenv('DB_USER');
		$dbpass = getenv('DB_PASS');

		$this->mail = new \PHPMailer;
		if (filter_var(getenv('SMTPDEBUG'), FILTER_VALIDATE_BOOLEAN)) $this->mail->SMTPDebug = 3;
		$this->mail->isSMTP();
		$this->mail->Host =		getenv('SMTPHOST');
		$this->mail->SMTPAuth =		getenv('SMTPAUTH');
		$this->mail->Username =		getenv('SMTPUSER');
		$this->mail->Password =		getenv('SMTPPASS');
		$this->mail->SMTPSecure =	filter_var(getenv('SMTPSECURE'), FILTER_VALIDATE_BOOLEAN);
		$this->mail->Port =		getenv('SMTPPORT');

		try {
			$this->db = new \PDO("mysql:host={$dbhost};dbname={$dbname}", $dbuser, $dbpass);
			$this->db->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );

			// https://stackoverflow.com/questions/10113562/pdo-mysql-use-pdoattr-emulate-prepares-or-not
			$this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		} catch (PDOException $e) {
			echo 'Error: ' .$e->getMessage();
			throw new PDOException("Error connecting to database.");
		}

		$this->user = new User($this);
		$this->post = new Post($this);

		$this->checkTablesExist();

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

	/**
	 * Check if the base tables in our database exists
	 * If not, create them
	 *
	 *
	 */
	function checkTablesExist() {
		$baseTables = array("login_attempts", "pages", "sessions", "user", "post", "saved", "category", "history");

		foreach($baseTables as $table) {
			try {
				$stmt = $this->db->prepare("SELECT 1 FROM $table");
				$stmt->execute();

			} catch (PDOException $e) {
				if (!isset($this->table)) {
					$this->table = new Table($this);
				}
				$this->table->createTable($table);
				$this->table->insertDefaultRows($table);
			}
		}
	}

	function getSiteUrl() {
		return preg_replace('/\/$/', '', getenv('SITEURL'));
	}

	function getDomain() {
		return str_replace(array('http://', 'https://'), '', $this->siteURL);
	}
}
