<?php
namespace Kidys;

use \Slim\App as App;
use \Slim\Views\Twig as Twig;
use \Slim\Views\TwigExtension as TwigExtension;
use \Psr\Http\Message\RequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Monolog\Logger as Logger;
use \Monolog\Formatter\LineFormatter as LineFormatter;
use \Monolog\Handler\StreamHandler as StreamHandler;
use \Monolog\Handler\FirePHPHandler as FirePHPHandler;

/**
 * Главный класс приложения
 */
class Application {

	private static $_instance = null;
	private $_slimApp = null;
	private $_logger = null;

	public static function bootstrap() {
		if (self::$_instance === null) {
            self::$_instance = new self();
        }

		$containerSlim = self::$_instance
		    ->bootstrapSlimApp()
		    ->getContainer();		

		Environment::load();

		self::$_instance->runLogger();

		self::$_instance->setViews($containerSlim);
		self::$_instance->setCustomErrors($containerSlim);
		
		return self::$_instance;
	}
    
    protected function setViews($containerSlim) {
        $containerSlim['view'] = function ($containerSlim) {
    		$view = new Twig('./application/templates', [
        		'cache' => false //'./application/cache'
    		]);

            $basePath = rtrim(str_ireplace('index.php', '', $containerSlim['request']->getUri()->getBasePath()), '/');
    		$view->addExtension(new TwigExtension($containerSlim['router'], $basePath));

    		return $view;
		};

		$this->setEnvVariables($containerSlim['view']->getEnvironment());	
    }

    private function runLogger() {
    	$this->_logger = (new Logger('logLevel'));

    	$debugHandler = new StreamHandler('./application/logs/debug.' . date('Y-m-d__H-00-00', time()) . '.log', Logger::DEBUG);
    	$errorHandler = new StreamHandler('./application/logs/errors__' . date('Y-m-d__H-00-00', time()) . '.log', Logger::ERROR);
    	//$lineFormatter = new LineFormatter('%datetime% > %level_name% > %message% %context%' . PHP_EOL, 'Y-m-d H:i:s');
    	$lineFormatter = new LineFormatter(null, null, false, true);

    	$debugHandler->setFormatter($lineFormatter);
    	$errorHandler->setFormatter($lineFormatter);

	    //$this->_logger->pushHandler($debugHandler);
	    $this->_logger->pushHandler($errorHandler);
    }

    private function setEnvVariables($environment) {
    	$environment->addGlobal('charset', getenv('charset') ?: 'utf-8');
    	$environment->addGlobal('lang', getenv('lang') ?: 'ru_RU');
    	$environment->addGlobal('baseUrl', getenv('url') ?: 'http://localhost/');
    }

    protected function setCustomErrors($containerSlim) {
    	$containerSlim['notFoundHandler'] = function ($containerSlim) {
		    return function (Request $req, Response $res) use ($containerSlim) {

		    	$this->writeLogError('Error 404', [
		    		'exception' => new \Exception('Error 404. Page not Found'),
		    		'text' => 'Не найден файл. Что же делать?'
		    	]);
	            return $containerSlim['view']->render($containerSlim['response']
            	    ->withStatus(404)
            	    ->withHeader('Content-Type', 'text/html'), '404.html', [
	            	    'title' => '404 Ошибка. Страница не найдена'
	                ]);
		    };
		};	
    }

    private function writeLogError($message = 'Возникла ошибка', $array = []) {
    	if (getenv('is_logged')) {
    		if (isset($array['exception']) && ($array['exception'] instanceof \Exeption)) {
    			$e = $array['exception'];
    			unset($array['exception']);
    		} else {
    			$e = new \Exception($message);
    		}

    		$arr = [ 'exception' => $e ];
    		
    		foreach ($array as $k => $v) {
    			$arr[$k] = $v;
    		}

    		$this->_logger->error($message, $arr);
		}
    }

	protected function bootstrapSlimApp() {
		/**
		* Создаем приложение \Slim\App
		* @var \Slim\App
		*/
		$this->_slimApp = new App();

		$this->_slimApp->get('/', function (Request $req,  Response $res, $args = []) {
		    //return $res->withStatus(200)->write('<h1>Привет, мир!</h1>');
		    return $this->view->render($res, 'index.html', [
        		'title' => 'Заголовок главной страницы'
    		]);
		});	

		return $this->_slimApp;	
	}

	public function run() {
		$this->_slimApp->run();
	}

}
