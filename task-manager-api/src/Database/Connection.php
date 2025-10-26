<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;

/**
 * Classe de conexão com banco de dados usando padrão Singleton
 * 
 * Esta classe garante que apenas uma instãncia da conexão PDO
 * seja criada durante toda a execução da aplicação.
 */
class Connection
{
    /**
     * Instância única da classe
     */
    private static ?Connection $instance = null;

    /**
     * Instância da conexão PDO
     */
    private ?PDO $connection = null;

    /**
     * Construtor provado para prevenir instanciação direta
     */
    private function __construct()
    {
        $this->connect();
    }

    /**
     * Previne clonagem do objeto
     */
    private function __clone(): void {}

    /**
     * Previne a desserialização do objeto
     */
    public function __wakeup(): void
    {
        throw new \Exception("Cannot unserialize singleton");
    }

    /**
     * Retorna a instância única da classe
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Estabelece a conexão com o banco de dados
     */
    private function connect(): void
    {
        try {
            $config = require __DIR__ . '/../../config/database.php';

            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            $this->connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );

            $this->connection->exec(
                "SET NAMES {$config['charset']} COLLATE {$config['collation']}"
            );
        } catch (PDOException $e) {
            error_log(sprintf(
                "[%s] Database Connection Error: %s",
                date('Y-m-d H:i:s'),
                $e->getMessage()
            ));

            throw new \RuntimeException(
                "Erro na conexão com o banco de dados. Verifique as configurações.",
                (int)$e->getCode(),
                $e
            );
        }
    }


    /**
     * Retorna a conexão PDO
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    /**
     * Testa a conexão com o banco de dados
     */
    public function testConnection(): bool
    {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            error_log("Connection test failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fecha a conexão com o banco de dados
     */
    public function disconnect(): void
    {
        $this->connection = null;
    }

    /**
     * Inicia transação
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit da transação
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Roolback da transação
     */
    public function rollback(): bool
    {
        return $this->connection->rollback();
    }

    /**
     * Retorna útlimo ID inserido
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }
}
