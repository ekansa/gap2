<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
ini_set("memory_limit", "3024M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class EdinaController extends Zend_Controller_Action
{
   
	
   function createBatchAction(){
		$this->_helper->viewRenderer->setNoRender();
		
		Zend_Loader::loadClass('Batch');
		Zend_Loader::loadClass('Document');
		Zend_Loader::loadClass('Geoparser_Edina');
		Zend_Loader::loadClass('Geoparser_EdinaBatch');
		
		$batchObj = New Batch;
		$batchObj->title = $_REQUEST["batchTitle"];
		$batchObj->note = $_REQUEST["batchNote"];
		$batchID = $batchObj->addBatch();
		$parserID = $batchObj->parserID;
		
		$docObj = New Document;
		$docObj->batchID = $batchID;
		$urls = $_REQUEST["docURL"];
		$i = 0;
		$docTitles = $_REQUEST["docTitle"];
		$texts = array();
		$requestEdina = false;
		foreach($urls as $url){
			$docTitle = $docTitles[$i];
			$docObj->url = $url;
			$docObj->title = $docTitle;
			$docID = $docObj->addDocument();
			if($docID != false){
				$requestEdina = true;
				$texts["Texts"][] = array("src" => $url,
					  "alternate-id" => $docID,
					  "output-format" => array("json", "kml")
					  );
			}
			$i++;
		}
		
		//$requestEdina = false;
		if($requestEdina){
			//only do this if there's a text to parse
			$edinaObj = New Geoparser_EdinaBatch;
			$edinaObj->prepSettings();
			$edinaObj->batchName = $parserID; //name / parser ID of the batch
			$edinaObj->textArray = $texts;
			$response = $edinaObj->createBatch();
			$batchStatus = $response->getStatus();
			$responseJSON = $response->getBody();
			if($batchStatus>= 200 && $batchStatus <300){
				$batchObj->updateStatus("pending");
			}
			else{
				$batchObj->updateStatus("error");
			}
		}
	
		//header('Content-Type: application/json; charset=utf8');
		//echo Zend_Json::encode(array("batchID" => $batchID, "parserID" => $parserID, "texts" => $texts));
		$location = "../geoparse/batch/".$batchID;
		header("Location: ".$location); 
   }//end function
   
	
	function batchStatusAction(){
    
		$this->_helper->viewRenderer->setNoRender();
		
		Zend_Loader::loadClass('Batch');
		Zend_Loader::loadClass('Document');
		Zend_Loader::loadClass('Geoparser_Edina');
		Zend_Loader::loadClass('Geoparser_EdinaBatch');
		
		$batchObj = New Batch;
		if(isset($_GET["batchID"])){
			$batchID = $_GET["batchID"];
			$batch = $batchObj->getByID($batchID);
		}
		elseif(isset($_GET["parserID"])){
			$parserID = $_GET["parserID"];
			$batch = $batchObj->getByParserID($parserID);
			$batchID = $batchObj->id;
		}
		
		if(!$batch){
			$this->view->requestURI = $this->_request->getRequestUri(); 
			return $this->render('404error');
		}
		
		$edinaObj = New Geoparser_EdinaBatch;
		$edinaObj->prepSettings();
		$edinaObj->batchName = $batchObj->parserID; //name of the batch
		$responseJSON = $edinaObj->cacheGetBatch(); //get the status, but use the cache
		if(!$edinaObj->HTTPstatusOK){
			$status = "error";
		}
		else{
			$status = "ok";
			$respObj = Zend_Json::decode($responseJSON);
			if(!$edinaObj->cacheUsed){
				//cache was not used to generate the resulse, so we can update the status of the batch and its documents
				$batchObj->updateStatus("pending");
				if(isset($respObj["Texts"])){
					if(is_array($respObj["Texts"])){
						$docObj = New Document;
						$docObj->batchID = $batchID;
						
						foreach($respObj["Texts"] as $text){
							$resourceID = $text["resource-id"];
							$docStatus = $text["status"];
							$data = array("parserID" => $resourceID,
											  "status" => $docStatus
											  );
							
							if($docStatus == "complete"){
								$data["pLinks"] = Zend_Json::encode($text["output"]);
							}
							
							$docObj->url = $text["src"];
							$docObj->updateDataByBatchURL($data);
						}
					}
				}
			}
			
			unset($batchObj);
			unset($docObj);
			$batchObj = New Batch;
			$batch = $batchObj->getByID($batchID);
			$batch["documents"] = $batchObj->documents;
			$batch["edina-resp"] = $respObj;
			header('Content-Type: application/json; charset=utf8');
			echo Zend_Json::encode($batch);
			
		}
	
   }
	
	
	function docReviewAction(){
    
		$this->_helper->viewRenderer->setNoRender();
		
		Zend_Loader::loadClass('Batch');
		Zend_Loader::loadClass('Document');
		$docObj = New Document;
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
		
		if(isset($_GET["type"])){
			$type = $_GET["type"];
			if(substr($type, 0, 1) != "."){
				$type = ".".$type;
			}
		}
		else{
			$type = "";
		}
		
		$format = "json"; //default to JSON format result
		$outputHeader = 'Content-Type: application/json; charset=utf8';
		if(isset($_GET["format"])){
			$format = $_GET["format"];
			if($format == "xml"){
				$outputHeader = 'Content-Type: application/xml; charset=utf8';
			}
		}
		
		if(!$doc){
			$this->view->requestURI = $this->_request->getRequestUri(); 
			return $this->render('404error');
		}
		else{
			$doc["result"] = false; //default to no result found
			$output = Zend_Json::encode($doc);
			$batchObj = New Batch;
			$batch = $batchObj->getByID($docObj->batchID);
			
			Zend_Loader::loadClass('Geoparser_Edina');
			Zend_Loader::loadClass('Geoparser_EdinaText');
			$edinaObj = New Geoparser_EdinaText;
			$edinaObj->prepSettings();
			$edinaObj->batchName = $batchObj->parserID; //Parser ID of the batch
			$edinaObj->resourceID = $docObj->parserID; //Parser ID of the resource for parsing
			$edinaObj->resultFormat = $format;
			if(is_array($docObj->pLinks)){
				$requestFile = $docObj->parserID.$type.".".$format; //this is the file type to get
				$requestLink = false;
				foreach($docObj->pLinks as $pLink){
					if(strstr($pLink, $requestFile)){
						$requestLink = $pLink; //yeah! we found the same format, and lemnatization status, so use this link
						break;
					}
				}
				$edinaObj->resourceURI = $requestLink;
				$respBody = $edinaObj->cacheGetResult(); //get the result or use the cache to get it
				if($edinaObj->HTTPstatusOK) {
					if($format == "json"){
						$doc["result"] = Zend_Json::decode($respBody);
						$output = Zend_Json::encode($doc);
					}
					else{
						$output = $respBody;
					}
				}
			}
			
			
			header($outputHeader);
			echo $output;
			
		}//end case with document in database
	}
	
	
	//store tokens
	function storeDataAction(){
    
		$this->_helper->viewRenderer->setNoRender();
		
		Zend_Loader::loadClass('Batch');
		Zend_Loader::loadClass('Document');
		Zend_Loader::loadClass('Geoparser_Edina');
		Zend_Loader::loadClass('Geoparser_EdinaText');
		Zend_Loader::loadClass('Geoparser_EdinaParse');
		Zend_Loader::loadClass('Tokens');
		Zend_Loader::loadClass('GazetteerRefs');
		
		$docID = $_GET["docID"];
		$parseObj = New Geoparser_EdinaParse;
		$xmlString = $parseObj->getLemnatizedXMLbyID($docID);
		$tokenIDs = $parseObj->storeParsedTokens($xmlString);
		$tokenCount = count($tokenIDs);
		$xmlString = $parseObj->getGazetteerXMLbyID($docID);
		$gazRefs = $parseObj->storeGazetteerRefs($xmlString);
		$gazRefCounts = count($gazRefs);
		
		$output = array("tokenCount" => $tokenCount,
							 "gazRefCount" => $gazRefCounts,
							 "gazRefs" => $gazRefs,
							 "tokenIDs" => $tokenIDs);
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($output);
	}
	
	
   
}

