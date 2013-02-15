<?php

/*
Interacts with the Edina API to manage batches. Batches are sets of documents to be processed by the GeoParser
Each batch can have 1 or more documents. The geoparser takes time to process through each document in a batch,
and one can make a request to get information about the status (pending or completed) of documents in a batch.

Functions for deleting batches are not provided.

*/
class Geoparser_EdinaBatch {
 
public $batchName;
public $textArray;

public $requestHeader; //header for debugging
public $requestBody; //header for debugging

public $HTTPstatusOK; //boolean, was the response (or cache retrieval) OK
public $cacheUsed; //boolean, was the cache used to get the response? 

private $APIuri; //base uri API
private $requestHost; //requester Host
private $user; //edina user
private $password; //edina password
private $userKey; //edina user key

const HTTPtimeout = 45; //time allowed for HTTP request. It's long to because Geoparser may need it
const statusCacheLife = 45; // cache lifetime, measured in seconds, 1 minute
const statusCache = "./status_cache/"; // Directory where to put the cache files;


//prepare general API settings
function prepSettings(){
    $endiaPrep = new Geoparser_Edina;
    $this->APIuri = $endiaPrep->get_API_URI();
    $this->requestHost = $endiaPrep->get_requestHost();
    $this->user = $endiaPrep->get_user();
    $this->password = $endiaPrep->get_password();
    $this->userKey = $endiaPrep->get_userKey();
    $this->textArray = false;
}


/*
get information about a batch
the bath information will list documents requested for processing, and the processing state (pending or completed)
it will provie links to retrieve processing results once these are available
*/
function getBatch(){
    
    $userBatchURI = $this->APIuri."/users/".$this->user."/batchjobs/".urlencode($this->batchName);
    $client = new Zend_Http_Client($userBatchURI,
											  array(
														  'maxredirects' => 0,
														  'timeout'      => self::HTTPtimeout));
    //$client->setHeaders('Host', $this->requestHost);
    $client->setHeaders('Accept', 'application/json');
    $client->setHeaders('AUTHORIZATION', $this->password);
    $client->setHeaders('User-Key', $this->userKey);
    
    $response = $client->request("GET"); //make the request using the GET method
    return $response;
}

/*
	 get the batch status, but use the cache. This helps with rate limiting requests to the Geoparser
	 for status updates.
*/
function cacheGetBatch(){
	 
	 $frontendOptions = $frontendOptions = array(
				 'lifetime' => self::statusCacheLife,
				 'automatic_serialization' => true
	  );
	 
	 $backendOptions = array(
			'cache_dir' => self::statusCache // Directory where to put the cache files
	  );
	 
	 $cache = Zend_Cache::factory('Core',
								 'File',
								 $frontendOptions,
								 $backendOptions);
	 
	 $cacheID = preg_replace('/[^a-z0-9]/i', '_', $this->batchName);
	 if(!$cache_result = $cache->load($cacheID)) {
		  $this->cacheUsed = false;
		  $response = $this->getBatch(); //do an HTTP request
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
get information about all batch created by a user
the results will have some metadata about different batches and links to different bathes.
*/
function getBatches(){
    
    $userBatchURI = $this->APIuri."/users/".$this->user."/batchjobs/";
    $client = new Zend_Http_Client($userBatchURI,
											  array(
														  'maxredirects' => 0,
														  'timeout'      => self::HTTPtimeout));
    //$client->setHeaders('Host', $this->requestHost);
    $client->setHeaders('Accept', 'application/json');
    $client->setHeaders('AUTHORIZATION', $this->password);
    $client->setHeaders('User-Key', $this->userKey);
    
    $response = $client->request("GET");
    return $response;
}


/*
Make a new batch, with the batchname (pubic variable $batchName). 
The Geoparser requires URLs for texts to process, these are in the pubic $textArray
The API response will have some metadata about the batch and status of the documents being analyzed
*/
function createBatch(){
    
    $userBatchURI = $this->APIuri."/users/".$this->user."/batchjobs/".urlencode($this->batchName);
    
    $client = new Zend_Http_Client($userBatchURI,
											  array(
														  'maxredirects' => 0,
														  'timeout'      => self::HTTPtimeout));
   // $client->setHeaders('Host', $this->requestHost);
    $client->setHeaders('Accept', 'application/json');
    $client->setHeaders('AUTHORIZATION', $this->password);
    
    if(is_array($this->textArray)){
		  /*
			The textArray is an array of URLs to text reources. The Geoparser retrieves documents to analyze from these URLs
		  */
		  $texts = Zend_Json::encode($this->textArray); //encode array as JSON
		  $client->setRawData($texts, 'application/json'); //put the JSON data into the body of the HTTP request, set mime as JSON 
    }

    $response = $client->request("POST"); //send request using POST method
    return $response;
}





}//end class

?>
