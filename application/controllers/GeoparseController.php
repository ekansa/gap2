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
			throw new Zend_Controller_Action_Exception('Cannnot find this page: '.$this->_request->getRequestUri(), 404);
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
			throw new Zend_Controller_Action_Exception('Cannnot find this page: '.$this->_request->getRequestUri(), 404);
		}
		
		$batch["documents"] = $batchObj->documents;
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($batch);
	}
   
	//review a document's parsed geospatial data
	function docReviewAction(){
		
		Zend_Loader::loadClass('Batch');
		Zend_Loader::loadClass('Document');
		$docObj = New Document;
		$doc = false;
		if(isset($_GET["docID"])){
			$docID = $_GET["docID"];
			$doc = $docObj->getByID($docID);
			$parserID = $docObj->parserID;
		}
		elseif(isset($_GET["parserID"])){
			$parserID = $_GET["parserID"];
			$doc = $docObj->getByParserID($parserID);
			$docID = $doc->id;
		}
		
		if(!$doc){
			throw new Zend_Controller_Action_Exception('Cannnot find this page: '.$this->_request->getRequestUri(), 404);
		}
		else{
			$this->view->docObj = $docObj;
		}
		
	}
   
   
}

