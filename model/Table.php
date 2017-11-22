<?php

class Table extends Model {
  public function __construct($parent) {
    $this->parent	= $parent;
    $this->db	= $this->parent->db;

    $this->tables = array(
      "login_attempts" => "CREATE TABLE `login_attempts` (
        `ip_address` varchar(16) DEFAULT NULL,
        `uid` int(11) DEFAULT NULL,
        `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      )",
      "pages" => "CREATE TABLE `pages` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `pagename` varchar(64) NOT NULL,
        `user_level` int(11) NOT NULL,
        PRIMARY KEY (`id`)
      )",
      "post" => "CREATE TABLE `post` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `category_id` int(11) NOT NULL,
        `title` varchar(128) NOT NULL,
        `shortdescription` varchar(512) NOT NULL,
        `content` varchar(10000) NOT NULL,
        `url` varchar(64) NOT NULL,
        PRIMARY KEY (`id`)
      )",
      "saved" => "CREATE TABLE `saved` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `pid` int(11) NOT NULL,
        `uid` int(11) NOT NULL,
        PRIMARY KEY (`id`)
      )",
      "history" => "CREATE TABLE `history` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `pid` int(11) NOT NULL,
        `viewed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `uid` int(11) NOT NULL,
        PRIMARY KEY (`id`)
      )",
      "category" => "CREATE TABLE `category` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(64) NOT NULL,
        `visible` int(11) NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `id` (`id`)
      )",
      "sessions" => "CREATE TABLE `sessions` (
        `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `uid` int(11) NOT NULL,
        `sid` varchar(64) NOT NULL,
        `tid` varchar(64) NOT NULL,
        `ip` varchar(16) NOT NULL
      )",
      "user" => "CREATE TABLE `user` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(24) DEFAULT NULL,
        `email` varchar(254) DEFAULT NULL,
        `password` varchar(64) DEFAULT NULL,
        `description` varchar(128) DEFAULT NULL,
        `user_type` int(11) DEFAULT NULL,
        `enabled` int(11) NOT NULL DEFAULT '1',
        PRIMARY KEY (`id`)
      )"
    );

    $this->tableRows = array(
      "login_attempts" => array(),
      "pages" => array("INSERT INTO pages VALUES(NULL, 'index', 10);",
        "INSERT INTO pages VALUES(NULL, 'admin', 1);",
        "INSERT INTO pages VALUES(NULL, 'admin-addproduct', 1);",
        "INSERT INTO pages VALUES(NULL, 'admin-edituser', 1);",
        "INSERT INTO pages VALUES(NULL, 'admin-manageproducts', 1);",
        "INSERT INTO pages VALUES(NULL, 'admin-manageusers', 1);",
        "INSERT INTO pages VALUES(NULL, 'admin-settings', 1);",
        "INSERT INTO pages VALUES(NULL, 'account', 5);",
        "INSERT INTO pages VALUES(NULL, 'account-saved', 5);",
        "INSERT INTO pages VALUES(NULL, 'account-history', 5);",
        "INSERT INTO pages VALUES(NULL, 'account-submitted', 5);",
        "INSERT INTO pages VALUES(NULL, 'account-settings', 5);",
        "INSERT INTO pages VALUES(NULL, 'search', 10);",
        "INSERT INTO pages VALUES(NULL, 'post', 10);",
        "INSERT INTO pages VALUES(NULL, 'login', 10);",
        "INSERT INTO pages VALUES(NULL, 'resetpassword', 5);",
        "INSERT INTO pages VALUES(NULL, 'logout', 10);",
        "INSERT INTO pages VALUES(NULL, '404', 10);",
        "INSERT INTO pages VALUES(NULL, 'purchase', 10);",
        "INSERT INTO pages VALUES(NULL, 'abuse', 10);",
        "INSERT INTO pages VALUES(NULL, 'customer', 5);",
        "INSERT INTO pages VALUES(NULL, 'faq', 10);",
        "INSERT INTO pages VALUES(NULL, 'termsofservice', 10);",
        "INSERT INTO pages VALUES(NULL, 'privacypolicy', 10);",
      ),
      "products" => array(),
      "product_features" => array(),
      "sessions" => array(),
      "user" => array("INSERT INTO user VALUES (NULL, 'Admin', 'admin@example.com', '$2y$10\$dnE.zKJt9Wr1RkAm1/WPM.ZCTSDhEokM.6pSyyYw9NYenoCinxtKy', 'Default admin account', 0, 1);")
    );
  }

  public function createTable($table) {
    $this->db->exec($this->tables[$table]);
  }

  public function insertDefaultRows($table) {
    foreach ($this->tableRows[$table] as $row) {
      $this->db->exec($row);
    }
  }
}
