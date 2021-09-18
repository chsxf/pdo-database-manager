# About This Project

The main purpose of this package is to extend the capabilities of [PDO](https://www.php.net/manual/en/book.pdo.php) by prodiving useful query methods to allow redaction of faster, safer and more reliable code.

## Legacy Repository

I originally wrote this package while working on several tools and websites for my now defunct videogame studio called Cheese Burgames.
As a fork, this repository is a continuation of the project.

## Conventions

This repository uses [gitmoji](https://gitmoji.dev) for its commit messages.

# Getting Started

## Requirements

* PHP 8.0.7+

## Installation

We strongly recommend using [Composer](https://getcomposer.org/) to install this package.

```
composer require chsxf/pdo-databasemanager
```

## Usage

The following uses a MySQL database but the same applies for [all database servers supported by PDO](https://www.php.net/manual/en/pdo.drivers.php). Adaptations may be needed though.

### Initialization

First, we have to load and initialize the database manager.
PDO uses a [Data Source Name](https://www.php.net/manual/en/pdo.construct.php), or DSN, to convey connection information.

```php
// Load the classes
require('vendor/autoload.php');
use \chsxf\PDO\DatabaseManager;

// Configuration - Adapt to fit your own configuration
$server_host = 'localhost';
$user = 'username';
$password = 'password';
$database_name = 'database_name';

// Initializing database manager
$dsn = "mysql:dbname={$database_name};host={$server_host}";
$dbm = new DatabaseManager($dsn, $user, $password);
```

### General Queries

You can use the [`query`](https://www.php.net/manual/en/pdo.query.php) method to execute any query you like.
In the context of this example, we create a temporary table to proceed with the rest of the helper functions.

```php
if ($dbm->query('CREATE TEMPORARY TABLE `test_table` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `label` varchar(255) COLLATE utf8_bin NOT NULL,
                `price` float unsigned NOT NULL,
                `stock` int(10) unsigned NOT NULL,
                PRIMARY KEY (`id`)
            )') === false) {
    die('Unable to create temporary table');
}
```

Both `query` and `exec` methods have been overridden to support error logging, among other things.

As most methods of the `DatabaseManager` class, `query` returns `false` in case of an error, so you can act accordingly.

### Executing Altering Statements with Placeholders

Use the [`exec`](https://www.php.net/manual/en/pdo.exec.php) method in order to retrieve the number of affected rows by an alterning statement like `UPDATE` or `DELETE`.
In complement of error logging, this method has been overridden to allow execution of statements with placeholders and values in one call.

```php
// Inserting sample data
$sql = 'INSERT INTO `test_table` (`label`, `price`, `stock`) VALUE (?, ?, ?)';
$values = [
        [ 'PHP for Dummies', 15.00, 100 ],
        [ 'The Lord of the Rings Trilogy', 46.74, 25 ],
        [ '1984', 13.42, 50 ]
];
foreach ($values as $rowValues) {
    if ($dbm->exec($sql, $rowValues) === false) {
        die('Unable to add sample data');
    }
}
```

### Retrieving a Single Specific Value

Use the `getValue` method to retrieve a single value from your database.
As for the `exec` method, you can pass a statement with placeholders along with the replacement values in a single call, improving security and productivity.

```php
$productLabel = '1984';
$productID = $dbm->getValue('SELECT `id` FROM `test_table` WHERE `label` = ?', $productLabel);
if ($productID === false) {
    die('Unable to retrieve product ID');
}
```

### Retrieval of a Specific Column

The `getColumn` method fetches the values of the first column returned by the provided statement.

```php
$productLabels = $dbm->getColumn('SELECT `label` FROM `test_table` ORDER BY `label` ASC');
if ($productLabels === false) {
    die('Unable to retrieve product labels');
}
echo "Product labels:\n";
foreach ($productLabels as $label) {
    echo "\t- {$label}\n";
}
```

Outputs:

```
Product labels:
	- 1984
	- PHP for Dummies
	- The Lord of the Rings Trilogy
```

### Retrieval of a Specific Row

The `getRow` method fetches the first row returned by the provided statement.

Along with the `get` and `getIndexed` methods, you can specify how rows are fetched.
At the moment, only `PDO::FETCH_ASSOC`, `PDO::FETCH_NUM`, `PDO::FETCH_OBJ` and `PDO::FETCH_BOTH` (PHP's default) are supported.

```php
$productData = $dbm->getRow('SELECT * FROM `test_table` WHERE `id` = ?', \PDO::FETCH_ASSOC, $productID);
if ($productData === false) {
    die('Unable to retrieve product data');
}
echo "Specific row (as an associative array):\n";
foreach ($productData as $k => $v) {
    echo "\t- {$k}: {$v}\n";
}
```

Outputs:

```
Specific row (as an associative array):
	- id: 3
	- label: 1984
	- price: 13.42
	- stock: 50
```

### Retrieval of Several Rows

The `get` method fetches all rows returned by the previded statement.

```php
$productData = $dbm->get('SELECT * FROM `test_table`', \PDO::FETCH_NUM);
if ($productData === false) {
    die('Unable to retrieve product data');
}
echo "Several rows (as numerically-indexed arrays):\n";
for ($i = 0; $i < count($productData); $i++) {
    $row = $productData[$i];
    echo "\tRow #{$i}:\n";
    foreach ($row as $k => $v) {
        echo "\t\t- {$k}: {$v}\n";
    }
}
```

Outputs:

```
Several rows (as numerically-indexed arrays):
	Row #0:
		- 0: 1
		- 1: PHP for Dummies
		- 2: 15
		- 3: 100
	Row #1:
		- 0: 2
		- 1: The Lord of the Rings Trilogy
		- 2: 46.74
		- 3: 25
	Row #2:
		- 0: 3
		- 1: 1984
		- 2: 13.42
		- 3: 50
```

### Retrieval of Rows Indexed by a Field Value

The `getIndexed` method fetches all rows returned by the provided statement, indexed by the value of a specific field.

```php
$productData = $dbm->getIndexed('SELECT * FROM `test_table`', 'id', \PDO::FETCH_OBJ);
if ($productData === false) {
    die('Unable to retrieve product data');
}
echo "Indexed rows (as objects):\n";
foreach ($productData as $id => $row) {
    echo "\tProduct with ID #{$id}:\n";
    foreach ($row as $k => $v) {
        echo "\t\t- {$k}: {$v}\n";
    }
}
```

Outputs:

```
Indexed rows (as objects):
	Product with ID #1:
		- id: 1
		- label: PHP for Dummies
		- price: 15
		- stock: 100
	Product with ID #2:
		- id: 2
		- label: The Lord of the Rings Trilogy
		- price: 46.74
		- stock: 25
	Product with ID #3:
		- id: 3
		- label: 1984
		- price: 13.42
		- stock: 50
```

### Retrieval of Pairs of Values

The `getPairs` method fetches pairs of values returned by the provided statement. The values of the first and second returned columns are used respectively as keys and values of the returned array.

```php
$productPairs = $dbm->getPairs('SELECT `id`, `price` FROM `test_table` ORDER BY `price` DESC');
if ($productPairs === false) {
    die('Unable to retrieve price per ID');
}
echo "Pairs of values:\n";
foreach ($productPairs as $k => $v) {
    echo "\t- Price for product #{$k}: {$v}\n";
}
```

Outputs:

```
Pairs of values:
	- Price for product #2: 46.74
	- Price for product #1: 15
	- Price for product #3: 13.42
```

# Error Logging

The `DatabaseManager` class provides an optional error logging system that can help a lot with debugging.

The errors are logged directly into the database in a specific table. **It is obviously NOT RECOMMENDED to use this error logging system in production environments.**

To enable error logging, pass `true` to the `$useDatabaseErrorLogging` parameter in the constructor.

## Error Table Structure

In order to work, your database must include a table with the following structure:

```sql
CREATE TABLE `database_errors` (
  `query` text COLLATE utf8_bin NOT NULL,
  `error_code` int(11) NOT NULL,
  `error_message` text COLLATE utf8_bin NOT NULL,
  `file` text COLLATE utf8_bin NOT NULL,
  `line` int(11) NOT NULL,
  `function` text COLLATE utf8_bin NOT NULL,
  `class` text COLLATE utf8_bin NOT NULL
);
```

By default, the name of the table is `database_errors`, but you can change it by defining a constant named `chsxf\PDO\ERRORS_TABLE` before loading the `DatabaseManager` class.

```php
define('chsxf\PDO\ERRORS_TABLE', 'my_custom_error_table_name');
use \chsxf\PDO\DatabaseManager;
```

# License

This project is released under the terms of the [MIT License](LICENSE).
