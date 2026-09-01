<?php
declare(strict_types=1);

namespace App\Validators;

use Core\Base\BaseValidator;
use App\Models\ResidentialCollectionModel;
use App\Validators\Utils\ValidatorUtils;
use App\Core\Utils\NeutralValue;

class ResidentialCollectionValidator extends BaseValidator {
  
  private const COLLECT_TYPES = ['weight', 'unit'];
  private const MAX_TEXT_LENGTH = 500;
  private const ALLOWED_STATUSES = ['pending', 'completed', 'cancelled', 'rejected', 'inactive'];
  
  private array $optional_fields = [
    'collected_by', 'collected_at', 'observation', 
    'description', 'deactivation_at', 'deactivation_justification'
  ];
  
  public function validate($obj): array {
    $this->errors = [];

    if (!$obj instanceof ResidentailCollectionModel) {
      $this->errors[] = "The object must be an instance of RoleModel.";
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

    if (!$attributes['collect_type'] instanceof NeutralValue) {
      $collect_type = (string) $attributes['collect_type'];
      if (!in_array($collect_type, self::COLLECT_TYPES)) {
        $this->errors['collect_type'] = "The collect_type field must be one of the following values: " . 
                                        implode(', ', self::COLLECT_TYPES) . ".";
      }
    }

    if (!$attributes['quantity'] instanceof NeutralValue) {
      if ((float) $attributes['quantity'] < 0) {
        $this->errors['quantity'] = "The quantity field must be greater than or equal to 0.";
      }
    }

    if (!$attributes['observation'] instanceof NeutralValue) {
      $observation_len = mb_strlen((string) $attributes['observation']);
      if ($observation_len > self::MAX_TEXT_LENGTH) {
        $this->errors['observation'] = "The observation field must have at most " . self::MAX_TEXT_LENGTH . " characters.";
      }
    }

    if (!$attributes['description'] instanceof NeutralValue) {
      $description_len = mb_strlen((string) $attributes['description']);
      if ($description_len > self::MAX_TEXT_LENGTH) {
        $this->errors['description'] = "The description field must have at most " . self::MAX_TEXT_LENGTH . " characters.";
      }
    }

    if (!$attributes['status'] instanceof NeutralValue) {
      $status = (string) $attributes['status'];
      if (!in_array($status, self::ALLOWED_STATUSES)) {
        $this->errors['status'] = "The status field must be one of the following values: " . 
                                  implode(', ', self::ALLOWED_STATUSES) . ".";
      }
    }

    if (!$attributes['collected_at'] instanceof NeutralValue) {
      $year = (int) date('Y', strtotime((string) $attributes['collected_at']));
      $max_allowed_year = (int) date('Y') + 1;
      
      if ($year <= 1900 || $year > $max_allowed_year) {
        $this->errors['collected_at'] = "The collected_at field must be between the year 1900 and {$max_allowed_year}.";
      }
    }

    if (!$attributes['requested_at'] instanceof NeutralValue) {
      $year = (int) date('Y', strtotime((string) $attributes['requested_at']));
      $max_allowed_year = (int) date('Y') + 1;
      
      if ($year <= 1900 || $year > $max_allowed_year) {
        $this->errors['requested_at'] = "The requested_at field must be between the year 1900 and {$max_allowed_year}.";
      }
    }

    if (!$attributes['deactivation_at'] instanceof NeutralValue) {
      $year = (int) date('Y', strtotime((string) $attributes['deactivation_at']));
      $max_allowed_year = (int) date('Y') + 1;
      
      if ($year <= 1900 || $year > $max_allowed_year) {
        $this->errors['deactivation_at'] = "The deactivation_at field must be between the year 1900 and {$max_allowed_year}.";
      }
    }

    if (!$attributes['deactivation_justification'] instanceof NeutralValue) {
      $justification_len = mb_strlen((string) $attributes['deactivation_justification']);
      if ($justification_len > self::MAX_TEXT_LENGTH) {
        $this->errors['deactivation_justification'] = "The deactivation_justification field must have at most " . self::MAX_TEXT_LENGTH . " characters.";
      }
    }

    return $this->errors;
  }

  public function validate_fillables($obj): array {
    $this->errors = [];

    if (!$obj instanceof ResidentialCollectionModel) {
      $this->errors[] = "The object must be an instance of ResidentialCollectionModel.";
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

    if (in_array('collect_type', $fillables) && !$attributes['collect_type'] instanceof NeutralValue) {
      $collect_type = (string) $attributes['collect_type'];
      if (!in_array($collect_type, self::COLLECT_TYPES)) {
        $this->errors['collect_type'] = "The collect_type field must be one of the following values: " . 
                                        implode(', ', self::COLLECT_TYPES) . ".";
      }
    }

    if (in_array('quantity', $fillables) && !$attributes['quantity'] instanceof NeutralValue) {
      if ((float) $attributes['quantity'] < 0) {
        $this->errors['quantity'] = "The quantity field must be greater than or equal to 0.";
      }
    }

    if (in_array('observation', $fillables) && !$attributes['observation'] instanceof NeutralValue) {
      $observation_len = mb_strlen((string) $attributes['observation']);
      if ($observation_len > self::MAX_TEXT_LENGTH) {
        $this->errors['observation'] = "The observation field must have at most " . self::MAX_TEXT_LENGTH . " characters.";
      }
    }

    if (in_array('description', $fillables) && !$attributes['description'] instanceof NeutralValue) {
      $description_len = mb_strlen((string) $attributes['description']);
      if ($description_len > self::MAX_TEXT_LENGTH) {
        $this->errors['description'] = "The description field must have at most " . self::MAX_TEXT_LENGTH . " characters.";
      }
    }

    if (in_array('status', $fillables) && !$attributes['status'] instanceof NeutralValue) {
      $status = (string) $attributes['status'];
      if (!in_array($status, self::ALLOWED_STATUSES)) {
        $this->errors['status'] = "The status field must be one of the following values: " . 
                                  implode(', ', self::ALLOWED_STATUSES) . ".";
      }
    }

    if (in_array('collected_at', $fillables) && !$attributes['collected_at'] instanceof NeutralValue) {
      $year = (int) date('Y', strtotime((string) $attributes['collected_at']));
      $max_allowed_year = (int) date('Y') + 1;
      
      if ($year <= 1900 || $year > $max_allowed_year) {
        $this->errors['collected_at'] = "The collected_at field must be between the year 1900 and {$max_allowed_year}.";
      }
    }

    if (in_array('requested_at', $fillables) && !$attributes['requested_at'] instanceof NeutralValue) {
      $year = (int) date('Y', strtotime((string) $attributes['requested_at']));
      $max_allowed_year = (int) date('Y') + 1;
      
      if ($year <= 1900 || $year > $max_allowed_year) {
        $this->errors['requested_at'] = "The requested_at field must be between the year 1900 and {$max_allowed_year}.";
      }
    }

    if (in_array('deactivation_at', $fillables) && !$attributes['deactivation_at'] instanceof NeutralValue) {
      $year = (int) date('Y', strtotime((string) $attributes['deactivation_at']));
      $max_allowed_year = (int) date('Y') + 1;
      
      if ($year <= 1900 || $year > $max_allowed_year) {
        $this->errors['deactivation_at'] = "The deactivation_at field must be between the year 1900 and {$max_allowed_year}.";
      }
    }

    if (in_array('deactivation_justification', $fillables) && !$attributes['deactivation_justification'] instanceof NeutralValue) {
      $justification_len = mb_strlen((string) $attributes['deactivation_justification']);
      if ($justification_len > self::MAX_TEXT_LENGTH) {
        $this->errors['deactivation_justification'] = "The deactivation_justification field must have at most " . self::MAX_TEXT_LENGTH . " characters.";
      }
    }

    return $this->errors;
  }
}