<?php
declare(strict_types=1);

namespace App\Services;

use Core\Base\BaseService;
use App\Models\CollectionLocationModel;
use App\Validators\CollectionLocationValidator;
use App\Repositories\CollectionLocationRepository;
use Core\Utils\AppConstants;
use Core\Utils\Logger;
use Exception;

class CollectionLocationService extends BaseService {

  public function __construct(CollectionLocationRepository $repository, CollectionLocationValidator $validator) {
    parent::__construct($repository, $validator);
  }

  public function create(array $data): array|CollectionLocationModel {
    try {
      if (isset($data['responsable_email'])) {
        $existing = $this->repository->find_by_email($data['responsable_email']);
        if ($existing !== null) {
          return ["errors" => ["responsable_email" => "Email already registered."]];
        }
      }

      [$collectionLocation, $errors] = $this->hydrate_and_validate_fillables($data);

      if (!empty($errors)) {
        return ["errors" => $errors];
      }

      if (isset($data['password'])) {
        $collectionLocation->set_password($data['password']);
      }

      $result = $this->repository->create_collection_location($collectionLocation->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to create collection location."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error creating collection location', ['error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }

  public function update(string|int $pk, array $data): array|CollectionLocationModel {
    try {
      $collectionLocation = $this->repository->find_by_id($pk);
      if ($collectionLocation === null) {
        return ["errors" => ["not_found_error" => "Collection location not found."]];
      }

      foreach ($data as $field => $value) {
        if ($field === 'password') continue;
        if (!in_array($field, $collectionLocation->get_fillables(), true)) {
          return ['errors' => [$field => 'Not fillable attribute received to update.']];
        }
      }

      if (isset($data['responsable_email'])) {
        $existing = $this->repository->find_by_email($data['responsable_email']);
        if ($existing !== null && (string) $existing->get_id() !== (string) $pk) {
          return ["errors" => ["responsable_email" => "Email already in use by another location."]];
        }
      }

      foreach ($data as $field => $value) {
        if ($field === 'password') continue;
        $setter = 'set_' . $field;
        if (method_exists($collectionLocation, $setter)) {
          $collectionLocation->$setter($value);
        }
      }

      if (isset($data['password'])) {
        $collectionLocation->set_password($data['password']);
      }

      $errors = $this->validator->validate_fillables($collectionLocation);
      if (!empty($errors)) {
        return ["errors" => $errors];
      }

      $result = $this->repository->update_collection_location($pk, $collectionLocation->get_attributes());
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to update collection location."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error updating collection location', ['pk' => $pk, 'error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }

  public function find_by_id(string $id): null|CollectionLocationModel|array {
    try {
      return $this->repository->find_by_id($id);
    } catch (Exception $e) {
      Logger::error('Error finding collection location by ID', ['id' => $id, 'error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }

  public function find_by_email(string $email): null|CollectionLocationModel|array {
    try {
      return $this->repository->find_by_email($email);
    } catch (Exception $e) {
      Logger::error('Error finding collection location by email', ['email' => $email, 'error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }

  public function find_by_cep(string $cep): array {
    try {
      return $this->repository->find_by_cep($cep);
    } catch (Exception $e) {
      Logger::error('Error finding collection location by CEP', ['cep' => $cep, 'error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }

  public function find_all_active(): array {
    try {
      return $this->repository->find_all_active();
    } catch (Exception $e) {
      Logger::error('Error finding active collection locations', ['error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }

  public function find_all(): array {
    try {
      return $this->repository->find_all();
    } catch (Exception $e) {
      Logger::error('Error finding all collection locations', ['error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }

  public function activate(string $pk): bool|array {
    try {
      $collectionLocation = $this->repository->find_by_id($pk);
      if ($collectionLocation === null) {
        return ["errors" => ["not_found_error" => "Collection location not found."]];
      }

      $collectionLocation->activate();
      $result = $this->repository->update_collection_location($pk, ['active' => $collectionLocation->is_active()]);
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to activate collection location."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error activating collection location', ['pk' => $pk, 'error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }

  public function deactivate(string $pk): bool|array {
    try {
      $collectionLocation = $this->repository->find_by_id($pk);
      if ($collectionLocation === null) {
        return ["errors" => ["not_found_error" => "Collection location not found."]];
      }

      $collectionLocation->deactivate();
      $result = $this->repository->update_collection_location($pk, ['active' => $collectionLocation->is_active()]);
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to deactivate collection location."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error deactivating collection location', ['pk' => $pk, 'error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }

  public function verify_password(string $id, string $password): bool|array {
    try {
      $collectionLocation = $this->repository->find_by_id($id);
      if ($collectionLocation === null) {
        return ["errors" => ["not_found_error" => "Collection location not found."]];
      }

      return $collectionLocation->verify_password($password);
    } catch (Exception $e) {
      Logger::error('Error verifying password', ['id' => $id, 'error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }

  public function add_points(string $id, int $points): CollectionLocationModel|array {
    try {
      $collectionLocation = $this->repository->find_by_id($id);
      if ($collectionLocation === null) {
        return ["errors" => ["not_found_error" => "Collection location not found."]];
      }

      $newPoints = $collectionLocation->get_current_points() + $points;
      $collectionLocation->set_current_points($newPoints);

      $result = $this->repository->update_collection_location($id, ['current_points' => $newPoints]);
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to add points."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error adding points', ['id' => $id, 'points' => $points, 'error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }

  public function deduct_points(string $id, int $points): bool|array {
    try {
      $collectionLocation = $this->repository->find_by_id($id);
      if ($collectionLocation === null) {
        return ["errors" => ["not_found_error" => "Collection location not found."]];
      }

      $current = $collectionLocation->get_current_points();
      if ($current < $points) {
        return ["errors" => ["current_points" => "Insufficient points."]];
      }

      $newPoints = $current - $points;
      $collectionLocation->set_current_points($newPoints);

      $result = $this->repository->update_collection_location($id, ['current_points' => $newPoints]);
      if ($result === null) {
        return ["errors" => ["service_error" => "Failed to deduct points."]];
      }

      return $result;
    } catch (Exception $e) {
      Logger::error('Error deducting points', ['id' => $id, 'points' => $points, 'error' => $e->getMessage()]);
      return [
        'errors' => [
          'server_error' =>
            AppConstants::RUN_MODE === 'debug'
              ? $e->getMessage()
              : 'unexpected_error',
        ],
      ];
    }
  }
}