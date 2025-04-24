<?php

use Dotenv\Dotenv;

/**
 * Custom environment loader for Laravel
 * -------------------------------------
 * This approach differs from Laravel's standard environment handling.
 * 
 * WHY WE NEED THIS:
 * Instead of loading only one .env file (environment-specific OR base),
 * this loader will load BOTH files when they exist:
 * 
 * 1. First loads environment-specific file (e.g., .env.testing)
 * 2. Then loads base .env file for any missing variables
 * 
 * This solves the problem of needing to duplicate all variables in each 
 * environment-specific file, while still allowing overrides of specific variables.
 */

// Base path where .env files are located
$basePath = dirname(__DIR__);

// Determine which environment we're running in (local, testing, production, etc.)
$environment = determineEnvironment();

// Load environment-specific variables first, then base variables
loadEnvironmentVariables($basePath, $environment);

/**
 * Determines which environment we're running in based on:
 * - Already set APP_ENV environment variable
 * - Command line argument (--env=xyz)
 * 
 * @return string The environment name (defaults to 'local' if none specified)
 */
function determineEnvironment(): string
{
    // Check if APP_ENV is already set in environment
    $environment = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null;

    // Check for --env CLI argument
    if (isset($_SERVER['argv'])) {
        foreach ($_SERVER['argv'] as $arg) {
            if (preg_match('/^--env=(.+)$/', $arg, $matches)) {
                $environment = $matches[1];
            }
        }
    }

    // Default to 'local' if no environment specified
    return $environment ?: 'local';
}

/**
 * Loads environment variables using a two-file approach:
 * 1. First tries to load environment-specific file (.env.{environment})
 * 2. Then loads base .env file to provide values for missing variables
 * 
 * @param string $basePath    Base directory path where .env files are located
 * @param string $environment Current environment name (testing, production, etc.)
 */
function loadEnvironmentVariables(string $basePath, string $environment): void
{
    // Step 1: Load environment-specific file if it exists
    $envSpecificFile = "{$basePath}/.env.{$environment}";
    if (file_exists($envSpecificFile)) {
        $dotenv = Dotenv::createImmutable($basePath, ".env.{$environment}");
        $dotenv->safeLoad();
    }

    // Step 2: Load base .env file for any missing variables
    // Variables already defined by the environment-specific file will not be overwritten
    // because we used createImmutable() in both cases
    $baseEnvFile = "{$basePath}/.env";
    if (file_exists($baseEnvFile)) {
        $dotenv = Dotenv::createImmutable($basePath);
        $dotenv->safeLoad();
    }
} 