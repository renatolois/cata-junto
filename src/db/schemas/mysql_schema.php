<?php
declare(strict_types=1);

namespace Db\Schemas;

class MysqlSchema {
  public static function get_creation_string(): string {
    return <<<SQL
CREATE TABLE IF NOT EXISTS pessoa (
  id UUID PRIMARY KEY DEFAULT UUID_V4(),
  cpf VARCHAR(11) NOT NULL UNIQUE,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  email_verificado BOOLEAN DEFAULT FALSE,
  senha_hash VARCHAR(255) NOT NULL,
  telefone VARCHAR(20) NOT NULL,
  data_nascimento DATE NOT NULL,
  pontos_atuais INT DEFAULT 0,
  ativo BOOLEAN DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS funcao (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(50) NOT NULL UNIQUE,
  ativo BOOLEAN DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS vinculo (
  id UUID PRIMARY KEY DEFAULT UUID_V4(),
  id_pessoa UUID NOT NULL,
  id_funcao INT NOT NULL,
  id_respondido_por UUID,
  id_encerrado_por UUID,
  data_solicitacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  data_resposta TIMESTAMP NULL,
  status ENUM('pending', 'approved', 'rejected', 'cancelled', 'dismissed') NOT NULL DEFAULT 'pending',
  justificativa_resposta TEXT,
  justificativa_demissao TEXT,
  data_encerramento TIMESTAMP NULL,
  FOREIGN KEY (id_pessoa) REFERENCES pessoa(id) ON DELETE RESTRICT,
  FOREIGN KEY (id_funcao) REFERENCES funcao(id) ON DELETE RESTRICT,
  FOREIGN KEY (id_respondido_por) REFERENCES vinculo(id) ON DELETE SET NULL,
  FOREIGN KEY (id_encerrado_por) REFERENCES vinculo(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS ponto_coleta (
  id UUID PRIMARY KEY DEFAULT UUID_V4(),
  email_responsavel VARCHAR(255) NOT NULL UNIQUE,
  email_verificado BOOLEAN DEFAULT FALSE,
  senha_hash VARCHAR(255) NOT NULL,
  telefone_responsavel VARCHAR(20) NOT NULL,
  rua VARCHAR(200) NOT NULL,
  numero VARCHAR(20) NOT NULL,
  bairro VARCHAR(100) NOT NULL,
  complemento VARCHAR(100),
  cidade VARCHAR(100) NOT NULL,
  estado CHAR(2) NOT NULL,
  cep VARCHAR(10) NOT NULL,
  pontos_atuais INT DEFAULT 0,
  ativo BOOLEAN DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS tipo_material (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL UNIQUE,
  preco_por_peso DECIMAL(10,2) NOT NULL DEFAULT 0,
  pontos_por_peso INT NOT NULL DEFAULT 0,
  preco_por_unidade DECIMAL(10,2) NOT NULL DEFAULT 0,
  pontos_por_unidade INT NOT NULL DEFAULT 0,
  peso_ativo BOOLEAN DEFAULT TRUE,
  unidade_ativo BOOLEAN DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS coleta (
  id VARCHAR(36) PRIMARY KEY,
  id_vinculo UUID,
  id_tipo_material INT NOT NULL,
  data_coleta TIMESTAMP NULL,
  tipo_coleta ENUM('weight', 'unit') NOT NULL,
  quantidade DECIMAL(10,2) NOT NULL,
  observacao TEXT,
  ativo BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (id_vinculo) REFERENCES vinculo(id) ON DELETE SET NULL,
  FOREIGN KEY (id_tipo_material) REFERENCES tipo_material(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS coleta_residencial (
  id VARCHAR(36) PRIMARY KEY,
  id_ponto_coleta UUID NOT NULL,
  data_solicitacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  descricao TEXT,
  status ENUM('pending', 'completed', 'cancelled', 'rejected') NOT NULL DEFAULT 'pending',
  data_desativacao TIMESTAMP NULL,
  justificativa_desativacao TEXT,
  FOREIGN KEY (id) REFERENCES coleta(id) ON DELETE CASCADE,
  FOREIGN KEY (id_ponto_coleta) REFERENCES ponto_coleta(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS coleta_presencial (
  id VARCHAR(36) PRIMARY KEY,
  FOREIGN KEY (id) REFERENCES coleta(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tipo_premio (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  descricao VARCHAR(500) NOT NULL,
  custo_pontos INT NOT NULL,
  ativo BOOLEAN DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS reivindicacao_premio (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_pessoa UUID NOT NULL,
  id_tipo_premio INT NOT NULL,
  id_ponto_coleta UUID NOT NULL,
  status ENUM('pending', 'finished', 'cancelled', 'rejected') NOT NULL DEFAULT 'pending',
  data_solicitacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  data_realizacao TIMESTAMP NULL,
  FOREIGN KEY (id_pessoa) REFERENCES pessoa(id) ON DELETE RESTRICT,
  FOREIGN KEY (id_tipo_premio) REFERENCES tipo_premio(id) ON DELETE RESTRICT,
  FOREIGN KEY (id_ponto_coleta) REFERENCES ponto_coleta(id) ON DELETE RESTRICT
);

CREATE INDEX idx_pessoa_email ON pessoa(email);
CREATE INDEX idx_pessoa_cpf ON pessoa(cpf);
CREATE INDEX idx_pessoa_ativo ON pessoa(ativo);
CREATE INDEX idx_vinculo_pessoa ON vinculo(id_pessoa);
CREATE INDEX idx_vinculo_status ON vinculo(status);
CREATE INDEX idx_vinculo_respondido_por ON vinculo(id_respondido_por);
CREATE INDEX idx_vinculo_encerrado_por ON vinculo(id_encerrado_por);
CREATE INDEX idx_pontocoleta_email ON ponto_coleta(email_responsavel);
CREATE INDEX idx_pontocoleta_cep ON ponto_coleta(cep);
CREATE INDEX idx_pontocoleta_ativo ON ponto_coleta(ativo);
CREATE INDEX idx_material_nome ON tipo_material(nome);
CREATE INDEX idx_coleta_vinculo ON coleta(id_vinculo);
CREATE INDEX idx_coleta_material ON coleta(id_tipo_material);
CREATE INDEX idx_coleta_data ON coleta(data_coleta);
CREATE INDEX idx_coleta_residencial_status ON coleta_residencial(status);
CREATE INDEX idx_coleta_residencial_ponto ON coleta_residencial(id_ponto_coleta);
CREATE INDEX idx_reivindicacao_status ON reivindicacao_premio(status);
CREATE INDEX idx_reivindicacao_pessoa ON reivindicacao_premio(id_pessoa);
CREATE INDEX idx_reivindicacao_premio ON reivindicacao_premio(id_tipo_premio);
CREATE INDEX idx_tipo_premio_ativo ON tipo_premio(ativo);
SQL;
  }
}