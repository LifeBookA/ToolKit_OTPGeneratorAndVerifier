<?php

declare(strict_types=1);

namespace Toolkit\Otp\Storage;

/**
 * Interface for database-based OTP storage.
 * 
 * Provides methods for storing and retrieving OTP data in a relational database.
 * This interface extends the base OtpStorageInterface with database-specific operations.
 * 
 * @package Toolkit\Otp\Storage
 * @author Toolkit Team
 * @since 2.0.0
 */
interface DatabaseStorageInterface extends OtpStorageInterface
{
    /**
     * Set the database connection parameters.
     *
     * @param string $host Database host.
     * @param int $port Database port.
     * @param string $database Database name.
     * @param string $username Database username.
     * @param string $password Database password.
     * @param string $driver PDO driver (mysql, pgsql, sqlite).
     * @return void
     */
    public function setConnection(
        string $host = 'localhost',
        int $port = 3306,
        string $database = 'otp_storage',
        string $username = 'root',
        string $password = '',
        string $driver = 'mysql'
    ): void;

    /**
     * Create the required database table if it doesn't exist.
     *
     * @return void
     */
    public function createTable(): void;

    /**
     * Get the PDO instance.
     *
     * @return \PDO|null
     */
    public function getConnection(): ?\PDO;

    /**
     * Clean up expired entries from the database.
     *
     * @return int Number of deleted entries.
     */
    public function cleanupExpired(): int;
}
