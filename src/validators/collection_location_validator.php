<?php
declare(strict_types=1);

namespace App\Validators;

use Core\Base\BaseValidator;
use App\Models\CollectionLocationModel;
use App\Validators\Utils\ValidatorUtils;
use App\Core\Utils\NeutralValue;

class CollectionLocationValidator extends BaseValidator {
  
  public function validate($obj): array {
    $this->errors = [];

    if (!$obj instanceof CollectionLocationModel) {
      $this->errors[] = "The object must be an instance of CollectionLocationModel.";
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

    if (!$attributes['password_hash'] instanceof NeutralValue) {
      if (mb_strlen((string) $attributes['password_hash']) !== 60) {
        $this->errors['password_hash'] = "The password_hash field must be a valid hash.";
      }
    }

    if (!$attributes['street'] instanceof NeutralValue) {
      $street_len = mb_strlen((string) $attributes['street']);
      if ($street_len < 3 || $street_len > 200) {
        $this->errors['street'] = "The street field must be between 3 and 200 characters.";
      }
    }

    if (!$attributes['number'] instanceof NeutralValue) {
      $number_len = mb_strlen((string) $attributes['number']);
      if ($number_len < 1 || $number_len > 20) {
        $this->errors['number'] = "The number field must be between 1 and 20 characters.";
      }
    }

    if (!$attributes['neighborhood'] instanceof NeutralValue) {
      $neighborhood_len = mb_strlen((string) $attributes['neighborhood']);
      if ($neighborhood_len < 2 || $neighborhood_len > 100) {
        $this->errors['neighborhood'] = "The neighborhood field must be between 2 and 100 characters.";
      }
    }

    if (!$attributes['complement'] instanceof NeutralValue) {
      $complement_len = mb_strlen((string) $attributes['complement']);
      if ($complement_len > 100) {
        $this->errors['complement'] = "The complement field must have at most 100 characters.";
      }
    }

    if (!$attributes['city'] instanceof NeutralValue) {
      $city_len = mb_strlen((string) $attributes['city']);
      if ($city_len < 2 || $city_len > 100) {
        $this->errors['city'] = "The city field must be between 2 and 100 characters.";
      }
    }

    if (!$attributes['state'] instanceof NeutralValue) {
      $state_len = mb_strlen((string) $attributes['state']);
      if ($state_len !== 2) {
        $this->errors['state'] = "The state field must be exactly 2 characters (UF).";
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

    if (!$obj instanceof CollectionLocationModel) {
      $this->errors[] = "The object must be an instance of CollectionLocationModel.";
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

    if (in_array('street', $fillables) && !$attributes['street'] instanceof NeutralValue) {
      $street_len = mb_strlen((string) $attributes['street']);
      if ($street_len < 3 || $street_len > 200) {
        $this->errors['street'] = "The street field must be between 3 and 200 characters.";
      }
    }

    if (in_array('number', $fillables) && !$attributes['number'] instanceof NeutralValue) {
      $number_len = mb_strlen((string) $attributes['number']);
      if ($number_len < 1 || $number_len > 20) {
        $this->errors['number'] = "The number field must be between 1 and 20 characters.";
      }
    }

    if (in_array('neighborhood', $fillables) && !$attributes['neighborhood'] instanceof NeutralValue) {
      $neighborhood_len = mb_strlen((string) $attributes['neighborhood']);
      if ($neighborhood_len < 2 || $neighborhood_len > 100) {
        $this->errors['neighborhood'] = "The neighborhood field must be between 2 and 100 characters.";
      }
    }

    if (in_array('complement', $fillables) && !$attributes['complement'] instanceof NeutralValue) {
      $complement_len = mb_strlen((string) $attributes['complement']);
      if ($complement_len > 100) {
        $this->errors['complement'] = "The complement field must have at most 100 characters.";
      }
    }

    if (in_array('city', $fillables) && !$attributes['city'] instanceof NeutralValue) {
      $city_len = mb_strlen((string) $attributes['city']);
      if ($city_len < 2 || $city_len > 100) {
        $this->errors['city'] = "The city field must be between 2 and 100 characters.";
      }
    }

    if (in_array('state', $fillables) && !$attributes['state'] instanceof NeutralValue) {
      $state_len = mb_strlen((string) $attributes['state']);
      if ($state_len !== 2) {
        $this->errors['state'] = "The state field must be exactly 2 characters (UF).";
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
