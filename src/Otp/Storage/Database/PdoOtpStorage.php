<?php

declare(strict_types=1);

namespace Toolkit\Otp\Storage\Database;

use PDO;
use PDOException;
use Toolkit\Otp\Config\OtpConfig;
use Toolkit\Otp\Storage\DatabaseStorageInterface;

/**
 * Database-based OTP storage implementation using PDO.
 * 
 * Stores OTP data in a relational database with support for MySQL, PostgreSQL, and SQLite.
 * Automatically creates the required table structure if it doesn't exist.
 * 
 * @package Toolkit\Otp\Storage\Database
 * @author Toolkit Team
 * @since 2.0.0
 */
class PdoOtpStorage implements DatabaseStorageInterface
{
    /**
     * @var PDO|null The PDO connection instance.
     */
    private ?PDO $connection = null;

    /**
     * @var string Table name for storing OTP entries.
     */
    private string $tableName = 'otp_entries';

    /**
     * @var array Connection configuration.
     */
    private array $config = [];

    /**
     * Constructor.
     *
     * @param PDO|null $pdo Optional existing PDO instance.
     */
    public function __construct(?PDO $pdo = null)
    {
        if ($pdo !== null) {
            $this->connection = $pdo;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(
        string $host = 'localhost',
        int $port = 3306,
        string $database = 'otp_storage',
        string $username = 'root',
        string $password = '',
        string $driver = 'mysql'
    ): void {
        $this->config = [
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'driver' => $driver,
        ];

        $dsn = $this->buildDsn();
        
        try {
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Build the DSN string based on the driver.
     *
     * @return string
     */
    private function buildDsn(): string
    {
        $driver = $this->config['driver'] ?? 'mysql';
        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? 3306;
        $database = $this->config['database'] ?? 'otp_storage';

        switch ($driver) {
            case 'sqlite':
                return "sqlite:$database";
            case 'pgsql':
                return "pgsql:host=$host;port=$port;dbname=$database";
            case 'mysql':
            default:
                return "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createTable(): void
    {
        if ($this->connection === null) {
            throw new \RuntimeException('Database connection not established. Call setConnection() first.');
        }

        $driver = $this->config['driver'] ?? 'mysql';
        
        switch ($driver) {
            case 'sqlite':
                $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
                    identifier TEXT PRIMARY KEY,
                    code TEXT NOT NULL,
                    expiry INTEGER NOT NULL,
                    attempts INTEGER DEFAULT 0,
                    created_at INTEGER NOT NULL
                )";
                break;
            
            case 'pgsql':
                $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
                    identifier VARCHAR(255) PRIMARY KEY,
                    code VARCHAR(50) NOT NULL,
                    expiry BIGINT NOT NULL,
                    attempts INTEGER DEFAULT 0,
                    created_at BIGINT NOT NULL
                )";
                break;
            
            case 'mysql':
            default:
                $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
                    `identifier` VARCHAR(255) PRIMARY KEY,
                    `code` VARCHAR(50) NOT NULL,
                    `expiry` BIGINT NOT NULL,
                    `attempts` INT DEFAULT 0,
                    `created_at` BIGINT NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                break;
        }

        $this->connection->exec($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(): ?PDO
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function cleanupExpired(): int
    {
        if ($this->connection === null) {
            throw new \RuntimeException('Database connection not established.');
        }

        $now = time();
        $stmt = $this->connection->prepare("DELETE FROM {$this->tableName} WHERE expiry < :now");
        $stmt->execute(['now' => $now]);
        
        return $stmt->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function save(string $identifier, string $code, int $ttl): void
    {
        $this->ensureConnection();
        
        $expiry = time() + $ttl;
        $createdAt = time();

        $sql = "INSERT OR REPLACE INTO {$this->tableName} (identifier, code, expiry, attempts, created_at) 
                VALUES (:identifier, :code, :expiry, 0, :created_at)";

        if (($this->config['driver'] ?? 'mysql') === 'mysql') {
            $sql = "INSERT INTO {$this->tableName} (identifier, code, expiry, attempts, created_at) 
                    VALUES (:identifier, :code, :expiry, 0, :created_at)
                    ON DUPLICATE KEY UPDATE code = :code, expiry = :expiry, attempts = 0, created_at = :created_at";
        } elseif (($this->config['driver'] ?? 'mysql') === 'pgsql') {
            $sql = "INSERT INTO {$this->tableName} (identifier, code, expiry, attempts, created_at) 
                    VALUES (:identifier, :code, :expiry, 0, :created_at)
                    ON CONFLICT (identifier) DO UPDATE SET code = :code, expiry = :expiry, attempts = 0, created_at = :created_at";
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'identifier' => $identifier,
            'code' => $code,
            'expiry' => $expiry,
            'created_at' => $createdAt,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $identifier): ?array
    {
        $this->ensureConnection();

        $stmt = $this->connection->prepare("SELECT code, expiry, attempts FROM {$this->tableName} WHERE identifier = :identifier");
        $stmt->execute(['identifier' => $identifier]);
        $result = $stmt->fetch();

        if ($result === false) {
            return null;
        }

        // Check if expired
        if ((int)$result['expiry'] < time()) {
            $this->delete($identifier);
            return null;
        }

        return [
            'code' => $result['code'],
            'expiry' => (int)$result['expiry'],
            'attempts' => (int)$result['attempts'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $identifier): void
    {
        $this->ensureConnection();

        $stmt = $this->connection->prepare("DELETE FROM {$this->tableName} WHERE identifier = :identifier");
        $stmt->execute(['identifier' => $identifier]);
    }

    /**
     * {@inheritdoc}
     */
    public function incrementAttempts(string $identifier): int
    {
        $this->ensureConnection();

        $sql = "UPDATE {$this->tableName} SET attempts = attempts + 1 WHERE identifier = :identifier";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['identifier' => $identifier]);

        // Get the new attempts count
        $data = $this->get($identifier);
        return $data !== null ? $data['attempts'] : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $identifier): bool
    {
        $this->ensureConnection();

        $stmt = $this->connection->prepare("SELECT COUNT(*) as count FROM {$this->tableName} WHERE identifier = :identifier AND expiry > :now");
        $stmt->execute(['identifier' => $identifier, 'now' => time()]);
        $result = $stmt->fetch();

        return $result !== false && (int)$result['count'] > 0;
    }

    /**
     * Ensure database connection is established.
     *
     * @return void
     */
    private function ensureConnection(): void
    {
        if ($this->connection === null) {
            if (!empty($this->config)) {
                $this->setConnection(
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['database'],
                    $this->config['username'],
                    $this->config['password'],
                    $this->config['driver']
                );
            } else {
                throw new \RuntimeException('Database connection not established. Call setConnection() or provide a PDO instance.');
            }
        }
    }

    /**
     * Set the table name.
     *
     * @param string $tableName
     * @return void
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * Get the table name.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }
}
