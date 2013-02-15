<?php

/**
*
* Basic settings for interacting with the
* Edina API

*/
class Geoparser_SEASR {
 
    const APIuri = "http://leovip025.ncsa.uiuc.edu:10000/service/geoNER"; //base uri for edina / unlock API
    const YahooAPIkey = "dj0yJmk9ZFlERjJXVDVkMjhaJmQ9WVdrOVYwUjRaelF4TkdVbWNHbzlNVGMxT0RReE9UWTJNZy0tJnM9Y29uc3VtZXJzZWNyZXQmeD1kMw--";
    
    public $text; //the text to analyze
    public $contentType;
    
    public $responseHeaders;
    
    function get_API_URI(){
		  return self::APIuri;
    }
    
    
    function postText($analysisMethod = 1){
    
	$client = new Zend_Http_Client(self::APIuri, array('timeout' => 120));
	$client->setHeaders('Accept', 'application/json');
	$client->setHeaders('Content-Type', $this->contentType);
	$client->setHeaders('x-geoNER-alg', $analysisMethod);
	$client->setHeaders('x-geoNER-yahooKey', self::YahooAPIkey);
	
	//$client->setParameterPost('data', $this->text);
	$client->setRawData($this->text);
	
	@$response = $client->request("POST");
	return $response;
    } 
    
    
    function setTextByURL($url){
	@$text = file_get_contents($url);
	if($text){
	    
	    $this->contentType = "text/html"; //the default content type
	    foreach($http_response_header as $header){
		if(stristr($header, "Content-Type")){
		    $typeArray = explode(":", $header);
		    $this->contentType = trim($typeArray[1]);
		}
	    }
	    
	    if($this->contentType == "application/xhtml+xml"){
		$this->contentType = "text/html"; //the default content type
	    }
	    
	    $this->text = $text;
	    return $text;
	}
	else{
	    return false;
	}
    }
    
    
    
    
    

}//end class


?>
