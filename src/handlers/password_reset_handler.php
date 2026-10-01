<?php
declare(strict_types=1);

namespace App\Handlers;

use Core\Base\BaseController;
use Core\Base\BaseAdapter;
use Core\Base\BaseService;
use Core\Utils\AppConstants;
use Core\Utils\EnvLoader;
use Core\Utils\Logger;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Exception;

class PasswordResetHandler extends BaseController {

  private BaseAdapter $db;

  public function __construct(BaseService $service, BaseAdapter $db) {
    parent::__construct($service);
    $this->db = $db;
  }

  public function request_person(): void   { $this->request_reset('person'); }
  public function request_location(): void { $this->request_reset('collection_location'); }
  public function reset_person(): void     { $this->reset('person'); }
  public function reset_location(): void   { $this->reset('collection_location'); }

  private function request_reset(string $type): void {
    $data = $this->get_body();

    $this->debug('request_reset: start', ['type' => $type, 'body' => $data]);

    if (empty($data['email'])) {
      $this->debug('request_reset: missing email');
      $this->error_response('email is required', 400);
      return;
    }
    $email = (string) $data['email'];

    try {
      $cfg = $this->config_for($type);
      $this->debug('request_reset: config', $cfg);

      $rows = $this->db->select($cfg['table'], [$cfg['email_col'] => $email]);
      $this->debug('request_reset: owner lookup', ['rows' => $rows]);

      if (empty($rows) || !isset($rows[0]['id'])) {
        $this->debug('request_reset: owner not found, returning generic message');
        $this->json_response(['message' => 'If the email exists, a code was sent.']);
        return;
      }
      $owner_id = $rows[0]['id'];

      $previous = $this->db->select($cfg['reset_table'], [
        $cfg['email_col'] => $email,
        'active'          => 1,
      ]);
      $this->debug('request_reset: deactivating previous resets', ['count' => count($previous)]);

      foreach ($previous as $p) {
        $this->db->update($cfg['reset_table'], $p['id'], ['active' => 0]);
      }

      $code    = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
      $expires = date('Y-m-d H:i:s', time() + 1800);

      $this->debug('request_reset: inserting new reset', [
        'owner_id'   => $owner_id,
        'email'      => $email,
        'code'       => $code,
        'expires_at' => $expires,
      ]);

      $this->db->insert($cfg['reset_table'], [
        $cfg['fk_col']    => $owner_id,
        $cfg['email_col'] => $email,
        'code'            => $code,
        'expires_at'      => $expires,
        'active'          => 1,
      ]);

      $this->send_email($email, $code);

      $this->json_response(['message' => 'If the email exists, a code was sent.']);
    } catch (Exception $e) {
      Logger::error('Error requesting password reset', [
        'type' => $type, 'email' => $email, 'error' => $e->getMessage(),
      ]);
      $this->error_response(
        AppConstants::RUN_MODE === 'debug' ? $e->getMessage() : 'unexpected_error',
        500
      );
    }
  }

