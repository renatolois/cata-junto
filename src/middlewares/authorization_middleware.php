<?php
declare(strict_types=1);

namespace App\Middlewares;

use Core\Base\BaseMiddleware;
use App\Repositories\ContractRepository;
use App\Repositories\RoleRepository;

class AuthorizationMiddleware extends BaseMiddleware {

  private ContractRepository $contract_repository;
  private RoleRepository $role_repository;
  private array $allowed_roles;

  public function __construct(
    ContractRepository $contract_repository,
    RoleRepository $role_repository,
    array $allowed_roles
  ) {
    $this->contract_repository = $contract_repository;
    $this->role_repository = $role_repository;
    $this->allowed_roles = $allowed_roles;
  }

  public function handle(array $context): array {
    $auth = $context['auth'] ?? null;
    if ($auth === null) {
      return ['ok' => false, 'status' => 401, 'error' => 'Unauthorized'];
    }

    if ($auth['type'] !== 'person') {
      return ['ok' => false, 'status' => 403, 'error' => 'Forbidden'];
    }

    $person_id = $auth['person_id'];

    $active_contracts = $this->contract_repository->find_active_by_person_id($person_id);
    if (empty($active_contracts)) {
      return ['ok' => false, 'status' => 403, 'error' => 'Forbidden'];
    }

    foreach ($active_contracts as $contract) {
      $role = $this->role_repository->find_by_id($contract->get_role_id());
      if ($role === null || !$role->is_active()) {
        continue;
      }
      if (in_array($role->get_name(), $this->allowed_roles, true)) {
        $context['auth']['contract_id'] = $contract->get_id();
        $context['auth']['role'] = $role->get_name();
        return ['ok' => true, 'context' => $context];
      }
    }

    return ['ok' => false, 'status' => 403, 'error' => 'Forbidden'];
  }
}