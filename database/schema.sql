-- Criação do banco de dados e da tabela usada pela API

CREATE DATABASE IF NOT EXISTS projwebservices
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE projwebservices;

CREATE TABLE IF NOT EXISTS alunos
(
    codigo   INT PRIMARY KEY AUTO_INCREMENT,
    nome     VARCHAR(50) NOT NULL,
    email    VARCHAR(50) NOT NULL,
    telefone VARCHAR(25) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- dados de exemplo (com código fixo e IGNORE: rodar o script de novo não duplica)
INSERT IGNORE INTO alunos (codigo, nome, email, telefone) VALUES (1, 'Lucio', 'lucio@teste.com', '123456789');
INSERT IGNORE INTO alunos (codigo, nome, email, telefone) VALUES (2, 'Nelson', 'Nelson@teste.com', '323456788');
INSERT IGNORE INTO alunos (codigo, nome, email, telefone) VALUES (3, 'Cao Ji Kan', 'Caojikan@teste.com', '988456788');
