<?php
namespace Kidys;

use \Dotenv\Dotenv as Dotenv;

/**
 * Класс глобального окружения
 */
class Environment {
    
    private static $_instance = null;
    private $_configFileName = '.env.config';
    
    public static function load() {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        self::$_instance->bootstrap();
        
        return self::$_instance;
    }

    protected function bootstrap() {
    	(new Dotenv(realpath(__DIR__ . '/../'), $this->_configFileName))->load();
    }
    
}