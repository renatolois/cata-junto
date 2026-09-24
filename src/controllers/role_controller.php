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
      $result = $this->service->find_by_id((int) $id);
      $this->handle_result($result);
      return;
    } else if ($name !== null) {
      $result = $this->service->find_by_name($name);
      $this->handle_result($result);
      return;
    } else if ($active !== null) {
      $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
      $result = $active
        ? $this->service->find_all_active()
        : $this->service->find_all();
      $this->handle_result($result);
      return;
    }

    $result = $this->service->find_all();
    $this->handle_result($result);
  }

  /*
  POST /role
  */
  public function create(): void {
    $data = $this->get_body();
    if (empty($data)) {
      $this->error_response('Data not sent', 400);
      return;
    }

    $result = $this->service->create($data);
    $this->handle_result($result, 201);
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
      $this->error_response('Data not sent', 400);
      return;
    }

    $result = $this->service->update((int) $id, $data);
    $this->handle_result($result);
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
    $this->handle_result($result);
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
    $this->handle_result($result);
  }
}