<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\CollectionModel;

class InPersonCollectionModel extends CollectionModel {
  
  public function __construct() {
    parent::__construct();

    $this->merge_attributes_and_types(
      attributes_and_types: [
        ['paid_value', 'float']
      ]
    );

    $this->merge_fillables(
      fillables: [
        'paid_value'
      ]
    );
  }

  public function get_paid_value(): float {
    return (float) ($this->paid_value ?? 0.0);
  }

  public function set_paid_value(float $paid_value): void {
    $this->paid_value = round($paid_value, 2);
  }
}