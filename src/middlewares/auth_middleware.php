<?php
declare(strict_types=1);

namespace App\Middlewares;

use Core\Base\BaseMiddleware;
use Core\Utils\EnvLoader;
use App\Repositories\PersonRepository;
use App\Repositories\CollectionLocationRepository;
use Exception;

class AuthMiddleware extends BaseMiddleware {

  private PersonRepository $person_repository;
  private CollectionLocationRepository $location_repository;

  public function __construct(
    PersonRepository $person_repository,
    CollectionLocationRepository $location_repository
  ) {
    $this->person_repository = $person_repository;
    $this->location_repository = $location_repository;
  }

  public function handle(array $context): array {
    $token = $this->extract_token();

    error_log("AUTH: token=" . ($token ? substr($token, 0, 40) . '...' : 'NULL'));

    if ($token === null) {
      error_log("AUTH: token is NULL, rejecting");
      return ['ok' => false, 'status' => 401, 'error' => 'Unauthorized'];
    }

    $secret = EnvLoader::get('jwt_secret');
    error_log("AUTH: secret=" . ($secret ?? 'NULL'));

    if ($secret === null) {
      error_log("AUTH: secret is NULL, server misconfiguration");
      return ['ok' => false, 'status' => 500, 'error' => 'Server misconfiguration'];
    }

    try {
      $payload = static::decode_jwt($token, $secret);
      error_log("AUTH: payload=" . json_encode($payload));
    } catch (Exception $e) {
      error_log("AUTH: decode failed: " . $e->getMessage());
      return ['ok' => false, 'status' => 401, 'error' => 'Unauthorized'];
    }

    if (isset($payload['person_id'])) {
      error_log("AUTH: looking up person_id=" . $payload['person_id']);

      $person = $this->person_repository->find_by_id($payload['person_id']);
      if ($person === null) {
        error_log("AUTH: person not found");
        return ['ok' => false, 'status' => 401, 'error' => 'Unauthorized'];
      }
      if (!$person->is_active()) {
        error_log("AUTH: person is not active");
        return ['ok' => false, 'status' => 401, 'error' => 'Unauthorized'];
      }

      error_log("AUTH: authenticated as person " . $person->get_id());

      $context['auth'] = [
        'type'      => 'person',
        'person_id' => $person->get_id(),
      ];

      return ['ok' => true, 'context' => $context];
    }

    if (isset($payload['collection_location_id'])) {
      error_log("AUTH: looking up collection_location_id=" . $payload['collection_location_id']);

      $location = $this->location_repository->find_by_id($payload['collection_location_id']);
      if ($location === null) {
        error_log("AUTH: location not found");
        return ['ok' => false, 'status' => 401, 'error' => 'Unauthorized'];
      }
      if (!$location->is_active()) {
        error_log("AUTH: location is not active");
        return ['ok' => false, 'status' => 401, 'error' => 'Unauthorized'];
      }

      error_log("AUTH: authenticated as location " . $location->get_id());

      $context['auth'] = [
        'type'                   => 'collection_location',
        'collection_location_id' => $location->get_id(),
      ];

      return ['ok' => true, 'context' => $context];
    }

    error_log("AUTH: payload has neither person_id nor collection_location_id");
    return ['ok' => false, 'status' => 401, 'error' => 'Unauthorized'];
  }

  private function extract_token(): ?string {
    error_log("AUTH: \$_COOKIE keys = " . implode(', ', array_keys($_COOKIE)));

    $token = $_COOKIE['token'] ?? null;
    if ($token === null || $token === '') {
      return null;
    }
    return $token;
  }
}