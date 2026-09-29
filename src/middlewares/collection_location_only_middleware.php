<?php
declare(strict_types=1);

namespace App\Middlewares;

use Core\Base\BaseMiddleware;

class CollectionLocationOnlyMiddleware extends BaseMiddleware {
  public function handle(array $context): array {
    $auth = $context['auth'] ?? null;

    if ($auth === null || ($auth['type'] ?? null) !== 'collection_location') {
      return ['ok' => false, 'status' => 403, 'error' => 'Forbidden'];
    }

    return ['ok' => true, 'context' => $context];
  }
}
