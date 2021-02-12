<?php 
/**
 * @author Renan Souza Rodrigues
 * @link http://www.powerpush.com.br/powerframe/core/Router
 */
class Router 
{
    private static $routes = [
        'v1' => [
            'img' => [
                'GET' => ['controller' => 'ImageController', 'action' => 'getRandomImage', 'parseBody' => false],
            ]
        ]
    ];

    /**
     * Static getter for the application to retrieve all the routes
     */
    public static function getRoutes() 
    {
        return self::$routes;
    }
}

?>