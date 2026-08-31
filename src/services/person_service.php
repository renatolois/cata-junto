<?php
declare(strict_types=1);

namespace App\Services;

use Core\Base\BaseService;
use App\Models\PersonModel;
use App\Validators\PersonValidator;

class PersonService extends BaseService {
	public function __construct(PersonRepository $repository, PersonValidator $validator) {
		parent::__construct($repository, $validator);
	}

	private function hydrate_and_validate(array $data): array {
		$person = $this->repository->hydrate($data);
		$errors = $this->validator->validate($person);
		return [$person, $errors];
	}

	public function create(array $data): array|PersonModel {
		[$person, $errors] = $this->hydrate_and_validate($data);

		if( !empty($errors) ) return ["errors" => $errors];

		$result = $this->repository->create_person($person->get_attributes());
		if($result === null) {
			return ["errors" => ["service_error" => "Failed to create person."]];
		}

		return $result;
	}

	public function update(string $pk, array $data): array|PersonModel {
		$person = $this->repository->find_by_id($pk);
    if ($person === null) {
      return ["errors" => ["service_error" => "Person not found."]];
    }
		
		foreach ($data as $field => $value) {
    	if (property_exists($person, $field)) {
      	$setter = 'set_' . $field;
      	if (method_exists($person, $setter)) {
	        $person->$setter($value);
      	}
	    }
		}

		if (isset($data["password"])) {
      $person->set_password($data["password"]);
    }

		$errors = $this->validator->validate($person);

		if( !empty($errors) ) return ["errors" => $errors];

		$result = $this->repository->update_person($pk, $person->get_attributes());
		if($result === null) {
			$errors['service_error'] = ["failed to update person."];
			return ["errors" => $errors];
		}

		return $result;
	}

	public function hard_delete(int|string $pk): bool|array {
		$person = $this->repository->find_by_id($pk);
    if ($person === null) {
      return ["errors" => ["service_error" => "Person not found."]];
    }
		return $this->repository->delete($pk);
	}

	public function find_by_id(string $id): ?PersonModel {
    return $this->repository->find_by_id($id);
	}

	public function find_by_email(string $email): ?PersonModel {
	  return $this->repository->find_by_email($email);
	}

	public function find_by_cpf(string $cpf): ?PersonModel {
	  return $this->repository->find_by_cpf($cpf);
	}

	public function find_all_active(): array {
	  return $this->repository->find_all_active();
	}

	public function count(): int {
    return $this->repository->count();
  }

	public function activate(string $pk): bool|array {
	  $person = $this->repository->find_by_id($pk);
	  if ($person === null) {
	    return ["errors" => ["service_error" => "Person not found."]];
	  }
	
	  $person->activate();
	  $result = $this->repository->update_person($pk, ['active' => $person->is_active()]);
	  if ($result === null) {
	    return ["errors" => ["service_error" => "Failed to activate person."]];
	  }
	
	  return $result;
	}

	public function deactivate(string $pk): bool|array {
	  $person = $this->repository->find_by_id($pk);
	  if ($person === null) {
	    return ["errors" => ["service_error" => "Person not found."]];
	  }
	
	  $person->deactivate();
	  $result = $this->repository->update_person($pk, ['active' => $person->is_active()]);
	  if ($result === null) {
	    return ["errors" => ["service_error" => "Failed to deactivate person."]];
	  }
	
	  return $result;
	}
}