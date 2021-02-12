<?php

require_once("Database.php");

abstract class FrameRecord extends Database {
    /**
     * Defines ActiveRecorde properties
     */
    protected $content;

    /**
     * Define the extended model table name. If not defined, it will use the model class name.
     */
    protected $table = null;

    /**
     * Model table primary key
     */
    protected $idField = null;

    /**
     * Define if the model will log the action timestamp.
     */
    protected $logTimestamp;

    /**
     * Define all the table fields
     */
    protected $fields = [];

    /**
     * Define all the model validation rules
     */
    protected $rules = [];

    /**
     * Parameter used to create de model class into a static class.
     */
    protected static $_models = [];


    /**
     * Magic method used to construct a new row fot the model.
     */
    public function __construct()
    {
        //Define the log timestamp as false by default
        if (!is_bool($this->logTimestamp)) {
            $this->logTimestamp = false;
        }

        // if the table name is not defined on the model class, it will use the model class name on lower case.
        if ($this->table == null) {
            $this->table = strtolower(get_class($this));
        }

        // Define the id field name as 'id' if not defined at the model class.
        if ($this->idField == null) {
            $this->idField = 'id';
        }

        // Initiate the database connection.
        $this->init();
    }

    /**
     * Magic method to set any dynamic parameter
     * @param mixed $parameter
     * @param mixed $value
     * @return void
     */
    public function __set($parameter, $value)
    {
        $this->content[$parameter] = $value;
    }

    /**
     * Magic method to get any dynamic parameter.
     * @param string $parameter
     * @return mixed $parameter
     */
    public function __get($parameter)
    {
        return $this->content[$parameter];
    }

    /**
     * Magic method to validate any dynamic parameter
     * @param string $parameter
     * @return bool
     */
    public function __isset($parameter)
    {
        return isset($this->content[$parameter]);
    }

    /**
     * Magic method to unset any dynamic parameter
     * @param string $parameter required
     */
    public function __unset($parameter)
    {
        if (isset($parameter)) {
            unset($this->content[$parameter]);
            return true;
        }
        return false;
    }

    private function __clone()
    {
        if (isset($this->content[$this->idField])) {
            unset($this->content[$this->idField]);
        }
    }

    /**
     * Define conversion methods
     */
    public function toArray()
    {
        return $this->content;
    }

    public function fromArray(array $array)
    {
        $this->content = $array;
    }

    public function __toString()
    {
        return json_encode($this->content);
    }

    public function fromJson(string $json)
    {
        $this->content = json_decode($json);
    }

    private function format($value)
    {
        if (is_string($value) && !empty($value)) {
            return "'" . addslashes($value) . "'";
        } else if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        } else if ($value !== '') {
            return $value;
        } else {
            return "NULL";
        }
    }

    private function convertContent()
    {
        $newContent = array();
        foreach ($this->content as $key => $value) {
            if (is_scalar($value)) {
                $newContent[$key] = $this->format($value);
            }
        }
        return $newContent;
    }


    /**
     * Method to find on database a row considering the table primary key
     * @param mixed primaryKeyValue
     * @param
     */
    public function findByPk($primaryKeyValue)
    {
        $class = get_called_class();
        $idField = (new $class())->idField;
        $table = (new $class())->table;
    
        $sql = 'SELECT * FROM ' . (is_null($table) ? strtolower($class) : $table);
        $sql .= ' WHERE ' . (is_null($idField) ? 'id' : $idField);
        $sql .= " = {$primaryKeyValue} ;";

        $this->createCommand($sql);

        return $this->query();
    }

    /**
     * Find a entity using a where statement.
     */
    public function find(string $where, array $params)
    {
        $class = get_called_class();
        $table = (new $class())->table;

        $sql = 'SELECT * FROM ' . (is_null($table) ? strtolower($class) : $table);
        $sql .= ' WHERE ' . $where;

        $this->createCommand($sql);
        $this->bindParameters($params);

        return $this->query();
    }

    /**
     * Create or upadate the entity.
     */
    public function save()
    {
        $newContent = $this->convertContent();
    
        if (isset($this->content[$this->idField])) {
            $sets = array();
            foreach ($newContent as $key => $value) {
                if ($key === $this->idField || $key == 'created_at' || $key == 'updated_at')
                    continue;
                $sets[] = "{$key} = {$value}";
            }

            if ($this->logTimestamp === TRUE) {
    
                $sets[] = "updated_at = '" . date('Y-m-d H:i:s') . "'";
            }

            $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE {$this->idField} = {$this->content[$this->idField]};";

        } else {
            if ($this->logTimestamp === TRUE) {
                $newContent['created_at'] = "'" . date('Y-m-d H:i:s') . "'";
                $newContent['updated_at'] = "'" . date('Y-m-d H:i:s') . "'";
            }

            $sql = "INSERT INTO {$this->table} (" . implode(', ', array_keys($newContent)) . ') VALUES (' . implode(',', array_values($newContent)) . ');';
        }

        $this->createCommand($sql);

        return $this->query();
    }

    public function delete()
    {
        if (isset($this->content[$this->idField])) {
    
            $sql = "DELETE FROM {$this->table} WHERE {$this->idField} = {$this->content[$this->idField]};";
    
            $this->createCommand($sql);

            return $this->query();
        }
    }

    public function all(string $filter = '', int $limit = 0, int $offset = 0)
    {
        $class = get_called_class();
        $table = (new $class())->table;
        $sql = 'SELECT * FROM ' . (is_null($table) ? strtolower($class) : $table);
        $sql .= ($filter !== '') ? " WHERE {$filter}" : "";
        $sql .= ($limit > 0) ? " LIMIT {$limit}" : "";
        $sql .= ($offset > 0) ? " OFFSET {$offset}" : "";
        $sql .= ';';
    
        $this->createCommand($sql);

        return $this->query();
    }

    public function findFirst(string $filter = '')
    {
        return $this->all($filter, 1);
    }

    
    /**
     * This method transform the class into a static class.
     */
    public static function model($className=__CLASS__)
    {
        if(isset(self::$_models[$className]))
            return self::$_models[$className];
        else
        {
            $model=self::$_models[$className]=new $className(null);
            return $model;
        }
    }
}

?>