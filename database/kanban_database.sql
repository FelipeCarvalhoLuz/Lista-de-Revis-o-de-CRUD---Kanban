CREATE DATABASE IF NOT EXISTS kanban_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE kanban_system;

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tarefas (
    id_tarefa INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    descricao TEXT NOT NULL,
    setor VARCHAR(50) NOT NULL,
    prioridade ENUM('baixa', 'media', 'alta') NOT NULL,
    data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('a_fazer', 'fazendo', 'pronto') NOT NULL DEFAULT 'a_fazer',
    
    CONSTRAINT fk_tarefa_usuario 
        FOREIGN KEY (id_usuario) 
        REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    
    INDEX idx_usuario (id_usuario),
    INDEX idx_status (status),
    INDEX idx_prioridade (prioridade),
    INDEX idx_data_cadastro (data_cadastro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE OR REPLACE VIEW vw_tarefas_completas AS
SELECT 
    t.id_tarefa,
    t.descricao,
    t.setor,
    t.prioridade,
    t.data_cadastro,
    t.status,
    u.id_usuario,
    u.nome AS usuario_nome,
    u.email AS usuario_email
FROM 
    tarefas t
INNER JOIN 
    usuarios u ON t.id_usuario = u.id_usuario
ORDER BY 
    FIELD(t.status, 'a_fazer', 'fazendo', 'pronto'),
    FIELD(t.prioridade, 'alta', 'media', 'baixa'),
    t.data_cadastro DESC;

CREATE OR REPLACE VIEW vw_estatisticas_usuario AS
SELECT 
    u.id_usuario,
    u.nome,
    u.email,
    COUNT(t.id_tarefa) AS total_tarefas,
    SUM(CASE WHEN t.status = 'a_fazer' THEN 1 ELSE 0 END) AS tarefas_a_fazer,
    SUM(CASE WHEN t.status = 'fazendo' THEN 1 ELSE 0 END) AS tarefas_fazendo,
    SUM(CASE WHEN t.status = 'pronto' THEN 1 ELSE 0 END) AS tarefas_prontas
FROM 
    usuarios u
LEFT JOIN 
    tarefas t ON u.id_usuario = t.id_usuario
GROUP BY 
    u.id_usuario, u.nome, u.email;