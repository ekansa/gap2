<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
// set maximum execution time to no limit
ini_set("max_execution_time", "0");
//error_reporting(0);
ini_set("memory_limit", "5120M");
//@ini_set('display_errors', 0);

class ReportController extends Zend_Controller_Action
{   
    public function init() {
        /* Initialize action controller here */
    }
      
    public function indexAction()
    {
		  $this->_helper->viewRenderer->setNoRender();
		  echo "Nothing to see here!";
    }
	 
	  //deletes an issue. this doesn't really delete it, it just makes it go inactive
	 public function searchAction(){
		  $requestParams = $this->_request->getParams();
		  Zend_Loader::loadClass("Issues");
		  Zend_Loader::loadClass("Tokens");
		  Zend_Loader::loadClass("Document");
		  Zend_Loader::loadClass("GazetteerRefs");
		  Zend_Loader::loadClass("GazetteerURIs");
		  
		  $docID = 1; //default to the first doc
		  $relatedPlaceTokens = false;
		  $placeURIs = false;
		  $foundTokens = false;
		  if(isset($requestParams["uri"])){
				$this->view->searchTerm = $requestParams["uri"];
				$this->view->searchType = "Place URI";
				$tokensObj = new Tokens;
				//$tokensObj->tokenStructure($docID);
				$relatedPlaceTokens = $tokensObj->getUniqueTokensFromPlaceURI($requestParams["uri"]);
				
				if(isset($requestParams["docID"])){
					 if(strlen($requestParams["docID"])> 0){
						  $docID = $requestParams["docID"];
					 }
				}
				$gazRefObj = new GazetteerRefs;
				$placeURIs = $gazRefObj->getListofURIs($docID);
		  }
		  elseif(isset($requestParams["q"])){
				$searchTerm = $requestParams["q"];
				$this->view->searchTerm = $searchTerm;
				$this->view->searchType = "Token String";
				$startPage = false;
				$endPage = false;
				$structure = false;
				$page = 1; //default for paging through requests
				
				if(isset($requestParams["docID"])){
					 if(strlen($requestParams["docID"])> 0){
						  $docID = $requestParams["docID"];
					 }
				}
				if(isset($requestParams["startPage"])){
					 if(strlen($requestParams["startPage"])> 0){
						  $startPage = $requestParams["startPage"];
					 }
				}
				if(isset($requestParams["endPage"])){
					 if(strlen($requestParams["endPage"])> 0){
						  $endPage = $requestParams["endPage"];
					 }
				}
				if(isset($requestParams["endPage"])){
					 if(strlen($requestParams["endPage"])> 0){
						  $endPage = $requestParams["endPage"];
					 }
				}
				if(isset($requestParams["page"])){
					 if(strlen($requestParams["page"])> 0){
						  $page = $requestParams["page"];
					 }
				}
				
				$tokensObj = new Tokens;
				$foundTokens = $tokensObj->getTokensByToken($searchTerm, $docID, $page, $startPage, $endPage, $structure);
		  }
		  
		  $this->view->docID = $docID;
		  $this->view->requestParams = $requestParams;
		  $this->view->relatedPlaceTokens = $relatedPlaceTokens;
		  $this->view->placeURIs = $placeURIs;
		  $this->view->foundTokens = $foundTokens;
	 }
	 
    
	 public function placeIssuesAction(){
		  $requestParams = $this->_request->getParams();
		  Zend_Loader::loadClass("Issues");
		  Zend_Loader::loadClass("Tokens");
		  Zend_Loader::loadClass("Document");
		  Zend_Loader::loadClass("GazetteerRefs");
		  if(isset($requestParams["uriID"])){
				
				$uriID = $requestParams["uriID"];
				$issuesObj = new Issues;
				$this->view->placeIssues = $issuesObj->getPlaceIssues($uriID);
				$this->view->uriErrors = false;
		  }
		  else{
				$this->view->requestURI = $this->_request->getRequestUri(); 
				return $this->render('404error');
		  }
	 }
	 
	 public function placeissuesjsonAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams = $this->_request->getParams();
		  Zend_Loader::loadClass("Issues");
		  Zend_Loader::loadClass("Tokens");
		  Zend_Loader::loadClass("Document");
		  Zend_Loader::loadClass("GazetteerRefs");
		  Zend_Loader::loadClass("GazetteerURIs");
		  
