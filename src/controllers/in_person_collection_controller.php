<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Base\BaseController;
use App\Services\InPersonCollectionService;

class InPersonCollectionController extends BaseController {
  public function __construct(InPersonCollectionService $service) {
    parent::__construct($service);
  }

  /*
  GET /in-person-collection?id=<ID>
  GET /in-person-collection?collected_by=<CONTRACT_ID>
  GET /in-person-collection?collected_by=<CONTRACT_ID>&active=1
  GET /in-person-collection?material_type_id=<ID>
  GET /in-person-collection?collect_type=weight|unit
  GET /in-person-collection?active=1
  GET /in-person-collection
  */
  public function list(): void {
    $id = $this->get_query('id');
    $collected_by = $this->get_query('collected_by');
    $material_type_id = $this->get_query('material_type_id');
    $collect_type = $this->get_query('collect_type');
    $active = $this->get_query('active');

    if ($id !== null) {
      $result = $this->service->find_by_id($id);
      $this->handle_result($result);
      return;
    }

    if ($collected_by !== null && $active !== null) {
      $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
      $result = $active
        ? $this->service->find_active_by_contract($collected_by)
        : $this->service->find_by_contract($collected_by);
      $this->handle_result($result);
      return;
    }

    if ($collected_by !== null) {
      $result = $this->service->find_by_contract($collected_by);
      $this->handle_result($result);
      return;
    }

    if ($material_type_id !== null) {
      $result = $this->service->find_by_material((int) $material_type_id);
      $this->handle_result($result);
      return;
    }

    if ($collect_type !== null) {
      $result = $this->service->find_by_collect_type($collect_type);
      $this->handle_result($result);
      return;
    }

    if ($active !== null) {
      $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
      $result = $active
        ? $this->service->find_active()
        : $this->service->find_all();
      $this->handle_result($result);
      return;
    }

    $result = $this->service->find_all();
    $this->handle_result($result);
  }

  /*
  POST /in-person-collection
  */
  public function create(): void {
    $data = $this->get_body();
    if (empty($data)) {
      $this->error_response('Data not sent', 400);
      return;
    }

    $result = $this->service->create($data);
    $this->handle_result($result, 201);
  }
}