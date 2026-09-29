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
    $id = $this->get_route('id') ?? $this->get_query('id');

    $filters = array_filter([$person_id, $role_id, $active, $pending, $id], fn($v) => $v !== null);
    if (count($filters) > 1) {
      $this->error_response('Use only 1 filter by time: person_id, role_id, active, pending or id', 400);
      return;
    }

    if ($id !== null) {
      $result = $this->service->find_by_id($id);
      $this->handle_result($result);
      return;
    } else if ($person_id !== null) {
      $result = $this->service->find_by_person($person_id);
      $this->handle_result($result);
      return;
    } else if ($role_id !== null) {
      $result = $this->service->find_by_role((int) $role_id);
      $this->handle_result($result);
      return;
    } else if ($active !== null) {
      $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
      $result = $active
        ? $this->service->find_active()
        : $this->service->find_all();
      $this->handle_result($result);
      return;
    } else if ($pending !== null) {
      $pending = filter_var($pending, FILTER_VALIDATE_BOOLEAN);
      $result = $pending
        ? $this->service->find_pending()
        : $this->service->find_all();
      $this->handle_result($result);
      return;
    }

    $result = $this->service->find_all();
    $this->handle_result($result);
  }

  /*
  POST /contract
  body: { "person_id": "<PERSON_ID>", "role_id": <ROLE_ID> }
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
  PUT /contract/{id}/approve
  body: { "approved_by_id": "<ADMIN_CONTRACT_ID>", "justification": "..." } (justification optional)
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

    $justification = $data['justification'] ?? null;
    $result = $this->service->approve($id, $data['approved_by_id'], $justification);
    $this->handle_result($result);
  }

  /*
  PUT /contract/{id}/reject
  body: { "rejected_by_id": "<ADMIN_CONTRACT_ID>", "justification": "..." }
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
  PUT /contract/{id}/cancel
  body: { "person_id": "<PERSON_ID>" }
  */
  public function cancel(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $data = $this->get_body();
    if (!isset($data['person_id'])) {
      $this->error_response('person_id is required', 400);
      return;
    }

    $result = $this->service->cancel($id, $data['person_id']);
    $this->handle_result($result);
  }

  /*
  PUT /contract/{id}/terminate
  body: { "contract_end_by_id": "<ADMIN_CONTRACT_ID>", "justification": "..." }
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

    if (!isset($data['justification']) || empty($data['justification'])) {
      $this->error_response('justification is required', 400);
      return;
    }

    $result = $this->service->terminate($id, $data['contract_end_by_id'], $data['justification']);
    $this->handle_result($result);
  }
}