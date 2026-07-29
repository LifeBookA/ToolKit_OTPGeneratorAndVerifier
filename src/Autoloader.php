<?php

/**
 * Autoloader for Toolkit Project
 * 
 * PSR-4 compliant autoloader that maps namespaces to file paths.
 * 
 * @package Toolkit
 * @version 1.0.0
 */

namespace Toolkit;

class Autoloader
{
    /**
     * @var array Namespace prefixes mapped to base directories
     */
    private static array $namespaces = [];

    /**
     * Register a namespace prefix with its base directory
     * 
     * @param string $namespace The namespace prefix
     * @param string $baseDir The base directory for the namespace
     * @return void
     */
    public static function registerNamespace(string $namespace, string $baseDir): void
    {
        self::$namespaces[trim($namespace, '\\') . '\\'] = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Load a class based on its fully qualified name
     * 
     * @param string $class The fully qualified class name
     * @return bool True if the class was loaded, false otherwise
     */
    public static function load(string $class): bool
    {
        foreach (self::$namespaces as $namespacePrefix => $baseDir) {
            if (strpos($class, $namespacePrefix) === 0) {
                $relativeClass = substr($class, strlen($namespacePrefix));
                $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

                if (file_exists($file)) {
                    require_once $file;
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Register the autoloader with PHP's spl_autoload_functions
     * 
     * @return void
     */
    public static function register(): void
    {
        spl_autoload_register([__CLASS__, 'load']);
    }

    /**
     * Get all registered namespaces
     * 
     * @return array Array of namespace mappings
     */
    public static function getNamespaces(): array
    {
        return self::$namespaces;
    }
}
