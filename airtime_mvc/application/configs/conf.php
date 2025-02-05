<?php
/* THIS FILE IS NOT MEANT FOR CUSTOMIZING.
 * PLEASE EDIT THE FOLLOWING TO CHANGE YOUR CONFIG:
 * LIBRETIME_CONF_DIR/airtime.conf
 */

require_once __DIR__ . '/constants.php';

class Config {
    private static $CC_CONFIG = null;
    private static $rootDir;
    public static function loadConfig() {

        self::$rootDir = __DIR__."/../..";
        $CC_CONFIG = array(
                /* ================================================ storage configuration */
                "rootDir" => self::$rootDir
        );
        
        //In the unit testing environment, LIBRETIME_CONF_DIR will our local airtime.conf in airtime_mvc/application/test/conf:
        $filename = isset($_SERVER['AIRTIME_CONF']) ? $_SERVER['AIRTIME_CONF'] : LIBRETIME_CONF_DIR . "/airtime.conf";
        
        $values = parse_ini_file($filename, true);

        // Name of the web server user
        $CC_CONFIG['webServerUser'] = $values['general']['web_server_user'];
        $CC_CONFIG['rabbitmq'] = $values['rabbitmq'];

        $CC_CONFIG['baseDir'] = $values['general']['base_dir'];
        $CC_CONFIG['baseUrl'] = $values['general']['base_url'];
        $CC_CONFIG['basePort'] = $values['general']['base_port'];
        $CC_CONFIG['stationId'] = $values['general']['station_id'];
        $CC_CONFIG['phpDir'] = $values['general']['airtime_dir'];
        if (isset($values['general']['dev_env'])) {
            $CC_CONFIG['dev_env'] = $values['general']['dev_env'];
        } else {
            $CC_CONFIG['dev_env'] = 'production';
        }

        //Backported static_base_dir default value into saas for now.
        if (array_key_exists('static_base_dir', $values['general'])) {
            $CC_CONFIG['staticBaseDir'] = $values['general']['static_base_dir'];
        } else {
            $CC_CONFIG['staticBaseDir'] = '/';
        }

        // Parse separate conf file for cloud storage values
        $cloudStorageConfig = LIBRETIME_CONF_DIR . '/' . $CC_CONFIG['dev_env']."/cloud_storage.conf";
        if (!file_exists($cloudStorageConfig)) {
            // If the dev env specific cloud_storage.conf doesn't exist default
            // to the production cloud_storage.conf
            $cloudStorageConfig = LIBRETIME_CONF_DIR . "/production/cloud_storage.conf";
        }
        $cloudStorageValues = parse_ini_file($cloudStorageConfig, true);
        
        $CC_CONFIG["supportedStorageBackends"] = array('amazon_S3');
        foreach ($CC_CONFIG["supportedStorageBackends"] as $backend) {
            $CC_CONFIG[$backend] = $cloudStorageValues[$backend];
        }
        
        // Tells us where file uploads will be uploaded to.
        // It will either be set to a cloud storage backend or local file storage.
        $CC_CONFIG["current_backend"] = $cloudStorageValues["current_backend"]["storage_backend"];

        $CC_CONFIG['cache_ahead_hours'] = $values['general']['cache_ahead_hours'];
        
	    // Database config
        $CC_CONFIG['dsn']['username'] = $values['database']['dbuser'];
        $CC_CONFIG['dsn']['password'] = $values['database']['dbpass'];
        $CC_CONFIG['dsn']['hostspec'] = $values['database']['host'];
        $CC_CONFIG['dsn']['phptype'] = 'pgsql';
        $CC_CONFIG['dsn']['database'] = $values['database']['dbname'];

        $CC_CONFIG['apiKey'] = array($values['general']['api_key']);
        
        if (defined('APPLICATION_ENV') && APPLICATION_ENV == "development"){
            $CC_CONFIG['apiKey'][] = "";
        }

        $CC_CONFIG['soundcloud-connection-retries'] = $values['soundcloud']['connection_retries'];
        $CC_CONFIG['soundcloud-connection-wait'] = $values['soundcloud']['time_between_retries'];

        $globalAirtimeConfig = LIBRETIME_CONF_DIR . '/' . $CC_CONFIG['dev_env']."/airtime.conf";
        if (!file_exists($globalAirtimeConfig)) {
            // If the dev env specific airtime.conf doesn't exist default
            // to the production airtime.conf
            $globalAirtimeConfig = LIBRETIME_CONF_DIR . "/production/airtime.conf";
        }
        $globalAirtimeConfigValues = parse_ini_file($globalAirtimeConfig, true);
        $CC_CONFIG['soundcloud-client-id'] = $globalAirtimeConfigValues['soundcloud']['soundcloud_client_id'];
        $CC_CONFIG['soundcloud-client-secret'] = $globalAirtimeConfigValues['soundcloud']['soundcloud_client_secret'];
        $CC_CONFIG['soundcloud-redirect-uri'] = $globalAirtimeConfigValues['soundcloud']['soundcloud_redirect_uri'];
        if (isset($globalAirtimeConfigValues['facebook']['facebook_app_id'])) {
            $CC_CONFIG['facebook-app-id'] = $globalAirtimeConfigValues['facebook']['facebook_app_id'];
            $CC_CONFIG['facebook-app-url'] = $globalAirtimeConfigValues['facebook']['facebook_app_url'];
            $CC_CONFIG['facebook-app-api-key'] = $globalAirtimeConfigValues['facebook']['facebook_app_api_key'];
        }

        if(isset($values['demo']['demo'])){
            $CC_CONFIG['demo'] = $values['demo']['demo'];
        }
        self::$CC_CONFIG = $CC_CONFIG;
    }
    
    public static function setAirtimeVersion() {
        $version = @file_get_contents(self::$rootDir."/../VERSION");
        if (!$version) {
            // fallback to constant from constants.php if no other info is available
            $version = LIBRETIME_MAJOR_VERSION;
        }
        self::$CC_CONFIG['airtime_version'] = trim($version);
    }
    
    public static function getConfig() {
        if (is_null(self::$CC_CONFIG)) {
            self::loadConfig();
        }
        return self::$CC_CONFIG;
    }
}
