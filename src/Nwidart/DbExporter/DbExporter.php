<?php namespace Nwidart\DbExporter;

use DB;

abstract class DbExporter
{
    /**
     * Contains the ignore tables
     * @var array $ignore
     */
    public static $ignore = array('migrations');
    public static $remote;
    public static $connection;

    protected static function getDB()
    {
        if (self::$connection)
            return DB::connection(self::$connection);
        return DB::connection();
    }

    /**
     * Get all the tables
     * @return mixed
     */
    protected function getTables()
    {
        $pdo = self::getDB()->getPdo();
        return $pdo->query('SELECT table_name FROM information_schema.tables WHERE table_schema="' . $this->database . '"');
    }

    public function getTableIndexes($table)
    {
        $pdo = self::getDB()->getPdo();
        return $pdo->query('SHOW INDEX FROM ' . $table . ' WHERE Key_name != "PRIMARY"');
    }

    /**
     * Get all the columns for a given table
     * @param $table
     * @return mixed
     */
    protected function getTableDescribes($table)
    {
        return self::getDB()->table('information_schema.columns')
            ->where('table_schema', '=', $this->database)
            ->where('table_name', '=', $table)
            ->get($this->selects);
    }

    /**
     * Grab all the table data
     * @param $table
     * @return mixed
     */
    protected function getTableData($table)
    {
        return self::getDB()->table($table)->get();
    }

    /**
     * Write the file
     * @return mixed
     */
    abstract public function write();

    /**
     * Convert the database to a usefull format
     * @param null $database
     * @return mixed
     */
    abstract public function convert($database = null);

    /**
     * Put the converted stub into a template
     * @return mixed
     */
    abstract protected function compile();
}