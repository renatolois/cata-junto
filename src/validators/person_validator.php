<?php
declare(strict_types=1);

namespace App\Validators;

use Core\Base\BaseValidator;
use App\Models\PersonModel;
use App\Validators\Utils\ValidatorUtils;
use App\Core\Utils\NeutralValue;

class PersonValidator extends BaseValidator {
  
  public function validate($obj): array {
    $this->errors = [];

    if (!$obj instanceof PersonModel) {
      $this->errors[] = "The object must be an instance of PersonModel.";
      return $this->errors;
    }

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
      if ($name_len < 2 || $name_len > 150) {
        $this->errors['name'] = "The name field must be between 2 and 150 characters.";
      }
    }

    if (!$attributes['password_hash'] instanceof NeutralValue) {
      if (mb_strlen((string) $attributes['password_hash']) !== 60) {
        $this->errors['password_hash'] = "The password_hash field must be a valid hash.";
      }
    }

    if (!$attributes['birth_date'] instanceof NeutralValue) {
      $birth_str = (string) $attributes['birth_date'];
      if (ValidatorUtils::date($birth_str)) {
        $year = (int) date('Y', strtotime($birth_str));
        $max_allowed_year = (int) date('Y') + 2;
        
        if ($year <= 1900 || $year > $max_allowed_year) {
          $this->errors['birth_date'] = "The birth_date field must be between the year 1900 and {$max_allowed_year}.";
        }
      }
    }

    if (!$attributes['current_points'] instanceof NeutralValue) {
      if ((int) $attributes['current_points'] < 0) {
        $this->errors['current_points'] = "The current_points field must be greater than or equal to 0.";
      }
    }

    return $this->errors;
  }

  public function validate_fillables($obj): array {
    $this->errors = [];
    
    if (!$obj instanceof PersonModel) {
      $this->errors[] = "The object must be an instance of PersonModel.";
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
      if ($name_len < 2 || $name_len > 150) {
        $this->errors['name'] = "The name field must be between 2 and 150 characters.";
      }
    }

    if (in_array('password_hash', $fillables) && !$attributes['password_hash'] instanceof NeutralValue) {
      if (mb_strlen((string) $attributes['password_hash']) !== 60) {
        $this->errors['password_hash'] = "The password_hash field must be a valid hash.";
      }
    }

    if (in_array('birth_date', $fillables) && !$attributes['birth_date'] instanceof NeutralValue) {
      $birth_str = (string) $attributes['birth_date'];
      if (ValidatorUtils::date($birth_str)) {
        $year = (int) date('Y', strtotime($birth_str));
        $max_allowed_year = (int) date('Y') + 2;
        
        if ($year <= 1900 || $year > $max_allowed_year) {
          $this->errors['birth_date'] = "The birth_date field must be between the year 1900 and {$max_allowed_year}.";
        }
      }
    }

    if (in_array('current_points', $fillables) && !$attributes['current_points'] instanceof NeutralValue) {
      if ((int) $attributes['current_points'] < 0) {
        $this->errors['current_points'] = "The current_points field must be greater than or equal to 0.";
      }
    }

    return $this->errors;
  }
}
