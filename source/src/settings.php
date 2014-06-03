<?php
/**
 * settings.php
 *
 * Contains global settings for the app.
 *
 * @author Ian Adamson
 */



// Caching
define('ALLOW_CACHING', true); // TODO: implement cache
define('CACHE_FOLDER', ''); // TODO: implement cache
define('CACHE_TIMEOUT', '');

// API
define('PROJECT_CATEGORY', "CCAT_active_project"); // Without "Category:" prefix
define('ROOT_ADDRESS', "http://www.appropedia.org"); // URL of Wiki, no trailing slash
define('API_ADDRESS', "http://www.appropedia.org/api.php"); // The location of api.php
define('API_USER', ""); // If necessary (TODO: implement login)
define('API_PASSWORD', ""); // If necessary(TODO: implement login)

error_reporting(E_ALL);

/* EOF settings.php */
