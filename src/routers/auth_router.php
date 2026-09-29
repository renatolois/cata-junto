<?php
declare(strict_types=1);

namespace App\Routers;

use Core\Base\BaseRouter;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\AuthorizationMiddleware;
use App\Middlewares\CollectionLocationOnlyMiddleware;

class AuthRouter extends BaseRouter {
  public function register_routes(): void {
    $this->set_route('POST', '/login',  'AuthController@login');
    $this->set_route('POST', '/logout', 'AuthController@logout');
  }
}