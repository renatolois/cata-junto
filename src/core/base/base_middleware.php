<?php
declare(strict_types=1);

namespace Core\Base;

class BaseMiddleware {
  protected static function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }

  protected static function base64url_decode(string $data): string {
    $remainder = strlen($data) % 4;
    if ($remainder) {
      $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
  }

  public static function encode_jwt(array $payload, string $secret): string {
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
    $payload_json = json_encode($payload);

    $header_encoded = static::base64url_encode($header);
    $payload_encoded = static::base64url_encode($payload_json);
    $header_payload = $header_encoded . '.' . $payload_encoded;

    $signature = hash_hmac('sha256', $header_payload, $secret, true);
    $signature_encoded = static::base64url_encode($signature);

    return $header_payload . '.' . $signature_encoded;
  }

  public static function decode_jwt(string $token, string $secret): array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
      throw new \Exception('Invalid token format');
    }

    [$header_encoded, $payload_encoded, $signature_encoded] = $parts;

    $header = json_decode(static::base64url_decode($header_encoded), true);
    if (!is_array($header) || ($header['alg'] ?? null) !== 'HS256') {
      throw new \Exception('Invalid token algorithm');
    }

    $header_payload = $header_encoded . '.' . $payload_encoded;
    $expected_signature = hash_hmac('sha256', $header_payload, $secret, true);
    $actual_signature = static::base64url_decode($signature_encoded);

    if (!hash_equals($expected_signature, $actual_signature)) {
      throw new \Exception('Invalid signature');
    }

    $payload = json_decode(static::base64url_decode($payload_encoded), true);
    if (!is_array($payload)) {
      throw new \Exception('Invalid token payload');
    }

    if (isset($payload['exp']) && time() > (int) $payload['exp']) {
      throw new \Exception('Token expired');
    }

    return $payload;
  }

  public function handle(array $context): array {
    return ['ok' => true, 'context' => $context];
  }
}