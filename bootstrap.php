<?php

if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300) {
    die('Pinion requires PHP 5.3 or higher');
}

// use utf8 as internal encoding standard
mb_internal_encoding('UTF-8');

// if there is firePHP, you can debug
define("DEBUG", false);

// paths (constants and include path)
include_once "pinion/php/include/paths.inc.php";

// usage
use pinion\data\database\ModelProvider;
use pinion\general\Request;
use pinion\general\Response;
use pinion\modules\ModuleManager;
use pinion\modules\ArModuleStatusChecker;
use pinion\general\FrontController;
use pinion\authorization\Authorizator;
use pinion\general\TemplateBuilder;
use pinion\general\TemplatePostProcessor;
use pinion\general\Session;
use pinion\general\Registry;

// initialize autoloader
include_once "pinion/php/include/autoload.inc.php";

// REQUEST, RESPONSE AND SESSION ABSTRACTION

// create request object
$request = new Request();

// define the current url (with the page request parameter)
define("CURRENT_URL", SITE_URL.$request->getGetParameter("page", ""));

// create session object
$session = new Session();

// create response object with request and session
$response = new Response($request, $session);

$authorizator = new Authorizator();

$logout = $request->getGetParameter("logout");
if(isset($logout))
    $authorizator->clearIdentity();


include_once "pinion/php/include/db.inc.php";

$identity = $authorizator->getIdentity($request, $salt);
Registry::setIdentity($identity);
/////////////////////////////////////////////////////////////////////////
// INITIALIZE SESSION
if(!$request->isAjax()) {
    $session->setParameter("files", array("js" => array(), "css" => array()));
}

$statusChecker = new ArModuleStatusChecker("module");

$moduleManager = new ModuleManager(MODULES_PATH, $dbAdminister, $statusChecker, $response, $request, $session);

$frontController = new FrontController($moduleManager);


/////////////////////////////////////////////////////////////////////////

$postProcessor = new TemplatePostProcessor();

$response
    ->setIdentity($identity)
    ->setPostProcessor($postProcessor)
    ->setModuleManager($moduleManager);

$moduleManager
    ->setIdentity($identity)
    ->loadModules();