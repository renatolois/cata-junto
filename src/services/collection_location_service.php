<?php
declare(strict_types=1);

namespace App\Services;

use Core\Base\BaseService;
use App\Models\CollectionLocationModel;
use App\Validators\CollectionLocationValidator;
use App\Repositories\CollectionLocationRepository;

class CollectionLocationService extends BaseService {
  public function __construct(CollectionLocationRepository $repository, CollectionLocationValidator $validator) {
    parent::__construct($repository, $validator);
  }
  
  public function create(array $data): array|CollectionLocationModel {
		[$collectionLocation, $errors] = $this->hydrate_and_validate($data);

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
  }

  public function update(string|int $pk, array $data): array|CollectionLocationModel {
    $collectionLocation = $this->repository->find_by_id($pk);
    if ($collectionLocation === null) {
      return ["errors" => ["service_error" => "Collection location not found."]];
    }

    foreach ($data as $field => $value) {
      if (property_exists($collectionLocation, $field)) {
        $setter = 'set_' . $field;
        if (method_exists($collectionLocation, $setter)) {
          $collectionLocation->$setter($value);
        }
      }
    }

    if (isset($data['password'])) {
      $collectionLocation->set_password($data['password']);
    }

    $errors = $this->validator->validate($collectionLocation);
    if (!empty($errors)) return ["errors" => $errors];

    $result = $this->repository->update_collection_location($pk, $collectionLocation->get_attributes());
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to update collection location."]];
    }

    return $result;
  }

  public function hard_delete(string $pk): bool|array {
    $collectionLocation = $this->repository->find_by_id($pk);
    if ($collectionLocation === null) {
      return ["errors" => ["service_error" => "Collection location not found."]];
    }
    return $this->repository->delete($pk);
  }

  public function find_by_id(string $id): ?CollectionLocationModel {
    return $this->repository->find_by_id($id);
  }

  public function find_by_email(string $email): ?CollectionLocationModel {
    return $this->repository->find_by_email($email);
  }

  public function find_by_cep(string $cep): ?CollectionLocationModel {
    return $this->repository->find_by_cep($cep);
  }

  public function find_all_active(): array {
    return $this->repository->find_all_active();
  }

  public function find_all(): array {
    return $this->repository->find_all();
  }

  public function activate(string $pk): bool|array {
    $collectionLocation = $this->repository->find_by_id($pk);
    if ($collectionLocation === null) {
      return ["errors" => ["service_error" => "Collection location not found."]];
    }

    $collectionLocation->activate();
    $result = $this->repository->update_collection_location($pk, ['active' => $collectionLocation->is_active()]);
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to activate collection location."]];
    }

    return $result;
  }

  public function deactivate(string $pk): bool|array {
    $collectionLocation = $this->repository->find_by_id($pk);
    if ($collectionLocation === null) {
      return ["errors" => ["service_error" => "Collection location not found."]];
    }

    $collectionLocation->deactivate();
    $result = $this->repository->update_collection_location($pk, ['active' => $collectionLocation->is_active()]);
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to deactivate collection location."]];
    }

    return $result;
  }

  public function verify_password(string $id, string $password): bool|array {
    $collectionLocation = $this->repository->find_by_id($id);
    if ($collectionLocation === null) {
      return ["errors" => ["service_error" => "Collection location not found."]];
    }

    return $collectionLocation->verify_password($password);
  }

  public function add_points(string $id, int $points): CollectionLocationModel|array {
    $collectionLocation = $this->repository->find_by_id($id);
    if ($collectionLocation === null) {
      return ["errors" => ["service_error" => "Collection location not found."]];
    }

    $newPoints = $collectionLocation->get_current_points() + $points;
    $collectionLocation->set_current_points($newPoints);

    $result = $this->repository->update_collection_location($id, ['current_points' => $newPoints]);
    if ($result === null) {
      return ["errors" => ["service_error" => "Failed to add points."]];
    }

    return $result;
  }

  public function deduct_points(string $id, int $points): bool|array {
    $collectionLocation = $this->repository->find_by_id($id);
    if ($collectionLocation === null) {
      return ["errors" => ["service_error" => "Collection location not found."]];
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
  }
}