<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
ini_set("memory_limit", "3024M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class SeasrController extends Zend_Controller_Action
{
    
   
   function testTextAction(){
	$this->_helper->viewRenderer->setNoRender();
	Zend_Loader::loadClass('SEASR');
	$url = "http://opencontext.org/subjects/GHF1SPA0000077843";
	$seasrObj = New SEASR;
	$text = $seasrObj->setTextByURL($url);
	$response = $seasrObj->postText(2);
	
	/*
	echo "<br/> Status Code: ".$response->getStatus();
	echo "<br/> Headers: ";
	foreach($response->getHeaders() as $key => $value){
	    echo "<br/>".$key.": ".$value;
	}
	echo "<br/><br/>Body: ".$response->getBody();
	*/
	 header('Content-Type: application/json; charset=utf8');
	 echo $response->getBody();
	
   }//end function
   
   
   function makeEmptyBatchAction(){
	$this->_helper->viewRenderer->setNoRender();
	
	Zend_Loader::loadClass('Edina');
	Zend_Loader::loadClass('EdinaBatch');
	$edinaObj = New EdinaBatch;
	$edinaObj->prepSettings();
	$edinaObj->batchName = "empty-batch"; //name of the batch
	$response = $edinaObj->createBatch();
    
	echo "<br/> Status Code: ".$response->getStatus();
	echo "<br/> Headers: ";
	foreach($response->getHeaders() as $key => $value){
	    echo "<br/>".$key.": ".$value;
	}
	echo "<br/><br/>Body: ".$response->getBody();
   }//end function
   
   
   function makeBatchAction(){
	$this->_helper->viewRenderer->setNoRender();
	
	Zend_Loader::loadClass('Edina');
	Zend_Loader::loadClass('EdinaBatch');
	$edinaObj = New EdinaBatch;
	$edinaObj->prepSettings();
	$edinaObj->batchName = "test-batch-4"; //name of the batch
	
	$texts = array();
	$texts["Texts"] = array();
	$texts["Texts"][] = array("src" => "http://opencontext.org/subjects/GHF1SPA0000077843",
				  "alternate-id" => "oc1",
				  "output-format" => array("json", "kml")
				  );
	$texts["Texts"][] = array("src" => "http://opencontext.org/subjects/GHF1SPA0000077841",
				  "alternate-id" => "oc2",
				  "output-format" => array("json", "kml")
				  );
	$texts["Texts"][] = array("src" => "http://opencontext.org/subjects/GHF1SPA0000077842",
				  "alternate-id" => "oc3",
				  "output-format" => array("json", "kml")
				  );
	$edinaObj->textArray = $texts;
	
	
	$response = $edinaObj->createBatch();
    
	echo "<br/> Status Code: ".$response->getStatus();
	echo "<br/> Headers: ";
	foreach($response->getHeaders() as $key => $value){
	    echo "<br/>".$key.": ".$value;
	}
	echo "<br/><br/>Body: ".$response->getBody();
	
	//header('Content-Type: application/json; charset=utf8');
	//echo Zend_Json::encode($edinaObj->textArray);
	
   }//end function
   
   
   function getAllBatchesAction(){
    
	$this->_helper->viewRenderer->setNoRender();
	
	Zend_Loader::loadClass('Edina');
	Zend_Loader::loadClass('EdinaBatch');
	$edinaObj = New EdinaBatch;
	$edinaObj->prepSettings();
	$response = $edinaObj->getBatches();
    
	echo "<br/> Status Code: ".$response->getStatus();
	echo "<br/> Headers: ";
	foreach($response->getHeaders() as $key => $value){
	    echo "<br/>".$key.": ".$value;
	}
	echo "<br/><br/>Body: ".$response->getBody();
    
   }
   
   
   function getBatchAction(){
    
	$this->_helper->viewRenderer->setNoRender();
	
	Zend_Loader::loadClass('Edina');
	Zend_Loader::loadClass('EdinaBatch');
	$edinaObj = New EdinaBatch;
	$edinaObj->prepSettings();
	$edinaObj->batchName = "test-batch-4"; //name of the batch
	$response = $edinaObj->getBatch();
    
	echo "<br/> Status Code: ".$response->getStatus();
	echo "<br/> Headers: ";
	foreach($response->getHeaders() as $key => $value){
	    echo "<br/>".$key.": ".$value;
	}
	echo "<br/><br/>Body: ".$response->getBody();
	
   }
   
   
   function getTextAction(){
	$this->_helper->viewRenderer->setNoRender();
	
	Zend_Loader::loadClass('Edina');
	Zend_Loader::loadClass('EdinaText');
	$edinaObj = New EdinaText;
	$edinaObj->prepSettings();
	$edinaObj->batchName = "test-batch-4"; //name of the batch
	$edinaObj->resourceID = "test-batch-41"; //name of the batch
	//$edinaObj->requestURI = "http://test8.edina.ac.uk/gp-test/users/berkeley/batchjobs/test-batch-3/test-batch-32.json";
	$response = $edinaObj->getText();
    
	echo "<br/> Status Code: ".$response->getStatus();
	echo "<br/> Headers: ";
	foreach($response->getHeaders() as $key => $value){
	    echo "<br/>".$key.": ".$value;
	}
	echo "<br/><br/>Body: ".$response->getBody();
	echo "<br/>Request URI: ".$edinaObj->resourceURI;
   }
   
   function getResultAction(){
	$this->_helper->viewRenderer->setNoRender();
	
	Zend_Loader::loadClass('Edina');
	Zend_Loader::loadClass('EdinaText');
	$edinaObj = New EdinaText;
	$edinaObj->prepSettings();
	$edinaObj->requestURI = "http://unlock.edina.ac.uk/gp-test/users/berkeley/batchjobs/test-batch-4/test-batch-41.kml";
	$response = $edinaObj->getResult();
    
	echo "<br/> Status Code: ".$response->getStatus();
	echo "<br/> Headers: ";
	foreach($response->getHeaders() as $key => $value){
	    echo "<br/>".$key.": ".$value;
	}
	echo "<br/><br/>Body: ".$response->getBody();
	//echo "<br/>Request URI: ".$edinaObj->resourceURI;
   }
   
   
 
   private function makeTile($lat, $lon, $zoom){
	$xtile = floor((($lon + 180) / 360) * pow(2, $zoom));
	$ytile = floor((1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / pi()) /2 * pow(2, $zoom));
	return array($xtile , $ytile);
   }
   
   private function tileToLatLon($xtile , $ytile, $zoom){
    
	$n = pow(2, $zoom);
	$lon_deg = $xtile / $n * 360.0 - 180.0;
	$lat_deg = rad2deg(atan(sinh(pi() * (1 - 2 * $ytile / $n))));
	
	return array($lat_deg, $lon_deg);
   }
   
   
}

