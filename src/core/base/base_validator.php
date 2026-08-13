<?php
declare(strict_types=1);

namespace Core\Base;

abstract class BaseValidator {
	protected array $errors = [];
	
	abstract public function validate($obj): array;
	abstract public function validate_fillables($obj): array;
}