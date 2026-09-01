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

// ============================================================
// CONFIGURAÇÕES
// ============================================================
require_once __DIR__ . '/core/utils/app_constants.php';  // ADICIONADO
require_once __DIR__ . '/core/utils/logger.php';

// ============================================================
// BANCO DE DADOS
// ============================================================
require_once __DIR__ . '/db/database.php';
require_once __DIR__ . '/db/adapters/mysql_adapter.php';

// ============================================================
// MODELS, REPOSITÓRIOS, SERVICES, CONTROLLERS
// ============================================================
require_once __DIR__ . '/models/person_model.php';
require_once __DIR__ . '/repositories/person_repository.php';
require_once __DIR__ . '/validators/person_validator.php';
require_once __DIR__ . '/services/person_service.php';
require_once __DIR__ . '/controllers/person_controller.php';

// ============================================================
// USAR AS CLASSES
// ============================================================
use Db\Database;

// ============================================================
// ROTEADOR
// ============================================================
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/';

try {
    $db = new Database();
    $db->connect([
        'host' => $env['DB_HOST'] ?? 'localhost',
        'port' => $env['DB_PORT'] ?? '3306',
        'dbname' => $env['DB_NAME'] ?? 'cooperativa',
        'user' => $env['DB_USER'] ?? 'root',
        'password' => $env['DB_PASSWORD'] ?? ''
    ]);

    $repository = new person_repository($db);
    $validator = new person_validator();
    $service = new person_service($repository, $validator);
    $controller = new person_controller($service);

    // Rotas
    if ($method === 'GET' && ($path === '/pessoas' || $path === '/person' || str_ends_with($path, '/pessoas'))) {
        $controller->index();
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Rota não encontrada. Use GET /pessoas']);
        exit;
    }
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