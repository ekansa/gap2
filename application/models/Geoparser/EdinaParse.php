<?php

/*

This class parses the results from the Edina Geoparser to load the database
with results that can be visualized and edited

*/
class Geoparser_EdinaParse {


	 public $docID; //id of the document where the tokens come from 
	 public $batchID; //batch id for the batch of the document
	 public $gazSourceRefs; //array of gazetteer source ref identifiers from the current document
 
	 public $gazRbIDs; //array of the RbIDs for gazetteer references
 
	 public $sourceGazetteers = array("Pleiades Plus" => array("baseURL" => "http://pleiades.stoa.org/places/",
																				  "xpathLimit" => true),
												 "GeoNames" => array("baseURL" => "http://www.geonames.org/",
																			"xpathLimit" => true),
												 "[Others]" => array("baseURL" => false,
																			"xpathLimit" => false)
												 );
 
 
	 //default sentences per-page (defined in the config.ini file)
	 function sentencesPerPage(){
		  $registry = Zend_Registry::getInstance();
		  return $registry->config->books->config->sentsPerPage;
    }
 
	 //default get the base url for edina geo-feature lookups
	 function EdinaLookupBaseURL(){
		  $registry = Zend_Registry::getInstance();
		  return $registry->config->edina->config->lookupBaseURL;
    }
 
 
	 //go through the Lemnatized tokens and store the tokens in the database, set asside unique gazeteer references
	 //into an array $gazSourceRefs
	 function storeParsedTokens($xmlString){
		  // $xmlString is the lemnatized XML result from the Edina Geoparser
		  
		  $sentencesPerPage = $this->sentencesPerPage();
		  $output = false;
		  $gazSourceRefs = array();
		  $gazRbIDs = array();
		  $tokensObj = New Tokens; //class for manipulating tokens in the database
		  $documentIn = $tokensObj->checkDocumentDone($this->docID);
		  if(!$documentIn){
				@$xml = simplexml_load_string($xmlString);
				unset($xmlString);
				if($xml){
					 $output = array();
					 
					 $pageNumber = 1;
					 $sentPage = array();
					 foreach($xml->xpath("//s") as $sentence){
						  $sentID = false;
						  foreach($sentence->xpath("@id") as $xres){
								$sentID = (string)$xres;
						  }
						  //echo "<h2>Sentence: ".$sentID."</h2>";
						  
						  $sentPage[] = $sentID;
						  if(count($sentPage)>= $sentencesPerPage){
								unset($sentPage);
								$sentPage = array();
								$pageNumber++;
						  }
						  
						  foreach($sentence->xpath("w") as $word){
								$tokenID = false;
								$pws = null;
								$token = false;
								$location = false;
								$gazRbID = false;
								$gazSource = false;
								$gazRef = false;
								foreach($word->xpath("@id") as $xres){
									 $tokenID = (string)$xres;
								}
								foreach($word->xpath("text()") as $xres){
									 $token = (string)$xres;
								}
								foreach($word->xpath("@pws") as $xres){
									 $pws = (string)$xres;
									 if($pws == "yes"){
										  $pws = true;
									 }
									 else{
										  $pws = false;
									 }
								}
								foreach($word->xpath("@locname") as $xres){
									 $location = (string)$xres;
								}
								
								if($location != false){
									 //echo "<p><strong>Token: $token (".$tokenID.") ($location)</strong></p>";
									 foreach($xml->xpath("//ent[parts/part/@ew = '$tokenID']") as $ent){
										  foreach($ent->xpath("@id") as $xres){
												$gazRbID = (string)$xres;
										  }
										  foreach($ent->xpath("@gazref") as $xres){
												$gazRef = (string)$xres;
										  }
										  foreach($ent->xpath("@source-gazref") as $xres){
												$gazSource = (string)$xres;
												if(!in_array($gazSource, $gazSourceRefs)){
													 $gazSourceRefs[] = $gazSource;
													 $this->gazSourceRefs = $gazSourceRefs;
												}
										  }
									 }
									 
									 if(!$gazRbID){
										  $location = "[place]"; //we couldn't find an entity for this token, but we want to note it is a place
									 }
									 else{
										  $location = $gazRbID;
									 }
								}//end case of a place reference
								
								$data = array("docID" => $this->docID,
												  "batchID" => $this->batchID,
												  "pageID" => $pageNumber,
												  "sentID" => $sentID,
												  "tokenID" => $tokenID,
												  "pws" => $pws,
												  "gazRef" => $location,
												  "token" => $token
												  );
								$DBtokenID = $tokensObj->addRecord($data);
								
								if($gazRbID != false && $DBtokenID != false){
									 if(!array_key_exists($gazRbID, $gazRbIDs)){
										  $gazRbIDs[$gazRbID] = $DBtokenID; //associate the place identification with the database token id
										  $this->gazRbIDs = $gazRbIDs;
									 }
								}
								$output[] = $DBtokenID;
						  }//end loop through tokens in a sentence
					 }//end loop through sentences
				}//end case of valid XML
		  }//end case where the document is not yet done
		  
		  return $output;
	 }
	 
	 
	 //this function addes gazetter referenes for a document to the database
	 function storeGazetteerRefs($xmlString){
		  //$xmlString is the xml of the gazetter refs
		  
		  $output = false;
		  $gazObj = New GazetteerRefs; //class for manipulating tokens in the database
		  $documentIn = $gazObj->checkDocumentDone($this->docID);
		  @$xml = simplexml_load_string($xmlString);
		  unset($xmlString);
		  if(!$documentIn && is_array($this->gazRbIDs) && $xml){
				$output = array();
				foreach($this->gazRbIDs as $gazRbID => $DBtokenID){
					 //search through gazetteer references from the JSON result to get data about discovered source-refs
					 $sourceFound = false;
					 $data = false;
					 foreach($xml->xpath("//placename[@id = '$gazRbID']") as $placeName){
						  
						  $latitude = false;
						  $longitude = false;
						  $gazetteer = false;
						  $unlockRef = false;
						  $sourceRef = false;
						  $docName = false;
						  $gazName = false;
						  $rank = false;
						  foreach($placeName->xpath("@name") as $xres){
								$docName = (string)$xres;
						  }
						  
						  foreach($this->sourceGazetteers as $prefGazetteer => $gazArray){
								
								if($gazArray["xpathLimit"]){
									 $query = "place[@gazetteer = '$prefGazetteer' or @rank = '1']";
								}
								else{
									 $query = "place";
								}
								
								foreach($placeName->xpath($query) as $place){
									 foreach($place->xpath("@gazetteer") as $xres){
										  $gazetteer = (string)$xres;
									 }
									 foreach($place->xpath("@lat") as $xres){
										  $latitude = (string)$xres;
									 }
									 foreach($place->xpath("@long") as $xres){
										  $longitude = (string)$xres;
									 }
									 foreach($place->xpath("@gazref") as $xres){
										  $unlockRef = (string)$xres;
									 }
									 foreach($place->xpath("@source-gazref") as $xres){
										  $sourceRef = (string)$xres;
									 }
									 foreach($place->xpath("@name") as $xres){
										  $gazName = (string)$xres;
									 }
									 foreach($place->xpath("@rank") as $xres){
										  $rank = (string)$xres;
									 }
									 break;
								}
								if($unlockRef != false){
									 break; //stop if we've found a place
								}
						  }//end loop through preferred gazetteers
				
						  if($unlockRef != false){
								$uri = $this->generateGazetteerURI($sourceRef, $unlockRef, $gazetteer);
								$uriID = $gazObj->URIgetMakeID($uri);
								
								$data = array("docID" => $this->docID,
													 "tokenID" => $DBtokenID,
													 "batchID" => $this->batchID,
													 "gazRef" => $gazRbID,
													 "unlockRef" => $unlockRef,
													 "sourceRef" => $sourceRef,
													 "latitude" => $latitude,
													 "longitude" => $longitude,
													 "docName" =>	$docName,
													 "gazName" =>	$gazName,
													 "uriID" => $uriID
													 );
							  
								$gazRefID = $gazObj->addRecord($data); 
								$output[] = $gazRefID;
						  }
					 }
				}// end loop through the source-refs found with the tokens
		  }
		  return $output;
	 }
 
 
 //generate a URI from the source gazetteer and provided identifiers
	 function generateGazetteerURI($sourceRef, $unlockRef, $gazetteer){
		  
		  if(!$gazetteer){
				$gazetteer = "[none]";
		  }
		  if(strstr($unlockRef, "geonames")){
				//a hack for the weird instances...
				$gazetteer = "GeoNames";
				$sourceRef = $unlockRef;
		  }
		  
		  $sourceGazetteers = $this->sourceGazetteers;
		  if(array_key_exists($gazetteer, $sourceGazetteers)){
				if(strstr($sourceRef, ":")){
					 $sourceEx = explode(":",$sourceRef);
					 $sourceID = $sourceEx[1]; //the value after the colon is the id for a gazetteer entry
				}
				else{
					 $sourceID = $sourceRef;
				}
				return $sourceGazetteers[$gazetteer]["baseURL"].$sourceID;
		  }
		  else{
				if(strstr($unlockRef, ":")){
					 $unlockEx = explode(":", $unlockRef);
					 $unlockID = $unlockEx[1]; //the value after the colon is the id for unlock
				}
				else{
					 $unlockID = $unlockRef;
				}
				return $this->EdinaLookupBaseURL()."?id=".$unlockID;
		  }
	 }
 
 
 
