<?php

/*
Interacts with the Edina API



*/

class Geoparser_EdinaText {
 
//database content 
public $user;
public $password;
public $resourceID;
public $resourceURI;
public $userKey;

public $resultFormat;

public $HTTPstatusOK; //boolean, was the response (or cache retrieval) OK
public $cacheUsed; //boolean, was the cache used to get the response? 

private $APIuri; //base uri API
private $requestHost; //requester Host

const HTTPtimeout = 240; //allow 240 seconds before timeout of HTTP request
const resultCacheLife = 720000; // cache lifetime, measured in seconds, 7200 = 2 hours
const resultCache = "./result_cache/"; // Directory where to put the cache files;

//prepare general API settings
function prepSettings(){
    $edinaPrep = new Geoparser_Edina;
    $this->APIuri = $edinaPrep->get_API_URI();
    $this->requestHost = $edinaPrep->get_requestHost();
    $this->user = $edinaPrep->get_user();
    $this->password = $edinaPrep->get_password();
    $this->userKey = $edinaPrep->get_userKey();
    $this->resourceURI = false;
    $this->resultFormat = "json"; //default to JSON
}

/*
This gets metadata and the status of a document processed by the Geoparser
it also returns links to results in different formats (XML, JSON, KML)
*/
function getText(){
    
    if(!$this->resourceURI){
		  $requestURI = $this->APIuri."/users/".$this->user."/batchjobs/".urlencode($this->batchName)."/".$this->resourceID;
		  //$requestURI = $this->APIuri."/users/".$this->user."/batchjobs/".$this->batchName;
		  $this->resourceURI = $requestURI;
    }
    else{
		  $requestURI = $this->resourceURI;
    }
    
    $client = new Zend_Http_Client($requestURI, array(
		  'maxredirects' => 1,
		  'timeout'      => self::HTTPtimeout));
	 
    //$client->setHeaders('Host', $this->requestHost);
    $client->setHeaders('Accept', 'application/json');
    $client->setHeaders('AUTHORIZATION', $this->password);
    //$client->setHeaders('User-Key', $this->userKey);
    
    @$response = $client->request("GET");
    return $response;
}

/*
This retrieves the results in of the geoparser analysis in a specific format (JSON, KML, XML)
*/

function getResult(){
    
    if(!$this->resourceURI){
		  $requestURI = $this->APIuri."/users/".$this->user."/batchjobs/".urlencode($this->batchName)."/".$this->resourceID.".".$this->resultFormat;
		  //$requestURI = $this->APIuri."/users/".$this->user."/batchjobs/".$this->batchName;
		  $this->resourceURI = $requestURI;
    }
    else{
		  $requestURI = $this->resourceURI;
    }
    
    $client = new Zend_Http_Client($requestURI, array(
		  'maxredirects' => 1,
		  'timeout'      => self::HTTPtimeout));
	 
	 
    //$client->setHeaders('Host', $this->requestHost);
	 if($this->resultFormat == "xml"){
		  $client->setHeaders('Accept', 'application/json');
	 }
	 else{
		  $client->setHeaders('Accept', 'application/json');
	 }
    //$client->setHeaders('Accept', 'application/json');
    $client->setHeaders('AUTHORIZATION', $this->password);
    //$client->setHeaders('User-Key', $this->userKey);
    
    @$response = $client->request("GET");
    return $response;
    
}

function getResultStream(){
    
    if(!$this->resourceURI){
		  $requestURI = $this->APIuri."/users/".$this->user."/batchjobs/".urlencode($this->batchName)."/".$this->resourceID.".".$this->resultFormat;
		  //$requestURI = $this->APIuri."/users/".$this->user."/batchjobs/".$this->batchName;
		  $this->resourceURI = $requestURI;
    }
    else{
		  $requestURI = $this->resourceURI;
    }
    
	 $uriExplode = explode("/", $this->resourceURI);
	 $fileName = $uriExplode[(count($uriExplode) -1)];
	 $tempID = preg_replace('/[^a-z0-9]/i', '_', $fileName);
	 $tempID_a = $tempID."-temp-a";
	 $tempID_b = $tempID."-temp-b";
	 
    $client = new Zend_Http_Client($requestURI, array(
		  'maxredirects' => 1,
		  'timeout'      => self::HTTPtimeout));
	 
	 $client->setStream();
	 
    //$client->setHeaders('Host', $this->requestHost);
	 if($this->resultFormat == "xml"){
		  $client->setHeaders('Accept', 'application/json');
	 }
	 else{
		  $client->setHeaders('Accept', 'application/json');
	 }
    //$client->setHeaders('Accept', 'application/json');
    $client->setHeaders('AUTHORIZATION', $this->password);
    //$client->setHeaders('User-Key', $this->userKey);
    
    $response = $client->request("GET");
	 copy($response->getStreamName(), self::resultCache."/".$tempID_a);
	 $fp = fopen( self::resultCache."/".$tempID_b, "w");
	 stream_copy_to_stream($response->getStream(), $fp);
	 $client->setStream(self::resultCache."/".$tempID_a)->request('GET');
	 
    return $response;
    
}

function cacheGetResult(){

	 if(!$this->resourceURI){
		  $requestURI = $this->APIuri."/users/".$this->user."/batchjobs/".urlencode($this->batchName)."/".$this->resourceID.".".$this->resultFormat;
		  //$requestURI = $this->APIuri."/users/".$this->user."/batchjobs/".$this->batchName;
		  $this->resourceURI = $requestURI;
    }
    else{
		  $requestURI = $this->resourceURI;
    }

	 $frontendOptions = $frontendOptions = array(
				 'lifetime' => self::resultCacheLife,
				 'automatic_serialization' => true
	  );
	 
	 $backendOptions = array(
			'cache_dir' => self::resultCache // Directory where to put the cache files
	  );
	 
	 $cache = Zend_Cache::factory('Core',
								 'File',
								 $frontendOptions,
								 $backendOptions);
	 
	 $uriExplode = explode("/", $this->resourceURI);
	 $fileName = $uriExplode[(count($uriExplode) -1)];
	 $cacheID = preg_replace('/[^a-z0-9]/i', '_', $fileName);
	 if(!$cache_result = $cache->load($cacheID)) {
		  $this->cacheUsed = false;
		  $response = $this->getResult(); //do an HTTP request
		  $batchStatus = $response->getStatus();
		  $responseBody = $response->getBody();
		  if($batchStatus>= 200 && $batchStatus <300){
				$this->HTTPstatusOK = true;
				$cache->save($responseBody, $cacheID ); //save result to the cache
				return $responseBody;
		  }
		  else{
				$this->HTTPstatusOK = false;
				return $response;
		  }
	 }
	 else{
		  $this->cacheUsed = true;
		  $this->HTTPstatusOK = true;
		  return $cache_result;
	 }

}




/*
DOES NOT WORK / NOT supporte by API

function postText(){
    
    if(!$this->resourceURI){
	$requestURI = $this->APIuri."/users/".$this->user."/batchjobs/".$this->batchName."/text"; //'text' is the default required name for a posted text
	
	$this->resourceURI = $requestURI;
    }
    else{
	$requestURI = $this->resourceURI;
    }
    
    $client = new Zend_Http_Client($requestURI);
    $client->setHeaders('Host', $this->requestHost);
    $client->setHeaders('Accept', 'application/json');
    $client->setHeaders('AUTHORIZATION', $this->password);
    $client->setHeaders('User-Key', $this->userKey);
    
    $client->setRawData($texts, 'application/json');
    
    $response = $client->request("POST");
    return $response;
}

*/



}//end class








?>
