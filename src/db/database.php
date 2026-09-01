<?php
declare(strict_types=1);

namespace Db;

use Core\Utils\AppConstants;
use Core\Base\BaseAdapter;
use \RuntimeException;

class Database {
  private const DB_TYPE = AppConstants::DB_TYPE;
  private BaseAdapter $adapter;

  public function __construct() {
    switch(self::DB_TYPE) {
      case 'mysql':
        require_once __DIR__ . '/adapters/mysql_adapter.php';
        $this->adapter = new \Db\Adapters\MysqlAdapter();
        break;
      case 'postgresql':
        require_once __DIR__ . '/adapters/postgresql_adapter.php';
        $this->adapter = new \Db\Adapters\PostgresqlAdapter();
        break;
      case 'supabase':
        require_once __DIR__ . '/adapters/supabase_adapter.php';
        $this->adapter = new \Db\Adapters\SupabaseAdapter();
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