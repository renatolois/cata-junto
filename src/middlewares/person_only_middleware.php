<?php
declare(strict_types=1);

namespace App\Middlewares;

use Core\Base\BaseMiddleware;
use Core\Utils\AppConstants;

class PersonOnlyMiddleware extends BaseMiddleware {
  public function handle(array $context): array {
    if (AppConstants::RUN_MODE === 'debug') {
      error_log("PERSON_ONLY: context=" . json_encode($context));
    }

    $auth = $context['auth'] ?? null;

    if ($auth === null || ($auth['type'] ?? null) !== 'person') {
      if (AppConstants::RUN_MODE === 'debug') {
        error_log("PERSON_ONLY: rejecting, auth=" . json_encode($auth));
      }
      return ['ok' => false, 'status' => 403, 'error' => 'Forbidden'];
    }

    if (AppConstants::RUN_MODE === 'debug') {
      error_log("PERSON_ONLY: ok");
    }
    return ['ok' => true, 'context' => $context];
  }
}