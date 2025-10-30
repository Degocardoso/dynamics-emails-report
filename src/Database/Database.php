<?php

namespace App\Database;

use PDO;
use PDOException;
use Exception;

/**
 * Gerenciador de conexão com banco de dados
 * Implementa padrão Singleton para garantir uma única conexão
 */
class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private array $config;

    /**
     * Construtor privado (Singleton)
     */
    private function __construct()
    {
        $this->config = [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_DATABASE') ?: 'dynamics_email_report',
            'username' => getenv('DB_USERNAME') ?: 'root',
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ];
    }

    /**
     * Obtém instância única da classe (Singleton)
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtém conexão PDO ativa
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    /**
     * Estabelece conexão com o banco de dados
     */
    private function connect(): void
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset']
            );

            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );

            // Configurar timezone
            $this->connection->exec("SET time_zone = '+00:00'");

        } catch (PDOException $e) {
            throw new Exception(
                "Erro ao conectar com o banco de dados: " . $e->getMessage()
            );
        }
    }

    /**
     * Executa query preparada
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Busca um único registro
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Busca múltiplos registros
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Executa INSERT e retorna o último ID inserido
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(array_values($data));

        return (int) $this->getConnection()->lastInsertId();
    }

    /**
     * Executa UPDATE
     */
    public function update(string $table, array $data, array $where): int
    {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "{$column} = ?";
        }

        $whereClause = [];
        foreach (array_keys($where) as $column) {
            $whereClause[] = "{$column} = ?";
        }

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $set),
            implode(' AND ', $whereClause)
        );

        $params = array_merge(array_values($data), array_values($where));
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Executa DELETE
     */
    public function delete(string $table, array $where): int
    {
        $whereClause = [];
        foreach (array_keys($where) as $column) {
            $whereClause[] = "{$column} = ?";
        }

        $sql = sprintf(
            "DELETE FROM %s WHERE %s",
            $table,
            implode(' AND ', $whereClause)
        );

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(array_values($where));

        return $stmt->rowCount();
    }

    /**
     * Inicia transação
     */
    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Confirma transação
     */
    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }

    /**
     * Reverte transação
     */
    public function rollback(): bool
    {
        return $this->getConnection()->rollBack();
    }

    /**
     * Testa conexão com banco de dados
     */
    public function testConnection(): bool
    {
        try {
            $this->getConnection()->query('SELECT 1');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Previne clonagem (Singleton)
     */
    private function __clone() {}

    /**
     * Previne deserialização (Singleton)
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
