<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Base\BaseController;
use App\Services\ResidentialCollectionService;
use App\Core\Utils\NeutralValue;


class ResidentialCollectionController extends BaseController {
  public function __construct(ResidentialCollectionService $service) {
    parent::__construct($service);
  }

  /*
  GET /residential-collection
  GET /residential-collection?id=<ID>
  GET /residential-collection?collection_location_id=<LOCATION_ID>
  GET /residential-collection?collection_location_id=<LOCATION_ID>&status=<STATUS>
  GET /residential-collection?status=<STATUS>
  GET /residential-collection?active=1
  */
  public function list(): void {
    $id = $this->get_query('id');
    $collection_location_id = $this->get_query('collection_location_id');
    $status = $this->get_query('status');
    $active = $this->get_query('active');

    if ($id !== null) {
      $result = $this->service->find_by_id($id);
      $this->handle_result($result);
      return;
    }

    if ($collection_location_id !== null && $status !== null) {
      $result = $this->service->find_by_location_and_status($collection_location_id, $status);
      $this->handle_result($result);
      return;
    }

    if ($collection_location_id !== null) {
      $result = $this->service->find_by_location($collection_location_id);
      $this->handle_result($result);
      return;
    }

    if ($status !== null) {
      $result = $this->service->find_by_status($status);
      $this->handle_result($result);
      return;
    }

    if ($active !== null) {
      $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
      $result = $active
        ? $this->service->find_active()
        : $this->service->find_all();
      $this->handle_result($result);
      return;
    }

    $result = $this->service->find_all();
    $this->handle_result($result);
  }

  /*
  GET /residential-collections/me
  */
  public function list_my(): void {
    $person_id = $this->get_route('auth_person_id');
    if (!$person_id) {
      $this->error_response('Unauthorized', 401);
      return;
    }

    $result = $this->service->find_by_person($person_id);
    $this->handle_result($result);
  }

  /*
  POST /residential-collection
  body: {
    "collection_location_id": "<LOCATION_ID>",
    "material_type_id": <MATERIAL_TYPE_ID>,
    "collect_type": "weight"|"unit",
    "description": "..." (optional)
  }
  */
  public function create(): void {
    $data = $this->get_body();
    if (empty($data)) {
      $this->error_response('Data not sent', 400);
      return;
    }

    $location_id = $this->get_route('auth_location_id');
    if (!$location_id) {
        $this->error_response('Unauthorized', 401);
        return;
    }

    $data['collection_location_id'] = $location_id;

    $result = $this->service->create($data);
    $this->handle_result($result, 201);
  }

  /*
  PUT /residential-collection/{id}
  body: {
    "description": "..." (optional),
    "material_type_id": <MATERIAL_TYPE_ID> (optional),
    "collect_type": "weight"|"unit" (optional)
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
      $this->error_response('Data not sent', 400);
      return;
    }

    $result = $this->service->update($id, $data);
    $this->handle_result($result);
  }

  /*
  PUT /residential-collection/{id}/complete
  body: {
    "contract_id": "<CONTRACT_ID>",
    "quantity": <FLOAT|INT>
  }
  */
  public function complete(): void {
    $collection_id = $this->get_route('collection_id');
    if (!$collection_id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $contract_id = $this->get_route('contract_id');
    if (!$contract_id) {
      $this->error_response('No active member contract', 403);
      return;
    }

    $data = $this->get_body();
    if (!isset($data['quantity']) || !is_numeric($data['quantity']) || $data['quantity'] <= 0) {
      $this->error_response('quantity must be a positive number', 400);
      return;
    }

    $result = $this->service->complete($collection_id, $contract_id, (float) $data['quantity']);
    $this->handle_result($result);
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
      $data['justification'] = NeutralValue::instance();
    }

    $result = $this->service->cancel($id, $data['justification']);
    $this->handle_result($result);
  }

  /*
  PUT /residential-collection/{id}/reject
  body: {
    "rejected_by_id": "<ADMIN_CONTRACT_ID>",
    "justification": "..."
  }
  */
  public function reject(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $data = $this->get_body();
    if (!isset($data['rejected_by_id'])) {
      $this->error_response('rejected_by_id is required', 400);
      return;
    }

    if (!isset($data['justification']) || empty($data['justification'])) {
      $this->error_response('justification is required', 400);
      return;
    }

    $result = $this->service->reject($id, $data['rejected_by_id'], $data['justification']);
    $this->handle_result($result);
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
    $this->handle_result($result);
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
    $this->handle_result($result);
  }

  /*
  GET /available-residential-collections
  */
  public function list_pending(): void {
    $result = $this->service->find_pending();
    $this->handle_result($result);
  }
}