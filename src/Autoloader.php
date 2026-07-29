<?php

/**
 * Autoloader for Toolkit Project
 * 
 * PSR-4 compliant autoloader that maps namespaces to file paths.
 * Supports deep nested directories and sub-namespaces.
 * 
 * @package Toolkit
 * @version 3.0.1
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
                
                // Convert namespace separators to directory separators
                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass);
                $file = $baseDir . $relativePath . '.php';

                if (file_exists($file)) {
                    require_once $file;
                    return true;
                }
                
                // Try to find the file in subdirectories for deeper nesting
                // This handles cases like Toolkit\Otp\Storage\Database\PdoOtpStorage
                $parts = explode(DIRECTORY_SEPARATOR, $relativePath);
                if (count($parts) > 1) {
                    // Reconstruct path assuming the last part is the class name
                    $className = array_pop($parts);
                    $subDir = implode(DIRECTORY_SEPARATOR, $parts);
                    $altFile = $baseDir . $subDir . DIRECTORY_SEPARATOR . $className . '.php';
                    
                    if (file_exists($altFile)) {
                        require_once $altFile;
                        return true;
                    }
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
    
    /**
     * Clear all registered namespaces (useful for testing)
     * 
     * @return void
     */
    public static function clearNamespaces(): void
    {
        self::$namespaces = [];
    }
}
