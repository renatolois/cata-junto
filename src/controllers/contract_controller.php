<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Base\BaseController;
use App\Services\ContractService;

class ContractController extends BaseController {
  public function __construct(ContractService $service) {
    parent::__construct($service);
  }

  /*
  GET /contract
  GET /contract?person_id=<PERSON_ID>
  GET /contract?role_id=<ROLE_ID>
  GET /contract?active=1
  GET /contract?pending=1
  GET /contract?id=<ID>
  */
  public function list(): void {
    $person_id = $this->get_query('person_id');
    $role_id = $this->get_query('role_id');
    $active = $this->get_query('active');
    $pending = $this->get_query('pending');
    $id = $this->get_query('id');

    $filters = array_filter([$person_id, $role_id, $active, $pending, $id], fn($v) => $v !== null);
    if (count($filters) > 1) {
      $this->error_response('Use only 1 filter by time: person_id, role_id, active, pending or id', 400);
      return;
    }

    if ($id !== null) {
      $contract = $this->service->find_by_id($id);
      $this->json_response($contract ? $contract->to_array() : []);
      return;
    } else if ($person_id !== null) {
      $contracts = $this->service->find_by_person($person_id);
      $this->json_response(array_map(fn($c) => $c->to_array(), $contracts));
      return;
    } else if ($role_id !== null) {
      $contracts = $this->service->find_by_role((int) $role_id);
      $this->json_response(array_map(fn($c) => $c->to_array(), $contracts));
      return;
    } else if ($active !== null) {
      $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
      $contracts = $active ? $this->service->find_active() : $this->service->find_all();
      $this->json_response(array_map(fn($c) => $c->to_array(), $contracts));
      return;
    } else if ($pending !== null) {
      $pending = filter_var($pending, FILTER_VALIDATE_BOOLEAN);
      $contracts = $pending ? $this->service->find_pending() : $this->service->find_all();
      $this->json_response(array_map(fn($c) => $c->to_array(), $contracts));
      return;
    }

    $contracts = $this->service->find_all();
    $this->json_response(array_map(fn($c) => $c->to_array(), $contracts));
  }

  /*
  POST /contract
  body: { "person_id": "<PERSON_ID>", "role_id": <ROLE_ID> }
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
  PUT /contract/{id}/approve
  body: { "approved_by_id": "<ADMIN_ID>", "justificative": "..." } (justificative optional)
  */
  public function approve(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $data = $this->get_body();
    if (!isset($data['approved_by_id'])) {
      $this->error_response('approved_by_id is required', 400);
      return;
    }

    $justificative = $data['justificative'] ?? null;
    $result = $this->service->approve($id, $data['approved_by_id'], $justificative);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Contract approved successfully']);
  }

  /*
  PUT /contract/{id}/reject
  body: { "refused_by_id": "<ADMIN_ID>", "justificative": "..." }
  */
  public function reject(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $data = $this->get_body();
    if (!isset($data['refused_by_id'])) {
      $this->error_response('refused_by_id is required', 400);
      return;
    }

    if (!isset($data['justificative']) || empty($data['justificative'])) {
      $this->error_response('justificative is required', 400);
      return;
    }

    $result = $this->service->reject($id, $data['refused_by_id'], $data['justificative']);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Contract rejected successfully']);
  }

  /*
  PUT /contract/{id}/cancel
  body: { "cancelled_by": "<PERSON_ID>" }
  */
  public function cancel(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $data = $this->get_body();
    if (!isset($data['cancelled_by'])) {
      $this->error_response('cancelled_by is required', 400);
      return;
    }

    $result = $this->service->cancel($id, $data['cancelled_by']);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Contract cancelled successfully']);
  }

  /*
  PUT /contract/{id}/terminate
  body: { "contract_end_by_id": "<ADMIN_ID>", "justificative": "..." }
  */
  public function terminate(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $data = $this->get_body();
    if (!isset($data['contract_end_by_id'])) {
      $this->error_response('contract_end_by_id is required', 400);
      return;
    }

    if (!isset($data['justificative']) || empty($data['justificative'])) {
      $this->error_response('justificative is required', 400);
      return;
    }

    $result = $this->service->terminate($id, $data['contract_end_by_id'], $data['justificative']);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Contract terminated successfully']);
  }

  /*
  DELETE /contract/{id}
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

    $this->json_response(['message' => 'Contract deleted successfully']);
  }
}