<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Base\BaseController;
use App\Services\RoleService;

class RoleController extends BaseController {
  public function __construct(RoleService $service) {
    parent::__construct($service);
  }

  /*
  GET /role
  GET /role?active=1
  GET /role?name=<NAME>
  GET /role?id=<ID>
  */
  public function list(): void {
    $active = $this->get_query('active');
    $name = $this->get_query('name');
    $id = $this->get_query('id');

    $filters = array_filter([$active, $name, $id], fn($v) => $v !== null);
    if (count($filters) > 1) {
      $this->error_response('Use only 1 filter by time: active, name or id', 400);
      return;
    }

    if ($id !== null) {
      $role = $this->service->find_by_id((int) $id);
      $this->json_response($role ? $role->to_array() : []);
      return;
    } else if ($name !== null) {
      $role = $this->service->find_by_name($name);
      $this->json_response($role ? $role->to_array() : []);
      return;
    } else if ($active !== null) {
      $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
      $roles = $active ? $this->service->find_all_active() : $this->service->find_all();
      $this->json_response(array_map(fn($r) => $r->to_array(), $roles));
      return;
    }

    $roles = $this->service->find_all();
    $this->json_response(array_map(fn($r) => $r->to_array(), $roles));
  }

  /*
  POST /role
  */
  public function create(): void {
    $data = $this->get_body();
    if (empty($data)) {
      $this->error_response('Data not sended', 400);
      return;
    }

    $result = $this->service->create($data);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 422);
      return;
    }

    $this->json_response($result->to_array(), 201);
  }

  /*
  PUT /role/{id}
  */
  public function update(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $data = $this->get_body();
    if (empty($data)) {
      $this->error_response('Data not sended', 400);
      return;
    }

    $result = $this->service->update((int) $id, $data);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 422);
      return;
    }

    $this->json_response($result->to_array());
  }

  /*
  DELETE /role/{id}
  */
  public function delete(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $result = $this->service->hard_delete((int) $id);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Role deleted successfully']);
  }

  /*
  PUT /role/{id}/activate
  */
  public function activate(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $result = $this->service->activate((int) $id);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Role activated successfully']);
  }

  /*
  PUT /role/{id}/deactivate
  */
  public function deactivate(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $result = $this->service->deactivate((int) $id);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Role deactivated successfully']);
  }
}