# Example

## About this example

This sample file executes a variety of queries on a database in order to demonstrate its capabilities.

The class DatabaseManager inherits from PHP's built-in [PDO class](http://php.net/manual/en/class.pdo.php) and thus has the same general behavior. However, we have added some useful extensions to some of the original functions.

Here is the list of the sequence executed by the script:
* Selection of a specific database
* Creation of a table (dropping it first if it already exists)
* Insertion of sample data into that table
* Update to selected parts of the inserted data
* Retrieval of a specific single value
* Retrieval of a specific column
* Retrieval of a sepcific row
* Retrieval of several rows
* Retrieval of rows indexed by an _id_ field

## How to run this example

### System requirements

To make this example work, you will need:
* a SQL server with a database called `test_database` (preferably MySQL)
* PHP 5.1 or later version (through a web server like Apache or from the command-line)

### Executing from the command-line

* Make sure the `php` executable is in your path
* Type the following command into your command-line interface `php -f /path/to/example.php`

### Executing through a web server

* Copy the content of this repository to a sub-folder of your webserver documents root directory
* Make sur your webserver is running
* In your favorite browser, head to `http://yourwebserveraddress/path/to/example.php` (ie `http://localhost/pdo-database-manager/example/example.php`)