		  if(isset($requestParams["uriID"])){
				$uriID = $requestParams["uriID"];
				$issuesObj = new Issues;
				$message = $issuesObj->getPlaceIssues($uriID);
				$gazURIobj = new GazetteerURIs;
				$message["badURIs"] = $gazURIobj->uniqueFalsePlaces();
				if(!$message){
					 $this->view->requestURI = $this->_request->getRequestUri(); 
					 return $this->render('404error');
				}
		  }
		  else{
				header("HTTP/1.0 403 Forbidden");
				$message = "Need a tokenID";
		  }
		  
		  $output = Zend_Json::encode($message);
		  if(isset($requestParams["callback"])){
				header('Content-Type: application/javascript; charset=utf8');
				$output = $requestParams["callback"]."(".$output.");";
		  }
		  else{
				header('Content-Type: application/json; charset=utf8');
				header("Access-Control-Allow-Origin: *");
		  }
		  echo $output;
	 }
	 
	 
	 //use pleiades data to update this place
	 function changePlaceUriAction(){
		  $requestParams = $this->_request->getParams();
		  Zend_Loader::loadClass("Issues");
		  Zend_Loader::loadClass("Tokens");
		  Zend_Loader::loadClass("Document");
		  Zend_Loader::loadClass("GazetteerRefs");
		  Zend_Loader::loadClass("GazetteerURIs");
		  if(isset($requestParams["uriID"]) && isset($requestParams["newURI"])){
				
				$uriID = $requestParams["uriID"];
				$gazURIobj = new GazetteerURIs;
				$issuesObj = new Issues;
				$newPlaceID = $gazURIobj->getOrAddPlaceRecord($requestParams["newURI"]);
				if($newPlaceID != false){
					 $this->_helper->viewRenderer->setNoRender();
					 $issuesObj->updateURIcloseIssues($uriID, $newPlaceID);
					 $docID = $issuesObj->getDocumentAssociationByOldPlace($uriID);
					 if($docID != false){
						  $location = "../../gapvis/index.html#book/".$docID."/place/".$newPlaceID;
					 }
					 else{
						  $location = "../../report/place-issues/".$newPlaceID;
					 }
					 header("Location: ".$location);
					 
				}
				else{
					 //problem! the new URI seems to have had some errors
					 $this->view->placeIssues = $issuesObj->getPlaceIssues($uriID);
					 $this->view->uriErrors = $gazURIobj->errors;
					 $this->render("place-issues");
				}
		  }
		  else{
				header("HTTP/1.0 403 Forbidden");
				$message = "Need a uriID amd a uri";
				echo $message;
		  }
	 }
	 
	 
	 //use pleiades data to update this place
	 function apiPleiadesUpdateAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams = $this->_request->getParams();
		  Zend_Loader::loadClass("GazetteerURIs");
		  if(isset($requestParams["uri"])){
				
				$gazURIobj = new GazetteerURIs;
				$gazURIobj->updatePleiadesData($requestParams["uri"]);
				$location = "../../report/search/?uri=".urlencode($requestParams["uri"]);
				header("Location: ".$location);
		  }
		  else{
				header("HTTP/1.0 403 Forbidden");
				$message = "Need a uriID amd a uri";
				echo $message;
		  }
	 }
	 
	 
	 
    public function tokenIssuesAction(){
		  $requestParams = $this->_request->getParams();
		  Zend_Loader::loadClass("Issues");
		  Zend_Loader::loadClass("Tokens");
		  Zend_Loader::loadClass("Document");
		  Zend_Loader::loadClass("GazetteerRefs");
		  Zend_Loader::loadClass("GazetteerURIs");
		  if(isset($requestParams["tokenID"])){
				
				$tokenID = $requestParams["tokenID"];
				$issuesObj = new Issues;
				$this->view->tokenIssues = $issuesObj->getTokenIssues($tokenID);
				$this->view->requestParams = $requestParams;
		  
		  }
		  else{
				$this->view->requestURI = $this->_request->getRequestUri(); 
				return $this->render('404error');
		  }
	 }
	 
	 public function tokenissuesjsonAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams = $this->_request->getParams();
		  Zend_Loader::loadClass("Issues");
		  Zend_Loader::loadClass("Tokens");
		  Zend_Loader::loadClass("Document");
		  if(isset($requestParams["tokenID"])){
				$tokenID = $requestParams["tokenID"];
				$issuesObj = new Issues;
				$message = $issuesObj->getTokenIssues($tokenID);
				if(!$message){
					 $this->view->requestURI = $this->_request->getRequestUri(); 
					 return $this->render('404error');
				}
		  }
		  else{
				header("HTTP/1.0 403 Forbidden");
				$message = "Need a tokenID";
		  }
		  
		  $output = Zend_Json::encode($message);
		  if(isset($requestParams["callback"])){
				header('Content-Type: application/javascript; charset=utf8');
				$output = $requestParams["callback"]."(".$output.");";
		  }
		  else{
				header('Content-Type: application/json; charset=utf8');
				header("Access-Control-Allow-Origin: *");
		  }
		  echo $output;
	 }
	 
    public function addIssueAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $postParams =  $this->_request->getPost();
	
		  if($postParams){
				Zend_Loader::loadClass("Issues");
				$issuesObj = new Issues;
				$issuesObj->postParams = $postParams;
				$success = $issuesObj->postIssue();
				
				if($success){
					 $message = array("success" => $success, "message" => "Issue successfully reported");
					 $output = Zend_Json::encode($message);
				}
				else{
					 header("HTTP/1.0 403 Forbidden");
				}
		  }
		  else{
				$message = array("success" => false, "message" => "Please POST issues to this URI.");
				header("HTTP/1.0 405 Method Not Allowed");
				header("Allow: POST");
		  }
		  
		  $output = Zend_Json::encode($message);
		  if(isset($requestParams["callback"])){
				header('Content-Type: application/javascript; charset=utf8');
				$output = $requestParams["callback"]."(".$output.");";
		  }
		  else{
				header('Content-Type: application/json; charset=utf8');
				header("Access-Control-Allow-Origin: *");
		  }
		  echo $output;
	 }
	 
	 
	 
	 public function updateTokenPlaceAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $postParams =  $this->_request->getPost();
	
		  if($postParams){
				Zend_Loader::loadClass("Issues");
				Zend_Loader::loadClass("Tokens");
				Zend_Loader::loadClass("Document");
				Zend_Loader::loadClass("GazetteerRefs");
				Zend_Loader::loadClass("GazetteerURIs");
				$issuesObj = new Issues;
				$issuesObj->postParams = $postParams;
				$success = $issuesObj->alterTokensPlace();
				
				if(!$issuesObj->nextTokenID){
					 $location = "../../report/token-issues/".$postParams["tokenID"];
				}
				else{
					 $location = "../../report/token-issues/".$issuesObj->nextTokenID."?lastIssueID=".$issuesObj->lastIssueID."&newPlaceURI=".urlencode($issuesObj->newPlaceURI);
				}
				
				header("Location: ".$location);
		  }
		  else{
				$message = array("success" => false, "message" => "Please POST issues to this URI.");
				header("HTTP/1.0 405 Method Not Allowed");
				header("Allow: POST");
		  }
		  
	 }
	 
	 
	 
	 
	 
	 //deletes an issue. this doesn't really delete it, it just makes it go inactive
	 public function deleteIssueAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $postParams =  $this->_request->getPost();
		  Zend_Loader::loadClass("Issues");
		  $issuesObj = new Issues;
		  $success = false;
		  if($postParams){
				if(isset($postParams["tokenID"]) && isset($postParams["issueID"])){
					 $issuesObj->deleteIssueByID($postParams["issueID"]);
					 $location = "../../report/token-issues/".$postParams["tokenID"];
					 header("Location: ".$location);
				}
				else{
					 header("HTTP/1.0 403 Forbidden");
					 echo "Need to POST to tokenID and issueID";
				}
				
		  }
		  else{
				$message = array("success" => false, "message" => "Please POST issues to this URI.");
				header('Content-Type: application/json; charset=utf8');
				header("HTTP/1.0 405 Method Not Allowed");
				header("Allow: POST");
				$output = Zend_Json::encode($message);
				echo $output;
		  }
	 }
	 
	 //deletes an issue. this doesn't really delete it, it just makes it go inactive
	 public function updateIssueAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $postParams =  $this->_request->getPost();
		  Zend_Loader::loadClass("Issues");
		  $issuesObj = new Issues;
		  $success = false;
		  if($postParams){
				if(isset($postParams["tokenID"]) && isset($postParams["issueID"]) && isset($postParams["newNote"])){
					 $issuesObj->updateIssueByID($postParams["issueID"], $postParams["newNote"]);
					 $location = "../../report/token-issues/".$postParams["tokenID"];
					 header("Location: ".$location);
				}
				else{
					 header("HTTP/1.0 403 Forbidden");
					 echo "Need to POST to tokenID and issueID";
				}
				
		  }
		  else{
				$message = array("success" => false, "message" => "Please POST issues to this URI.");
				header('Content-Type: application/json; charset=utf8');
				header("HTTP/1.0 405 Method Not Allowed");
				header("Allow: POST");
				$output = Zend_Json::encode($message);
				echo $output;
		  }
	 }
 
 

 
 
 
 
 
 
}//end class

