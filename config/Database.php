<?php

declare(strict_types=1);

namespace config\Database;

use PDO;
use PDOException;
use PDOStatement;
use Throwable;

final class Database
{

    private static ?self $instance = null;
    private PDO $connection;

    /*
     *  private to implement the pattern singleton
     */

    private function __construct(
        string $host,
        string $dbname,
        string $username,
        string $password,
        array  $options = []
    )
    {
        $defultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        $finalOptions = $options + $defultOptions;
        $dsn = "mysql:host=$host;$dbname=$dbname;charset=utf8mb4";

        try {
            $this->connection = new PDO($dsn, $username, $password, $finalOptions);
        } catch (PDOException $e) {
            throw new DatabaseConnectionExciption(
                "Connection failed: " . $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    /*
     * For recive instance class singleton function
     */

    public static function getInstance(
        string $host,
        string $dbname,
        string $username,
        string $password,
        array  $options = []
    ): self
    {
        if (self::$instance === null) {
            self::$instance = new self($host, $dbname, $username, $password, $options);
        }
        return self::$instance;
    }

    /*
     * Running a query that returns no results (INSERT, UPDATE, DELETE)
     */

    public function execute(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $this->bindValues($stmt, $params);
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new QueryExecutionException(
                "Query execution failed: " . $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }


    public function fetch(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $this->bindValues($stmt, $params);
            $stmt->execute();
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            throw new QueryExecutionException(
                "Fetch query failed: " . $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }


    public function fetchAll(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $this->bindValues($stmt, $params);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new QueryExecutionException(
                "Fetch all query failed: " . $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    public function beginTransaction(): void
    {
        try {
            $this->connection->beginTransaction();
        } catch (PDOException $e) {
            throw new TransactionException(
                "Transaction start failed: " . $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    public function commit(): void
    {
        try {
            $this->connection->commit();
        } catch (PDOException $e) {
            throw new TransactionException(
                "Transaction commit failed: " . $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    public function rollBack(): void
    {
        try {
            $this->connection->rollBack();
        } catch (PDOException $e) {
            throw new TransactionException(
                "Transaction rollback failed: " . $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    public function close(): void
    {
        self::$instance = null;
    }

    private function bindValues(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $stmt->bindValue($key, $value, $type);
        }
    }

    private function __clone()
    {

    }

    public function __wakeup()
    {
        throw new \RuntimeException("Cannot unserialize a singleton");
    }
}

class DatabaseException extends \RuntimeException
{
}

class QueryExecutionException extends \RuntimeException
{
}

class TransactionException extends \RuntimeException
{
}