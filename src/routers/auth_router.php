<?php
declare(strict_types=1);

namespace App\Routers;

use Core\Base\BaseRouter;

class AuthRouter extends BaseRouter {
  public function register_routes(): void {
    $this->set_route('POST', '/login/person',   'AuthController@login_person');
    $this->set_route('POST', '/login/location', 'AuthController@login_location');
    $this->set_route('POST', '/logout',         'AuthController@logout');
  }
}