  private function reset(string $type): void {
    $data = $this->get_body();

    $this->debug('reset: start', ['type' => $type, 'body' => $data]);

    foreach (['email', 'password', 'code'] as $f) {
      if (empty($data[$f])) {
        $this->debug('reset: missing field', ['field' => $f]);
        $this->error_response("{$f} is required", 400);
        return;
      }
    }

    $email    = (string) $data['email'];
    $password = (string) $data['password'];
    $code     = (string) $data['code'];

    try {
      $cfg = $this->config_for($type);
      $this->debug('reset: config', $cfg);

      $rows = $this->db->select($cfg['reset_table'], [
        $cfg['email_col'] => $email,
        'code'            => $code,
      ]);
      $this->debug('reset: candidate rows', ['rows' => $rows]);

      $reset = null;
      $now   = time();
      foreach ($rows as $r) {
        if ((int) $r['active'] !== 1) {
          $this->debug('reset: skipping inactive', ['id' => $r['id']]);
          continue;
        }
        if (strtotime($r['expires_at']) < $now) {
          $this->debug('reset: skipping expired', ['id' => $r['id'], 'expires_at' => $r['expires_at']]);
          continue;
        }
        $reset = $r;
        break;
      }

      if ($reset === null) {
        $this->debug('reset: no valid reset found');
        $this->error_response('Invalid or expired code', 422);
        return;
      }

      $this->debug('reset: valid reset found', ['id' => $reset['id']]);

      $owner = $this->db->select($cfg['table'], [$cfg['email_col'] => $email]);
      if (empty($owner) || !isset($owner[0]['id'])) {
        $this->debug('reset: owner not found');
        $this->error_response('Invalid or expired code', 422);
        return;
      }
      $owner_id = $owner[0]['id'];

      $hash = password_hash($password, PASSWORD_DEFAULT);
      $this->debug('reset: updating password', ['owner_id' => $owner_id]);
      $this->db->update($cfg['table'], $owner_id, [$cfg['hash_col'] => $hash]);

      $this->debug('reset: deactivating used reset', ['id' => $reset['id']]);
      $this->db->update($cfg['reset_table'], $reset['id'], [
        'active'  => 0,
        'used_at' => date('Y-m-d H:i:s'),
      ]);

      $others = $this->db->select($cfg['reset_table'], [
        $cfg['email_col'] => $email,
        'active'          => 1,
      ]);
      foreach ($others as $o) {
        $this->db->update($cfg['reset_table'], $o['id'], ['active' => 0]);
      }
      $this->debug('reset: deactivated other active resets', ['count' => count($others)]);

      $this->json_response(['message' => 'Password reset successfully']);
    } catch (Exception $e) {
      Logger::error('Error resetting password', [
        'type' => $type, 'email' => $email, 'error' => $e->getMessage(),
      ]);
      $this->error_response(
        AppConstants::RUN_MODE === 'debug' ? $e->getMessage() : 'unexpected_error',
        500
      );
    }
  }

  private function config_for(string $type): array {
    if ($type === 'person') {
      return [
        'table'       => 'person',
        'email_col'   => 'email',
        'fk_col'      => 'person_id',
        'reset_table' => 'person_password_reset',
        'hash_col'    => 'password_hash',
      ];
    }
    return [
      'table'       => 'collection_location',
      'email_col'   => 'responsable_email',
      'fk_col'      => 'collection_location_id',
      'reset_table' => 'collection_location_password_reset',
      'hash_col'    => 'password_hash',
    ];
  }

  private function send_email(string $to, string $code): void {
    $this->debug('send_email: start', ['to' => $to, 'code' => $code]);

    $from     = EnvLoader::get('google_email');
    $password = EnvLoader::get('google_app_password');

    $this->debug('send_email: credentials check', [
      'has_from'     => !empty($from),
      'has_password' => !empty($password),
    ]);

    if (empty($from) || empty($password)) {
      Logger::error('PASSWORD_RESET_EMAIL: missing google_email or google_app_password');
      return;
    }

    try {
      $mailer = new PHPMailer(true);

      if (AppConstants::RUN_MODE === 'debug') {
        $mailer->SMTPDebug   = 3;
        $mailer->Debugoutput = function (string $str, int $level) {
          Logger::error('PWRESET_SMTP', ['level' => $level, 'msg' => trim($str)]);
        };
      }

      $mailer->isSMTP();
      $mailer->Host       = 'smtp.gmail.com';
      $mailer->SMTPAuth   = true;
      $mailer->Username   = $from;
      $mailer->Password   = $password;
      $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mailer->Port       = 587;

      $mailer->setFrom($from, EnvLoader::get('mailer_from_name') ?? 'Cata Junto');
      $mailer->addAddress($to);
      $mailer->isHTML(true);
      $mailer->Subject = 'Password reset code';
      $mailer->Body    = "<p>Your password reset code is: <strong>{$code}</strong></p>"
                       . "<p>It expires in 30 minutes.</p>";
      $mailer->AltBody = "Your password reset code is: {$code}\nIt expires in 30 minutes.";

      $mailer->send();

      $this->debug('send_email: sent ok', ['to' => $to]);
    } catch (PHPMailerException $e) {
      Logger::error('PASSWORD_RESET_EMAIL: failed to send', [
        'to'    => $to,
        'error' => $e->getMessage(),
      ]);
      $this->debug('send_email: exception', ['error' => $e->getMessage()]);
    }
  }

  private function debug(string $message, array $context = []): void {
    if (AppConstants::RUN_MODE === 'debug') {
      Logger::error('PWRESET: ' . $message, $context);
    }
  }
}