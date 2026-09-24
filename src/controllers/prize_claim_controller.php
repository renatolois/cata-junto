<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Base\BaseController;
use App\Services\PrizeClaimService;

class PrizeClaimController extends BaseController {
  public function __construct(PrizeClaimService $service) {
    parent::__construct($service);
  }

  public function list(): void {
    $status = $this->get_query('status');
    $location_id = $this->get_query('location_id');
    $prize_type_id = $this->get_query('prize_type_id');
    $id = $this->get_query('id');

    $filters = array_filter([$status, $location_id, $prize_type_id, $id], fn($v) => $v !== null);
    if (count($filters) > 1) {
      $this->error_response('Use only 1 filter by time: status, location_id, prize_type_id or id', 400);
      return;
    }

    if ($id !== null) {
      $result = $this->service->find_by_id((int) $id);
      $this->handle_result($result);
      return;
    } else if ($status !== null) {
      $result = $this->service->find_by_status($status);
      $this->handle_result($result);
      return;
    } else if ($location_id !== null) {
      $result = $this->service->find_by_location((int) $location_id);
      $this->handle_result($result);
      return;
    } else if ($prize_type_id !== null) {
      $result = $this->service->find_by_prize_type((int) $prize_type_id);
      $this->handle_result($result);
      return;
    }

    $result = $this->service->find_all();
    $this->handle_result($result);
  }

  public function create(): void {
    $data = $this->get_body();
    if (empty($data)) {
      $this->error_response('Data not provided', 400);
      return;
    }

    $result = $this->service->create($data);
    $this->handle_result($result, 201);
  }

  public function complete(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $result = $this->service->complete((int) $id);
    $this->handle_result($result);
  }

  public function cancel(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $result = $this->service->cancel((int) $id);
    $this->handle_result($result);
  }

  public function reject(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $result = $this->service->reject((int) $id);
    $this->handle_result($result);
  }

  public function destroy(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $result = $this->service->hard_delete((int) $id);
    $this->handle_result($result);
  }
}