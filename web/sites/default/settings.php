<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Include the Pantheon-specific settings file.
 *
 * n.b. The settings.pantheon.php file makes some changes
 *      that affect all envrionments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to ensure that
 *      the site settings remain consistent.
 */
include __DIR__ . "/settings.pantheon.php";

/**
 * Place the config directory outside of the Drupal root.
 */
$settings['config_sync_directory'] = dirname(DRUPAL_ROOT) . '/config';

/**
 * If there is a local settings file, then include it
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}

/**
 * Always install the 'standard' profile to stop the installer from
 * modifying settings.php.
 */
$settings['install_profile'] = 'standard';

if (isset($_ENV['PANTHEON_ENVIRONMENT']) && $_ENV['PANTHEON_ENVIRONMENT'] != 'lando') {
  // from: https://pantheon.io/docs/redirects/
  // Require HTTPS across all Pantheon environments
  // Check if Drupal or WordPress is running via command line
  if (isset($_SERVER['PANTHEON_ENVIRONMENT']) && ($_SERVER['HTTPS'] === 'OFF') && (php_sapi_name() != "cli")) {
    if (!isset($_SERVER['HTTP_USER_AGENT_HTTPS']) || (isset($_SERVER['HTTP_USER_AGENT_HTTPS']) && $_SERVER['HTTP_USER_AGENT_HTTPS'] != 'ON')) {

      header('HTTP/1.0 301 Moved Permanently');
      header('Location: https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

      # Name transaction "redirect" in New Relic for improved reporting (optional)
      if (extension_loaded('newrelic')) {
        newrelic_name_transaction("redirect");
      }

      exit();
    }
  }
}