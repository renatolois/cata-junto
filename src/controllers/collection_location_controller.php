<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Base\BaseController;
use App\Services\CollectionLocationService;

class CollectionLocationController extends BaseController {
  public function __construct(CollectionLocationService $service) {
    parent::__construct($service);
  }

  /*
  GET /collection-location
  GET /collection-location?active=1
  GET /collection-location?email=<EMAIL>
  GET /collection-location?id=<ID>
  GET /collection-location?cep=<CEP>
  */
  public function list(): void {
    $active = $this->get_query('active');
    $email = $this->get_query('email');
    $id = $this->get_query('id');
    $cep = $this->get_query('cep');

    $filters = array_filter([$active, $email, $id, $cep], fn($v) => $v !== null);
    if (count($filters) > 1) {
      $this->error_response('Use only 1 filter by time: active, email, id or cep', 400);
      return;
    }

    if ($id !== null) {
      $location = $this->service->find_by_id($id);
      $this->json_response($location ? $location->to_array() : []);
      return;
    } else if ($email !== null) {
      $location = $this->service->find_by_email($email);
      $this->json_response($location ? $location->to_array() : []);
      return;
    } else if ($cep !== null) {
      $locations = $this->service->find_by_cep($cep);
      $this->json_response(array_map(fn($l) => $l->to_array(), $locations));
      return;
    } else if ($active !== null) {
      $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
      $locations = $active ? $this->service->find_all_active() : $this->service->find_all();
      $this->json_response(array_map(fn($l) => $l->to_array(), $locations));
      return;
    }

    $locations = $this->service->find_all();
    $this->json_response(array_map(fn($l) => $l->to_array(), $locations));
  }

  /*
  POST /collection-location
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
  PUT /collection-location/{id}
  body: { data }
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
  DELETE /collection-location/{id}
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

    $this->json_response(['message' => 'Collection location deleted successfully']);
  }

  /*
  PUT /collection-location/{id}/activate
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

    $this->json_response(['message' => 'Collection location activated successfully']);
  }

  /*
  PUT /collection-location/{id}/deactivate
  */
  public function deactivate(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $result = $this->service->deactivate($id);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Collection location deactivated successfully']);
  }

  /*
  POST /collection-location/{id}/verify-password
  body: { "password": "..." }
  */
  public function verify_password(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $data = $this->get_body();
    if (!isset($data['password'])) {
      $this->error_response('Password not provided', 400);
      return;
    }

    $result = $this->service->verify_password($id, $data['password']);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['valid' => $result]);
  }

  /*
  POST /collection-location/{id}/add-points
  body: { "points": 10 }
  */
  public function add_points(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $data = $this->get_body();
    if (!isset($data['points']) || !is_numeric($data['points']) || $data['points'] <= 0) {
      $this->error_response('Invalid points value', 400);
      return;
    }

    $result = $this->service->add_points($id, (int) $data['points']);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Points added successfully']);
  }

  /*
  POST /collection-location/{id}/deduct-points
  body: { "points": 5 }
  */
  public function deduct_points(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $data = $this->get_body();
    if (!isset($data['points']) || !is_numeric($data['points']) || $data['points'] <= 0) {
      $this->error_response('Invalid points value', 400);
      return;
    }

    $result = $this->service->deduct_points($id, (int) $data['points']);
    if (isset($result['errors'])) {
      $this->error_response($result['errors'], 400);
      return;
    }

    $this->json_response(['message' => 'Points deducted successfully']);
  }
}