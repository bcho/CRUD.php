<?php

namespace CRUD;

/**
 * Database configurations class
 */
class CRUD
{
    /**
     * PDO connection configurations
     */
    private static $configs = array(
        'dsn' => 'sqlite::memory:',
        'username' => null,
        'password' => null,
        'driver_options' => array()
    );

    /**
     * PDO connection instance
     */
    private static $pdo = null;

    /**
     * Setup up `PDO` connection configurations.
     *
     * @example CRUD::configure('sqlite::memory:')
     * @example CRUD::configure('username', 'username')
     * @example CRUD::configure(array(
     *              'username' => 'username',
     *              'dsn' => 'sqlite::memory:')
     *              )
     */
    public static function configure($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                self::configure($k, $v);
            }
        } else {
            // shortcut for setting dsn
            if (is_null($value)) {
                $value = $name;
                $name = 'dsn';
            }
            self::$configs[$name] = $value;
        }
    }

    /**
     * Retrieve a `PDO` connection instance.
     */
    public static function getCursor()
    {
        if (!static::$pdo) {
            $pdo = new \PDO(
                static::$configs['dsn'],
                static::$configs['username'],
                static::$configs['password'],
                static::$configs['driver_options']
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            static::$pdo = $pdo;
        }

        return static::$pdo;
    }
}

abstract class CRUDModel
{
    /**
     * Table name
     */
    protected static $table = null;

    /**
     * Table primary key name
     */
    protected static $pk = null;

    /**
     * Prepare a statement.
     *
     * @param string sql statement
     * @param array bind values
     * @return \PDOStatement
     */
    protected static function prepare($rawStat, $bindValues = array())
    {
        $cursor = CRUD::getCursor();
        $stat = $cursor->prepare($rawStat);

        foreach($bindValues as $k => $v) {
            $stat->bindValue(":$k", $v);
        }

        return $stat;
    }

    /**
     * Execute a sql statement.
     *
     * @param string sql statement
     * @param array bind values
     * @return bool
     */
    protected static function execute($rawStat, $bindValues = array())
    {
        return static::prepare($rawStat, $bindValues)->execute();
    }

    /**
     * Fetch a sql statement execute result.
     *
     * @param string sql statement
     * @param array bind values
     * @return mixed
     */
    protected static function fetch($rawStat, $bindValues = array())
    {
        $stat = static::prepare($rawStat, $bindValues);
        $stat->execute();
        return $stat->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetch all sql statement execute results.
     *
     * @param string sql statement
     * @param array bind values
     * @return array
     */
    protected static function fetchAll($rawStat, $bindValues = array())
    {
        $stat = static::prepare($rawStat, $bindValues);
        $stat->execute();
        return $stat->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Build a where clause.
     *
     * @param array list of condition's name
     * @return string
     */
    protected static function buildWhereClause($conditionNames)
    {
        $clause = array();
        foreach ($conditionNames as $name) {
            $clause[] = "$name = :$name";
        }
        return "WHERE " . join(' AND ', $clause);
    }

    /**
     * Create a record.
     *
     * @param array row data
     * @return int
     */
    public static function create($data)
    {
        $keys = array();
        $values = array();
        if (isset($data[static::$pk])) {
            unset($data[static::$pk]);
        }
        foreach ($data as $k => $v) {
            $keys[] = "'$k'";
            $values[] = ":$k";
        }

        $table = static::$table;
        $keys = join(', ', $keys);
        $values = join(', ', $values);
        $stat = "INSERT INTO $table ($keys) VALUES ($values)";
        static::execute($stat, $data);

        return CRUD::getCursor()->lastInsertId();
    }

    /**
     * Read a record.
     *
     * @param mixed primary key's value or where conditions
     * @return array
     */
    public static function readOne($conditions)
    {
        // shortcut for primary key
        if (!is_array($conditions)) {
            return static::readOne(array(
                static::$pk => $conditions
            ));
        }

        $table = static::$table;
        $whereClause = static::buildWhereClause(array_keys($conditions));
        $stat = "SELECT * FROM $table $whereClause LIMIT 1";
        return static::fetch($stat, $conditions);
    }

    /**
     * Read some records.
     *
     * @param array conditions
     * @return array
     */
    public static function readMany($conditions)
    {
        $table = static::$table;
        $whereClause = static::buildWhereClause(array_keys($conditions));
        $stat = "SELECT * FROM $table $whereClause";
        return static::fetchAll($stat, $conditions);
    }

    /**
     * Update a record.
     *
     * @param mixed primary key's value
     * @param array record updated data
     * @return array
     */
    public static function update($pk, $data)
    {
        $table = static::$table;

        if (isset($data[static::$pk])) {
            unset($data[static::$pk]);
        }
        $updated = array();
        foreach (array_keys($data) as $k) {
            $updated[] = "'$k' = :$k";
        }
        $updated = 'SET ' . join(',', $updated);

        $conditions = array(
            static::$pk => $pk
        );
        $whereClause = static::buildWhereClause(array_keys($conditions));

        $stat = "UPDATE $table $updated $whereClause";
        static::execute($stat, array_merge($data, $conditions));

        return static::readOne($pk);
    }

    /**
     * Delete a record.
     *
     * @param mixed primary key's value or where conditions
     * @return bool
     */
    public static function delete($conditions)
    {
        // shortcut for primary key
        if (!is_array($conditions)) {
            return static::delete(array(
                static::$pk => $conditions
            ));
        }

        $table = static::$table;
        $whereClause = static::buildWhereClause(array_keys($conditions));
        $stat = "DELETE FROM $table $whereClause";
        return static::execute($stat, $conditions);
    }
}
