<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Base\BaseController;
use App\Services\InPersonCollectionService;

class InPersonCollectionController extends BaseController {
  public function __construct(InPersonCollectionService $service) {
    parent::__construct($service);
  }

  /*
  GET /in-person-collection
  GET /in-person-collection?contract_id=<CONTRACT_ID>
  GET /in-person-collection?material_type_id=<MATERIAL_TYPE_ID>
  GET /in-person-collection?collect_type=<TYPE>
  GET /in-person-collection?active=1
  GET /in-person-collection?id=<ID>
  */
  public function list(): void {
    $contract_id = $this->get_query('contract_id');
    $material_type_id = $this->get_query('material_type_id');
    $collect_type = $this->get_query('collect_type');
    $active = $this->get_query('active');
    $id = $this->get_query('id');

    $filters = array_filter([$contract_id, $material_type_id, $collect_type, $active, $id], fn($v) => $v !== null);
    if (count($filters) > 1) {
      $this->error_response('Use only 1 filter by time: contract_id, material_type_id, collect_type, active or id', 400);
      return;
    }

    if ($id !== null) {
      $collection = $this->service->find_by_id($id);
      $this->json_response($collection ? $collection->to_array() : []);
      return;
    } else if ($contract_id !== null) {
      $collections = $this->service->find_by_contract($contract_id);
      $this->json_response(array_map(fn($c) => $c->to_array(), $collections));
      return;
    } else if ($material_type_id !== null) {
      $collections = $this->service->find_by_material((int) $material_type_id);
      $this->json_response(array_map(fn($c) => $c->to_array(), $collections));
      return;
    } else if ($collect_type !== null) {
      $collections = $this->service->find_by_collect_type($collect_type);
      $this->json_response(array_map(fn($c) => $c->to_array(), $collections));
      return;
    } else if ($active !== null) {
      $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
      $collections = $active ? $this->service->find_active() : $this->service->find_all();
      $this->json_response(array_map(fn($c) => $c->to_array(), $collections));
      return;
    }

    $collections = $this->service->find_all();
    $this->json_response(array_map(fn($c) => $c->to_array(), $collections));
  }

  /*
  POST /in-person-collection
  body: { 
    "contract_id": "<CONTRACT_ID>", 
    "material_type_id": <MATERIAL_TYPE_ID>, 
    "collect_type": "weight"|"unit",
    "quantity": <FLOAT|INT>,
    "observation": "..." (optional)
  }
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
  DELETE /in-person-collection/{id}
  */
  public function delete(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $result = $this->service->hard_delete($id);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'In-person collection deleted successfully']);
  }
}