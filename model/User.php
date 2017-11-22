<?php

class User extends Model {
	public $user;

	public function __construct($parent) {
		$this->parent	= $parent;
		$this->db	= $this->parent->db;

		$this->userLoggedin = false;
		$this->isLogged();

		if (!isset($_SESSION))
			session_start();
	}

	/**
	 * Try log the user in with given email/password
	 * @return boolean
	 */
	public function login() {
		$login = $_POST["login"];
		$password = $_POST["password"];

		$stmt = $this->db->prepare("SELECT id, password FROM user WHERE login = :login");

		$stmt->bindParam(":login", $login);
		$stmt->execute();

		$user = $stmt->fetch();

		if (!isset($user["id"])) {
			$this->bruteforceLoginCheck(0);
		} else {
		 	$this->bruteforceLoginCheck($user["id"]);
		}

		if (!$user) {
			$this->setAlert("danger", "Incorrect Login");

			return false;
		}

		$hash = $user["password"];

		if (password_verify($password, $hash)) {
			$this->email = $email;
			$this->uid = $user["id"];
			$this->getIDs();

			$this->setAlert("success", "Login Success");

			$this->removeLoginAttempt($this->uid);

			$this->userLoggedin = true;

			return true;
		} else {
			$this->setAlert("danger", "Incorrect Login");

			return false;
		}
	}

	/**
	 * Check login form for bruteforce attempts
	 *
	 * @param  int $uid User ID to check against bruteforce
	 *
	 */
	public function bruteforceLoginCheck($uid) {
		$stmt = $this->db->prepare("SELECT count(ip_address) AS attempts FROM login_attempts WHERE ip_address = :ipaddr AND DATE(`timestamp`) = CURDATE()");
		$stmt->bindValue(":ipaddr", $_SERVER["REMOTE_ADDR"]);
		$stmt->execute();

		// Remove old attempts
		$removeOld = $this->db->prepare("DELETE FROM login_attempts WHERE DATE(`timestamp`) < CURDATE()");
		$removeOld->execute();

		$result = $stmt->fetch();
		$attempts = (int) $result["attempts"];

		// Try and stop bruteforce attempts by slowing them down?
		// Maybe revise this?
		// [TODO] maybe after 10 incorrect, block ip..
		if ($attempts <= 3) {
			$sleep = 0;
		} elseif ($attempts <= 5) {
			$sleep = 5;
		} else {
			$sleep = $attempts * 5;
		}

		sleep($sleep);

		$newAttempt = $this->db->prepare("INSERT INTO login_attempts VALUES(:ipaddr, :uid, NULL)");
		$newAttempt->bindValue(":ipaddr", $_SERVER["REMOTE_ADDR"]);
		$newAttempt->bindValue(":uid", $uid);
		$newAttempt->execute();
	}

	/**
	 * On successful login remove login_attempts
	 *
	 * @param  int $uid User id
	 */
	public function removeLoginAttempt($uid) {
		$stmt = $this->db->prepare("DELETE FROM login_attempts WHERE ip_address = :ipaddr AND uid = :uid");
		$stmt->bindValue(":ipaddr", $_SERVER["REMOTE_ADDR"]);
		$stmt->bindValue(":uid", $uid);

		$stmt->execute();
	}

	/**
	 * Get user level of logged in user
	 * @return int User level
	 */
	public function getUserLevel() {
		if (!$this->userLoggedin) {
			return 10;
		}

		$stmt = $this->db->prepare("SELECT user_type FROM user WHERE id = :uid");

		$stmt->bindParam(":uid", $this->uid);
		$stmt->execute();

		$user = $stmt->fetch();

		return $user["user_type"];
	}

	/**
	 * Logout the current user
	 *
	 * @return boolean
	 */
	public function logout() {
		if (!isset($_SESSION["uid"])) {
			$this->setAlert("success", "No Session to destroy");
			return true;
		}

		$stmt = $this->db->prepare("DELETE FROM sessions WHERE sid = :sid");
		$stmt->bindParam(":sid", $_SESSION["sid"]);

		$stmt->execute();

		session_destroy();

		$this->setAlert("success", "You have been logged out");

		return true;
	}

