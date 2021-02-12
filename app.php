<?php 
/**
 * Require the core classes.
 */
require_once("core/Autoloader.php");

/**
 * Define time zone
 */
date_default_timezone_set('America/Sao_Paulo');

/**
 * Define all your access controls
 */
Powerframe::defineAllAccessControls('*', '*', '*');

/**
 * Recieve a frendly url and transform it into array
 */
$receivedURL = explode("/", $_SERVER['REQUEST_URI']);

/**
 * Get the number of arguments
 */
$numberOfArguments = count($receivedURL);

/**
 * Validate the number of arguments to assure the framework pattern
 */
if($numberOfArguments < 4 || $receivedURL[$numberOfArguments - 1] == '') {
    $response = [
        'status' => 'error',
        'error' => true,
        'message' => 'Bad request',
    ];

    Powerframe::sendRestResponse($response, 400);
}

/**
 * To use dynamic environment validation, set true to Powerframe::setUseDynamicEnvironment()
 */
Powerframe::setUseDynamicEnvironment(true);

if(Powerframe::usingDynamicEnvironment()) {
    $baseUrl = explode('.', $receivedURL[2]);

    $dynamicEnvironment = isset($baseUrl[1]) ? $baseUrl[1] : false ;

    Powerframe::setEnvironment($dynamicEnvironment);
}

/**
 * Create a variable to get the url
 */
$url = "";

/**
 * Treats the receved url
 */
for ($index = 4; $index < $numberOfArguments; $index++) { 
    $url = $url . $receivedURL[$index] . "/";
}

$url = substr($url,0,-1);

/**
 * Check for status endpoint
 */
if($url == 'status') {
    $response = [
        'status' => 'success',
        'error' => false,
        'message' => 'Api is working fine',
    ];

    Powerframe::sendRestResponse($response, 200);
}

/**
 * Check for environment endpoint
 */
if($url == 'env') {
    $response = [
        'status' => "success",
        'error' => false,
        'usingDynamicEnvironment' => Powerframe::usingDynamicEnvironment(),
        'environment' => Powerframe::getEnvironment()
    ];

    Powerframe::sendRestResponse($response, 200);
}

/**
 * Get the url module
 */
$module = $receivedURL[3];

/**
 * Create a Route instance and validate module
 */
$routes = Router::getRoutes();

if(!isset($routes[$module])) {
    $response = [
        "status" => 'error',
        "error" => true,
        "message" => 'Module ' . $module . ' not found or not exist',
    ];

    Powerframe::sendRestResponse($response, 400);
}

/**
 * Check if it is a valid request
 */
if(array_key_exists($url, $routes[$module])) {
    /**
     * Get all information about the request
     */
    $request = $routes[$module][$url];

    /**
     * Get the sended method 
     */
    $method = $_SERVER['REQUEST_METHOD'];

    /**
     * Validate the method
     */
    if(!isset($request[$method])) {
        $response = [
            'status' => 'error',
            'error' => true,
            'message' => "Method ". $method ." not allowed",
        ];

        Powerframe::sendRestResponse($response, 200);
    }

    /**
     * Get the controller to be used
     */
    $controllerToBeLoaded = $request[$method]['controller'];

    /**
     * Get the action to be used
     */
    $actionToBePerformed = $request[$method]['action'];

    /**
     * Validate to discover if thats a body expected
     */
    $parseBody = isset($request[$method]['parseBody']) ? $request[$method]['parseBody'] : false; 

    /**
     * Validate the controller name
     */
    $controllerToBeLoadedFileName = 'modules/'. $module . '/controllers/' . $controllerToBeLoaded . ".php";

    /**
     * Validate controller existence
     */
    if(!file_exists($controllerToBeLoadedFileName)) {
        $response = [
            'status' => 'error',
            'error' => true,
            'message' => "Controller '" . $controllerToBeLoadedFileName . "' not found", 
        ];

        Powerframe::sendRestResponse($response, 500);
    }

    define("module", $module);

    /**
     * Import the controller
     */
    Powerframe::loadControllers($controllerToBeLoaded, $module);

    /**
     * Crate the controller instance
     */
    $controller = new $controllerToBeLoaded();

    /**
     * Get the request body
     */
    if($parseBody) {
        $body = Powerframe::getRequestBody();
        $response = $controller->$actionToBePerformed($body);
    } else {
        $response = $controller->$actionToBePerformed();
    }
    
    /**
     * Send the controller response as JSON
     */
    Powerframe::sendRestResponse($response);

} else {
    $response = [
        'status' => 'error',
        'error' => true,
        'message' => 'Bad Request'
    ];

    Powerframe::sendRestResponse($response, 400);
}

?>