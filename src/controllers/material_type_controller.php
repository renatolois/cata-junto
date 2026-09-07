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
    $id = $this->get_query('id');

    $filters = array_filter([$active, $name, $id], fn($v) => $v !== null);
    if (count($filters) > 1) {
      $this->error_response('Use only 1 filter by time: active, name or id', 400);
      return;
    }

    if ($id !== null) {
      $material_type = $this->service->find_by_id((int) $id);
      $this->json_response($material_type ? $material_type->to_array() : []);
      return;
    } else if ($name !== null) {
      $material_type = $this->service->find_by_name($name);
      $this->json_response($material_type ? $material_type->to_array() : []);
      return;
    } else if ($active !== null) {
      $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
      $material_types = $active ? $this->service->find_all_active() : $this->service->find_all();
      $this->json_response(array_map(fn($m) => $m->to_array(), $material_types));
      return;
    }

    $material_types = $this->service->find_all();
    $this->json_response(array_map(fn($m) => $m->to_array(), $material_types));
  }

  /*
  POST /material-type
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
  DELETE /material-type/{id}
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

    $this->json_response(['message' => 'Material type deleted successfully']);
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
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Weight calculation activated successfully']);
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
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Weight calculation deactivated successfully']);
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
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Unit calculation activated successfully']);
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
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Unit calculation deactivated successfully']);
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
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response([
      'weight' => (float) $data['weight'],
      'price' => $result['price'],
      'points' => $result['points']
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
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response([
      'units' => (int) $data['units'],
      'price' => $result['price'],
      'points' => $result['points']
    ]);
  }
}