	 //this function gets the JSON data for the gazeteer references from a source document (docID)
	 function getGazetteerXMLbyID($docID){
		  $output = false;
		  $docObj = New Document;
		  $doc = $docObj->getByID($docID);
		  if(is_array($doc)){
				if(!$this->docID){
					 $this->docID = $docID;
				}
				if(!$this->batchID){
					 $this->batchID = $docObj->batchID;
				}
				$parserID = $docObj->parserID;
				$batchObj = New Batch;
				$batch = $batchObj->getByID($docObj->batchID);
				$edinaObj = New Geoparser_EdinaText;
				$edinaObj->prepSettings();
				$edinaObj->batchName = $batchObj->parserID; //Parser ID of the batch
				$edinaObj->resourceID = $docObj->parserID; //Parser ID of the resource for parsing
				$edinaObj->resultFormat = "json";
				$requestFile = $docObj->parserID.".xml"; //this is the file type to get
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
					 $output = $respBody; //these are the XML we're looking for
				}
		  }
		  return $output;
	 }
 
 
	 //this function gets the lemnatized XML for a Doc by ID. it returns false if something when wrong.
    function getLemnatizedXMLbyID($docID){
		  
		  $output = false;
		  $docObj = New Document;
		  $doc = $docObj->getByID($docID);
		  if(is_array($doc)){
				if(!$this->docID){
					 $this->docID = $docID;
				}
				if(!$this->batchID){
					 $this->batchID = $docObj->batchID;
				}
				$parserID = $docObj->parserID;
				$batchObj = New Batch;
				$batch = $batchObj->getByID($docObj->batchID);
				$edinaObj = New Geoparser_EdinaText;
				$edinaObj->prepSettings();
				$edinaObj->batchName = $batchObj->parserID; //Parser ID of the batch
				$edinaObj->resourceID = $docObj->parserID; //Parser ID of the resource for parsing
				$edinaObj->resultFormat = "xml";
				$requestFile = $docObj->parserID.".lem.xml"; //this is the file type to get
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
					 $output = $respBody; //these are the XML we're looking for
				}
		  }
		  
		  return $output;
	 }

}//end class


?>