	/**
	 * [register description]
	 *
	 * @return boolean                 [description]
	 */
	public function register() {
		$expectedPost = array(
			'CSRFName' => "",
			'CSRFToken' => "",
			'username' => "",
			'email' => "",
			'password' => "",
			'password2' => "",
		);
		if ( count(array_intersect_key($_POST, $expectedPost)) != count($_POST) ) {
			// [todo] This should already be covered by some js, but just incase
			//          Tell the user what field is empty
			$this->setAlert('danger', "Field empty");
			return false;
		}

		$username = $_POST["username"];
		$email = $_POST["email"];
		$password = $_POST["password"];
		$repeatpassword = $_POST["password2"];

		if ($password !== $repeatpassword) {
			$this->setAlert('danger', "Passwords don't match". $password . $repeatpassword);
			return false;
		}

		// Check if the username, or email is already in use
		if ($this->userExists($login)) {
			$this->setAlert('danger', "Account already exists");
			return false;
		}

		$hash = password_hash($password, PASSWORD_BCRYPT);

		$stmt = $this->db->prepare("INSERT INTO user VALUES(NULL, :username, :email, :hash, '', 5, 1)");

		$stmt->bindValue(":username", $username);
		$stmt->bindValue(":email", $email);
		$stmt->bindValue(":hash", $hash);

		$stmt->execute();

		$this->login();

		return true;
	}

	/**
	 * Reset password of given userLoggedin
	 * @param int $uid User ID of account to reset password
	 * @param string/null If a password is not given, randomly generate one
	 *
	 * @return string new password
	 */
	public function resetPassword($uid, $password = null) {
		if (is_null($password)) {
			$password = $this->random_str(16);
		}
		$stmt = $this->db->prepare("UPDATE user SET password = :hash");
		$hash = password_hash($password, PASSWORD_BCRYPT);
		$stmt->bindValue(":hash", $hash);

		return $password;
	}

	/**
	 * Send an email to the new user with their password
	 *
	 * @param  str $email    [description]
	 * @param  str $password [description]
	 * @return [type]           [description]
	 */
	public function email_new_user($email, $password) {
		$this->mail->setFrom("noreply{$this->model->siteDomain}", $this->model->siteName);
		$this->mail->addAddress($email);

		$this->mail->isHTML(true);

		$this->mail->Subject = $this->model->siteName . ' Registration';
		$this->mail->Body    = 'This is the HTML message body <b>in bold!</b><br>Password: ' .$password;
		$this->mail->AltBody = 'This is the body in plain text for non-HTML mail clients\n Password:' .$password;

		if(!$this->mail->send()) {
		    echo 'Message could not be sent.';
		    echo 'Mailer Error: ' . $this->mail->ErrorInfo;
		} else {
		    echo 'Message has been sent';
		}

	}

	/**
	 * Generate a random string, using a cryptographically secure
	 * pseudorandom number generator (random_int)
	 * Credit: http://stackoverflow.com/questions/6101956/generating-a-random-password-in-php/31284266#31284266
	 *
	 * For PHP 7, random_int is a PHP core function
	 * For PHP 5.x, depends on https://github.com/paragonie/random_compat
	 *
	 * @param int $length      How many characters do we want?
	 * @param string $keyspace A string of all possible characters
	 *                         to select from
	 * @return string
	 */
	public function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
		$str = '';
		$max = mb_strlen($keyspace, '8bit') - 1;

		if ($max < 1) {
			throw new Exception('$keyspace must be at least two characters long');
		}
		for ($i = 0; $i < $length; ++$i) {
			$str .= $keyspace[random_int(0, $max)];
		}

