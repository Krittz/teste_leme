<?php

declare(strict_types=1);

namespace App\Models;

use App\Database\Connection;
use PDO;
use PDOStatement;


/**
 * Base Model
 * 
 * Model base com operações CRUD genéricas
 */
abstract class BaseModel
{
    /**
     * Nome da tabela (deve ser definido nas classes filhas)
     */
    protected string $table;

    /**
     * Primary Key
     */
    protected string $primaryKey = 'id';

    /**
     * Campos fillable (podem ser preenchidos em massa)
     */
    protected array $fillable = [];

    /**
     * Timestamps automaticos
     */
    protected bool $timestamps = true;

    /**
     * Conexão PDO
     */
    protected PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    /**
     * Busca todos os registros
     */
    public function all(array $columns = ['*']): array
    {
        $columns = implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table}";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Busca registro por ID
     */
    public function find(int $id, array $columns = ['*']): ?array
    {
        $columns = implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Busca primeiro registro com condição
     */
    public function where(string $column, mixed $value, string $operator = '='): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} {$operator} ? LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Busca mútliplos registros com condição
     */
    public function whereAll(string $column, mixed $value, string $operator = '='): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} {$operator} ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);

        return $stmt->fetchAll();
    }

    /**
     * Cria novo registro
     */
    public function create(array $data): ?int
    {
        $data = $this->filterFillable($data);

        if ($this->timestamps) {
            $data['created_at'] = now();
            $data['updated_at'] = now();
        }

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);

        if ($stmt->execute(array_values($data))) {
            return (int) $this->db->lastInsertId();
        }

        return null;
    }

    /**
     * Atualiza registro
     */
    public function update(int $id, array $data): bool
    {
        $data = $this->filterFillable($data);

        if ($this->timestamps) {
            $data['updated_at'] = now();
        }

        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = "{$column} = ?";
        }

        $sql = sprintf(
            "UPDATE %s SET %s WHERE {$this->primaryKey} = ?",
            $this->table,
            implode(', ', $sets)
        );

        $values = array_values($data);
        $values[] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Deleta registro
     * 
     * Particularmente sou contra tal prática, e uso soft deletes, com uma flag
     * geralmente um campo de exclusão  lógica que serve tanto para flag de delete 
     * quanto para auditória, sendo um campo deleted_at com timestamps do momeneto da exclusão do registro.
     * Mas por que não apliquei aqui? (boa pergunta)
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    /**
     * Verifica se registro existe
     */
    public function exists(int $id): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        return (bool) $stmt->fetch();
    }

    /**
     * Conta registros
     */
    public function count(string $column = '*', mixed $value = null): int
    {
        if ($value === null) {
            $sql = "SELECT COUNT({$column}) as total FROM {$this->table}";
            $stmt = $this->db->query($sql);
        } else {
            $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$column} = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$value]);
        }

        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }
    /**
     * Query customizada
     */
    protected function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Inicia transação
     */
    protected function beginTransaction(): bool
    {
        return Connection::getInstance()->beginTransaction();
    }

    /**
     * Commit transação
     */
    protected function commit(): bool
    {
        return Connection::getInstance()->commit();
    }
    /**
     * Rollback transação
     */
    protected function rollback(): bool
    {
        return Connection::getInstance()->rollback();
    }

    /**
     * Filtra apenas campos fillable
     */
    private function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Paginação
     * NOTA: Seria interessante colocar um limite de quantidade por pagina
     */
    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        $total = $this->count();
        $sql = "SELECT * FROM {$this->table} LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetchAll();

        return [
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $page,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
            ]
        ];
    }
}
