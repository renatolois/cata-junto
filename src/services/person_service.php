  <?php
  declare(strict_types=1);

  namespace App\Services;

  use Core\Base\BaseService;
  use App\Models\PersonModel;
  use App\Validators\PersonValidator;
  use App\Repositories\PersonRepository;
  use Core\Utils\AppConstants;
  use Core\Utils\Logger;
  use Exception;

  class PersonService extends BaseService
  {
    public function __construct(
      PersonRepository $repository,
      PersonValidator $validator
    ) {
      parent::__construct($repository, $validator);
    }

    public function create(array $data): array|PersonModel
    {
      try {
        $data['id'] = $this->generate_uuid();

        if (isset($data['cpf'])) {
          $existing = $this->repository->find_by_cpf($data['cpf']);
          if ($existing !== null) {
            return ['errors' => ['cpf' => 'CPF already registered.']];
          }
        }

        if (isset($data['email'])) {
          $existing = $this->repository->find_by_email($data['email']);
          if ($existing !== null) {
            return ['errors' => ['email' => 'Email already registered.']];
          }
        }

        $person = $this->repository->hydrate($data);

        if (isset($data['password'])) {
          $person->set_password($data['password']);
        }

        $errors = $this->validator->validate_fillables($person);

        if (!empty($errors)) {
          return ['errors' => $errors];
        }

        $result = $this->repository->create_person($person->get_attributes());
        if ($result === null) {
          return ['errors' => ['service_error' => 'Failed to create person.']];
        }

        return $result;
      } catch (Exception $e) {
        Logger::error('Error creating person', ['error' => $e->getMessage()]);
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

    public function update(string|int $pk, array $data): array|PersonModel
    {
      try {
        $person = $this->repository->find_by_id($pk);
        if ($person === null) {
          return ['errors' => ['not_found_error' => 'Person not found.']];
        }

        foreach ($data as $field => $value) {
          if ($field === 'password') {
            continue;
          }
          
          if (in_array($field, $person->get_fillables())) {
            continue;
          } else {
            return [
              'errors' => [$field => 'Not fillable attribute received to update.']
            ];
          }
        }

        if (isset($data['email'])) {
          $existing = $this->repository->find_by_email($data['email']);
          if ($existing !== null && $existing->get_id() !== $pk) {
            return [
              'errors' => ['email' => 'Email already in use by another user.'],
            ];
          }
        }

        if (isset($data['cpf'])) {
          $existing = $this->repository->find_by_cpf($data['cpf']);
          if ($existing !== null && $existing->get_id() !== $pk) {
            return ['errors' => ['cpf' => 'CPF already in use by another user.']];
          }
        }

        foreach ($data as $field => $value) {
          if ($field === 'password') continue;
          $setter = 'set_' . $field;
          if (method_exists($person, $setter)) {
            $person->$setter($value);
          }
        }

        if (isset($data['password'])) {
          $person->set_password($data['password']);
        }

        $errors = $this->validator->validate_fillables($person);

        if (!empty($errors)) {
          return ['errors' => $errors];
        }

        $result = $this->repository->update_person(
          $pk,
          $person->get_attributes()
        );
        if ($result === null) {
          $errors['service_error'] = ['failed to update person.'];
          return ['errors' => $errors];
        }

        return $result;
      } catch (Exception $e) {
        Logger::error('Error updating person', ['pk' => $pk, 'error' => $e->getMessage()]);
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

    public function find_by_id(string $id): ?PersonModel|array
    {
      try {
        return $this->repository->find_by_id($id);
      } catch (Exception $e) {
        Logger::error('Error finding person by ID', ['id' => $id, 'error' => $e->getMessage()]);
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

    public function find_by_email(string $email): ?PersonModel|array
    {
      try {
        return $this->repository->find_by_email($email);
      } catch (Exception $e) {
        Logger::error('Error finding person by email', ['email' => $email, 'error' => $e->getMessage()]);
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

    public function find_by_cpf(string $cpf): ?PersonModel|array
    {
      try {
        return $this->repository->find_by_cpf($cpf);
      } catch (Exception $e) {
        Logger::error('Error finding person by CPF', ['cpf' => $cpf, 'error' => $e->getMessage()]);
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

    public function find_all_active(): array
    {
      try {
        return $this->repository->find_all_active();
      } catch (Exception $e) {
        Logger::error('Error finding active persons', ['error' => $e->getMessage()]);
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

    public function activate(string $pk): bool|array
    {
      try {
        $person = $this->repository->find_by_id($pk);
        if ($person === null) {
          return ['errors' => ['not_found_error' => 'Person not found.']];
        }

        $person->activate();
        $result = $this->repository->update_person($pk, [
          'active' => $person->is_active(),
        ]);
        if ($result === null) {
          return ['errors' => ['service_error' => 'Failed to activate person.']];
        }

        return $result;
      } catch (Exception $e) {
        Logger::error('Error activating person', ['pk' => $pk, 'error' => $e->getMessage()]);
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

    public function deactivate(string $pk): bool|array
    {
      try {
        $person = $this->repository->find_by_id($pk);
        if ($person === null) {
          return ['errors' => ['not_found_error' => 'Person not found.']];
        }

        $person->deactivate();
        $result = $this->repository->update_person($pk, [
          'active' => $person->is_active(),
        ]);
        if ($result === null) {
          return [
            'errors' => ['service_error' => 'Failed to deactivate person.'],
          ];
        }

        return $result;
      } catch (Exception $e) {
        Logger::error('Error deactivating person', ['pk' => $pk, 'error' => $e->getMessage()]);
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

    public function verify_password(string $id, string $password): bool|array
    {
      try {
        $person = $this->repository->find_by_id($id);
        if ($person === null) {
          return ['errors' => ['not_found_error' => 'Person not found.']];
        }
        return $person->verify_password($password);
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

    public function add_points(string $id, int $points): bool|array
    {
      try {
        $person = $this->repository->find_by_id($id);
        if ($person === null) {
          return ['errors' => ['not_found_error' => 'Person not found.']];
        }

        $newPoints = $person->get_current_points() + $points;
        $person->set_current_points($newPoints);

        $result = $this->repository->update_person($id, [
          'current_points' => $newPoints,
        ]);
        if ($result === null) {
          return ['errors' => ['service_error' => 'Failed to add points.']];
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

    public function deduct_points(string $id, int $points): bool|array
    {
      try {
        $person = $this->repository->find_by_id($id);
        if ($person === null) {
          return ['errors' => ['not_found_error' => 'Person not found.']];
        }

        $current = $person->get_current_points();
        if ($current < $points) {
          return ['errors' => ['current_points' => 'Insufficient points.']];
        }

        $newPoints = $current - $points;
        $person->set_current_points($newPoints);
        
        $result = $this->repository->update_person($id, ['current_points' => $newPoints]);
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
