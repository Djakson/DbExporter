<?php namespace Nwidart\DbExporter;

use Config, DB, Str, File;

class DbSeeding extends DbExporter
{
    /**
     * @var String
     */
    protected $database;

    /**
     * @var String
     */
    protected $seedingStub;

    /**
     * @var bool
     */
    protected $customDb = false;

    /**
     * Set the database name
     * @param String $database
     */
    function __construct($database)
    {
        $this->database = $database;
    }

    /**
     * Write the seed file
     */
    public function write()
    {
        // Check if convert method was called before
        // If not, call it on default DB
        if (!$this->customDb) {
            $this->convert();
        }

        $seed = $this->compile();

        $filename = Str::camel($this->database) . "TableSeeder";

        file_put_contents(Config::get('db-exporter::export_path.seeds')."{$filename}.php", $seed);
    }

    protected function isIgnoredTable($table_name)
    {
        if (in_array($table_name, self::$ignore)) {
            return true;
        }
        foreach (self::$ignore as $ignore) {
            if ('/' === $ignore[0] && preg_match($ignore, $table_name)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Convert the database tables to something usefull
     * @param null $database
     * @return $this
     */
    public function convert($database = null)
    {
        if (!is_null($database)) {
            $this->database = $database;
            $this->customDb = true;
        }

        // Get the tables for the database
        $tables = $this->getTables();

        $stub = "";
        // Loop over the tables
        foreach ($tables as $key => $value) {
            $tableName = $value['table_name'];
            if ($prefix = self::getDB()->getConfig('prefix')) {
                $tableName = $prefix . str_replace($prefix, '', $tableName);
            }
//            dd($tableName);
            // Do not export the ignored tables
            if ($this->isIgnoredTable($tableName)) continue;

            $tableData = $this->getTableData($tableName);
            $insertStub = "";

            foreach ($tableData as $obj) {
                $insertStub .= "
            array(\n";
                foreach ($obj as $prop => $value) {
                    $insertStub .= $this->insertPropertyAndValue($prop, $value);
                }

                if (count($tableData) > 1) {
                    $insertStub .= "            ),\n";
                } else {
                    $insertStub .= "            )\n";
                }
            }

            if ($this->hasTableData($tableData)) {
                $stub .= "
        DB::table('" . $tableName . "')->insert(array(
            {$insertStub}
        ));";
            }
        }

        $this->seedingStub = $stub;

        return $this;
    }

    /**
     * Compile the current seedingStub with the seed template
     * @return mixed
     */
    protected function compile()
    {
        // Grab the template
        $template = File::get(__DIR__ . '/templates/seed.txt');

        // Replace the classname
        $template = str_replace('{{className}}', \Str::camel($this->database) . "TableSeeder", $template);
        $template = str_replace('{{run}}', $this->seedingStub, $template);

        return $template;
    }

    private function insertPropertyAndValue($prop, $value)
    {
        $prop = addslashes($prop);
        $value = addslashes($value);
        if (is_numeric($value)) {
            return "                '{$prop}' => {$value},\n";
        } elseif($value == '') {
            return "                '{$prop}' => NULL,\n";
        } else {
            return "                '{$prop}' => '{$value}',\n";
        }
    }

    /**
     * @param $tableData
     * @return bool
     */
    public function hasTableData($tableData)
    {
        return count($tableData) >= 1;
    }
}
