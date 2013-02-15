<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
//ini_set("max_execution_time", "0");
//error_reporting(0);
//ini_set("memory_limit", "5120M");

class PlacesController extends Zend_Controller_Action
{
    
	 
	 
	 //this gives JSON for the books referencing a given place
    public function bookjsonAction(){
		  
		  Zend_Loader::loadClass("Documents");
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		  
		  $IDgazURI = false;
		  $uri = false;
		  if(isset($requestParams["IDgazURI"])){
				$IDgazURI	= $requestParams["IDgazURI"];
		  }
		  if(isset($requestParams["uri"])){
				$uri = $requestParams["uri"];
		  }
		  
		  $DocObj = new Documents;
		  if($uri != false){
				Zend_Loader::loadClass("GazetteerRefs");
				$DocObj->getGapVisWithPlaceURI($uri);
		  }
		  else{
				$DocObj->getGapVisWithPlaceID($IDgazURI);
		  }
		  
		  $data = $DocObj->documentData;
		  unset($DocObj);
		  $output = Zend_Json::encode($data);
		  
		  if(isset($requestParams["callback"])){
			  header('Content-Type: application/javascript; charset=utf8');
			  $output = $requestParams["callback"]."(".$output.");";
			  echo $output;
		  }
		  else{
			  header('Content-Type: application/json; charset=utf8');
			  header("Access-Control-Allow-Origin: *");
			  echo $output; //outputs JSON of a given book's word cloud
		  }
		  
    }//end function
    
    
	 public function placejsonAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		  
		  Zend_Loader::loadClass("GazetteerRefs");
		  $data = false;
		  $gazObj = new GazetteerRefs;
		  if(isset($requestParams["IDgazURI"])){
				$IDgazURI	= $requestParams["IDgazURI"];
				$data = $gazObj->getGapVisPlace($IDgazURI);
		  }
		  
		  if(!$data){
				$this->view->requestURI = $this->_request->getRequestUri(); 
				return $this->render('404error');
		  }
		  else{
				$output = Zend_Json::encode($data);
		  }
	 
		  if(isset($requestParams["callback"])){
			  header('Content-Type: application/javascript; charset=utf8');
			  $output = $requestParams["callback"]."(".$output.");";
			  echo $output;
		  }
		  else{
			  header('Content-Type: application/json; charset=utf8');
			  header("Access-Control-Allow-Origin: *");
			  echo $output; //outputs JSON of a given book's word cloud
		  }
	 }

   
}

