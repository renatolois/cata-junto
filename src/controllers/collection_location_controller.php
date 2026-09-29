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
      $result = $this->service->find_by_id($id);
      $this->handle_result($result);
      return;
    } else if ($email !== null) {
      $result = $this->service->find_by_email($email);
      $this->handle_result($result);
      return;
    } else if ($cep !== null) {
      $result = $this->service->find_by_cep($cep);
      $this->handle_result($result);
      return;
    } else if ($active !== null) {
      $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
      $result = $active
        ? $this->service->find_all_active()
        : $this->service->find_all();
      $this->handle_result($result);
      return;
    }

    $result = $this->service->find_all();
    $this->handle_result($result);
  }

  /*
  POST /collection-location
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
      $this->error_response('Data not sent', 400);
      return;
    }

    $result = $this->service->update($id, $data);
    $this->handle_result($result);
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
    $this->handle_result($result);
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
    $this->handle_result($result);
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
    $this->handle_result($result);
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

    if ($this->is_error_result($result)) {
      $this->handle_error($result['errors']);
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
    $this->handle_result($result);
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
    $this->handle_result($result);
  }
}