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
		$email = $_POST["login"];
		$password = $_POST["password"];

		$stmt = $this->db->prepare("SELECT id, password FROM user WHERE email = :email");

		$stmt->bindParam(":email", $email);
		$stmt->execute();

		$user = $stmt->fetch();

		if (!$user) {
			$this->parent->setAlert("danger", "Incorrect Login (Bad e-mail)");
			return false;
		}

		$hash = $user["password"];

		if (password_verify($password, $hash)) {
			$this->email = $email;
			$this->uid = $user["id"];
			$this->getIDs();

			$this->parent->setAlert("success", "Login Success");
			return true;
		} else {
			$this->parent->setAlert("danger", "Incorrect Login (Bad password)");
			return false;
		}
	}

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
			$this->parent->setAlert("success", "No Session to destroy");
			return true;
		}

		$stmt = $this->db->prepare("DELETE FROM sessions WHERE sid = :sid");
		$stmt->bindParam(":sid", $_SESSION["sid"]);

		$stmt->execute();

		session_destroy();

		$this->parent->setAlert("success", "You have been logged out");

		return true;
	}

	/**
	 * [register description]
	 * @param  [type] $stripeId       [description]
	 * @param  [type] $email          [description]
	 * @param  [type] $password       [description]
	 * @param  [type] $repeatpassword [description]
	 *
	 * @return boolean                 [description]
	 */
	public function register($stripeId, $email, $password = null, $repeatpassword = null) {
		if (is_null($password)) {
			$password = $this->random_str(16);
		} else {
			if ($password !== $repeatpassword) {
				$this->setAlert('danger', "Passwords don't match");
				return false;
			}
		}

		if ($this->userExists($email)) {
			$this->setAlert('danger', "Email address already has an account");
			return false;
		}

		$hash = password_hash($password, PASSWORD_BCRYPT);

		$stmt = $this->db->prepare("INSERT INTO user VALUES(NULL, :email, :hash, '', 5, 1, :stripeId)");

		$stmt->bindValue(":email", $email);
		$stmt->bindValue(":hash", $hash);
		$stmt->bindValue(":stripeId", $stripeId);

		$stmt->execute();

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
	 * [email_new_user description]
	 * @param  [type] $email    [description]
	 * @param  [type] $password [description]
	 * @return [type]           [description]
	 */
	public function email_new_user($email, $password) {
		$mail->setFrom('noreply@<domain.tld>', $this->model->siteName);
		$mail->addAddress($email);

		$mail->isHTML(true);

		$mail->Subject = $this->model->siteName . ' Registration';
		$mail->Body    = 'This is the HTML message body <b>in bold!</b><br>Password: ' .$password;
		$mail->AltBody = 'This is the body in plain text for non-HTML mail clients\n Password:' .$password;

		if(!$mail->send()) {
		    echo 'Message could not be sent.';
		    echo 'Mailer Error: ' . $mail->ErrorInfo;
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
	public function userExists($email) {
		$stmt = $this->db->prepare("SELECT email FROM user WHERE email = :email");

		$stmt->bindParam(":email", $email);
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

		$stmt->execute();

		$row = $stmt->fetch();

		if ($row["uid"] != $_SESSION["uid"]) {
			return false;
		}

		if ($row["sid"] != $_SESSION["sid"]) {
			return false;
		}

		if ($row["tid"] != $_SESSION["tid"]) {
			return false;
		}

		if ($row["ip"] != $_SERVER["REMOTE_ADDR"]) {
			return false;
		}

		$this->uid = $row["uid"];
		$this->updateIDs();

		$this->userLoggedin = true;

		return true;
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
		$stmt = $this->db->prepare("SELECT user_level FROM pages WHERE pagename = :page");

		$stmt->bindParam(":page", $page);
		$stmt->execute();

		$page = $stmt->fetch();

		return $page["user_level"];
	}

}
