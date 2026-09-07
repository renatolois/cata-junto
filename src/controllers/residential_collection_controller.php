<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Base\BaseController;
use App\Services\ResidentialCollectionService;

class ResidentialCollectionController extends BaseController {
  public function __construct(ResidentialCollectionService $service) {
    parent::__construct($service);
  }

  /*
  GET /residential-collection
  GET /residential-collection?location_id=<LOCATION_ID>
  GET /residential-collection?status=<STATUS>
  GET /residential-collection?active=1
  GET /residential-collection?pending=1
  GET /residential-collection?completed=1
  GET /residential-collection?cancelled=1
  GET /residential-collection?id=<ID>
  */
  public function list(): void {
    $location_id = $this->get_query('location_id');
    $status = $this->get_query('status');
    $active = $this->get_query('active');
    $pending = $this->get_query('pending');
    $completed = $this->get_query('completed');
    $cancelled = $this->get_query('cancelled');
    $id = $this->get_query('id');

    $filters = array_filter([$location_id, $status, $active, $pending, $completed, $cancelled, $id], fn($v) => $v !== null);
    if (count($filters) > 1) {
      $this->error_response('Use only 1 filter by time: location_id, status, active, pending, completed, cancelled or id', 400);
      return;
    }

    if ($id !== null) {
      $collection = $this->service->find_by_id($id);
      $this->json_response($collection ? $collection->to_array() : []);
      return;
    } else if ($location_id !== null) {
      $collections = $this->service->find_by_location($location_id);
      $this->json_response(array_map(fn($c) => $c->to_array(), $collections));
      return;
    } else if ($status !== null) {
      $collections = $this->service->find_by_status($status);
      $this->json_response(array_map(fn($c) => $c->to_array(), $collections));
      return;
    } else if ($pending !== null) {
      $pending = filter_var($pending, FILTER_VALIDATE_BOOLEAN);
      $collections = $pending ? $this->service->find_pending() : $this->service->find_all();
      $this->json_response(array_map(fn($c) => $c->to_array(), $collections));
      return;
    } else if ($completed !== null) {
      $completed = filter_var($completed, FILTER_VALIDATE_BOOLEAN);
      $collections = $completed ? $this->service->find_completed() : $this->service->find_all();
      $this->json_response(array_map(fn($c) => $c->to_array(), $collections));
      return;
    } else if ($cancelled !== null) {
      $cancelled = filter_var($cancelled, FILTER_VALIDATE_BOOLEAN);
      $collections = $cancelled ? $this->service->find_canceled() : $this->service->find_all();
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
  POST /residential-collection
  body: { 
    "collection_location_id": "<LOCATION_ID>", 
    "material_type_id": <MATERIAL_TYPE_ID>, 
    "description": "..." (optional)
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
  PUT /residential-collection/{id}
  body: { 
    "collection_location_id": "<LOCATION_ID>", (optional)
    "material_type_id": <MATERIAL_TYPE_ID>, (optional)
    "description": "..." (optional)
  }
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

    $result = $this->service->update($id, $data);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 422);
      return;
    }

    $this->json_response($result->to_array());
  }

  /*
  PUT /residential-collection/{id}/complete
  body: { 
    "contract_id": "<CONTRACT_ID>", 
    "quantity": <FLOAT|INT>, 
    "collect_type": "weight"|"unit" 
  }
  */
  public function complete(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $data = $this->get_body();
    if (!isset($data['contract_id'])) {
      $this->error_response('contract_id is required', 400);
      return;
    }

    if (!isset($data['quantity']) || !is_numeric($data['quantity']) || $data['quantity'] <= 0) {
      $this->error_response('quantity must be greater than zero', 400);
      return;
    }

    if (!isset($data['collect_type']) || !in_array($data['collect_type'], ['weight', 'unit'])) {
      $this->error_response('collect_type must be "weight" or "unit"', 400);
      return;
    }

    $result = $this->service->complete(
      $id,
      $data['contract_id'],
      (float) $data['quantity'],
      $data['collect_type']
    );

    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Residential collection completed successfully']);
  }

  /*
  PUT /residential-collection/{id}/cancel
  body: { "justification": "..." }
  */
  public function cancel(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $data = $this->get_body();
    if (!isset($data['justification']) || empty($data['justification'])) {
      $this->error_response('justification is required', 400);
      return;
    }

    $result = $this->service->cancel($id, $data['justification']);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Residential collection cancelled successfully']);
  }

  /*
  PUT /residential-collection/{id}/activate
  */
  public function activate(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $result = $this->service->activate($id);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Residential collection activated successfully']);
  }

  /*
  PUT /residential-collection/{id}/deactivate
  body: { "justification": "..." }
  */
  public function deactivate(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $data = $this->get_body();
    if (!isset($data['justification']) || empty($data['justification'])) {
      $this->error_response('justification is required', 400);
      return;
    }

    $result = $this->service->deactivate($id, $data['justification']);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Residential collection deactivated successfully']);
  }

  /*
  DELETE /residential-collection/{id}
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

    $this->json_response(['message' => 'Residential collection deleted successfully']);
  }
}