<?php 

declare(strict_types=1);

namespace App\Core\Utils;

class NeutralValue {
  private static ?NeutralValue $instance = null;

  private function __construct() {}

  public static function instance(): self {
    if (self::$instance === null) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  public function __toString(): string{
  	return 'Neutral';
  }
}