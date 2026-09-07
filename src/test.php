<?php
// /home/lois/projects/cata-junto/src/test.php

declare(strict_types=1);

// Autoload do Composer
require_once __DIR__ . '/../vendor/autoload.php';

// ============================================================
// LER .env DIRETAMENTE
// ============================================================
$env_file = __DIR__ . '/../.env';
$env = [];
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $value = trim($value, '"\'');
            $env[$key] = $value;
        }
    }
}

// ============================================================
// CLASSES BASE
// ============================================================
require_once __DIR__ . '/core/base/base_adapter.php';
require_once __DIR__ . '/core/base/base_repository.php';
require_once __DIR__ . '/core/base/base_model.php';
require_once __DIR__ . '/core/base/base_service.php';
require_once __DIR__ . '/core/base/base_validator.php';
require_once __DIR__ . '/core/base/base_controller.php';
require_once __DIR__ . '/core/base/base_router.php';

// ============================================================
// CONFIGURAÇÕES
// ============================================================
require_once __DIR__ . '/core/utils/app_constants.php';
require_once __DIR__ . '/core/utils/logger.php';
require_once __DIR__ . '/core/utils/neutral_value.php';
require_once __DIR__ . '/validators/utils/validator_utils.php';

// ============================================================
// BANCO DE DADOS
// ============================================================
require_once __DIR__ . '/db/database.php';
require_once __DIR__ . '/db/adapters/mysql_adapter.php';

// ============================================================
// MODELS, REPOSITÓRIOS, SERVICES, CONTROLLERS, ROUTERS
// ============================================================
require_once __DIR__ . '/models/person_model.php';
require_once __DIR__ . '/repositories/person_repository.php';
require_once __DIR__ . '/validators/person_validator.php';
require_once __DIR__ . '/services/person_service.php';
require_once __DIR__ . '/controllers/person_controller.php';
require_once __DIR__ . '/routers/person_router.php';

// ============================================================
// USAR AS CLASSES
// ============================================================
use Db\Database;
use App\Repositories\PersonRepository;
use App\Validators\PersonValidator;
use App\Services\PersonService;
use App\Controllers\PersonController;
use App\Routers\PersonRouter;

// ============================================================
// ROTEADOR
// ============================================================
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

// REMOVE O /src/test.php DO INÍCIO DA URI
$base_path = '/src/test.php';
if (str_starts_with($uri, $base_path)) {
    $uri = substr($uri, strlen($base_path));
}

// SE A URI FICOU VAZIA, DEFINE COMO '/'
if (empty($uri) || $uri === '') {
    $uri = '/';
}

// GARANTE QUE A URI COMEÇA COM '/'
if (!str_starts_with($uri, '/')) {
    $uri = '/' . $uri;
}

try {
    // Conecta ao banco
    $db = new Database();
    $db->connect([
        'host' => $env['DB_HOST'] ?? 'localhost',
        'port' => $env['DB_PORT'] ?? '3306',
        'dbname' => $env['DB_NAME'] ?? 'cooperativa',
        'user' => $env['DB_USER'] ?? 'root',
        'password' => $env['DB_PASSWORD'] ?? ''
    ]);

    // Setup das dependências
    $repository = new PersonRepository($db);
    $validator = new PersonValidator();
    $service = new PersonService($repository, $validator);
    $controller = new PersonController($service);

    // Registrar rotas no router
    $router = new PersonRouter($controller);

    // Dispatch da requisição
    $router->dispatch($method, $uri);
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Erro interno',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}