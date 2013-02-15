<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
// set maximum execution time to no limit
ini_set("max_execution_time", "0");
//error_reporting(0);
ini_set("memory_limit", "5120M");
//@ini_set('display_errors', 0);

class ReportIssueController extends Zend_Controller_Action
{   
    public function init() {
        /* Initialize action controller here */
    }
      
    public function indexAction()
    {
	$this->_helper->viewRenderer->setNoRender();
	echo "Nothing here, yet...";
    }
    
    public function OLDgoogleEarthAction(){
	$this->_helper->viewRenderer->setNoRender();
	$requestParams =  $this->_request->getParams();
    }
 
 
 
}//end class

