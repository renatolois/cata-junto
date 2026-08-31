<?php
declare(strict_types=1);

namespace App\Validators;

use Core\Base\BaseValidator;
use App\Models\MaterialTypeModel;
use App\Validators\Utils\ValidatorUtils;
use App\Core\Utils\NeutralValue;

class MaterialTypeValidator extends BaseValidator {
  
  private const MAX_NAME_LENGTH = 100;
  
  public function validate(MaterialTypeModel $obj): array {
    $this->errors = [];

    $attributes = $obj->get_attributes();
    $cast_types = $obj->get_cast_types();

    foreach ($attributes as $field => $value) {
      if ($value instanceof NeutralValue || $value === null) {
        $this->errors[$field] = "The field {$field} is required.";
        continue;
      }

      if (!isset($cast_types[$field])) {
        $this->errors[$field] = "The field {$field} does not have a valid type.";
        continue;
      }

      $type = $cast_types[$field];

      if (method_exists(ValidatorUtils::class, $type)) {
        if (!ValidatorUtils::{$type}($value)) {
          $this->errors[$field] = "The field {$field} is invalid.";
        }
      } else {
        $this->errors[$field] = "There is no validator defined for type '{$type}'.";
      }
    }

    if (!$attributes['name'] instanceof NeutralValue) {
      $name_len = mb_strlen((string) $attributes['name']);
      if ($name_len < 2 || $name_len > self::MAX_NAME_LENGTH) {
        $this->errors['name'] = "The name field must be between 2 and " . self::MAX_NAME_LENGTH . " characters.";
      }
    }

    if (!$attributes['price_per_weight'] instanceof NeutralValue) {
      if ((float) $attributes['price_per_weight'] < 0) {
        $this->errors['price_per_weight'] = "The price_per_weight field must be greater than or equal to 0.";
      }
    }

    if (!$attributes['points_per_weight'] instanceof NeutralValue) {
      if ((int) $attributes['points_per_weight'] < 0) {
        $this->errors['points_per_weight'] = "The points_per_weight field must be greater than or equal to 0.";
      }
    }

    if (!$attributes['price_per_unit'] instanceof NeutralValue) {
      if ((float) $attributes['price_per_unit'] < 0) {
        $this->errors['price_per_unit'] = "The price_per_unit field must be greater than or equal to 0.";
      }
    }

    if (!$attributes['points_per_unit'] instanceof NeutralValue) {
      if ((int) $attributes['points_per_unit'] < 0) {
        $this->errors['points_per_unit'] = "The points_per_unit field must be greater than or equal to 0.";
      }
    }

    return $this->errors;
  }

  public function validate_fillables($obj): array {
    $this->errors = [];

    if (!$obj instanceof MaterialTypeModel) {
      $this->errors[] = "The object must be an instance of MaterialTypeModel.";
      return $this->errors;
    }
    
    $fillables = $obj->get_fillables();
    $attributes = $obj->get_attributes();
    $cast_types = $obj->get_cast_types();

    foreach ($fillables as $field) {
      $value = $attributes[$field];

      if ($value instanceof NeutralValue || $value === null) {
        $this->errors[$field] = "The fillable field {$field} is required.";
        continue;
      }

      if (!isset($cast_types[$field])) {
        $this->errors[$field] = "The field {$field} does not have a valid type.";
        continue;
      }

      $type = $cast_types[$field];

      if (method_exists(ValidatorUtils::class, $type)) {
        if (!ValidatorUtils::{$type}($value)) {
          $this->errors[$field] = "The field {$field} is invalid.";
        }
      } else {
        $this->errors[$field] = "There is no validator defined for type '{$type}'.";
      }
    }

    if (in_array('name', $fillables) && !$attributes['name'] instanceof NeutralValue) {
      $name_len = mb_strlen((string) $attributes['name']);
      if ($name_len < 2 || $name_len > self::MAX_NAME_LENGTH) {
        $this->errors['name'] = "The name field must be between 2 and " . self::MAX_NAME_LENGTH . " characters.";
      }
    }

    if (in_array('price_per_weight', $fillables) && !$attributes['price_per_weight'] instanceof NeutralValue) {
      if ((float) $attributes['price_per_weight'] < 0) {
        $this->errors['price_per_weight'] = "The price_per_weight field must be greater than or equal to 0.";
      }
    }

    if (in_array('points_per_weight', $fillables) && !$attributes['points_per_weight'] instanceof NeutralValue) {
      if ((int) $attributes['points_per_weight'] < 0) {
        $this->errors['points_per_weight'] = "The points_per_weight field must be greater than or equal to 0.";
      }
    }

    if (in_array('price_per_unit', $fillables) && !$attributes['price_per_unit'] instanceof NeutralValue) {
      if ((float) $attributes['price_per_unit'] < 0) {
        $this->errors['price_per_unit'] = "The price_per_unit field must be greater than or equal to 0.";
      }
    }

    if (in_array('points_per_unit', $fillables) && !$attributes['points_per_unit'] instanceof NeutralValue) {
      if ((int) $attributes['points_per_unit'] < 0) {
        $this->errors['points_per_unit'] = "The points_per_unit field must be greater than or equal to 0.";
      }
    }

    return $this->errors;
  }
}
