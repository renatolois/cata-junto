<?php
  declare(strict_types=1);

  namespace App\Validators\Utils;

  class ValidatorUtils {
    public static function int($value): bool {
      return is_int($value);
    }

    public static function float($value): bool {
      return is_float($value) || is_int($value);
    }

    public static function string($value): bool {
      return is_string($value);
    }

    public static function bool($value): bool {
      return is_bool($value);
    }

    public static function array($value): bool {
      return is_array($value);
    }

    public static function json($value): bool {
      if (is_array($value)) {
        return true;
      }
      if (!is_string($value)) {
        return false;
      }
      json_decode($value);
      return json_last_error() === JSON_ERROR_NONE;
    }

    public static function email(string $email, int $maxLength = 254): bool {
      if (strlen($email) > $maxLength) {
        return false;
      }
      $regex = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
      return (bool) preg_match($regex, $email);
    }

    public static function phone(string $phone): bool {
      $regex = '/^[0-9\(\)\-\+ ]{8,15}$/';
      return (bool) preg_match($regex, $phone);
    }

    public static function cep(string $cep): bool {
      $regex = '/^\d{5}-\d{3}$|^\d{8}$/';
      return (bool) preg_match($regex, $cep);
    }

    public static function cpf(string $cpf): bool {
      $regex = '/^\d{3}\.?\d{3}\.?\d{3}-?\d{2}$/';
      return (bool) preg_match($regex, $cpf);
    }

    public static function uuid(string $uuid): bool {
      $regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
      return (bool) preg_match($regex, $uuid);
    }

    public static function date(string $date, string $format = 'Y-m-d'): bool {
      $d = \DateTime::createFromFormat($format, $date);
      return $d && $d->format($format) === $date;
    }

    public static function datetime(string $datetime, string $format = 'Y-m-d H:i:s'): bool {
      $d = \DateTime::createFromFormat($format, $datetime);
      return $d && $d->format($format) === $datetime;
    }
  }