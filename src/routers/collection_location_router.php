<?php
declare(strict_types=1);

namespace App\Routers;

use Core\Base\BaseRouter;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\AuthorizationMiddleware;
use App\Middlewares\CollectionLocationOnlyMiddleware;

class CollectionLocationRouter extends BaseRouter {

  public function register_routes(
    AuthMiddleware $auth,
    CollectionLocationOnlyMiddleware $location_only,
    AuthorizationMiddleware $admin_only
  ): void {
    $this->set_route('POST', '/collection-location', 'CollectionLocationController@create');

    $this->set_route('GET', '/collection-location/me', 'CollectionLocationController@list', [
      $auth,
      $location_only,
    ], inject_auth_id: true);

    $this->set_route('PUT', '/collection-location/me', 'CollectionLocationController@update', [
      $auth,
      $location_only,
    ], inject_auth_id: true);

    $this->set_route('POST', '/collection-location/me/verify-password', 'CollectionLocationController@verify_password', [
      $auth,
      $location_only,
    ], inject_auth_id: true);

    $this->set_route('GET', '/collection-location', 'CollectionLocationController@list', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('GET', '/collection-location/{id}', 'CollectionLocationController@list', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('PUT', '/collection-location/{id}', 'CollectionLocationController@update', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('PUT', '/collection-location/{id}/activate', 'CollectionLocationController@activate', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('PUT', '/collection-location/{id}/deactivate', 'CollectionLocationController@deactivate', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('POST', '/requested-residential-collections', 'ResidentialCollectionController@create', [
      $auth,
      $location_only,
    ], inject_auth_id: true);

    $this->set_route('GET', '/requested-residential-collections/me', 'ResidentialCollectionController@list', [
      $auth,
      $location_only,
    ], inject_auth_id: true);

    $this->set_route('PUT', '/requested-residential-collections/{id}', 'ResidentialCollectionController@update', [
      $auth,
      $location_only,
    ]);

    $this->set_route('PUT', '/requested-residential-collections/{id}/cancel', 'ResidentialCollectionController@cancel', [
      $auth,
      $location_only,
    ]);

    $this->set_route('POST', '/prize-claim', 'PrizeClaimController@create', [
      $auth,
      $location_only,
    ], inject_auth_id: true);

    $this->set_route('GET', '/prize-claim/me', 'PrizeClaimController@list', [
      $auth,
      $location_only,
    ], inject_auth_id: true);

    $this->set_route('GET', '/prize-type/active', 'PrizeTypeController@list', [
      $auth,
      $location_only,
    ]);
    
    $this->set_route('GET', '/material-type', 'MaterialTypeController@list', []);
  }
}