<?php
/**
 * PDO database connection factory
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                if (class_exists('AppLog')) {
                    AppLog::error('Database connection failed', [
                        'host' => DB_HOST,
                        'name' => DB_NAME,
                        'user' => DB_USER,
                        'env'  => defined('APP_ENV') ? APP_ENV : 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
                throw $e;
            }
        }

        return self::$instance;
    }

    private function __construct()
    {
    }
}
