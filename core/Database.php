<?php 
/**
 * @author: Renan Souza Rodrigues
 * @link: http://www.powerpush.com.br/powerframe/core/database
 */
class Database {
    /**
     * This parameter will be a copy of Powerframe::getEnvironment() return. It's existence is only
     * to makes the code more readable.
     */
    private $environment;

    /**
     * This parameters defines all the connections available on the dynamic environments.
     * Be sure to always configure a connection to all your usable environments.
     */
    private $environmentDynamicData = [
        'local' => [
            'database'  => 'mysql',
            'host'      => 'localhost',
            'schema'    => 'my_db',
            'user'      => 'root',
            'password'  => ''
        ]
    ];

    /**
     * $this parameter defines the database connection string. The framework use's PDO as it connection method.
     */
    private $connectionString;

    /**
     * Define an active connection.
     */
    protected $activeConnection;

    /**
     * This parameter will be used to save the raw sql query
     */
    protected $sql;

    /**
     * This parameter will be use to define a useable sql command.
     */
    protected $command;

    /**
     * Define the reported error.
     */
    protected $error;
    

    /**
     * This method must be called to initiate this class if it is extended. Be sure to use at the correct scenario.
     * @return mixed
     */
    protected function init()
    {
        // Environment validation
        if($this->validateEnvironment()) {

            //  Set a brand new connection string using the select valid environment.
            $this->connectionString = 
                $this->environmentDynamicData[$this->environment]['database'] . 
                ":dbname=" . $this->environmentDynamicData[$this->environment]['schema'] . 
                ";host=" . $this->environmentDynamicData[$this->environment]['host'];

            // It will return a success if the connection was succefulled created.
            return $this->setConnection();
        
        } else {
            // In this case, the class was not able to find a valid environment.
            $this->setError('Unknown environment');
            
            return false;
        }
    }

    /**
     * This method can be use to set a new connection, based on your environments
     * @return bool
     */
    public function setConnection() 
    {   
        if(!isset($this->connectionString)) {
            if(!$this->init()) return false;
        }
        
        // It will try to create a new PDO connection
        try {
            $this->activeConnection = new PDO (
                $this->connectionString, 
                $this->environmentDynamicData[$this->environment]['user'],
                $this->environmentDynamicData[$this->environment]['password'],
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
            );

            $this->activeConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // If everything goes righ, it will return a success
            return true;

        } catch (Exception $e) {
            // If not possible to create the connection, it will throw a error. To get this error use getError() method.
            $this->setError($e->getMessage());
            
            // It will return a failure.
            return false;
        }
    }

    /**
     * This method will return a valid PDO connection. If there is no active connection, it will create one.
     * @return object activeConnection
     */
    public function getConnection()
    {
        return $this->activeConnection;
    }

    /**
     * Use this method to create a new sql command to be executed on the database.
     * @param string $sqlQuery
     * @return void
     */
    public function createCommand($sqlQuery)
    {   
        $this->sql = $sqlQuery;
        
        if(!$this->activeConnection) {
            if(!$this->init()) { return false; }
        }

        $this->command = $this->activeConnection->prepare($sqlQuery);

        if(!$this->command) 
            $this->setError($this->activeConnection->errorInfo());
    }

    /**
     * Bind the necessary parameters to the query
     * @param array $parameters
     * @return null
     */
    public function bindParameters($parameters)
    {
        foreach ($parameters as $key => $value) {
            $this->command->bindValue($key, $value);
        }
    }

    /**
     * Execute the sql command created
     * @return mixed $result
     */
    public function query()
    {
        $queryType = explode(" ", trim($this->sql));
        if($queryType[0] == "SELECT" || $queryType[0] == "select") {
            $this->command->execute();
            
            $result = $this->command->fetchAll(PDO::FETCH_ASSOC);

            if(!$result) {
                $this->setError($this->command->errorInfo());
                return false;

            } else {
                return $result;
            }

        } else {
            if(!$this->command->execute()) {
                $this->setError($this->command->errorInfo());
                return false;

            } else {
                return true;
            }
        }
    }

    /**
     * Set the returned error into the error message property
     */
    public function setError($errorMessage) 
    {
        $this->error = [
            'status' => 'error',
            'type' => 'database',
            'error' => $errorMessage,        
        ];
    }

    /**
     * Return the error message property
     */
    public function getError($type = 'object') 
    {
        switch ($type) {
            case 'json':
                return json_encode($this->error);
                break;
            case 'array':
                return $this->error;
                break;
            default:
                return (object) $this->error;
                break;
        }
    }

    /**
     * Validate the environment to see if there is a valid connection data to this environment
     */
    private function validateEnvironment()
    {
        if(!isset($this->environment)) $this->environment = Powerframe::getEnvironment();

        return (array_key_exists($this->environment, $this->environmentDynamicData)) ? true : false ;
    }

}

?>