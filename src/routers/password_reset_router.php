<?php
declare(strict_types=1);

namespace App\Routers;

use Core\Base\BaseRouter;

class PasswordResetRouter extends BaseRouter {
  public function register_routes(): void {
    $this->set_route('POST', '/password-reset/person/request',              'PasswordResetHandler@request_person');
    $this->set_route('POST', '/password-reset/collection-location/request', 'PasswordResetHandler@request_location');
    $this->set_route('POST', '/password-reset/person',                      'PasswordResetHandler@reset_person');
    $this->set_route('POST', '/password-reset/collection-location',         'PasswordResetHandler@reset_location');
  }
}