<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
//ini_set("max_execution_time", "0");
//error_reporting(0);
//ini_set("memory_limit", "5120M");

class imagesController extends Zend_Controller_Action
{
    
    public function undefinedAction(){
	$this->_helper->viewRenderer->setNoRender();
	echo false;
    }
   
}

