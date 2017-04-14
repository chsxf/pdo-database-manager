<?php
	header("Content-type: text/plain; charset=UTF-8");

	// Configuration
	$server_host = 'localhost';
	$user = 'root';
	$password = 'root';
	$database_name = 'test_database';
	
	// Include database manager
	require_once('../src/CheeseBurgames/PDO/DatabaseManager.php');
	$dsn = "mysql:dbname={$database_name};host={$server_host}";
	$dbm = new \CheeseBurgames\PDO\DatabaseManager($dsn, $user, $password);
	
	// Table creation (dropping it first if already existing)
	$dbm->query("DROP TABLE IF EXISTS `test_table`");
	$dbm->query("CREATE TABLE `test_table` (
				 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				 `label` varchar(255) COLLATE utf8_bin NOT NULL,
				 `price` float unsigned NOT NULL,
				 `stock` int(10) unsigned NOT NULL,
				 PRIMARY KEY (`id`)
				)");
	
	// Inserting sample data
	$sql = "INSERT INTO `test_table` (`label`, `price`, `stock`) VALUE (?, ?, ?)";
	$values = array(
			array( 'PHP for Dummies', 15.00, 100 ),
			array( 'The Lord of the Rings Trilogy', 46.74, 25 ),
			array( '1984', 13.42, 0 )
	);
	foreach ($values as $rowValues)
		$dbm->exec($sql, $rowValues);
	
	// Retrieving a single specific value
	$productLabel = '1984';
	$productID = $dbm->getValue("SELECT `id` FROM `test_table` WHERE `label` = ?", $productLabel);
		
	// Updating sample data
	$newStock = 50; 
	$dbm->exec("UPDATE `test_table` SET `stock` = ? WHERE `id` = ?", $newStock, $productID);
	
	// Retrieval of a specific column
	$productLabels = $dbm->getColumn("SELECT `label` FROM `test_table` ORDER BY `label` ASC");
	echo "Product labels:\n";
	var_dump($productLabels);
	echo "\n";
	
	// Retrieval of a specific row (as an associative array)
	$productData = $dbm->getRow("SELECT * FROM `test_table` WHERE `id` = ?", PDO::FETCH_ASSOC, $productID);
	echo "Specific row (as an associative array):\n";
	var_dump($productData);
	echo "\n";
	
	// Retrieval of several rows (as numerically-indexed arrays)
	$productData = $dbm->get("SELECT * FROM `test_table`", PDO::FETCH_NUM);
	echo "Several rows (as numerically-indexed arrays):\n";
	var_dump($productData);
	echo "\n";
	
	// Retrieval of rows indexed by an id field (as objects)
	$productData = $dbm->getIndexed("SELECT * FROM `test_table`", 'id');
	echo "Indexed rows (as objects):\n";
	var_dump($productData);
	echo "\n";
	
	// Retrieval of pairs of values
	$productPairs = $dbm->getPairs("SELECT `id`, `price` FROM `test_table` ORDER BY `price` DESC");
	echo "Pairs of values:\n";
	var_dump($productPairs);
	echo "\n";