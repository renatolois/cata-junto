<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Base\BaseController;
use Core\Base\BaseMiddleware;
use Core\Base\BaseService;
use Core\Utils\EnvLoader;
use App\Services\PersonService;
use App\Services\CollectionLocationService;
use App\Models\PersonModel;
use App\Models\CollectionLocationModel;

class AuthController extends BaseController {

  private PersonService $person_service;
  private CollectionLocationService $location_service;

  public function __construct(
    PersonService $person_service,
    CollectionLocationService $location_service,
    BaseService $service
  ) {
    parent::__construct($service);
    $this->person_service = $person_service;
    $this->location_service = $location_service;
  }

  public function login_person(): void {
    $this->clear_token_cookie();

    $data = $this->get_body();
    if (empty($data['email']) || empty($data['password'])) {
      $this->error_response('email and password are required', 400);
      return;
    }

    $email = (string) $data['email'];
    $password = (string) $data['password'];

    $person = $this->person_service->find_by_email($email);
    if (!($person instanceof PersonModel) || !$person->verify_password($password)) {
      $this->error_response('Invalid credentials', 401);
      return;
    }

    if (!$person->is_active()) {
      $this->error_response('Account is inactive', 403);
      return;
    }

    $this->issue_token(['person_id' => $person->get_id()]);
  }

  public function login_location(): void {
    $this->clear_token_cookie();

    $data = $this->get_body();
    if (empty($data['email']) || empty($data['password'])) {
      $this->error_response('email and password are required', 400);
      return;
    }

    $email = (string) $data['email'];
    $password = (string) $data['password'];

    $location = $this->location_service->find_by_email($email);
    if (!($location instanceof CollectionLocationModel) || !$location->verify_password($password)) {
      $this->error_response('Invalid credentials', 401);
      return;
    }

    if (!$location->is_active()) {
      $this->error_response('Account is inactive', 403);
      return;
    }

    $this->issue_token(['collection_location_id' => $location->get_id()]);
  }

  public function logout(): void {
    $this->clear_token_cookie();
    $this->json_response(['message' => 'Logged out']);
  }

  private function clear_token_cookie(): void {
    setcookie('token', '', [
      'expires'  => time() - 3600,
      'path'     => '/',
      'httponly' => true,
      'samesite' => 'Strict',
    ]);
  }

  private function issue_token(array $payload): void {
    $secret = EnvLoader::get('jwt_secret');
    if ($secret === null) {
      $this->error_response('Server misconfiguration', 500);
      return;
    }

    $payload['exp'] = time() + 86400;
    $token = BaseMiddleware::encode_jwt($payload, $secret);

    setcookie('token', $token, [
      'expires'  => time() + 86400,
      'path'     => '/',
      'httponly' => true,
      'samesite' => 'Strict',
    ]);

    $this->json_response(['message' => 'Logged in']);
  }
}