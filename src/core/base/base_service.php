<?php
declare(strict_types=1);

namespace Core\Base;

use Db\Database;
use Core\Base\BaseRepository;
use Core\Base\BaseValidator;

abstract class BaseService {
	protected BaseRepository $repository;
	protected BaseValidator  $validator;

	public function __construct(BaseRepository $repository, BaseValidator $validator) {
		$this->repository = $repository;
		$this->validator = $validator;
	}

	public function create(array $data): array|BaseModel {
		return $this->repository->create($data);
	}

	public function find_all(): array {
		return $this->repository->find_all();
	}

	public function update(int|string $pk, array $data): array|BaseModel {
		return $this->repository->update($pk, $data);
	}

	public function hard_delete(int|string $pk): bool|array {
		return $this->repository->delete($pk);
	}
}