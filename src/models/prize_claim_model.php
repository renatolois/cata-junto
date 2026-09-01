<?php
declare(strict_types=1);

namespace App\Models;

use Core\Base\BaseModel;

class PrizeClaimModel extends BaseModel {
  public function __construct() {
    parent::__construct(
      attributes_and_types: [
        ['id', 'int'],
        ['claimed_by', 'uuid'],
        ['prize_type_id', 'int'],
        ['status', 'string'],
        ['claimed_at', 'datetime'],
        ['collected_at', 'datetime']
      ],
      fillables: [
        'claimed_by', 'prize_type_id', 'status', 
        'claimed_at', 'collected_at'
      ],
      hiddens: []
    );

    if (empty($this->status)) {
        $this->status = 'pending';
    }
  }

  public function get_id(): int {
    return (int) $this->id;
  }

  public function get_claimed_by(): string {
    return $this->claimed_by;
  }

  public function get_prize_type_id(): int {
    return (int) $this->prize_type_id;
  }

  public function get_status(): string {
    return $this->status;
  }

  public function get_claimed_at(): string {
    return $this->claimed_at;
  }

  public function get_collected_at(): ?string {
    return $this->collected_at ?? null;
  }

  public function set_claimed_by(string $claimed_by): void {
    $this->claimed_by = $claimed_by;
  }

  public function set_prize_type_id(int $prize_type_id): void {
    $this->prize_type_id = $prize_type_id;
  }

  public function set_status(string $status): void {
    $this->status = $status;
  }

  public function set_claimed_at(string $claimed_at): void {
    $this->claimed_at = $claimed_at;
  }

  public function set_collected_at(?string $collected_at): void {
    $this->collected_at = $collected_at;
  }

  public function complete(): void {
    if ($this->status !== 'pending') {
      throw new \Exception("Only pending claims can be completed.");
    }

    $this->status = 'finished';
    $this->collected_at = date('Y-m-d H:i:s');
  }

  public function cancel(): void {
    if ($this->status !== 'pending') {
      throw new \Exception("Cannot cancel a claim that is not pending.");
    }

    $this->status = 'cancelled';
  }

  public function reject(): void {
    if ($this->status !== 'pending') {
      throw new \Exception("Only pending claims can be rejected.");
    }

    $this->status = 'rejected';
  }
}
