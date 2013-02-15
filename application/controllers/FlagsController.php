<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
//ini_set("max_execution_time", "0");
//error_reporting(0);
//ini_set("memory_limit", "5120M");

class FlagsController extends Zend_Controller_Action
{
    
    public function errorAction(){
	/*
	$request = $this->getRequest();

        // Check if we have a POST request
        if ($request->isPost()) {
            //return $this->_request->setActionName('newerror');
	    $this->_forward('newerror', 'flags');
	    break;
        }
	else{
	
	    $this->_helper->viewRenderer->setNoRender();
	    $requestParams =  $this->_request->getParams();
	    $output = Zend_Json::encode($requestParams);
	    header('Content-Type: application/json; charset=utf8');
	    header("Access-Control-Allow-Origin: *");
	    echo Zend_Json::encode($output);
	}
	*/
    }//end function
    
    
    public function viewAction(){
	$this->_helper->viewRenderer->setNoRender();
	$requestParams =  $this->_request->getParams();
	
	$id = $requestParams["id"];
	$flagObj = new ErrorFlags;
	$output = $flagObj->getFlagByID($id);
	
	$bookObj = new Book;
	$bookObj->initialize($flagObj->bookID);
	$bookObj->get_book_meta();
	$output["pageID"] = $bookObj->convertPageOffset($flagObj->bookPartID);
	unset($output["bookPartID"]);
	
	header('Content-Type: application/json; charset=utf8');
	echo Zend_Json::encode($output);
    }//end function
    
    
    
    
    public function newAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	$requestParams =  $this->_request->getParams();
	$request     = $this->getRequest();
	$contentType = $request->getHeader('Content-Type');
	$rawBody = $request->getRawBody();
	@$testJSON = Zend_Json::decode($rawBody);
	if(is_array($testJSON)){
	    $requestParams = $testJSON;
	}
	
	
	$flagObj = new ErrorFlags;
	
	
	if(isset($requestParams["bookid"])){
	    $flagObj->bookID = $requestParams["bookid"];
	}
	if(isset($requestParams["placeid"])){
	    $flagObj->placeID = $requestParams["placeid"];
	}
	if(isset($requestParams["pageid"])){
	    $flagObj->bookPartID = $requestParams["pageid"];
	    
	    //deal with page offsets
	    $bookObj = new Book;
	    $bookObj->initialize($flagObj->bookID);
	    $bookObj->get_book_meta();
	    $flagObj->bookPartID = $bookObj->convertPageToRaw($flagObj->bookPartID);
	    
	}
	if(isset($requestParams["token"])){
	    $flagObj->text_string = $requestParams["token"];
	}
	if(isset($requestParams["note"])){
	    $flagObj->note = $requestParams["note"];
	}
	
	
	$flagObj->tokenID = false;
	$flagObj->status = false;
	$flagObj->error_type = false;
	$flagObj->user_email = false;
	
	
	$oldMaxID = $flagObj->getMaxID();
	$newID = $flagObj->createNew();
	if($newID > $oldMaxID){
	    $success = true;
	    $error = false;
	}
	else{
	    $success = false;
	    $error = "Something horrible happened! Your note did not save. :( ";
	}
	
	$output = array("success" => $success, "flagID" => $newID, "error" => $error, "postedData" => $requestParams);
	header('Content-Type: application/json; charset=utf8');
	echo Zend_Json::encode($output);
    }
    
    
    
    function checkPostAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	
	$client = new Zend_Http_Client("http://gap.alexandriaarchive.org/flags/new");
	
	
	$json = '{"bookid":"2","placeid":"423025","token":"Roma","pageid":"73","note":"test"}';
	
	$client->setRawData($json, 'application/json'); //put the JSON data into the body of the HTTP request, set mime as JSON 
    
	$response = $client->request("POST"); //send request using POST method
	
	echo print_r($response);
	
	/*$output = Zend_Json::encode($response);
	
	header('Content-Type: application/json; charset=utf8');
	header("Access-Control-Allow-Origin: *");
	echo $output; //outputs JSON of a given book's word cloud
	*/
    }    



   
}

