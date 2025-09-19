<?php

/**
 * @file
 * Local development override configuration feature.
 */

/**
 * Database settings
 */
$databases['default']['default'] = [
  'database' => getenv('DB_NAME') ?: 'drupaldb',
  'username' => getenv('DB_USER') ?: 'drupaluser',
  'password' => getenv('DB_PASSWORD') ?: 'drupalpass',
  'prefix' => '',
  'host' => getenv('DB_HOST') ?: 'db',
  'port' => '3306',
  'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
  'driver' => 'mysql',
  'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
];

/**
 * Salt for one-time login links, cancel links, form tokens, etc.
 */
$settings['hash_salt'] = getenv('DRUPAL_HASH_SALT') ?: 'changeme';

/**
 * Disable CSS and JS aggregation.
 */
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

/**
 * Disable the render cache.
 */
$settings['cache']['bins']['render'] = 'cache.backend.null';

/**
 * Disable caching for migrations.
 */
$settings['cache']['bins']['discovery_migration'] = 'cache.backend.memory';

/**
 * Disable Internal Page Cache.
 */
$settings['cache']['bins']['page'] = 'cache.backend.null';

/**
 * Disable Dynamic Page Cache.
 */
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

/**
 * Allow test modules and themes to be installed.
 */
$settings['extension_discovery_scan_tests'] = TRUE;

/**
 * Enable access to rebuild.php.
 */
$settings['rebuild_access'] = TRUE;

/**
 * Skip file system permissions hardening.
 */
$settings['skip_permissions_hardening'] = TRUE;

/**
 * Exclude modules from configuration synchronization.
 */
$settings['config_exclude_modules'] = ['devel', 'stage_file_proxy'];

/**
 * Private file path.
 */
$settings['file_private_path'] = '/opt/drupal/private';

/**
 * Temporary directory.
 */
$settings['file_temp_path'] = '/tmp';

/**
 * Trusted host configuration.
 */
$settings['trusted_host_patterns'] = [
  '^localhost$',
  '^drupal\.local$',
  '^127\.0\.0\.1$',
  '^host\.docker\.internal$',
];

/**
 * Error reporting.
 */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

/**
 * Show all error messages, with backtrace information.
 */
$config['system.logging']['error_level'] = 'verbose';

/**
 * Disable CSS and JS aggregation.
 */
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

/**
 * Enable Twig debugging.
 */
$settings['twig_debug'] = TRUE;
$settings['twig_auto_reload'] = TRUE;
$settings['twig_cache'] = FALSE;

/**
 * Enable local development services.
 */
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';

/**
 * MailHog Configuration for email testing
 */
$config['smtp.settings']['smtp_host'] = 'mailhog';
$config['smtp.settings']['smtp_port'] = 1025;
$config['smtp.settings']['smtp_protocol'] = 'standard';
$config['smtp.settings']['smtp_autotls'] = FALSE;

/**
 * File system settings
 */
$settings['file_public_path'] = 'sites/default/files';
$settings['file_public_base_url'] = '/sites/default/files';

/**
 * Session configuration
 */
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 200000);
ini_set('session.cookie_lifetime', 2000000);

/**
 * Performance settings for development
 */
$settings['rebuild_access'] = TRUE;
$settings['skip_permissions_hardening'] = TRUE;

/**
 * Disable Drupal's built-in cron
 */
$config['automated_cron.settings']['interval'] = 0;