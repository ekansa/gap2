<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
ini_set("memory_limit", "3024M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class IndexController extends Zend_Controller_Action
{
    
   function indexAction(){
		$this->_helper->viewRenderer->setNoRender();
		
		echo "<h1>Index page YEAH!</h1>";
   }
   
   
	
   
  
}

