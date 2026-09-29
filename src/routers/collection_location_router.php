<?php
declare(strict_types=1);

namespace App\Routers;

use Core\Base\BaseRouter;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CollectionLocationOnlyMiddleware;

class CollectionLocationRouter extends BaseRouter {

  public function register_routes(
    AuthMiddleware $auth,
    CollectionLocationOnlyMiddleware $location_only
  ): void {

    // COLLECTION LOCATION
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

    // RESIDENTIAL COLLECTION
    $this->set_route('POST', '/residential-collection', 'ResidentialCollectionController@create', [
      $auth,
      $location_only,
    ]);

    $this->set_route('GET', '/residential-collection/me', 'ResidentialCollectionController@list', [
      $auth,
      $location_only,
    ], inject_auth_id: true);

    $this->set_route('PUT', '/residential-collection/{id}', 'ResidentialCollectionController@update', [
      $auth,
      $location_only,
    ]);

    $this->set_route('PUT', '/residential-collection/{id}/cancel', 'ResidentialCollectionController@cancel', [
      $auth,
      $location_only,
    ]);

    // PRIZE CLAIM
    $this->set_route('POST', '/prize-claim', 'PrizeClaimController@create', [
      $auth,
      $location_only,
    ], inject_auth_id: true);

    $this->set_route('GET', '/prize-claim/me', 'PrizeClaimController@list', [
      $auth,
      $location_only,
    ], inject_auth_id: true);

    // PRIZE TYPE
    $this->set_route('GET', '/prize-type/active', 'PrizeTypeController@list', [
      $auth,
      $location_only,
    ]);
  }
}