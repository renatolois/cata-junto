<?php
declare(strict_types=1);

// core/base
require __DIR__ . '/core/base/base_adapter.php';
require __DIR__ . '/core/base/base_model.php';
require __DIR__ . '/core/base/base_validator.php';
require __DIR__ . '/core/base/base_repository.php';
require __DIR__ . '/core/base/base_service.php';
require __DIR__ . '/core/base/base_controller.php';
require __DIR__ . '/core/base/base_middleware.php';
require __DIR__ . '/core/base/base_router.php';

// core/utils
require __DIR__ . '/core/utils/app_constants.php';
require __DIR__ . '/core/utils/logger.php';
require __DIR__ . '/core/utils/neutral_value.php';
require __DIR__ . '/core/utils/load_dotenv.php';

// db
require __DIR__ . '/db/database.php';
require __DIR__ . '/db/adapters/mysql_adapter.php';

// validators/utils
require __DIR__ . '/validators/utils/validator_utils.php';

// validators
require __DIR__ . '/validators/person_validator.php';
require __DIR__ . '/validators/collection_location_validator.php';
require __DIR__ . '/validators/contract_validator.php';
require __DIR__ . '/validators/in_person_collection_validator.php';
require __DIR__ . '/validators/residential_collection_validator.php';
require __DIR__ . '/validators/material_type_validator.php';
require __DIR__ . '/validators/prize_type_validator.php';
require __DIR__ . '/validators/prize_claim_validator.php';
require __DIR__ . '/validators/role_validator.php';

// models
require __DIR__ . '/models/person_model.php';
require __DIR__ . '/models/collection_location_model.php';
require __DIR__ . '/models/contract_model.php';
require __DIR__ . '/models/collection_model.php';
require __DIR__ . '/models/in_person_collection_model.php';
require __DIR__ . '/models/residential_collection_model.php';
require __DIR__ . '/models/material_type_model.php';
require __DIR__ . '/models/prize_type_model.php';
require __DIR__ . '/models/prize_claim_model.php';
require __DIR__ . '/models/role_model.php';

// repositories
require __DIR__ . '/repositories/person_repository.php';
require __DIR__ . '/repositories/collection_location_repository.php';
require __DIR__ . '/repositories/contract_repository.php';
require __DIR__ . '/repositories/role_repository.php';
require __DIR__ . '/repositories/residential_collection_repository.php';
require __DIR__ . '/repositories/material_type_repository.php';
require __DIR__ . '/repositories/prize_type_repository.php';
require __DIR__ . '/repositories/prize_claim_repository.php';
require __DIR__ . '/repositories/in_person_collection_repository.php';

// services
require __DIR__ . '/services/person_service.php';
require __DIR__ . '/services/collection_location_service.php';
require __DIR__ . '/services/contract_service.php';
require __DIR__ . '/services/residential_collection_service.php';
require __DIR__ . '/services/material_type_service.php';
require __DIR__ . '/services/prize_type_service.php';
require __DIR__ . '/services/prize_claim_service.php';
require __DIR__ . '/services/role_service.php';
require __DIR__ . '/services/in_person_collection_service.php';

// middlewares
require __DIR__ . '/middlewares/auth_middleware.php';
require __DIR__ . '/middlewares/authorization_middleware.php';
require __DIR__ . '/middlewares/collection_location_only_middleware.php';

// controllers
require __DIR__ . '/controllers/auth_controller.php';
require __DIR__ . '/controllers/person_controller.php';
require __DIR__ . '/controllers/collection_location_controller.php';
require __DIR__ . '/controllers/contract_controller.php';
require __DIR__ . '/controllers/in_person_collection_controller.php';
require __DIR__ . '/controllers/residential_collection_controller.php';
require __DIR__ . '/controllers/material_type_controller.php';
require __DIR__ . '/controllers/prize_type_controller.php';
require __DIR__ . '/controllers/prize_claim_controller.php';
require __DIR__ . '/controllers/role_controller.php';

// routers
require __DIR__ . '/routers/auth_router.php';
require __DIR__ . '/routers/person_router.php';
require __DIR__ . '/routers/collection_location_router.php';

use Core\Utils\EnvLoader;
use Db\Adapters\MysqlAdapter;

use App\Repositories\PersonRepository;
use App\Repositories\CollectionLocationRepository;
use App\Repositories\ContractRepository;
use App\Repositories\RoleRepository;
use App\Repositories\InPersonCollectionRepository;
use App\Repositories\ResidentialCollectionRepository;
use App\Repositories\MaterialTypeRepository;
use App\Repositories\PrizeTypeRepository;
use App\Repositories\PrizeClaimRepository;

use App\Validators\PersonValidator;
use App\Validators\CollectionLocationValidator;
use App\Validators\ContractValidator;
use App\Validators\InPersonCollectionValidator;
use App\Validators\ResidentialCollectionValidator;
use App\Validators\MaterialTypeValidator;
use App\Validators\PrizeTypeValidator;
use App\Validators\PrizeClaimValidator;

use App\Services\PersonService;
use App\Services\CollectionLocationService;
use App\Services\ContractService;
use App\Services\InPersonCollectionService;
use App\Services\ResidentialCollectionService;
use App\Services\MaterialTypeService;
use App\Services\PrizeTypeService;
use App\Services\PrizeClaimService;

