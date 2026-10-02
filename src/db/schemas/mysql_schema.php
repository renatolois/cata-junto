<?php
declare(strict_types=1);

namespace Db\Schemas;

class MysqlSchema {
  public static function get_creation_string(): string {
    # if you want, change by another hash. the password for the used hash is "admin123"
    return <<<SQL
CREATE DATABASE IF NOT EXISTS cata_junto
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE cata_junto;

CREATE TABLE IF NOT EXISTS person (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  cpf VARCHAR(11) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  verified_email BOOLEAN NOT NULL DEFAULT FALSE,
  password_hash VARCHAR(255) NOT NULL,
  phone_number VARCHAR(20) NOT NULL,
  birth_date DATE NOT NULL,
  current_points INT NOT NULL DEFAULT 0,
  active BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS role (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  active BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS contract (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  person_id CHAR(36) NOT NULL,
  role_id INT NOT NULL,
  responded_by_id CHAR(36) NULL,
  contract_end_by CHAR(36) NULL,
  requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
  verified_email BOOLEAN NOT NULL DEFAULT FALSE,
  password_hash VARCHAR(255) NOT NULL,
  responsable_phone_number VARCHAR(20) NOT NULL,
  street VARCHAR(200) NOT NULL,
  number VARCHAR(20) NOT NULL,
  neighborhood VARCHAR(100) NOT NULL,
  complement VARCHAR(100),
  city VARCHAR(100) NOT NULL,
  state CHAR(2) NOT NULL,
  cep VARCHAR(10) NOT NULL,
  current_points INT NOT NULL DEFAULT 0,
  active BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS material_type (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  price_per_weight DECIMAL(10,2) NOT NULL DEFAULT 0,
  points_per_weight INT NOT NULL DEFAULT 0,
  price_per_unit DECIMAL(10,2) NOT NULL DEFAULT 0,
  points_per_unit INT NOT NULL DEFAULT 0,
  weight_active BOOLEAN NOT NULL DEFAULT TRUE,
  unit_active BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS prize_type (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(500) NOT NULL,
  cost_points INT NOT NULL,
  active BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS residential_collection (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  collected_by CHAR(36) NULL,
  material_type_id INT NOT NULL,
  collected_at TIMESTAMP NULL,
  collect_type ENUM('weight', 'unit') NOT NULL,
  quantity DECIMAL(10,2) NULL,
  observation TEXT,
  active BOOLEAN NOT NULL DEFAULT TRUE,
  collection_location_id CHAR(36) NOT NULL,
  requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  description TEXT,
  status ENUM('pending', 'completed', 'cancelled', 'rejected') NOT NULL DEFAULT 'pending',
  rejected_by CHAR(36) NULL,
  deactivation_at TIMESTAMP NULL,
  deactivation_justification TEXT,
  conceded_points INT NOT NULL DEFAULT 0,
  FOREIGN KEY (collected_by) REFERENCES contract(id) ON DELETE SET NULL,
  FOREIGN KEY (rejected_by) REFERENCES contract(id) ON DELETE SET NULL,
  FOREIGN KEY (material_type_id) REFERENCES material_type(id) ON DELETE RESTRICT,
  FOREIGN KEY (collection_location_id) REFERENCES collection_location(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS in_person_collection (
  id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
  collected_by CHAR(36) NULL,
  material_type_id INT NOT NULL,
  collected_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  collect_type ENUM('weight', 'unit') NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  paid_value DECIMAL(10,2) NOT NULL,
  observation TEXT,
  active BOOLEAN NOT NULL DEFAULT TRUE,
  FOREIGN KEY (collected_by) REFERENCES contract(id) ON DELETE SET NULL,
  FOREIGN KEY (material_type_id) REFERENCES material_type(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS prize_claim (
  id INT AUTO_INCREMENT PRIMARY KEY,
  claimed_by CHAR(36) NOT NULL,
  prize_type_id INT NOT NULL,
  status ENUM('pending', 'completed', 'cancelled', 'rejected') NOT NULL DEFAULT 'pending',
  requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  realized_at TIMESTAMP NULL,
  FOREIGN KEY (claimed_by) REFERENCES collection_location(id) ON DELETE RESTRICT,
  FOREIGN KEY (prize_type_id) REFERENCES prize_type(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS person_password_reset (
  id INT AUTO_INCREMENT PRIMARY KEY,
  person_id CHAR(36) NOT NULL,
  email VARCHAR(255) NOT NULL,
  code VARCHAR(6) NOT NULL,
  expires_at TIMESTAMP NOT NULL,
  active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  used_at TIMESTAMP NULL,
  FOREIGN KEY (person_id) REFERENCES person(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS collection_location_password_reset (
  id INT AUTO_INCREMENT PRIMARY KEY,
  collection_location_id CHAR(36) NOT NULL,
  email VARCHAR(255) NOT NULL,
  code VARCHAR(6) NOT NULL,
  expires_at TIMESTAMP NOT NULL,
  active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  used_at TIMESTAMP NULL,
  FOREIGN KEY (collection_location_id) REFERENCES collection_location(id) ON DELETE CASCADE
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
CREATE INDEX idx_person_reset_active ON person_password_reset(person_id, active);
CREATE INDEX idx_person_reset_code ON person_password_reset(code, active);
CREATE INDEX idx_location_reset_active ON collection_location_password_reset(collection_location_id, active);
CREATE INDEX idx_location_reset_code ON collection_location_password_reset(code, active);

INSERT INTO role (name, active) VALUES ('admin', 1), ('member', 1);

INSERT INTO person
  (id, cpf, name, email, verified_email, password_hash, phone_number, birth_date, current_points, active)
VALUES
  (UUID(), '11111111111', 'Admin', 'admin@catajunto.local', 1, 'temp', '21999999991', '1990-01-01', 0, 1);

UPDATE person
SET password_hash = '$2y$12$0eec/8yrwql8HyBVIwZHKO6necqJyR0qkFG6BsGe.XSqPuTyrBwxi'
WHERE email = 'admin@catajunto.local';

INSERT INTO contract (id, person_id, role_id, requested_at, responded_at, status)
SELECT UUID(), p.id, r.id, NOW(), NOW(), 'approved'
FROM person p, role r
WHERE p.email = 'admin@catajunto.local' AND r.name = 'admin';
SQL;
  }
}
