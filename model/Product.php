<?php

class Product extends Model {
	public function __construct($parent) {
		$this->parent	= $parent;
		$this->db	= $this->parent->db;
  }

	public function getProducts() {
		$stmt = $this->db->prepare("SELECT * FROM products ORDER BY id ASC LIMIT 5");

		$stmt->execute();

		return $stmt->fetchAll();
	}

	public function getAllFeatures($products) {
		$features = array();
		foreach($products as $product) {
			$features[$product["id"]] = $this->getProductFeatures($product["id"]);
		}

		return $features;
	}

	public function getProductFeatures($id) {
		$stmt = $this->db->prepare("SELECT feature FROM product_features WHERE pid = :id");

		$stmt->bindValue(":id", $id);

		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}

	public function getProduct($id) {
		$stmt = $this->db->prepare("SELECT * FROM products WHERE id = :id");

		$stmt->bindValue(":id", $id);

		$stmt->execute();

		return $stmt->fetch();
	}

	/**
	 * Return the price of a product
	 * @param  str $stripeSubPlan Stripe Plan ID
	 *
	 * @return str Price of product
	 */
	public function getProductPrice($stripeSubPlan) {
		$stmt = $this->db->prepare("SELECT price FROM products WHERE stripe_sub_plan = :id");

		$stmt->bindValue(":id", $stripeSubPlan);

		$stmt->execute();

		$price = $stmt->fetch();

		return $price["price"];
	}

	/**
	 * Check if given product exists
	 *
	 * @param  int $id Products ID
	 *
	 * @return bool     Status of existence
	 */
	public function productIdExists($id) {
		$stmt = $this->db->prepare("SELECT id FROM product WHERE id = :id");

		$stmt->bindParam(":id", $id);
		$stmt->execute();

		return count($stmt->fetchAll()) == 1;
	}

	/**
	 * Add a new product
	 *
	 * @param str $title       Title of the product
	 * @param str $description Desc. of the product
	 * @param str $price       Price of the, you guessed it, product
	 * @param str $stripePlan  Stripe plan id of the product
	 *
	 * @return int ID of newly inserted product
	 */
	public function addNewProduct($title, $description, $price, $stripePlan = null) {
		$stmt = $this->db->prepare("INSERT INTO products VALUES(NULL, :title, :description, :price, :stripe_plan)");
		$stmt->bindValue(":title", $title);
		$stmt->bindValue(":description", $description);
		$stmt->bindValue(":price", $price);
		$stmt->bindValue(":stripe_plan", $stripePlan);

		$stmt->execute();

		return $this->db->lastInsertId();
	}

	/**
	 * Delete rows from `product` and `product_features` that have the given ID
	 *
	 * @param  int $id ID of product to delete
	 *
	 */
	public function deleteProduct($id) {
		$stmt = $this->db->prepare("DELETE FROM products WHERE id = :id");
		$stmt->bindValue(":id", $id);
		$stmt->execute();

		$stmt = $this->db->prepare("DELETE FROM product_features WHERE pid = :id");
		$stmt->bindValue(":id", $id);
		$stmt->execute();
	}

	/**
	 * [addProductFeature description]
	 * @param int $id      [description]
	 * @param str $feature [description]
	 */
	public function addProductFeature($id, $feature) {
		try {
			$stmt = $this->db->prepare("INSERT INTO product_features VALUES(NULL, :pid, :feature);");
			$stmt->bindValue(":pid", $id);
			$stmt->bindValue(":feature", $feature);

			$stmt->execute();
		} catch (PDOException $e) {
			$this->checkTablesExist();
			return $this->addProductFeature($id, $feature);
		}
	}

}
