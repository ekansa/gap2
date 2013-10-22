<?php
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', 1);
date_default_timezone_set('America/Los_Angeles');
// directory setup and class loading
set_include_path('.' . PATH_SEPARATOR . '../library/'
     . PATH_SEPARATOR . '../application/models'
     . PATH_SEPARATOR . get_include_path());

mb_internal_encoding( 'UTF-8' );
include 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('App_');


//$registry = new Zend_Registry(array('index' => $value));
//Zend_Registry::setInstance($registry);


// load configuration
$config = new Zend_Config_Ini('../application/config.ini', 'general');
$registry = Zend_Registry::getInstance();
$registry->set('config', $config);

// setup database
$db = Zend_Db::factory($config->db->adapter,
$config->db->config->toArray());
Zend_Db_Table::setDefaultAdapter($db); 
Zend_Registry::set('db', $db);


// setup controller
$frontController = Zend_Controller_Front::getInstance();
$frontController->throwExceptions(true);
$frontController->registerPlugin(new Zend_Controller_Plugin_ErrorHandler(array(
    'module'     => 'error',
    'controller' => 'error',
    'action'     => 'error'
)));

// Custom routes
$router = $frontController->getRouter();


$geoparseBatchViewRoute = new Zend_Controller_Router_Route('geoparse/batch/:batchID', array('controller' => 'geoparse', 'action' => 'batch'));
// Add it to the router
$router->addRoute('geoparseBatch', $geoparseBatchViewRoute); // 'subjects refers to a unique route name


//A given book, JSON format
$geoparseBatchJSONRoute = new Zend_Controller_Router_Route_Regex('geoparse/batch/(.*)\.json',
                                                        array('controller' => 'geoparse', 'action' => 'batchjson'),
                                                        array(1 => 'batchID'), 'geoparse/batch/%s/');
// Add it to the router
$router->addRoute('geoparseBatchJSON', $geoparseBatchJSONRoute ); // A given book, JSON format







//A given book, JSON format
$bookJSONRoute = new Zend_Controller_Router_Route_Regex('books/(.*)\.json',
                                                        array('controller' => 'books', 'action' => 'bookjson'),
                                                        array(1 => 'id'), 'books/%s/');
// Add it to the router
$router->addRoute('bookJSONRoute', $bookJSONRoute ); // A given book, JSON format

//Summary of all books JSON format
$allBooksJSONRoute = new Zend_Controller_Router_Route_Regex('books/\.json',
                                                            array('controller' => 'books', 'action' => 'alljson'));
// Add it to the router
$router->addRoute('allBooksJSONRoute', $allBooksJSONRoute ); // //Summary of all books JSON format

//Summary of all books JSON format
$allBooksJSONRouteB = new Zend_Controller_Router_Route('books.json',
                                                             array('controller' => 'books', 'action' => 'alljson'));
// Add it to the router
$router->addRoute('allBooksJSONRouteB', $allBooksJSONRouteB ); // //Summary of all books JSON format

//Summary of all books JSON format
$allBooksJSONRouteC = new Zend_Controller_Router_Route('books/.json',
                                                             array('controller' => 'books', 'action' => 'alljson'));
// Add it to the router
$router->addRoute('allBooksJSONRouteC', $allBooksJSONRouteC ); // //Summary of all books JSON format


//A given book, and page JSON format
$bookpageJSONRoute = new Zend_Controller_Router_Route_Regex('books/(.*)/page/(.*)\.json',
                                                        array('controller' => 'books', 'action' => 'bookpagejson'),
                                                        array(1 => 'docID', 2 => 'pageID'), 'books/%s/');
// Add it to the router
$router->addRoute('bookpageJSONRoute', $bookpageJSONRoute ); // A given book, JSON format





$placeJSONroute = new Zend_Controller_Router_Route_Regex('places/(.*)\.json',
                                                        array('controller' => 'places', 'action' => 'placejson'),
                                                        array(1 => 'IDgazURI'), 'places/%s/');
// Add it to the router
$router->addRoute('placeJSONroute', $placeJSONroute ); // A given book, JSON format 

$placeJSONrouteB = new Zend_Controller_Router_Route_Regex('places/(.*)\/.json',
                                                        array('controller' => 'places', 'action' => 'placejson'),
                                                        array(1 => 'IDgazURI'), 'places/%s/');
// Add it to the router
$router->addRoute('placeJSONrouteB', $placeJSONrouteB ); // A given book, JSON format 

$placeBookJSONroute = new Zend_Controller_Router_Route_Regex('places/(.*)\/books.json',
                                                        array('controller' => 'places', 'action' => 'bookjson'),
                                                        array(1 => 'IDgazURI'), 'places/%s/');
// Add it to the router
$router->addRoute('placeBookJSONroute', $placeBookJSONroute ); // A given book, JSON format 



$tokenIssuesRoute = new Zend_Controller_Router_Route('report/token-issues/:tokenID', array('controller' => 'report', 'action' => 'token-issues'));
// Add it to the router
$router->addRoute('tokenIssuesRoute', $tokenIssuesRoute); // Issues on a token, HTML



$tokenIssuesJSONroute = new Zend_Controller_Router_Route_Regex('report/token-issues/(.*)\.json',
                                                        array('controller' => 'report', 'action' => 'tokenissuesjson'),
                                                        array(1 => 'tokenID'), 'places/token-issues/%s/');
// Add it to the router
$router->addRoute('tokenIssuesJSONroute', $tokenIssuesJSONroute ); // Issues on a token, JSON format 


$placeIssuesRoute = new Zend_Controller_Router_Route('report/place-issues/:uriID', array('controller' => 'report', 'action' => 'place-issues'));
// Add it to the router
$router->addRoute('placeIssuesRoute', $placeIssuesRoute ); // Issues on a place, HTML



$placeIssuesJSONroute = new Zend_Controller_Router_Route_Regex('report/place-issues/(.*)\.json',
                                                        array('controller' => 'report', 'action' => 'placeissuesjson'),
                                                        array(1 => 'uriID'), 'places/place-issues/%s/');
// Add it to the router
$router->addRoute('placeIssuesJSONroute', $placeIssuesJSONroute ); // Issues on a token, JSON format 







$frontController->setControllerDirectory('../application/controllers');
try {
    $frontController->dispatch();

}catch (Exception $e){
    echo $e;
}