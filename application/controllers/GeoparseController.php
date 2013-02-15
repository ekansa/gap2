<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
ini_set("memory_limit", "3024M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class GeoparseController extends Zend_Controller_Action
{
   
   function indexAction(){
	
   }//end function
   
   
	function batchAction(){
		//$this->_helper->viewRenderer->setNoRender();
		$batchID = $this->_request->getParam('batchID');
		
		Zend_Loader::loadClass('Batch');
		Zend_Loader::loadClass('Document');
		$batchObj = New Batch;
		$batch = $batchObj->getByID($batchID);
		if(!$batch){
			$this->view->requestURI = $this->_request->getRequestUri(); 
			return $this->render('404error');
		}
		else{
			$this->view->batchObj = $batchObj;
		}
	}
	
	function batchjsonAction(){
		$this->_helper->viewRenderer->setNoRender();
		$batchID = $this->_request->getParam('batchID');
		
		Zend_Loader::loadClass('Batch');
		Zend_Loader::loadClass('Document');
		$batchObj = New Batch;
		$batch = $batchObj->getByID($batchID);
		if(!$batch){
			$this->view->requestURI = $this->_request->getRequestUri(); 
			return $this->render('404error');
		}
		
		$batch["documents"] = $batchObj->documents;
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($batch);
	}
   
   
   
}

