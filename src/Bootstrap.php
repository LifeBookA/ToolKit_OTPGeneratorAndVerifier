<?php

/**
 * Bootstrap for Toolkit Project
 * 
 * Initializes the autoloader and registers all namespaces.
 * 
 * @package Toolkit
 * @version 3.0.0
 */

namespace Toolkit;

require_once __DIR__ . '/Autoloader.php';

class Bootstrap
{
    /**
     * Initialize the Toolkit framework
     * 
     * Registers all necessary namespaces and sets up the autoloader.
     * 
     * @return void
     */
    public static function init(): void
    {
        // Register the Toolkit namespace
        Autoloader::registerNamespace('Toolkit', __DIR__);
        
        // Register the autoloader
        Autoloader::register();
    }

    /**
     * Get the base directory of the Toolkit project
     * 
     * @return string The base directory path
     */
    public static function getBaseDir(): string
    {
        return __DIR__;
    }

    /**
     * Get the version of the Toolkit project
     * 
     * @return string Version number
     */
    public static function getVersion(): string
    {
        return '3.0.1';
    }
}

// Initialize the bootstrap
Bootstrap::init();
