<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Base\BaseController;
use App\Services\PersonService;

class PersonController extends BaseController {
  public function __construct(PersonService $service) {
    parent::__construct($service);
  }

  /*
  GET /person?cpf=<CPF>
  GET /person?id=<ID>
  GET /person?email=<EMAIL>
  GET /person?active=1
  */
  public function list(): void {
    $active = $this->get_query('active');
    $email = $this->get_query('email');
    $cpf = $this->get_query('cpf');
    $id = $this->get_query('id');

    $filters = array_filter([$active, $email, $cpf, $id], fn($v) => $v !== null);
    if (count($filters) > 1) {
      $this->error_response('Use only 1 filter by time: active, email, cpf or id', 400);
      return;
    }

    if ($id !== null) {
      $person = $this->service->find_by_id($id);
      $this->json_response($person ? $person->to_array() : []);
      return;
    } else if ($email !== null) {
      $person = $this->service->find_by_email($email);
      $this->json_response($person ? $person->to_array() : []);
      return;
    } else if ($cpf !== null) {
      $person = $this->service->find_by_cpf($cpf);
      $this->json_response($person ? $person->to_array() : []);
      return;
    } else if ($active !== null) {
      $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
      $people = $active ? $this->service->find_all_active() : $this->service->find_all();
      $this->json_response(array_map(fn($p) => $p->to_array(), $people));
      return;
    }

    $people = $this->service->find_all();
    $this->json_response(array_map(fn($p) => $p->to_array(), $people));
  }

  /*
  POST /person
  */
  public function create(): void {
    $data = $this->get_body();
    if (empty($data)) {
      $this->error_response('Data not sended', 400);
      return;
    }

    $result = $this->service->create($data);
    if (isset($result['errors'])) {
      $this->json_response(['errors' => $result['errors']], 422);
      return;
    }

    $this->json_response($result->to_array(), 201);
  }

  /*
  PUT /person/{id}
  */
  public function update(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID is null', 400);
      return;
    }

    $data = $this->get_body();
    if (empty($data)) {
      $this->error_response('Data not sended', 400);
      return;
    }

    $result = $this->service->update($id, $data);
    if (isset($result['errors'])) {
      $this->json_response(['errors' => $result['errors']], 422);
      return;
    }

    $this->json_response($result->to_array());
  }

  /*
  DELETE /person/{id}
  */
  public function destroy(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not sended', 400);
      return;
    }

    $result = $this->service->hard_delete($id);
    if (isset($result['errors'])) {
      $this->json_response(['errors' => $result['errors']], 400);
      return;
    }

    $this->json_response(['message' => 'Person deleted successfully']);
  }

  /*
  PUT /person/{id}/activate
  */
  public function activate(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not sended', 400);
      return;
    }

    $result = $this->service->activate($id);
    if (isset($result['errors'])) {
      $this->json_response(['errors' => $result['errors']], 400);
      return;
    }

    $this->json_response(['message' => 'Person activated successfully']);
  }

  /*
  PUT /person/{id}/deactivate
  */
  public function deactivate(): void {
    $id = $this->get_route('id');
    if (!$id) {
      $this->error_response('ID not provided', 400);
      return;
    }

    $result = $this->service->deactivate($id);
    if (isset($result['errors'])) {
      $this->json_response(['errors' => $result['errors']], 400);
      return;
    }

    $this->json_response(['message' => 'Person deactivated successfully']);
  }

  /*
  POST /person/{id}/add-points
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

    $result = $this->service->add_points($id, (int)$data['points']);
    if (isset($result['errors'])) {
      $this->json_response(['errors' => $result['errors']], 400);
      return;
    }

    $this->json_response(['message' => 'Points added successfully']);
  }

  /*
  POST /person/{id}/deduct-points
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

    $result = $this->service->deduct_points($id, (int)$data['points']);
    if (isset($result['errors'])) {
      $this->json_response(['errors' => $result['errors']], 400);
      return;
    }

    $this->json_response(['message' => 'Points deducted successfully']);
  }

  /*
  POST /person/{id}/verify-password
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
      $this->json_response(['errors' => $result['errors']], 400);
      return;
    }

    $this->json_response(['valid' => $result]);
  }
}