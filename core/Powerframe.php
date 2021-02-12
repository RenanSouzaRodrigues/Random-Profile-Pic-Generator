<?php 
/**
 * @author Renan Souza Rodrigues
 * @link http://www.powerpush.com.br/powerframe/core/Powerframe
 */
class Powerframe 
{
    /**
     * Define the application environments. It can me defined by the developer at any time and use any name. 
     * Just be sure to remember the created names on database configuration.
     */
    private static $environment = "no-environment-selected";

    /**
     * Define a default environment if the framework could not find one using dynamic enviornment validation 
     */
    private static $defautlEnvirontment = "local";

    /**
     * The framework use this parameter to decide if it will use the dynamic environment validation
     */
    private static $dynamicEnvironment = false;

    /**
     * Define the authentifications to validate if the application uses authentification methods as security measures.
     */
    private static $authentifications = [];

    /**
     * Define the hash key for encryption and decryption
     */
    private static $encryptHashKey = '667fa516e746db73720914eefdf2d9bd63fcd66dd980d86d42abb3d4';

    /**
     * Method to define all api access controlls by headers (CORS)
     * @param string $origins
     * @param string $methods
     * @param string $headers
     * @return void
     */
    public static function defineAllAccessControls($origins = '*', $methods = '*', $headers = '*') 
    {
        header("Access-Control-Allow-Origin: " . $origins);

        header("Access-Control-Allow-Methods: " . $methods);

        header("Access-Control-Allow-Headers: " . $headers);
    }

    /**
     * Set method for the environment paramenter.
     * @param string $environment required
     * @return void
     */
    public static function setEnvironment(string $environment) 
    {
        self::$environment = !$environment ? self::$defautlEnvirontment : $environment ;
    }

    /**
     * Get method for the environment parameter.
     * @return string environment 
     */ 
    public static function getEnvironment(): string
    {
        return self::$environment; 
    }

    /**
     * Method to define if the framework will use dynamic environment validation.
     * @param bool $boolean required
     * @return void
     */
    public static function setUseDynamicEnvironment(bool $boolean) 
    {
        self::$dynamicEnvironment = $boolean;
    }

    /**
     * Return true if the framework is set to use the dynamic environment validation or false if it will not.
     * @return bool
     */
    public static function usingDynamicEnvironment(): bool
    {
        return self::$dynamicEnvironment;
    }

    /**
     * Method to set custom or out of the class authorizations
     * $param array $authentification
     * @return void
     */
    public static function setAuthorizations(array $authorization)
    {
        foreach($authorization as $key => $value) {
            self::$authentifications[$key] = $value;
        }
    }

    public static function getAuthorizations()
    {
        return self::$authentifications;
    }

