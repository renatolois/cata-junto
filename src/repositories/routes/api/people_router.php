<?php 
declare(strict_types=1);

namespace Routes;

require_once __DIR__ . '/../core/base/router.php';

use Core\Router;

class PeopleRouter extends BaseRouter{
  public function __construct() {
    parent::__construct();
    
    $this->setRoute('GET', '/api/people', 'PeopleController@list');
    $this->setRoute('GET', '/api/people/{id}', 'PeopleController@get');
    $this->setRoute('POST', '/api/people', 'PeopleController@add');
    $this->setRoute('PUT', '/people/{id}', 'PeopleController@update');
    $this->setRoute('DELETE', '/people/{id}', 'PeopleController@delete');
  }
}
