<?php
declare(strict_types=1);

namespace App\Routers;

use Core\Base\BaseRouter;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\AuthorizationMiddleware;
use App\Middlewares\PersonOnlyMiddleware;

class PersonRouter extends BaseRouter {
  public function register_routes(
    AuthMiddleware $auth,
    AuthorizationMiddleware $admin_only,
    AuthorizationMiddleware $member_only,
    PersonOnlyMiddleware $person_only
  ): void {

    // PERSON
    $this->set_route('POST', '/person', 'PersonController@create');

    $this->set_route('GET', '/person/me', 'PersonController@list', [
      $auth,
      $person_only,
    ], inject_auth_id: true);

    $this->set_route('PUT', '/person/me', 'PersonController@update', [
      $auth,
      $person_only,
    ], inject_auth_id: true);

    $this->set_route('POST', '/person/me/verify-password', 'PersonController@verify_password', [
      $auth,
      $person_only,
    ], inject_auth_id: true);

    $this->set_route('GET', '/person', 'PersonController@list', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('GET', '/person/{id}', 'PersonController@list', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('PUT', '/person/{id}', 'PersonController@update', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('PUT', '/person/{id}/activate', 'PersonController@activate', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('PUT', '/person/{id}/deactivate', 'PersonController@deactivate', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('POST', '/person/{id}/verify-password', 'PersonController@verify_password', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    // CONTRACT
    $this->set_route('POST', '/contract', 'ContractController@create', [
      $auth,
      $person_only,
    ], inject_auth_id: true);

    $this->set_route('GET', '/contract/me', 'ContractController@list', [
      $auth,
      $person_only,
    ], inject_auth_id: true);

    $this->set_route('GET', '/contract/responded', 'ContractController@list_responded', [
      $auth,
      $person_only,
      $admin_only,
    ], inject_auth_id: true);

    $this->set_route('GET', '/contract', 'ContractController@list', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('GET', '/contract/{id}', 'ContractController@list', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('PUT', '/contract/{id}/approve', 'ContractController@approve', [
      $auth,
      $person_only,
      $admin_only,
    ], inject_auth_id: true);

    $this->set_route('PUT', '/contract/{id}/reject', 'ContractController@reject', [
      $auth,
      $person_only,
      $admin_only,
    ], inject_auth_id: true);

    $this->set_route('PUT', '/contract/{id}/cancel', 'ContractController@cancel', [
      $auth,
      $person_only,
    ], inject_auth_id: true);

    $this->set_route('PUT', '/contract/{id}/terminate', 'ContractController@terminate', [
      $auth,
      $person_only,
      $admin_only,
    ], inject_auth_id: true);

    // IN PERSON COLLECTION
    $this->set_route('POST', '/in-person-collection', 'InPersonCollectionController@create', [
      $auth,
      $person_only,
      $member_only,
    ], inject_auth_id: true);

    $this->set_route('GET', '/in-person-collection/me', 'InPersonCollectionController@list_my', [
      $auth,
      $person_only,
    ], inject_auth_id: true);

    $this->set_route('GET', '/in-person-collection', 'InPersonCollectionController@list', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('GET', '/in-person-collection/{id}', 'InPersonCollectionController@list', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    // RESIDENTIAL COLLECTION
    $this->set_route('GET', '/available-residential-collections', 'ResidentialCollectionController@list_pending', [
      $auth,
      $person_only,
      $member_only,
    ]);

    $this->set_route('GET', '/residential-collection', 'ResidentialCollectionController@list', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('GET', '/residential-collection/{id}', 'ResidentialCollectionController@list', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('PUT', '/residential-collection/{id}/reject', 'ResidentialCollectionController@reject', [
      $auth,
      $person_only,
      $admin_only,
    ], inject_auth_id: true);

    $this->set_route('PUT', '/residential-collection/{collection_id}/complete', 'ResidentialCollectionController@complete', [
      $auth,
      $person_only,
      $member_only,
    ], inject_auth_id: true);

    $this->set_route('GET', '/residential-collections/me', 'ResidentialCollectionController@list_my', [
      $auth,
      $person_only,
    ], inject_auth_id: true);

    // MATERIAL TYPE
    $this->set_route('POST', '/material-type', 'MaterialTypeController@create', [
      $auth,
      $person_only,
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
      $person_only,
      $admin_only,
    ]);

    $this->set_route('PUT', '/material-type/{id}/activate-weight', 'MaterialTypeController@activate_weight', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('PUT', '/material-type/{id}/deactivate-weight', 'MaterialTypeController@deactivate_weight', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('PUT', '/material-type/{id}/activate-unit', 'MaterialTypeController@activate_unit', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('PUT', '/material-type/{id}/deactivate-unit', 'MaterialTypeController@deactivate_unit', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    // PRIZE TYPE
    $this->set_route('POST', '/prize-type', 'PrizeTypeController@create', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('GET', '/prize-type', 'PrizeTypeController@list', [
      $auth,
    ]);

    $this->set_route('GET', '/prize-type/{id}', 'PrizeTypeController@list', [
      $auth,
    ]);

    $this->set_route('PUT', '/prize-type/{id}', 'PrizeTypeController@update', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('PUT', '/prize-type/{id}/activate', 'PrizeTypeController@activate', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('PUT', '/prize-type/{id}/deactivate', 'PrizeTypeController@deactivate', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    // PRIZE CLAIM
    $this->set_route('GET', '/prize-claim', 'PrizeClaimController@list', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('GET', '/prize-claim/{id}', 'PrizeClaimController@list', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('PUT', '/prize-claim/{id}/complete', 'PrizeClaimController@complete', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('PUT', '/prize-claim/{id}/reject', 'PrizeClaimController@reject', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    // ROLE
    $this->set_route('GET', '/role', 'RoleController@list', [
      $auth,
      $person_only,
    ]);

    $this->set_route('POST', '/role', 'RoleController@create', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('PUT', '/role/{id}', 'RoleController@update', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('PUT', '/role/{id}/activate', 'RoleController@activate', [
      $auth,
      $person_only,
      $admin_only,
    ]);

    $this->set_route('PUT', '/role/{id}/deactivate', 'RoleController@deactivate', [
      $auth,
      $person_only,
      $admin_only,
    ]);
  }
}