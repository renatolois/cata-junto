<?php
declare(strict_types=1);

namespace App\Routers;

use Core\Base\BaseRouter;
use App\Controllers\PersonController;

class PersonRouter extends BaseRouter {
  public function __construct(PersonController $controller) {
    parent::__construct($controller);
    
    $this->set_route('GET', '/person', 'PersonController@list');
    $this->set_route('POST', '/person', 'PersonController@create');
    $this->set_route('PUT', '/person/{id}', 'PersonController@update');
    $this->set_route('DELETE', '/person/{id}', 'PersonController@destroy');
    $this->set_route('PUT', '/person/{id}/activate', 'PersonController@activate');
    $this->set_route('PUT', '/person/{id}/deactivate', 'PersonController@deactivate');
    $this->set_route('POST', '/person/{id}/add-points', 'PersonController@add_points');
    $this->set_route('POST', '/person/{id}/deduct-points', 'PersonController@deduct_points');
    $this->set_route('POST', '/person/{id}/verify-password', 'PersonController@verify_password');
  }
}