		return $str;
	}

	/**
	 * Check if a user exists with the $email address given
	 *
	 * @param  string $email	Email address of user
	 *
	 * @return boolean
	 */
	public function userExists($username, $email = null) {
		if (is_null($email)) {
			$stmt = $this->db->prepare("SELECT email FROM user WHERE username = :username");
		} else {
			$stmt = $this->db->prepare("SELECT email FROM user WHERE username = :username OR email = :email");
			$stmt->bindParam(":email", $email);
		}

		$stmt->bindParam(":username", $username);
		$stmt->execute();

		return count($stmt->fetchAll()) == 1;
	}

	/**
	 * Check if a user exists with the $id given
	 *
	 * @param  int $id	ID of user
	 *
	 * @return boolean
	 */
	public function userIdExists($id) {
		$stmt = $this->db->prepare("SELECT id FROM user WHERE id = :id");

		$stmt->bindParam(":id", $id);
		$stmt->execute();

		return count($stmt->fetchAll()) == 1;
	}

	/**
	 * Check if the user is logged in by checking session cookies
	 *
	 * @return boolean
	 */
	public function isLogged() {
		if (!isset($_SESSION)) session_start();

		if ($this->userLoggedin) {
			return true;
		}

		if (!isset($_SESSION["uid"])) {
			return false;
		}

		$stmt = $this->db->prepare("SELECT * FROM sessions WHERE uid = :uid AND sid = :sid ORDER BY timestamp DESC");

		$stmt->bindParam(":uid", $_SESSION["uid"]);
		$stmt->bindParam(":sid", $_SESSION["sid"]);

		$row = $stmt->fetch();

		if ($row["uid"] != $_SESSION["uid"] ||
			$row["sid"] != $_SESSION["sid"] ||
			$row["tid"] != $_SESSION["tid"] ||
			$row["ip"] != $_SERVER["REMOTE_ADDR"]) {
			return false;
		}

		$this->uid = $row["uid"];
		$this->updateIDs();

		$this->userLoggedin = true;

		return true;
	}

	/**
	 * Return all users
	 *
	 * @return array All user results
	 */
	public function getAllUsers() {
		$stmt = $this->db->prepare("SELECT * FROM user ORDER BY id ASC");// LIMIT 0,20"); [TODO] Some kind of pagination?

		$stmt->execute();

		return $stmt->fetchAll();
	}

	/**
	 * Get currently logged in user's email address
	 * @return string/false email address of currently logged in user or false if not logged
	 */
	public function getUserEmail() {
		if (!$this->userLoggedin) return false;

		$stmt = $this->db->prepare("SELECT email FROM user WHERE id = :uid");
		$stmt->bindParam(":uid", $this->uid);

		$stmt->execute();

		$row = $stmt->fetch();

		return $row["email"];
	}

	/**
	 * [getStripeId description]
	 * @return string Logged in user's Stripe ID
	 */
	public function getStripeId() {
		if (!$this->userLoggedin) return false;

		$stmt = $this->db->prepare("SELECT stripe_id FROM user WHERE id = :uid");
		$stmt->bindParam(":uid", $this->uid);

		$stmt->execute();

		$row = $stmt->fetch();

		return $row["stripe_id"];
	}

	/**
	 * [getIDs description]
	 * @return [type] [description]
	 */
	function getIDs() {
		$sid = session_id();
		$tid = md5(microtime(true));
		$ip = $_SERVER["REMOTE_ADDR"];

		$stmt = $this->db->prepare("INSERT INTO sessions VALUES(NULL, :uid, :sid, :tid, :ip)");

		$stmt->bindParam(":uid", $this->uid);
		$stmt->bindParam(":sid", $sid);
		$stmt->bindParam(":tid", $tid);
		$stmt->bindParam(":ip", $ip);

		$stmt->execute();

		$_SESSION["uid"] = $this->uid;
		$_SESSION["sid"] = $sid;
		$_SESSION["tid"] = $tid;
	}

	function updateIDs() {
		$sid = $_SESSION["sid"];
		$tid = md5(microtime(true));
		$ip = $_SERVER["REMOTE_ADDR"];

		$stmt = $this->db->prepare("UPDATE sessions SET tid = :tid WHERE sid = :sid");

		$stmt->bindParam(":sid", $sid);
		$stmt->bindParam(":tid", $tid);

		$stmt->execute();

		$_SESSION["tid"] = $tid;

		return true;
	}

	function getPagePerms($page) {
		try {
			$stmt = $this->db->prepare("SELECT user_level FROM pages WHERE pagename = :page");

			$stmt->bindParam(":page", $page);
			$stmt->execute();
		} catch (PDOException $e) {
			$this->checkTablesExist();
			return $this->getPagePerms($page);
		}

		$page = $stmt->fetch();

		return $page["user_level"];
	}

	/**
	 * Get all information about user from user table
	 *
	 * @param  int $id ID of user to retrieve
	 *
	 * @return arr Array of user information
	 */
	function getUser($id) {
		$stmt = $this->db->prepare("SELECT * FROM user WHERE id = :id");

		$stmt->bindValue(":id", $id);

		$stmt->execute();

		return $stmt->fetch();

	}

	/**
	 * Lock given user from logging into the system
	 *
	 * @param  int $id ID of user to lock
	 *
	 * @return int ID of user that has been locked
	 */
	function lockUser($id) {
		$stmt = $this->db->prepare("UPDATE user SET enabled = 0 WHERE id = :id");

		$stmt->bindValue(":id", $id);

		$stmt->execute();

		return $stmt->lastInsertId();
	}

}
