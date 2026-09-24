<?php
declare(strict_types=1);

namespace Db;

use Core\Utils\AppConstants;
use Core\Base\BaseAdapter;
use Db\Adapters\MysqlAdapter;
use Db\Adapters\PostgresqlAdapter;
use Db\Adapters\SupabaseAdapter;
use \RuntimeException;


class Database {
  private const DB_TYPE = AppConstants::DB_TYPE;
  private BaseAdapter $adapter;

  public function __construct() {
    switch(self::DB_TYPE) {
      case 'mysql':
        $this->adapter = new MysqlAdapter();
        break;
      case 'postgresql':
        $this->adapter = new PostgresqlAdapter();
        break;
      case 'supabase':
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