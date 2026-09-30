<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Base\BaseController;
use App\Services\ContractService;

class ContractController extends BaseController {
  public function __construct(ContractService $service) {
    parent::__construct($service);
  }

  public function list(): void {
    $auth_person_id = $this->get_route('auth_person_id');

    if ($auth_person_id !== null) {
        $result = $this->service->find_by_person($auth_person_id);
        $this->handle_result($result);
        return;
    }
    
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

  public function list_responded(): void {
    $auth_person_id = $this->get_route('auth_person_id');
    if (!$auth_person_id) {
      $this->error_response('Unauthorized', 401);
      return;
    }

    $admin_contract_id = $this->get_route('contract_id');
    if (!$admin_contract_id) {
      $this->error_response('No active admin contract', 403);
      return;
    }

    $result = $this->service->find_by_responded_by($admin_contract_id);
    $this->handle_result($result);
  }

  public function create(): void {
    $data = $this->get_body();
    if (empty($data)) {
      $this->error_response('Data not sent', 400);
      return;
    }

    $auth_person_id = $this->get_route('auth_person_id');
    if (!$auth_person_id) {
      $this->error_response('Unauthorized', 401);
      return;
    }

    $data['person_id'] = $auth_person_id;

    $result = $this->service->create($data);
    $this->handle_result($result, 201);
  }

  public function approve(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $admin_contract_id = $this->get_route('contract_id');
    if (!$admin_contract_id) {
      $this->error_response('No active admin contract', 403);
      return;
    }

    $data = $this->get_body();
    $justification = $data['justification'] ?? null;

    $result = $this->service->approve($id, $admin_contract_id, $justification);
    $this->handle_result($result);
  }

  public function reject(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $admin_contract_id = $this->get_route('contract_id');
    if (!$admin_contract_id) {
      $this->error_response('No active admin contract', 403);
      return;
    }

    $data = $this->get_body();
    if (!isset($data['justification']) || empty($data['justification'])) {
      $this->error_response('justification is required', 400);
      return;
    }

    $result = $this->service->reject($id, $admin_contract_id, $data['justification']);
    $this->handle_result($result);
  }

  public function cancel(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $auth_person_id = $this->get_route('auth_person_id');
    if (!$auth_person_id) {
      $this->error_response('Unauthorized', 401);
      return;
    }

    $result = $this->service->cancel($id, $auth_person_id);
    $this->handle_result($result);
  }

  public function terminate(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $admin_contract_id = $this->get_route('contract_id');
    if (!$admin_contract_id) {
      $this->error_response('No active admin contract', 403);
      return;
    }

    $data = $this->get_body();
    if (!isset($data['justification']) || empty($data['justification'])) {
      $this->error_response('justification is required', 400);
      return;
    }

    $result = $this->service->terminate($id, $admin_contract_id, $data['justification']);
    $this->handle_result($result);
  }
}