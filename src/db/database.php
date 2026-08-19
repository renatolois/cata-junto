<?php
declare(strict_types=1);

namespace Db;

require "../core/app_constants.php";
require "../core/base/base_adapter.php";

use \RuntimeException;
use Core\AppConstants;

class Database {
  private const DB_TYPE = AppConstants::DB_TYPE;
  private BaseAdapter $adapter;

  public function __construct() {
    switch(self::DB_TYPE) {
      case 'mysql':
        require_once "../db/adapters/mysql_adapter.php";
        $this->adapter = new MysqlAdapter();
        break;
      case 'postgresql':
        require_once "../db/adapters/postgresql_adapter.php";
        $this->adapter = new PostgresqlAdapter();
        break;
      case 'supabase':
        require_once "../db/adapters/supabase_adapter.php";
        $this->adapter = new SupabaseAdapter();
        break;
      default:
        throw new RuntimeException("Unrecognized database type: {$DB_TYPE}");
    }
  }

  public function __call(string $name, array $arguments) {
    if (method_exists($this->adapter, $name)) {
      return $this->adapter->$name(...$arguments);
    }
    
    throw new \BadMethodCallException(
      "Method {$name} does not exist in adapter " . get_class($this->adapter)
    );
  }
}