<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Base\BaseController;
use App\Services\PrizeClaimService;

class PrizeClaimController extends BaseController {
  public function __construct(PrizeClaimService $service) {
    parent::__construct($service);
  }

  /*
  GET /prize-claim
  GET /prize-claim?status=<STATUS>
  GET /prize-claim?location_id=<LOCATION_ID>
  GET /prize-claim?prize_type_id=<PRIZE_TYPE_ID>
  GET /prize-claim?id=<ID>
  */
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
      $claim = $this->service->find_by_id((int) $id);
      $this->json_response($claim ? $claim->to_array() : []);
      return;
    } else if ($status !== null) {
      $claims = $this->service->find_by_status($status);
      $this->json_response(array_map(fn($c) => $c->to_array(), $claims));
      return;
    } else if ($location_id !== null) {
      $claims = $this->service->find_by_location($location_id);
      $this->json_response(array_map(fn($c) => $c->to_array(), $claims));
      return;
    } else if ($prize_type_id !== null) {
      $claims = $this->service->find_by_prize_type((int) $prize_type_id);
      $this->json_response(array_map(fn($c) => $c->to_array(), $claims));
      return;
    }

    $claims = $this->service->find_all();
    $this->json_response(array_map(fn($c) => $c->to_array(), $claims));
  }

  /*
  POST /prize-claim
  body: { "claimed_by": "<LOCATION_ID>", "prize_type_id": <PRIZE_TYPE_ID> }
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
  PUT /prize-claim/{id}/complete
  */
  public function complete(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $result = $this->service->complete((int) $id);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Prize claim completed successfully']);
  }

  /*
  PUT /prize-claim/{id}/cancel
  */
  public function cancel(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $result = $this->service->cancel((int) $id);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Prize claim cancelled successfully']);
  }

  /*
  PUT /prize-claim/{id}/reject
  */
  public function reject(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $result = $this->service->reject((int) $id);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Prize claim rejected successfully']);
  }

  /*
  DELETE /prize-claim/{id}
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

    $this->json_response(['message' => 'Prize claim deleted successfully']);
  }
}