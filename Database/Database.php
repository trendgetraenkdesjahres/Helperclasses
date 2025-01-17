<?php

namespace  PHP_Library\Database;

use PHP_Library\Database\Error\DatabaseError;
use PHP_Library\Database\SQLanguage\Statement\AbstractStatement;
use PHP_Library\Database\Table\Column\Column;
use PHP_Library\Database\Table\DataTable;
use PHP_Library\Settings\Settings;
use ReflectionClass;

/**
 * Abstract class representing a database.
 * Provides methods to interact with database storage or file-based storage,
 * including querying data, managing tables, and handling errors.
 * Relies on `Settings` for configuration and `DatabaseError` for error handling.
 */
abstract class Database
{
    /**
     * Holds the initialized storage instance (SQL or File-based).
     * @var Database|null
     */
    private static ?Database $instance = null;

    /**
     * Initialize the database connection.
     * @return bool Returns true if initialization is successful, false otherwise.
     */
    abstract protected static function initalize(): bool;

    /**
     * Get the last inserted ID.
     * @return int|false The last inserted ID, or false if not available.
     */
    abstract public static function last_insert_id(): int|false;

    /**
     * Retrieve the queried data.
     * @param bool $clean_array If true, flattens arrays with a single element.
     * @return mixed The queried data.
     */
    abstract protected static function get_queried_data(bool $clean_array = false): mixed;

    /**
     * Execute the given SQL statement.
     * @param AbstractStatement $sql_statement The SQL statement to execute.
     * @return bool Returns true on success, false on failure.
     */
    abstract protected static function execute_query(AbstractStatement $sql_statement): bool;


    /**
     * Database constructor to initialize the database connection.
     * Calls `initalize()` to set up the connection.
     */
    final public function __construct()
    {
        static::initalize();
    }

    /**
     * Execute a SQL query and fetch results.
     * @param AbstractStatement $sql_statement The SQL statement to execute.
     * @return bool Returns true on success, false on failure.
     */
    final public static function query(AbstractStatement $sql_statement): bool
    {
        if (static::get_type() === __CLASS__) {
            DatabaseError::trigger("Database is not initiated.", fatal: true);
        }
        return self::get_instance()::execute_query($sql_statement);
    }

    /**
     * Get the result of the most recent query.
     * @param bool $clean_array If true, flattens arrays with a single element.
     * @return mixed The queried data.
     */
    public static function get_query_result(bool $clean_array = false): mixed
    {
        return self::get_instance()::get_queried_data($clean_array);
    }

    /**
     * Get a table instance by name.
     * @param string $name The name of the table.
     * @return DataTable The table instance.
     */
    public static function get_table(string $name): DataTable
    {
        return self::get_instance()::get_table($name);
    }

    /**
     * Get the last inserted ID.
     * @return int|false The last inserted ID or false if unavailable.
     */
    public static function get_last_insert_id(): int|false
    {
        return self::get_instance()::last_insert_id();
    }

    /**
     * Create a new table with the specified columns.
     * @param string $name The table name.
     * @param Column ...$columns The table columns.
     * @return bool Returns true on success, false on failure.
     */
    public static function create_table(string $name, Column ...$columns): bool
    {
        return self::get_instance()::create_table($name, ...$columns);
    }

    /**
     * Check if a table exists.
     * @param string $name The name of the table.
     * @return bool True if the table exists, false otherwise.
     */
    public static function table_exists(string $name): bool
    {
        return self::get_instance()::table_exists($name);
    }

    /**
     * Get the type of the database storage (SQL or File-based).
     * @return string The class name of the database storage implementation.
     */
    public static function get_type(): string
    {
        return (new ReflectionClass(self::get_instance()))->getShortName();
    }

    /**
     * Get the last database error.
     * @return DatabaseError|null The last error or null if no error.
     */
    public static function get_last_error(): DatabaseError|null
    {
        return isset(DatabaseError::$last_error) ? DatabaseError::$last_error : null;
    }

    /**
     * Factory method to get or initialize the appropriate database instance.
     * @return Database The initialized database instance.
     * @throws DatabaseError If no suitable database configuration is found.
     */
    protected static function get_instance(): static
    {
        if (self::$instance === null) {
            // Check settings to decide the storage type
            if (Settings::get('Database/database_name')) {
                self::$instance = new SQLDatabase();
            } elseif (Settings::get('Database/file_name')) {
                self::$instance = new FileDatabase();
            } else {
                DatabaseError::trigger("No setting for 'Database/database_name' or 'Database/file_name' found in settings-file.", fatal: true);
            }
        }
        return self::$instance;
    }

    /**
     * Clean an array by flattening single-element arrays.
     * @param array $array The array to clean.
     * @return mixed The cleaned array or value.
     */
    protected static function clean_array(array $array): mixed
    {
        $count = count($array);
        switch ($count) {
            case 0:
                return [];
            case 1:
                $value = $array[array_key_first($array)];
                if (is_array($value)) {
                    return static::clean_array($value);
                }
                return $value;
            default:
                return $array;
        }
    }
}
