# Example

## About This Example

This sample file executes a variety of queries on a database in order to demonstrate its capabilities.

The class DatabaseManager inherits from PHP's built-in [PDO class](http://php.net/manual/en/class.pdo.php) and thus has the same general behavior. However, we have added some useful extensions to some of the original functions.

Here is the list of the sequence executed by the script:
* Creation of a table (dropping it first if it already exists)
* Insertion of sample data into that table
* Retrieval of a specific single value
* Update to selected parts of the inserted data
* Retrieval of a specific column
* Retrieval of a specific row
* Retrieval of several rows
* Retrieval of rows indexed by an _id_ field
* Retrieval of pairs of values

## How to Run This Example

### System Requirements

To make this example work, you will need:
* a MySQL 5+ server with a database called `test_database`
* PHP 8.0 or later version (through a web server like Apache or from the command-line)

### Script Configuration

This sample file uses a database called `test_database` as a demonstration.
If such a database already exists on your SQL server, you may want the script to use a different one.

Also, the scripts assumes your MySQL server is running locally (as `localhost`), using user `root` with password `root`.

You can change the default configuration by modifying lines 9 to 12 in the `example.php` file with appropriate values for your own setup.

```php
$server_host = 'localhost';
$user = 'root';
$password = 'root';
$database_name = 'test_database';
```

### Executing From the Command-line

* Make sure the `php` executable is in your path
* Type the following command into your command-line interface `php -f /path/to/example.php`

### Executing Through a Web Server

* Copy the content of this repository to a sub-folder of your webserver documents root directory
* Make sur your webserver is running
* In your favorite browser, head to `http://yourwebserveraddress/path/to/example.php` (ie `http://localhost/pdo-database-manager/example/example.php`)
