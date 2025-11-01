CREATE SCHEMA IF NOT EXISTS task_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE task_manager;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    last_login DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uk_users_email UNIQUE (email),
    INDEX idx_users_email (email),
    INDEX idx_users_created_at (created_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Tabela de usuários do sistema';

CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT 'Criador/Owner do projeto',
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    attachment_path VARCHAR(500) NULL COMMENT 'Caminho do arquivo PDF ou Imagem',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_projects_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_projects_user_id (user_id),
    INDEX idx_projects_start_date (start_date),
    INDEX idx_projects_end_date (end_date),
    INDEX idx_projects_created_at (created_at),
    INDEX idx_projects_dates (start_date, end_date)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Tabela de projetos';

ALTER TABLE projects
ADD CONSTRAINT chk_projects_dates CHECK (end_date >= start_date);

CREATE TABLE IF NOT EXISTS tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NULL COMMENT 'NULL = tarefa pessoal (sem projeto)',
    user_id INT UNSIGNED NOT NULL COMMENT 'Responsável pela tarefa',
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    due_date DATE NOT NULL COMMENT 'Data de vencimento',
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    status ENUM(
        'pending',
        'in_progress',
        'completed'
    ) NOT NULL DEFAULT 'pending',
    attachment_path VARCHAR(500) NULL COMMENT 'Caminho do arquivo PDF',
    completed_at DATETIME NULL COMMENT 'Data/hora de conclusão',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tasks_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_tasks_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_tasks_project_id (project_id),
    INDEX idx_tasks_user_id (user_id),
    INDEX idx_tasks_status (status),
    INDEX idx_tasks_priority (priority),
    INDEX idx_tasks_due_date (due_date),
    INDEX idx_tasks_created_at (created_at),
    INDEX idx_tasks_user_status (user_id, status),
    INDEX idx_tasks_user_priority (user_id, priority),
    INDEX idx_tasks_user_due_date (user_id, due_date),
    INDEX idx_tasks_status_due_date (status, due_date),
    INDEX idx_tasks_project_status (project_id, status)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Tabela de tarefas';

ALTER TABLE tasks
MODIFY COLUMN priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium' COMMENT 'Prioridade: low=baixa, medium=média, high=alta';

ALTER TABLE tasks
MODIFY COLUMN status ENUM(
    'pending',
    'in_progress',
    'completed'
) NOT NULL DEFAULT 'pending' COMMENT 'Status: pending=pendente, in_progress=em andamento, completed=concluída';

CREATE TABLE IF NOT EXISTS project_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role ENUM('owner', 'member') NOT NULL DEFAULT 'member' COMMENT 'owner=dono, member=membro',
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de entrada no projeto',
    CONSTRAINT fk_project_members_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_project_members_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT uk_project_members_project_user UNIQUE KEY (project_id, user_id),
    INDEX idx_project_members_project_id (project_id),
    INDEX idx_project_members_user_id (user_id),
    INDEX idx_project_members_role (role),
    INDEX idx_project_members_project_role (project_id, role)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Tabela de membros dos projetos';

ALTER TABLE project_members
MODIFY COLUMN role ENUM('owner', 'member') NOT NULL DEFAULT 'member' COMMENT 'Papel do usuário: owner=proprietário (criador), member=membro colaborador';

CREATE INDEX idx_tasks_overdue ON tasks (user_id, due_date, status);

CREATE INDEX idx_projects_period ON projects (user_id, start_date, end_date);

CREATE INDEX idx_tasks_project_priority ON tasks (project_id, priority, status);

ALTER TABLE projects
ADD FULLTEXT INDEX ft_projects_search (title, description);

ALTER TABLE tasks
ADD FULLTEXT INDEX ft_tasks_search (title, description);

-- CREATE TABLE users (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(255) NOT NULL,
--     email VARCHAR(255) NOT NULL UNIQUE,
--     password_hash VARCHAR(255) NOT NULL,
--     last_login TIMESTAMP,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     INDEX idx_email (email)
-- ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- CREATE TABLE projects (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     title VARCHAR(255) NOT NULL,
--     description TEXT,
--     start_date DATE NOT NULL,
--     end_date DATE NOT NULL,
--     owner_id INT NOT NULL,
--     file_path VARCHAR(500),
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE,
--     INDEX idx_owner (owner_id)
-- ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- CREATE TABLE project_members (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     project_id INT NOT NULL,
--     user_id INT NOT NULL,
--     added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
--     FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
--     UNIQUE KEY unique_member (project_id, user_id),
--     INDEX idx_project (project_id),
--     INDEX idx_user (user_id)
-- ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- CREATE TABLE tasks (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     title VARCHAR(255) NOT NULL,
--     description TEXT,
--     due_date DATE NOT NULL,
--     priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
--     status ENUM(
--         'pending',
--         'in_progress',
--         'completed'
--     ) NOT NULL DEFAULT 'pending',
--     project_id INT NOT NULL,
--     user_id INT NOT NULL,
--     file_path VARCHAR(500),
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
--     FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
--     INDEX idx_project (project_id),
--     INDEX idx_user (user_id),
--     INDEX idx_status (status),
--     INDEX idx_priority (priority),
--     INDEX idx_due_date (due_date)
-- ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;