<?php
declare(strict_types=1);

namespace Db\Schemas;

class MysqlSchema {
  public static function get_creation_string(): string {
    return <<<SQL
CREATE TABLE IF NOT EXISTS person (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  cpf VARCHAR(11) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  verified_email BOOLEAN DEFAULT FALSE,
  password_hash VARCHAR(255) NOT NULL,
  phone_number VARCHAR(20) NOT NULL,
  birth_date DATE NOT NULL,
  current_points INT DEFAULT 0,
  active BOOLEAN DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS role (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  active BOOLEAN DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS contract (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  person_id CHAR(36) NOT NULL,
  role_id INT NOT NULL,
  responded_by_id CHAR(36) NULL,
  contract_end_by CHAR(36) NULL,
  requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  responded_at TIMESTAMP NULL,
  status ENUM('pending', 'approved', 'rejected', 'cancelled', 'dismissed') NOT NULL DEFAULT 'pending',
  response_justification TEXT,
  dismissal_justification TEXT,
  contract_end_at TIMESTAMP NULL,
  FOREIGN KEY (person_id) REFERENCES person(id) ON DELETE RESTRICT,
  FOREIGN KEY (role_id) REFERENCES role(id) ON DELETE RESTRICT,
  FOREIGN KEY (responded_by_id) REFERENCES contract(id) ON DELETE SET NULL,
  FOREIGN KEY (contract_end_by) REFERENCES contract(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS collection_location (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  responsable_email VARCHAR(255) NOT NULL UNIQUE,
  verified_email BOOLEAN DEFAULT FALSE,
  password_hash VARCHAR(255) NOT NULL,
  responsable_phone_number VARCHAR(20) NOT NULL,
  street VARCHAR(200) NOT NULL,
  number VARCHAR(20) NOT NULL,
  neighborhood VARCHAR(100) NOT NULL,
  complement VARCHAR(100),
  city VARCHAR(100) NOT NULL,
  state CHAR(2) NOT NULL,
  cep VARCHAR(10) NOT NULL,
  current_points INT DEFAULT 0,
  active BOOLEAN DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS material_type (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  price_per_weight DECIMAL(10,2) NOT NULL DEFAULT 0,
  points_per_weight INT NOT NULL DEFAULT 0,
  price_per_unit DECIMAL(10,2) NOT NULL DEFAULT 0,
  points_per_unit INT NOT NULL DEFAULT 0,
  weight_active BOOLEAN DEFAULT TRUE,
  unit_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS prize_type (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(500) NOT NULL,
  cost_points INT NOT NULL,
  active BOOLEAN DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS residential_collection (
  id VARCHAR(36) PRIMARY KEY,
  collected_by CHAR(36) NULL,
  material_type_id INT NOT NULL,
  collected_at TIMESTAMP NULL,
  collect_type ENUM('weight', 'unit') NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  observation TEXT,
  active BOOLEAN DEFAULT TRUE,
  collection_location_id CHAR(36) NOT NULL,
  requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  description TEXT,
  status ENUM('pending', 'completed', 'cancelled', 'rejected') NOT NULL DEFAULT 'pending',
  deactivation_at TIMESTAMP NULL,
  deactivation_justification TEXT,
  FOREIGN KEY (collected_by) REFERENCES contract(id) ON DELETE SET NULL,
  FOREIGN KEY (material_type_id) REFERENCES material_type(id) ON DELETE RESTRICT,
  FOREIGN KEY (collection_location_id) REFERENCES collection_location(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS in_person_collection (
  id VARCHAR(36) PRIMARY KEY,
  collected_by CHAR(36) NULL,
  material_type_id INT NOT NULL,
  collected_at TIMESTAMP NULL,
  collect_type ENUM('weight', 'unit') NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  paid_value FLOAT NOT NULL,
  observation TEXT,
  active BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (collected_by) REFERENCES contract(id) ON DELETE SET NULL,
  FOREIGN KEY (material_type_id) REFERENCES material_type(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS prize_claim (
  id INT AUTO_INCREMENT PRIMARY KEY,
  claimed_by CHAR(36) NOT NULL,
  prize_type_id INT NOT NULL,
  status ENUM('pending', 'finished', 'cancelled', 'rejected') NOT NULL DEFAULT 'pending',
  claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  collected_at TIMESTAMP NULL,
  FOREIGN KEY (claimed_by) REFERENCES collection_location(id) ON DELETE RESTRICT,
  FOREIGN KEY (prize_type_id) REFERENCES prize_type(id) ON DELETE RESTRICT
);

CREATE INDEX idx_person_email ON person(email);
CREATE INDEX idx_person_cpf ON person(cpf);
CREATE INDEX idx_person_active ON person(active);
CREATE INDEX idx_contract_person ON contract(person_id);
CREATE INDEX idx_contract_status ON contract(status);
CREATE INDEX idx_contract_responded_by ON contract(responded_by_id);
CREATE INDEX idx_contract_end_by ON contract(contract_end_by);
CREATE INDEX idx_collection_location_email ON collection_location(responsable_email);
CREATE INDEX idx_collection_location_cep ON collection_location(cep);
CREATE INDEX idx_collection_location_active ON collection_location(active);
CREATE INDEX idx_material_type_name ON material_type(name);
CREATE INDEX idx_residential_collection_collected_by ON residential_collection(collected_by);
CREATE INDEX idx_residential_collection_material ON residential_collection(material_type_id);
CREATE INDEX idx_residential_collection_status ON residential_collection(status);
CREATE INDEX idx_residential_collection_location ON residential_collection(collection_location_id);
CREATE INDEX idx_in_person_collection_collected_by ON in_person_collection(collected_by);
CREATE INDEX idx_in_person_collection_material ON in_person_collection(material_type_id);
CREATE INDEX idx_prize_claim_status ON prize_claim(status);
CREATE INDEX idx_prize_claim_claimed_by ON prize_claim(claimed_by);
CREATE INDEX idx_prize_claim_prize_type ON prize_claim(prize_type_id);
CREATE INDEX idx_prize_type_active ON prize_type(active);
SQL;
  }
}