<?php
declare(strict_types=1);

namespace App\Validators;

use Core\Base\BaseValidator;
use App\Models\ContractModel;
use App\Validators\Utils\ValidatorUtils;
use App\Core\Utils\NeutralValue;

class ContractValidator extends BaseValidator {
  
  private const ALLOWED_STATUSES = ['pending', 'canceled', 'approved', 'rejected', 'dismissed'];
  
  private array $optional_fields = [
    'responded_by_id', 'contract_end_by', 'responded_at', 
    'response_justification', 'dismissal_justification', 'contract_end_at'
  ];
  
  public function validate(ContractModel $obj): array {
    $this->errors = [];

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

    if (!$attributes['response_justification'] instanceof NeutralValue) {
      $justification_len = mb_strlen((string) $attributes['response_justification']);
      if ($justification_len < 10 || $justification_len > 500) {
        $this->errors['response_justification'] = "The response_justification field must be between 10 and 500 characters.";
      }
    }

    if (!$attributes['dismissal_justification'] instanceof NeutralValue) {
      $justification_len = mb_strlen((string) $attributes['dismissal_justification']);
      if ($justification_len < 10 || $justification_len > 500) {
        $this->errors['dismissal_justification'] = "The dismissal_justification field must be between 10 and 500 characters.";
      }
    }

    if (!$attributes['requested_at'] instanceof NeutralValue) {
      $requested_date_str = (string) $attributes['requested_at'];
      if (ValidatorUtils::datetime($requested_date_str)) {
        $year = (int) date('Y', strtotime($requested_date_str));
        $max_allowed_year = (int) date('Y') + 1;
        
        if ($year <= 1900 || $year > $max_allowed_year) {
          $this->errors['requested_at'] = "The requested_at field must be between the year 1900 and {$max_allowed_year}.";
        }
      }
    }

    if (!$attributes['responded_at'] instanceof NeutralValue) {
      $responded_date_str = (string) $attributes['responded_at'];
      if (ValidatorUtils::datetime($responded_date_str)) {
        $year = (int) date('Y', strtotime($responded_date_str));
        $max_allowed_year = (int) date('Y') + 1;
        
        if ($year <= 1900 || $year > $max_allowed_year) {
          $this->errors['responded_at'] = "The responded_at field must be between the year 1900 and {$max_allowed_year}.";
        }
      }
    }

    if (!$attributes['status'] instanceof NeutralValue) {
      $status = (string) $attributes['status'];
      if (!in_array($status, self::ALLOWED_STATUSES)) {
        $this->errors['status'] = "The status field must be one of the following values: " . 
                                  implode(', ', self::ALLOWED_STATUSES) . ".";
      }
    }

    if (!$attributes['contract_end_at'] instanceof NeutralValue) {
      $end_date_str = (string) $attributes['contract_end_at'];
      if (ValidatorUtils::datetime($end_date_str)) {
        $year = (int) date('Y', strtotime($end_date_str));
        $max_allowed_year = (int) date('Y') + 1;
        
        if ($year <= 1900 || $year > $max_allowed_year) {
          $this->errors['contract_end_at'] = "The contract_end_at field must be between the year 1900 and {$max_allowed_year}.";
        }
      }
    }

    return $this->errors;
  }

  public function validate_fillables($obj): array {
    $this->errors = [];

    if (!$obj instanceof ContractModel) {
      $this->errors[] = "The object must be an instance of ContractModel.";
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

    if (in_array('response_justification', $fillables) && !$attributes['response_justification'] instanceof NeutralValue) {
      $justification_len = mb_strlen((string) $attributes['response_justification']);
      if ($justification_len < 10 || $justification_len > 500) {
        $this->errors['response_justification'] = "The response_justification field must be between 10 and 500 characters.";
      }
    }

    if (in_array('dismissal_justification', $fillables) && !$attributes['dismissal_justification'] instanceof NeutralValue) {
      $justification_len = mb_strlen((string) $attributes['dismissal_justification']);
      if ($justification_len < 10 || $justification_len > 500) {
        $this->errors['dismissal_justification'] = "The dismissal_justification field must be between 10 and 500 characters.";
      }
    }

    if (in_array('requested_at', $fillables) && !$attributes['requested_at'] instanceof NeutralValue) {
      $requested_date_str = (string) $attributes['requested_at'];
      if (ValidatorUtils::datetime($requested_date_str)) {
        $year = (int) date('Y', strtotime($requested_date_str));
        $max_allowed_year = (int) date('Y') + 1;
        
        if ($year <= 1900 || $year > $max_allowed_year) {
          $this->errors['requested_at'] = "The requested_at field must be between the year 1900 and {$max_allowed_year}.";
        }
      }
    }

    if (in_array('responded_at', $fillables) && !$attributes['responded_at'] instanceof NeutralValue) {
      $responded_date_str = (string) $attributes['responded_at'];
      if (ValidatorUtils::datetime($responded_date_str)) {
        $year = (int) date('Y', strtotime($responded_date_str));
        $max_allowed_year = (int) date('Y') + 1;
        
        if ($year <= 1900 || $year > $max_allowed_year) {
          $this->errors['responded_at'] = "The responded_at field must be between the year 1900 and {$max_allowed_year}.";
        }
      }
    }

    if (in_array('status', $fillables) && !$attributes['status'] instanceof NeutralValue) {
      $status = (string) $attributes['status'];
      if (!in_array($status, self::ALLOWED_STATUSES)) {
        $this->errors['status'] = "The status field must be one of the following values: " . 
                                  implode(', ', self::ALLOWED_STATUSES) . ".";
      }
    }

    if (in_array('contract_end_at', $fillables) && !$attributes['contract_end_at'] instanceof NeutralValue) {
      $end_date_str = (string) $attributes['contract_end_at'];
      if (ValidatorUtils::datetime($end_date_str)) {
        $year = (int) date('Y', strtotime($end_date_str));
        $max_allowed_year = (int) date('Y') + 1;
        
        if ($year <= 1900 || $year > $max_allowed_year) {
          $this->errors['contract_end_at'] = "The contract_end_at field must be between the year 1900 and {$max_allowed_year}.";
        }
      }
    }

    return $this->errors;
  }
}
