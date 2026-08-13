<?php 
declare(strict_types=1);
namespace Core\Base;

abstract class BaseRouter {
  protected $routes = [];

  public function set_route(string $method, string $route, string $actions): void {
    $pattern = preg_replace('/\{([a-z]+)\}/', '(?P<$1>[^/]+)', $route);
    $pattern = '#^' . $pattern . '$#';
    
    $this->routes[$method][$route] = [
      'action' => $actions,
      'regex' => $pattern
    ];
  }

  public function call(string $action): void {
    [$controller, $method] = explode('@', $action);

    $controllerClassFile = __DIR__ . "/../../core/$controller.php";
    
    if (!file_exists($controllerClassFile)) {
      http_response_code(404);
      echo "Controller not found";
      return;
    }

    require_once $controllerClassFile;

    $controllerClass = "Core\\$controller";

    if (!class_exists($controllerClass)) {
      http_response_code(404);
      echo "Not found";
      return;
    }

    $instance = new $controllerClass();
    
    if (method_exists($instance, $method)) {
      $instance->$method();
    } else {
      http_response_code(404);
      echo "Not found";
      return;
    }
  }

  public function dispatch(string $method, string $uri): void {
    $uri_route = parse_url($uri, PHP_URL_PATH);

    if (!isset($this->routes[$method])) {
      http_response_code(405);
      echo "Method not allowed";
      return;
    }

    if (isset($this->routes[$method][$uri_route])) {
      $this->call($this->routes[$method][$uri_route]['action']);
      return;
    }

    http_response_code(404);
    echo "Not found";
    return;
  }
}