use App\Controllers\AuthController;
use App\Controllers\PersonController;
use App\Controllers\CollectionLocationController;
use App\Controllers\ContractController;
use App\Controllers\InPersonCollectionController;
use App\Controllers\ResidentialCollectionController;
use App\Controllers\MaterialTypeController;
use App\Controllers\PrizeTypeController;
use App\Controllers\PrizeClaimController;

use App\Middlewares\AuthMiddleware;
use App\Middlewares\AuthorizationMiddleware;
use App\Middlewares\CollectionLocationOnlyMiddleware;

use App\Routers\AuthRouter;
use App\Routers\PersonRouter;
use App\Routers\CollectionLocationRouter;

EnvLoader::load();

$db = new MysqlAdapter();
$db->connect([]);

$person_repo                 = new PersonRepository($db);
$collection_location_repo    = new CollectionLocationRepository($db);
$contract_repo               = new ContractRepository($db);
$role_repo                   = new RoleRepository($db);
$in_person_collection_repo   = new InPersonCollectionRepository($db);
$residential_collection_repo = new ResidentialCollectionRepository($db);
$material_type_repo          = new MaterialTypeRepository($db);
$prize_type_repo             = new PrizeTypeRepository($db);
$prize_claim_repo            = new PrizeClaimRepository($db);

$person_validator                 = new PersonValidator();
$collection_location_validator    = new CollectionLocationValidator();
$contract_validator               = new ContractValidator();
$in_person_collection_validator   = new InPersonCollectionValidator();
$residential_collection_validator = new ResidentialCollectionValidator();
$material_type_validator          = new MaterialTypeValidator();
$prize_type_validator             = new PrizeTypeValidator();
$prize_claim_validator            = new PrizeClaimValidator();

$person_service                 = new PersonService($person_repo, $person_validator);
$collection_location_service    = new CollectionLocationService($collection_location_repo, $collection_location_validator);
$contract_service               = new ContractService($contract_repo, $contract_validator, $person_repo, $role_repo);
$in_person_collection_service   = new InPersonCollectionService($in_person_collection_repo, $in_person_collection_validator, $contract_repo, $material_type_repo);
$residential_collection_service = new ResidentialCollectionService($residential_collection_repo, $residential_collection_validator, $contract_repo, $material_type_repo, $collection_location_repo, $role_repo);
$material_type_service          = new MaterialTypeService($material_type_repo, $material_type_validator);
$prize_type_service             = new PrizeTypeService($prize_type_repo, $prize_type_validator);
$prize_claim_service            = new PrizeClaimService($prize_claim_repo, $prize_claim_validator, $collection_location_repo, $prize_type_repo);

$auth_controller                   = new AuthController($person_service, $collection_location_service, $person_service);
$person_controller                 = new PersonController($person_service);
$collection_location_controller    = new CollectionLocationController($collection_location_service);
$contract_controller               = new ContractController($contract_service);
$in_person_collection_controller   = new InPersonCollectionController($in_person_collection_service);
$residential_collection_controller = new ResidentialCollectionController($residential_collection_service);
$material_type_controller          = new MaterialTypeController($material_type_service);
$prize_type_controller             = new PrizeTypeController($prize_type_service);
$prize_claim_controller            = new PrizeClaimController($prize_claim_service);

$controllers = [
    'AuthController'                  => $auth_controller,
    'PersonController'                => $person_controller,
    'CollectionLocationController'    => $collection_location_controller,
    'ContractController'              => $contract_controller,
    'InPersonCollectionController'    => $in_person_collection_controller,
    'ResidentialCollectionController' => $residential_collection_controller,
    'MaterialTypeController'          => $material_type_controller,
    'PrizeTypeController'             => $prize_type_controller,
    'PrizeClaimController'            => $prize_claim_controller,
];

$auth_middleware    = new AuthMiddleware($person_repo, $collection_location_repo);
$admin_only         = new AuthorizationMiddleware($contract_repo, $role_repo, ['admin']);
$admin_or_member    = new AuthorizationMiddleware($contract_repo, $role_repo, ['admin', 'cooperado']);
$admin_or_deliverer = new AuthorizationMiddleware($contract_repo, $role_repo, ['admin', 'cooperado']);
$location_only      = new CollectionLocationOnlyMiddleware();

$auth_router                = new AuthRouter($controllers);
$person_router              = new PersonRouter($controllers);
$collection_location_router = new CollectionLocationRouter($controllers);

$auth_router->register_routes();

$person_router->register_routes(
    $auth_middleware,
    $admin_only,
    $admin_or_member,
    $admin_or_deliverer
);

$collection_location_router->register_routes(
    $auth_middleware,
    $location_only
);

$routers = [
    $auth_router,
    $person_router,
    $collection_location_router,
];

$method = $_SERVER['REQUEST_METHOD'];
$uri    = $_SERVER['REQUEST_URI'];

$handled = false;
foreach ($routers as $router) {
    if ($router->dispatch($method, $uri)) {
        $handled = true;
        break;
    }
}

if (!$handled) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Route not found'], JSON_UNESCAPED_UNICODE);
}