<?php

/**
 * Database management helpers
 *
 * @author Christophe SAUVEUR <chsxf.pro@gmail.com>
 * @version 2.0.4
 */

namespace chsxf\PDO;

/**
 * Constant for the name of the table into which errors are logged (if the feature is enabled).
 * Can be overridden if defined before the inclusion of this file.
 */
if (!defined('chsxf\PDO\ERRORS_TABLE')) {
    define('chsxf\PDO\ERRORS_TABLE', 'database_errors');
}

/**
 * Database manager class
 */
class DatabaseManager extends \PDO
{
    /**
     * @var array Accepted values for the return type of rows
     */
    private static $_validReturnTypes = array(\PDO::FETCH_OBJ, \PDO::FETCH_ASSOC, \PDO::FETCH_NUM, \PDO::FETCH_BOTH);

    /**
     * @var bool If set, errors will be logged in the database
     */
    private bool $_useDatabaseErrorLogging;

    /**
     * @var array Errors holder used while in transaction
     */
    private array $_errors;

    /**
     * @var bool Flag indicating if the instance is inside the error logging procedure.
     */
    private bool $_loggingError;

    /**
     * Constructor
     * @param string $dsn Data Source Name (ie mysql:host=localhost;dbname=mydb)
     * @param string $username Username
     * @param string $password Password
     * @param array $options Driver options
     * @param bool $useDatabaseErrorLogging Is set, errors will be logged in the database. False by default
     *
     * @see \PDO::__construct()
     */
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null, bool $useDatabaseErrorLogging = false)
    {
        parent::__construct($dsn, $username, $password, $options);

        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);

        $this->_useDatabaseErrorLogging = $useDatabaseErrorLogging;
        $this->_errors = array();
        $this->_loggingError = false;
    }

    /**
     * (non-PHPdoc)
     * @see \PDO::setAttribute()
     */
    public function setAttribute(int $attribute, mixed $value): bool
    {
        if ($attribute != \PDO::ATTR_DEFAULT_FETCH_MODE) {
            return parent::setAttribute($attribute, $value);
        } else {
            $validReturnType = $this->_validateReturnType($value);
            if ($validReturnType === \PDO::FETCH_DEFAULT) {
                return false;
            }
            return parent::setAttribute($attribute, $validReturnType);
        }
    }

    /**
     * Validates the return type for query results
     * @param int $return_type Return type
     * @return int the specified return type, or \PDO::FETCH_DEFAULT if invalid
     */
    private function _validateReturnType(int $return_type): int
    {
        return in_array($return_type, self::$_validReturnTypes) ? $return_type : \PDO::FETCH_DEFAULT;
    }

    /**
     * Logs a SQL error into the database for further analysis.
     * If the connection is currently in a transaction, the errors are logged and pushed to the database on the next rollback or commit.
     * If error logging to database is disabled, this function returns immediately.
     *
     * @param string $query The SQL query that generated the error.
     * @param \PDOStatement $statement If provided, error info is gathered from this statement handle. (Defaults to NULL)
     */
    private function _logError(string $query, \PDOStatement $statement = null)
    {
        if (!$this->_useDatabaseErrorLogging || $this->_loggingError) {
            return;
        }
        $this->_loggingError = true;

        $errInfo = ($statement !== null) ? $statement->errorInfo() : $this->errorInfo();
        $errCode = $errInfo[1];
        $errMsg = $errInfo[2];

        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $btNext = reset($bt);
        do {
            $btEl = $btNext;
            if ($btEl === false) {
                break;
            }
            $btNext = next($bt);
        } while ($btNext !== false && $btNext['class'] == __CLASS__);
        if ($btEl === false) {
            $this->_loggingError = false;
            return;
        }

        $err = array($query, $errCode, $errMsg, $btEl['file'], $btEl['line'], $btEl['function'], $btEl['class']);
        if ($this->inTransaction()) {
            $this->_errors[] = $err;
        } else {
            $this->_pushError($err);
        }
        $this->_loggingError = false;
    }

    /**
     * Pushes error info to the database
     * If error logging to database is disabled, this function is never called.
     *
     * @param array $error Error info
     */
    private function _pushError(array $error)
    {
        $stmt = $this->prepare(sprintf("INSERT INTO `%s` VALUE (?, ?, ?, ?, ?, ?, ?)", ERRORS_TABLE));
        if ($stmt !== false) {
            $stmt->execute($error);
        }
    }

    /**
     * Pushes all error info to the database
     * If error logging to database is disabled, this function iterates over an empty array.
     */
    private function _pushErrors()
    {
        foreach ($this->_errors as $error) {
            $this->_pushError($error);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \PDO::commit()
     */
    public function commit(): bool
    {
        $res = parent::commit();
        $this->_pushErrors();
        return $res;
    }

    /**
     * (non-PHPdoc)
     * @see \PDO::rollBack()
     */
    public function rollBack(): bool
    {
        $res = parent::rollBack();
        $this->_pushErrors();
        return $res;
    }

    /**
     * Filter arguments so if the first argument is an array, then it is used instead
     * @var array $arguments List of arguments to filter
     * @return array The filtered arguments list
     */
    private static function _filterArguments(array $arguments): array
    {
        if (isset($arguments[0]) && is_array($arguments[0])) {
            return $arguments[0];
        }
        return $arguments;
    }

    /**
     * (non-PHPdoc)
     * @param string $statement SQL statement
     * @param array $driver_options Optional PDO driver options
     * @see \PDO::prepare()
     */
    public function prepare($statement, array $driver_options = []): \PDOStatement|false
    {
        $stmt = parent::prepare($statement, $driver_options);
        if ($stmt === false) {
            $this->_logError($statement);
        }
        return $stmt;
    }

    /**
     * (non-PHPdoc)
     * @param string $statement SQL statement
     * @see \PDO::query()
     */
    public function query($statement, $fetchMode = null, ...$arguments): \PDOStatement|false
    {
        $stmt = parent::query($statement, $fetchMode, ...$arguments);
        if ($stmt === false) {
            $this->_logError($statement);
        }
        return $stmt;
    }

    /**
     * Executes an SQL statement.
     *
     * This function is augmented from the original version and makes use of prepared statement
     * if additional parameters are passed.
     *
     * @param string $statement SQL statement
     * @return int|false The number of rows affected or false in case of an error
     *
     * @see \PDO::exec()
     * @see \PDO::prepare()
     * @see \PDOStatement::execute()
     */
    public function exec(string $statement, mixed ...$arguments): int|false
    {
        // Using prepared statement
        if (!empty($arguments)) {
            $stmt = $this->prepare($statement);
            if ($stmt == false) {
                return false;
            }

            $args = self::_filterArguments($arguments);
            if ($stmt->execute($args) === false) {
                $this->_logError($statement, $stmt);
                return false;
            }
            return $stmt->rowCount();
        }
        // Using native function if no parameters
        else {
            $res = parent::exec($statement);
            if ($res === false) {
                $this->_logError($statement);
            }
            return $res;
        }
    }

    /**
     * Retrieves one or several rows from the database
     *
     * This function accepts optional arguments for prepared statements.
     *
     * @param string $statement SQL statement
     * @param string $return_type Specifies if the function should return rows as objects, associative or numeric arrays. (Defaults to the default configuration).
     * @return array|false an array containing all rows returned by the statement or false in case of an error.
     */
    public function get(string $statement, int $return_type = \PDO::FETCH_DEFAULT, mixed ...$arguments): array|false
    {
        $stmt = $this->prepare($statement);
        if ($stmt === false) {
            return false;
        }

        // Executing the query
        $args = self::_filterArguments($arguments);
        if ($stmt->execute($args) === false) {
            $this->_logError($statement, $stmt);
            return false;
        }

        // Retrieving data
        return $stmt->fetchAll($this->_validateReturnType($return_type));
    }

    /**
     * Retrieves a column from the database
     *
     * This function accepts optional arguments for prepared statements.
     *
     * @param string $statement SQL statement
     * @return array|false an array containing all values from the column returned by the statement or false in case of an error.
     */
    public function getColumn(string $statement, mixed ...$arguments): array|false
    {
        $stmt = $this->prepare($statement);
        if ($stmt === false) {
            return false;
        }

        // Executing the query
        $args = self::_filterArguments($arguments);
        if ($stmt->execute($args) === false) {
            $this->_logError($statement, $stmt);
            return false;
        }

        return $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
    }

    /**
     * Retrieves one row from the database
     *
     * This function accepts optional arguments for prepared statements.
     *
     * @param string $statement SQL statement
     * @param string $return_type Specifies if the function should return the row as an object, an associative or a numeric array. (Defaults to the default configuration).
     * @return array|object|false an array or an object containing the row returned by the statement or false in case of an error.
     */
    public function getRow(string $statement, int $return_type = \PDO::FETCH_DEFAULT, mixed ...$arguments): array|object|false
    {
        $stmt = $this->prepare($statement);
        if ($stmt === false) {
            return false;
        }

        // Executing the query
        $args = self::_filterArguments($arguments);
        if ($stmt->execute($args) === false) {
            $this->_logError($statement, $stmt);
            return false;
        }

        // Retrieving data
        $row = $stmt->fetch($this->_validateReturnType($return_type));
        $stmt->closeCursor();
        return $row;
    }

    /**
     * Retrieves a value from the database
     *
     * This function accepts optional arguments for prepared statements.
     *
     * @param string $statement SQL statement
     * @return mixed the value returned by the statement or false in case of an error.
     */
    public function getValue(string $statement, mixed ...$arguments): mixed
    {
        $stmt = $this->prepare($statement);
        if ($stmt === false) {
            return false;
        }

        // Executing the query
        $args = self::_filterArguments($arguments);
        if ($stmt->execute($args) === false) {
            $this->_logError($statement, $stmt);
            return false;
        }

        $value = $stmt->fetchColumn();
        $stmt->closeCursor();
        return $value;
    }

    /**
     * Retrieves value as a key-value associative array, where for each row the first returned value
     * is the key and the second the value.
     *
     * This function accepts optional arguments for prepared statements.
     *
     * @param string $statement SQL statement
     *
     * @return array|false the key-value associative array or false in case of an error
     */
    public function getPairs(string $statement, mixed ...$arguments): array|false
    {
        $stmt = $this->prepare($statement);
        if ($stmt === false) {
            return false;
        }

        // Executing the query
        $args = self::_filterArguments($arguments);
        if ($stmt->execute($args) === false) {
            $this->_logError($statement, $stmt);
            return false;
        }
        if ($stmt->columnCount() < 2) {
            return false;
        }

        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /**
     * Retrieves one or several rows from the database, indexed by a key field
     *
     * This function accepts optional arguments for prepared statements.
     *
     * @param string $statement SQL statement
     * @param string $keyField Key field name
     * @param int $return_type Specifies if the function should return rows as objects, associative or numeric arrays. (Defaults to the default configuration).
     * @return array|false an array containing all rows returned by the statement or false if the key field does not exists or in case of an error.
     */
    public function getIndexed(string $statement, string $keyField, int $return_type = \PDO::FETCH_DEFAULT, mixed ...$arguments): array|false
    {
        $stmt = $this->prepare($statement);
        if ($stmt === false) {
            return false;
        }

        // Executing the query
        $args = self::_filterArguments($arguments);
        if ($stmt->execute($args) === false) {
            $this->_logError($statement, $stmt);
            return false;
        }

        // Checking key field existence
        $found = -1;
        $cc = $stmt->columnCount();
        for ($i = 0; $i < $cc; $i++) {
            $cm = $stmt->getColumnMeta($i);
            if ($cm['name'] == $keyField) {
                $found = $i;
                break;
            }
        }
        if ($found < 0) {
            return false;
        }

        // Retrieving data
        $rt = $this->_validateReturnType($return_type);
        if ($rt === \PDO::FETCH_DEFAULT) {
            $defaultReturnType = $this->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE);
            $rt = $this->_validateReturnType($defaultReturnType);
            // Fails if still not a valid return type
            if ($rt === \PDO::FETCH_DEFAULT) {
                return false;
            }
        }
        $results = array();
        while ($row = $stmt->fetch($rt)) {
            unset($altKey);
            switch ($rt) {
                case \PDO::FETCH_OBJ:
                    $key = $row->$keyField;
                    break;

                case \PDO::FETCH_BOTH:
                case \PDO::FETCH_ASSOC:
                    $key = $row[$keyField];
                    break;

                case \PDO::FETCH_NUM:
                    $key = $row[$found];
                    break;
            }
            $results[$key] = $row;
        }
        return $results;
    }
}
