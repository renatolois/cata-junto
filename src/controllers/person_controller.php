<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Base\BaseController;
use App\Services\PersonService;

class PersonController extends BaseController {
  private PersonService $service;

  public function __construct() {
    parent::__construct();
    $this->service = new PersonService(
      new \App\Repositories\PersonRepository(/* dependências */),
      new \App\Validators\PersonValidator()
    );
  }

  /**
   * GET /person?active=true&email=<EMAIL>&cpf=<CPF>
   */
  public function index(): void {
    $active = $this->get_query('active');
    $email = $this->get_query('email');
    $cpf = $this->get_query('cpf');

    if ($email !== null) {
      $pessoa = $this->service->find_by_email($email);
      $this->json_response($pessoa ? [$pessoa] : []);
      return;
    }
    if ($cpf !== null) {
      $pessoa = $this->service->find_by_cpf($cpf);
      $this->json_response($pessoa ? [$pessoa] : []);
      return;
    }
    if ($active !== null) {
      $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
      $pessoas = $active ? $this->service->find_all_active() : $this->service->find_all();
      $this->json_response($pessoas);
      return;
    }

    // Sem filtros, retorna todos
    $pessoas = $this->service->find_all();
    $this->json_response($pessoas);
  }

}