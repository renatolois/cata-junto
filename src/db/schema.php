<?php
declare(strict_types=1);

namespace Db;

require_once __DIR__ . '/../core/Utils/app_constants.php';
require_once __DIR__ . '/../core/utils/Logger.php';
require_once __DIR__ . '/Database.php';

use Core\AppConstants;
use Core\Utils\Logger;
use Db\Schemas\MysqlSchema;
use Db\Schemas\SupabaseSchema;
use RuntimeException;

class Schema {
  
  public static function initializeDatabase(): void {
    $DB_TYPE = AppConstants::DB_TYPE;
    $schema = null;
    
    switch ($DB_TYPE) {
      case 'mysql':
				require_once __DIR__ . '/schemas/mysql_schema.php';
        $creation_string = MysqlSchema::get_creation_string();
        break;
      case 'postgresql':
				require_once __DIR__ . '/schemas/postgresql_schema.php';
        $creation_string = PostgresSchema::get_creation_string();
        break;
			case 'supabase':
				require_once __DIR__ . '/schemas/supabase_schema.php';
        $creation_string = SupabaseSchema::get_creation_string();
        break;
      default:
        throw new RuntimeException("Tipo de banco de dados não reconhecido: {$DB_TYPE}");
    }
    
    if (empty($creation_string)) {
      Logger::info("Schema vazio para {$DB_TYPE}, nenhuma acao necessaria");
      return;
    }
    
    Logger::info("Inicializando schema no {$DB_TYPE}");
    
    $statements = self::splitSql($schema);
    $db = new Database();
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
      try {
        $db->query($statement);
        Logger::debug("Executado: " . substr($statement, 0, 60));
        $success++;
      } catch (RuntimeException $e) {
        Logger::error("Erro ao executar SQL", [
          'sql' => substr($statement, 0, 100),
          'error' => $e->getMessage()
        ]);
        $errors++;
      }
    }
    
    Logger::info("Schema {$DB_TYPE} inicializado", [
      'success' => $success,
      'errors' => $errors
    ]);
  }
  
  private static function splitSql(string $sql): array {
    $sql = preg_replace('/--[^\n]*\n/', "\n", $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    return array_values($statements);
  }
}