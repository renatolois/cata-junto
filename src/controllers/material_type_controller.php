<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Base\BaseController;
use App\Services\MaterialTypeService;

class MaterialTypeController extends BaseController {
  public function __construct(MaterialTypeService $service) {
    parent::__construct($service);
  }

  /*
  GET /material-type
  GET /material-type?active=1
  GET /material-type?name=<NAME>
  GET /material-type?id=<ID>
  */
  public function list(): void {
    $active = $this->get_query('active');
    $name = $this->get_query('name');
    $id = $this->get_route('id') ?? $this->get_query('id');

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
  POST /material-type
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
  PUT /material-type/{id}
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
  PUT /material-type/{id}/activate-weight
  */
  public function activate_weight(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $result = $this->service->activate_weight((int) $id);
    $this->handle_result($result);
  }

  /*
  PUT /material-type/{id}/deactivate-weight
  */
  public function deactivate_weight(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $result = $this->service->deactivate_weight((int) $id);
    $this->handle_result($result);
  }

  /*
  PUT /material-type/{id}/activate-unit
  */
  public function activate_unit(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $result = $this->service->activate_unit((int) $id);
    $this->handle_result($result);
  }

  /*
  PUT /material-type/{id}/deactivate-unit
  */
  public function deactivate_unit(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $result = $this->service->deactivate_unit((int) $id);
    $this->handle_result($result);
  }

  /*
  POST /material-type/{id}/calculate-by-weight
  body: { "weight": 10.5 }
  */
  public function calculate_by_weight(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $data = $this->get_body();
    if (!isset($data['weight']) || !is_numeric($data['weight']) || $data['weight'] <= 0) {
      $this->error_response('Invalid weight value', 400);
      return;
    }

    $result = $this->service->calculate_by_weight((int) $id, (float) $data['weight']);

    if ($this->is_error_result($result)) {
      $this->handle_error($result['errors']);
      return;
    }

    $this->json_response([
      'weight' => (float) $data['weight'],
      'price'  => $result['price'],
      'points' => $result['points'],
    ]);
  }

  /*
  POST /material-type/{id}/calculate-by-units
  body: { "units": 5 }
  */
  public function calculate_by_units(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $data = $this->get_body();
    if (!isset($data['units']) || !is_numeric($data['units']) || $data['units'] <= 0) {
      $this->error_response('Invalid units value', 400);
      return;
    }

    $result = $this->service->calculate_by_units((int) $id, (int) $data['units']);

    if ($this->is_error_result($result)) {
      $this->handle_error($result['errors']);
      return;
    }

    $this->json_response([
      'units'  => (int) $data['units'],
      'price'  => $result['price'],
      'points' => $result['points'],
    ]);
  }
}