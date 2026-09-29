<?php
declare(strict_types=1);

namespace App\Routers;

use Core\Base\BaseRouter;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\AuthorizationMiddleware;

class PersonRouter extends BaseRouter {

  public function register_routes(
    AuthMiddleware $auth,
    AuthorizationMiddleware $admin_only,
    AuthorizationMiddleware $member_only
  ): void {

    // PERSON
    $this->set_route('POST', '/person', 'PersonController@create');

    $this->set_route('GET', '/person/me', 'PersonController@list', [
      $auth,
    ], inject_auth_id: true);

    $this->set_route('PUT', '/person/me', 'PersonController@update', [
      $auth,
      $member_only,
    ], inject_auth_id: true);

    $this->set_route('POST', '/person/me/verify-password', 'PersonController@verify_password', [
      $auth,
    ], inject_auth_id: true);

    $this->set_route('GET', '/person', 'PersonController@list', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('GET', '/person/{id}', 'PersonController@list', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('PUT', '/person/{id}', 'PersonController@update', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('PUT', '/person/{id}/activate', 'PersonController@activate', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('PUT', '/person/{id}/deactivate', 'PersonController@deactivate', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('POST', '/person/{id}/add-points', 'PersonController@add_points', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('POST', '/person/{id}/deduct-points', 'PersonController@deduct_points', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('POST', '/person/{id}/verify-password', 'PersonController@verify_password', [
      $auth,
      $admin_only,
    ]);

    // CONTRACT
    $this->set_route('POST', '/contract', 'ContractController@create', [
      $auth,
    ]);

    $this->set_route('GET', '/contract/me', 'ContractController@list', [
      $auth,
      $member_only,
    ], inject_auth_id: true);

    $this->set_route('GET', '/contract/responded', 'ContractController@list_responded', [
      $auth,
      $admin_only,
    ], inject_auth_id: true);

    $this->set_route('GET', '/contract', 'ContractController@list', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('GET', '/contract/{id}', 'ContractController@list', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('PUT', '/contract/{id}/approve', 'ContractController@approve', [
      $auth,
      $admin_only,
    ], inject_auth_id: true);

    $this->set_route('PUT', '/contract/{id}/reject', 'ContractController@reject', [
      $auth,
      $admin_only,
    ], inject_auth_id: true);

    $this->set_route('PUT', '/contract/{id}/cancel', 'ContractController@cancel', [
      $auth,
      $member_only,
    ], inject_auth_id: true);

    $this->set_route('PUT', '/contract/{id}/terminate', 'ContractController@terminate', [
      $auth,
      $admin_only,
    ], inject_auth_id: true);

    // IN PERSON COLLECTION
    $this->set_route('POST', '/in-person-collection', 'InPersonCollectionController@create', [
      $auth,
      $member_only,
    ], inject_auth_id: true);

    $this->set_route('GET', '/in-person-collection/me', 'InPersonCollectionController@list', [
      $auth,
      $member_only,
    ], inject_auth_id: true);

    $this->set_route('GET', '/in-person-collection', 'InPersonCollectionController@list', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('GET', '/in-person-collection/{id}', 'InPersonCollectionController@list', [
      $auth,
      $admin_only,
    ]);

    // RESIDENTIAL COLLECTION
    $this->set_route('GET', '/residential-collection', 'ResidentialCollectionController@list', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('GET', '/residential-collection/{id}', 'ResidentialCollectionController@list', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('PUT', '/residential-collection/{id}/reject', 'ResidentialCollectionController@reject', [
      $auth,
      $admin_only,
    ], inject_auth_id: true);

    $this->set_route('PUT', '/residential-collection/{collection_id}/complete', 'ResidentialCollectionController@complete', [
      $auth,
      $member_only,
    ], inject_auth_id: true);

    // MATERIAL TYPE
    $this->set_route('POST', '/material-type', 'MaterialTypeController@create', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('GET', '/material-type', 'MaterialTypeController@list', [
      $auth,
    ]);

    $this->set_route('GET', '/material-type/{id}', 'MaterialTypeController@list', [
      $auth,
    ]);

    $this->set_route('PUT', '/material-type/{id}', 'MaterialTypeController@update', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('PUT', '/material-type/{id}/activate-weight', 'MaterialTypeController@activate_weight', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('PUT', '/material-type/{id}/deactivate-weight', 'MaterialTypeController@deactivate_weight', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('PUT', '/material-type/{id}/activate-unit', 'MaterialTypeController@activate_unit', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('PUT', '/material-type/{id}/deactivate-unit', 'MaterialTypeController@deactivate_unit', [
      $auth,
      $admin_only,
    ]);

    // PRIZE TYPE
    $this->set_route('POST', '/prize-type', 'PrizeTypeController@create', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('GET', '/prize-type', 'PrizeTypeController@list', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('GET', '/prize-type/{id}', 'PrizeTypeController@list', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('PUT', '/prize-type/{id}', 'PrizeTypeController@update', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('PUT', '/prize-type/{id}/activate', 'PrizeTypeController@activate', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('PUT', '/prize-type/{id}/deactivate', 'PrizeTypeController@deactivate', [
      $auth,
      $admin_only,
    ]);

    // PRIZE CLAIM
    $this->set_route('GET', '/prize-claim', 'PrizeClaimController@list', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('GET', '/prize-claim/{id}', 'PrizeClaimController@list', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('PUT', '/prize-claim/{id}/complete', 'PrizeClaimController@complete', [
      $auth,
      $admin_only,
    ]);

    $this->set_route('PUT', '/prize-claim/{id}/reject', 'PrizeClaimController@reject', [
      $auth,
      $admin_only,
    ]);

    // COLLECTION LOCATION
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
  }
}