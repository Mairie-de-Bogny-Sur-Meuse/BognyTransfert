<?php

class Router
{
    private array $routes = [];

    public function add(string $uri, string $controller, string $method = 'index'): void
    {
        $this->routes[$uri] = [
            'controller' => $controller,
            'method' => $method
        ];
    }

    public function dispatch(string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);

        if (isset($this->routes[$uri])) {
            $route = $this->routes[$uri];
            $controllerName = $route['controller'];
            $methodName = $route['method'];

            $controllerFile = __DIR__ . "/../app/controllers/{$controllerName}.php";
            if (!file_exists($controllerFile)) {
                
                http_response_code(404);
                require_once __DIR__ . '/../app/views/errors/404.php';
                exit;
            }

            require_once $controllerFile;
            $controller = new $controllerName();

            if (!method_exists($controller, $methodName)) {
                http_response_code(404);
                require_once __DIR__ . '/../app/views/errors/404.php';
                exit;
            }

            $controller->$methodName();
            return;
        }

        // Route non trouvée
        http_response_code(404);
        require_once __DIR__ . '/../app/views/errors/404.php';
    }
}
