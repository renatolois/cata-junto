<?php
declare(strict_types=1);

namespace App\Validators;

use Core\Base\BaseValidator;
use App\Models\PrizeClaimModel;
use App\Validators\Utils\ValidatorUtils;
use App\Core\Utils\NeutralValue;

class PrizeClaimValidator extends BaseValidator {
  
  private const ALLOWED_STATUSES = ['pending', 'finished', 'cancelled', 'rejected', 'inactive'];
  
  private array $optional_fields = [
    'collected_at'
  ];
  
  public function validate($obj): array {
    $this->errors = [];

    if (!$obj instanceof PrizeClaimModel) {
      $this->errors[] = "The object must be an instance of PrizeClaimModel.";
      return $this->errors;
    }

    $attributes = $obj->get_attributes();
    $cast_types = $obj->get_cast_types();

    foreach ($attributes as $field => $value) {
      if (($value instanceof NeutralValue && !in_array($field, $this->optional_fields)) || $value === null) {
        $this->errors[$field] = "The field {$field} is required.";
        continue;
      }

      if ($value instanceof NeutralValue) {
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

    if (!$attributes['status'] instanceof NeutralValue) {
      $status = (string) $attributes['status'];
      if (!in_array($status, self::ALLOWED_STATUSES)) {
        $this->errors['status'] = "The status field must be one of the following values: " . implode(', ', self::ALLOWED_STATUSES) . ".";
      }
    }

    if (!$attributes['claimed_at'] instanceof NeutralValue) {
      $year = (int) date('Y', strtotime((string) $attributes['claimed_at']));
      $max_allowed_year = (int) date('Y') + 1;
      
      if ($year <= 1900 || $year > $max_allowed_year) {
        $this->errors['claimed_at'] = "The claimed_at field must be between the year 1900 and {$max_allowed_year}.";
      }
    }

    if (!$attributes['collected_at'] instanceof NeutralValue) {
      $year = (int) date('Y', strtotime((string) $attributes['collected_at']));
      $max_allowed_year = (int) date('Y') + 1;
      
      if ($year <= 1900 || $year > $max_allowed_year) {
        $this->errors['collected_at'] = "The collected_at field must be between the year 1900 and {$max_allowed_year}.";
      }
    }

    return $this->errors;
  }

  public function validate_fillables($obj): array {
    $this->errors = [];

    if (!$obj instanceof PrizeClaimModel) {
      $this->errors[] = "The object must be an instance of PrizeClaimModel.";
      return $this->errors;
    }
    
    $fillables = $obj->get_fillables();
    $attributes = $obj->get_attributes();
    $cast_types = $obj->get_cast_types();

    foreach ($fillables as $field) {
      $value = $attributes[$field];

      if (($value instanceof NeutralValue && !in_array($field, $this->optional_fields)) || $value === null) {
        $this->errors[$field] = "The fillable field {$field} is required.";
        continue;
      }

      if ($value instanceof NeutralValue) {
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

    if (in_array('status', $fillables) && !$attributes['status'] instanceof NeutralValue) {
      $status = (string) $attributes['status'];
      if (!in_array($status, self::ALLOWED_STATUSES)) {
        $this->errors['status'] = "The status field must be one of the following values: " . 
                                  implode(', ', self::ALLOWED_STATUSES) . ".";
      }
    }

    if (in_array('claimed_at', $fillables) && !$attributes['claimed_at'] instanceof NeutralValue) {
      $year = (int) date('Y', strtotime((string) $attributes['claimed_at']));
      $max_allowed_year = (int) date('Y') + 1;
      
      if ($year <= 1900 || $year > $max_allowed_year) {
        $this->errors['claimed_at'] = "The claimed_at field must be between the year 1900 and {$max_allowed_year}.";
      }
    }

    if (in_array('collected_at', $fillables) && !$attributes['collected_at'] instanceof NeutralValue) {
      $year = (int) date('Y', strtotime((string) $attributes['collected_at']));
      $max_allowed_year = (int) date('Y') + 1;
      
      if ($year <= 1900 || $year > $max_allowed_year) {
        $this->errors['collected_at'] = "The collected_at field must be between the year 1900 and {$max_allowed_year}.";
      }
    }

    return $this->errors;
  }
}