    /**
     * Return all the headers sended by the front-end request.
     * @return array $headers
     */
    public static function getRequestHeaders(): array
    {
        $headers = array();

        foreach($_SERVER as $name => $value) {
            if($name != 'HTTP_MOD_REWRITE' && (substr($name, 0, 5) == 'HTTP_' || $name == 'CONTENT_LENGTH' || $name == 'CONTENT_TYPE')) {
                $name = str_replace(' ', '-', strtolower(str_replace('_', ' ', str_replace('HTTP_', '', $name))));

                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /**
     * Return true if the sended headers are valid and false if not.
     * @return bool $validation
     */
    public static function validateAuthentifications()
    {
        $requestHeaders = self::getRequestHeaders();

        $validAccess = false;

        foreach($requestHeaders as $arrayKey => $arrayValue) {
            if(array_key_exists($arrayKey, self::$authentifications)) {
                $validAccess = ($arrayValue != self::$authentifications[$arrayKey]) ? false : true ;
            } else {
                $validAccess = false;
            }
        }

        return $validAccess;
    }

    /**
     * Return the front end request body as an object or as an array
     * @param bool $asArray
     * @return mixed $body
     */
    public static function getRequestBody(bool $asArray = false)
    {
        $body = trim(file_get_contents("php://input"));

        $body = json_decode($body, $asArray);

        return $body;
    }

    /**
     * Send a json response to the front end application. Also, it will send a http request status.
     * By default, it will send status 200
     * The framework can handle the following status: 200, 400, 401, 403, 404, 405, 500 e 503
     * @param array $response required
     * @param int $status
     * @return void
     */
    public static function sendRestResponse(array $response, int $status = 200)
    {
        switch ($status) {
            case 200: $text = 'OK'; break;
            case 400: $text = 'Bad Request'; break;
            case 401: $text = 'Unauthorized'; break;
            case 403: $text = 'Forbidden'; break;
            case 404: $text = 'Not Found'; break;
            case 405: $text = 'Method Not Allowed'; break;
            case 500: $text = 'Internal Server Error'; break;
            case 503: $text = 'Service Unavailable'; break;
            default: exit('Unknown http status "' . htmlentities($status) . '"'); break;
        }

        $protocol = $_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
        
        header($protocol . ' ' . $status . ' ' . $text);

        header("Content-type: application/json");

        echo(json_encode($response));

        exit;
    }

    /**
     * Method to include one or more controllers into the application
     * @param mixed controllerName required
     * @param string $module required
     * @param string sufix
     * @return object controller
     */
    public static function loadControllers($controllerName, string $module, string $sufix = '')
    {
        $controllerDir = "modules/".$module."/controllers";
        
        if(is_array($controllerName)) {
            foreach($controllerName as $controller) {
                $controllerFile = $controllerDir . '/' . $controller . $sufix . ".php";

                if(file_exists($controllerFile)) {
                    require_once($controllerFile);
                }
            }
        } else {
            $controllerFile = $controllerDir . '/' . $controllerName . $sufix . ".php";

            if(file_exists($controllerFile)) {
                require_once($controllerFile);
            }
        }
    }

    /**
     * Method to include one or more models into the application
     * @param mixed $modelName required
     * @param string $module required
     * @param string $sufix
     * @return object $model
     */
    public static function loadModels($modelName, string $module, string $sufix = '') 
    {
        $modelDir = "modules/" . $module . "/models";

        if(is_array($modelName)) {
            foreach($modelName as $model) {
                $modelFile = $modelDir . '/' . $model . $sufix . ".php";

                if(file_exists($modelFile)) {
                    require_once($modelFile);
                }
            }
        } else {
            $modelFile = $modelDir . '/' . $modelName . $sufix . ".php";
            if(file_exists($modelFile)) {
                require_once($modelFile);
            }
        }
    }

    /**
     * Method to Include one or more plugins into the application
     * @param mixed $pluginName required
     * @return object $plugin
     */
    public static function loadPlugins($pluginName = null)
    {
        $pluginDir = "plugins";

        if($pluginName !== null) {
            if(is_array($pluginName)) {
                foreach($pluginName as $plugin) {
                    $pluginFile = $pluginDir . '/' . $plugin . '/' . $plugin . '.php'; 

                    if(file_exists($pluginFile)) {
                        require_once($pluginFile);
                    }
                }
            } else {
                $pluginFile = $pluginDir . '/' . $pluginName . '/' . $pluginName . '.php';
                require_once($pluginFile);
            }
        } else {
            $dirRunner = new \DirectoryIterator($pluginDir);

            foreach($dirRunner as $dir) {
                if($dir->getFilename() !== '.' && $dir->getFilename() !== '..') {
                    $pluginFile = $pluginDir . '/' . $dir->getFilename() . '/' . $dir->getFilename() . '.php';
                    if(file_exists($pluginFile)) {
                        require_once($pluginFile);
                    }
                }
            }
        }
    }

    /**
     * This method encrypts a forneced string.
     * @param string $string required
     * @return string $encryptedString
     */
    public static function encrypt(string $string)
    {
        $encryption_key = base64_decode(self::$encryptHashKey);

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        $encrypted = openssl_encrypt($string, 'aes-256-cbc', $encryption_key, 0, $iv);

        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * This method will decrypt a encrypted string 
     * @param string $string required
     * @return string $decryptedString
     */
    public static function decrypt(string $string)
    {
        $encryption_key = base64_decode(self::$encryptHashKey);

        list($encrypted_data, $iv) = array_pad(explode('::', base64_decode($string), 2),2,null);

        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
    }

    /**
     * Method to validate the ip's inside the defined security group on the security-group json file.
     * @return bool validation
     */
    public function validateSecurityGroup() 
    {
        $securityGroupJson = file_get_contents("security-group.json");

        $securityGroup = json_decode($securityGroupJson, true);

        $headers = self::getRequestHeaders();

        if (!in_array($headers['ip'], $securityGroup)) {
            return false;
        }

        return true;
    }

    /**
     * Method to insert a new ip into the security group json file.
     * @param string $ip
     * @return bool $ipInsertionSuccess
     */
    public function setIpOnSecurityGroup(string $ip)
    {
        $securityGroupJson = file_get_contents("security-group.json");

        $securityGroup = json_decode($securityGroupJson, true);

        array_push($securityGroup['ips'], $ip);

        $securityGroupJson = json_encode($securityGroup);

        return file_put_contents("security-group.json", $securityGroupJson);
    }

}

?>