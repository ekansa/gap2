<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';

ini_set("memory_limit", "256M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class BooksController extends Zend_Controller_Action
{
    
    
    /*
    get JSON data on a book, as specified by Nick at
    https://github.com/nrabinowitz/gapvis/tree/master/stub_api
    */
    public function alljsonAction(){

		  Zend_Loader::loadClass("Documents");
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		  $host = App_Config::getHost();
		  
		  $DocObj = new Documents;
		  $DocObj->getAllComplete();
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
    
	 
	 
	 /*
    get JSON data on a book, as specified by Nick at
    https://github.com/nrabinowitz/gapvis/tree/master/stub_api
    */
    public function bookjsonAction(){

		  Zend_Loader::loadClass("Document");
		  Zend_Loader::loadClass("GazetteerRefs");
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		  $host = App_Config::getHost();
		  
		  $output = array("found" => $requestParams);
		  
		  if(isset($requestParams['id'])){
				$docID = $requestParams['id'];
				$docObj = new Document;
				$data = $docObj->getGapVisDataByID($docID);
				if(!$data){
					 $this->view->requestURI = $this->_request->getRequestUri(); 
					 return $this->render('404error');
				}
				unset($docObj);
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
				
		  }
		 
    }//end function
	 
	 
    //JSON for text from a given page
    public function bookpagejsonAction(){
	
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		  
		  $data = false;
		  if(isset($requestParams['docID']) && isset($requestParams['pageID'])){
				$docID = $requestParams['docID'];
				$pageID = $requestParams['pageID'];
				Zend_Loader::loadClass("Tokens");
				$tokObj = new Tokens;
				$text = $tokObj->getGapVisDocPage($docID, $pageID);
				if($text != false){
					 $data = array("text" => $text, "image" => false);
				}
		  }
		  if(!$data){
				$this->view->requestURI = $this->_request->getRequestUri(); 
				return $this->render('404error');
		  }
		  else{
				$output = Zend_Json::encode($data);
		  }
		  //$output["memory"] = memory_get_usage(true);
		  header('Content-Type: application/json; charset=utf8');
		  header("Access-Control-Allow-Origin: *");
		  echo $output;
		  
    }//end function
    
    
    //JSON for generating a tag cloud of words
    public function bookwordsjsonAction(){
	
		  $this->_helper->viewRenderer->setNoRender();
		  
		  //$output["memory"] = memory_get_usage(true);
		  header('Content-Type: application/json; charset=utf8');
		  header("Access-Control-Allow-Origin: *");
		  echo Zend_Json::encode($output);
	
    }//end function
    
   
}

