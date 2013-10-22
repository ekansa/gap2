<?php

/*
 
Class for adding, removing, retrieving information about place entities

*/
class GazetteerURIs {
 
    public $errors = false;
    public $db; //database connection object
    
	 
	 function getOrAddPlaceRecord($uri){
		  $uriID = false;
		  if(stristr($uri, "http://pleiades.stoa.org")){
				$uriID = $this->getOrAddPleiadesPlace($uri);
		  }
		  else{
				$this->noteError("Gazetteer for '$uri' not recognized.");
		  }
		  return $uriID;
	 }
	 
	 
	 //get pleaides Label data
	 function getOrAddPleiadesPlace($uri){
		  
		  $output = false;
		  $errors = array();
		  $db = $this->startDB();
		  
		  $uri = $this->cleanPleiadesURI($uri);
		  if($uri != false){
				$sql = "SELECT id FROM gap_gazuris WHERE uri = '$uri' LIMIT 1;";
				$result = $db->fetchAll($sql, 2);
				if($result){
					 $output = $result[0]["id"]; //return the ID for the URI, it already exists in the database
				}
				else{
					 //OK now we have to 
					 $jsonURI = $uri. "/json";
					 @$jsonStringData = file_get_contents($jsonURI);
					 if($jsonStringData ){
						  @$jsonData = Zend_Json::decode($jsonStringData);
						  if(is_array($jsonData)){
								$data = array();
								$data["uri"] = $uri;
								$data["label"] = $jsonData["title"];
								$data["latitude"] = $jsonData["reprPoint"][1];
								$data["longitude"] = $jsonData["reprPoint"][0];
								$db->insert('gap_gazuris', $data);
								$n = $db->lastInsertId();
								$output = $n;
						  }
						  else{
								$this->noteError("Pleiades not responding with usable data for '$uri'.");
						  }
					 }
					 else{
						  $this->noteError("Pleiades not recognizing '$uri' as a valid URI.");
					 }
				}
				
		  }
		  else{
				$this->noteError("Not a valid Pleiades Place URI");
		  }
		  
		  return $output;
	 }
	 
	 //use the Pleiades API to update name and location information, returns true if updated, false if something went wrong
	 function updatePleiadesData($uri){
		  $output = false;
		  $db = $this->startDB();
		  
		  $uri = $this->cleanPleiadesURI($uri);
		  if($uri != false){
				$sql = "SELECT id FROM gap_gazuris WHERE uri = '$uri' LIMIT 1;";
				$result = $db->fetchAll($sql, 2);
				if($result){
					 $jsonURI = $uri. "/json";
					 @$jsonStringData = file_get_contents($jsonURI);
					 if($jsonStringData ){
						  @$jsonData = Zend_Json::decode($jsonStringData);
						  if(is_array($jsonData)){
								$where = "uri = '$uri' ";
								$data = array();
								$data["label"] = $jsonData["title"];
								if($jsonData["reprPoint"][1] != 0 && $jsonData["reprPoint"][0] != 0){
									 $data["latitude"] = $jsonData["reprPoint"][1];
									 $data["longitude"] = $jsonData["reprPoint"][0];
								}
								$db->update('gap_gazuris', $data, $where);
								$output = true;
						  }
						  else{
								$this->noteError("Pleiades not responding with usable data for '$uri'.");
						  }
					 }
					 else{
						  $this->noteError("Pleiades not recognizing '$uri' as a valid URI.");
					 }
				}
		  }
		  else{
				$this->noteError("Not a valid Pleiades Place URI");
		  }
		  
		  return $output;
	 }
	 
	 
	 //makes it easier to update false places that are unique
	 function uniqueFalsePlaces(){
		  $output = array();
		  $placeTypes = array("lake" => "first",
									 "sea" => "first" ,
									 "valley" => "first",
									 "mountains" => "first",
									 "gulf" => "first",
									 "mouth" => "first",
									 "mountain" => "first",
									 "Coast" => "first",
									 "Heads" => "first",
									 "Cities" => "first",
									 "Bay" => "first");
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT gt.token, count(gt.id) AS idCount
		  FROM gap_gazrefs AS refs
		  JOIN gap_tokens AS gt ON gt.id = refs.tokenID
		  WHERE refs.uriID = 611 OR refs.uriID = 1
		  GROUP BY gt.token
		  ORDER BY idCount DESC
		  ";
		  
		  $i = 1;
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				foreach($result as $row){
					 $actAdd = $row;
					 $token = $row["token"];
					 $lcToken = $token;
					 if(array_key_exists($lcToken, $placeTypes)){
						  
						  $sql = "SELECT DISTINCT bgt.token AS btoken, gt.token
						  FROM gap_tokens AS gt
						  JOIN gap_tokens AS bgt ON gt.id - 1 = bgt.id
						  JOIN gap_tokens AS agt ON gt.id + 1 = agt.id
						  JOIN gap_gazrefs AS refs ON gt.id = refs.tokenID
						  WHERE gt.token = '$token'
						  ORDER BY gt.id
						  ";
						  
						  $resultB = $db->fetchAll($sql, 2);
						  if($resultB){
								foreach($resultB as $rowB){
								
									 $Ftoken = $rowB["btoken"];
									 $Stoken = $rowB["token"];
									 if(strlen($Ftoken)>1){
								
										  $sql = "SELECT gt.id
										  FROM gap_tokens AS gt
										  JOIN gap_tokens AS bgt ON gt.id - 1 = bgt.id
										  JOIN gap_gazrefs AS refs ON gt.id = refs.tokenID
										  WHERE gt.token = '$token' AND bgt.token = '$Ftoken'
										  ORDER BY gt.id
										  ";
										  
										  $resultC = $db->fetchAll($sql, 2);
										  $tokenIDs = array();
										  foreach($resultC as $rowC){
												$tokenIDs[] = $rowC["id"];
										  }
										  
										  $placeName = $Ftoken." ".$Stoken;
										  $this->changeTokenTempURI($placeName, $tokenIDs);
										  $actAdd["2grams"][] = array("placeName" => $placeName, "ids" => $tokenIDs);
									 }
								}
						  }
					 }
					 else{
						  $actAdd["2grams"] = false;
						  
						  $sql = "SELECT gt.id
									 FROM gap_tokens AS gt
									 JOIN gap_gazrefs AS refs ON gt.id = refs.tokenID
									 WHERE gt.token = '$token'
									 ORDER BY gt.id
									 ";
										  
						  $resultC = $db->fetchAll($sql, 2);
						  $tokenIDs = array();
						  foreach($resultC as $rowC){
								$tokenIDs[] = $rowC["id"];
						  }
						  $this->changeTokenTempURI($token, $tokenIDs);
						  $actAdd["ids"] = $tokenIDs;
					 }
					 
					 $output[] = $actAdd;
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 function changeTokenTempURI($token, $tokenIDs){
		  $db = $this->startDB();
		  $newURIid = $this->getMakeTempURI($token);
		  foreach($tokenIDs as $tokenID){
				$where = "tokenID = $tokenID ";
				$data = array("uriID" => $newURIid);
				$db->update("gap_gazrefs", $data, $where);
		  }
	 }
	 
	 
	 function getMakeTempURI($token){
		  $id = false;
		  $db = $this->startDB();
		  $uri = "http://pleiades.stoa.org/places/#".$token;
		  
		  $sql = "SELECT id FROM gap_gazuris WHERE uri = '$uri' LIMIT 1; ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$id = $result[0]["id"];
		  }
		  else{
				$data = array("uri" => $uri,
							 "label" => $token,
							 "latitude" => 0,
							 "longitude" => 0
							 );
				
				$db->insert("gap_gazuris", $data);
				$id = $db->lastInsertId();
		  }
		  return $id;
	 }
	 
	 
	 
	 //make a clean pleiades place URI
	 function cleanPleiadesURI($uri){
		  $uri = trim($uri);
		  $uri = str_replace("http://", "", $uri);
		  $uriEx = explode("/", $uri);
		  if(isset($uriEx[2])){
				$placeID = $uriEx[2];
				$fixedURI = "http://pleiades.stoa.org/places/".$placeID;
				return $fixedURI;
		  }
		  else{
				return false;
		  }
	 }
	 
	 //stores an error message
	 function noteError($errorMessage){
		  if(is_array($this->errors)){
				$errors = $this->errors;
		  }
		  else{
				$errors = array();
		  }
		  $errors[] = $errorMessage;
		  $this->errors = $errors;
	 }
	 
	 
	 
	 
	 
	 
	 //add a place reference identified from the Geoparser
	 function addRecord($data){
		  $db = $this->startDB();
		  
		  try{
				$db->insert('gap_gazrefs', $data);
				$n = $db->lastInsertId();
				return $n;
		  }
		  catch (Exception $e) {
				//echo (string)$e;
				return false;
		  }  
	 }
	 
	 //check to see if the document is already in
	 function checkDocumentDone($docID){
		  $db = $this->startDB();
		  $sql = "SELECT count(id) as idCount FROM gap_gazrefs WHERE docID = $docID ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				return $result[0]["idCount"];
		  }
		  else{
				return false;
		  }
	 }
	 
	 function initializeTab(){
		  
		  $db = $this->startDB();
		  $sql = "
		  CREATE TABLE IF NOT EXISTS gap_gazrefs (
				id int(11) NOT NULL AUTO_INCREMENT,
				tokenID int(11) NOT NULL,
				docID int(11) NOT NULL,
				batchID int(11) NOT NULL,
				gazRef varchar(100) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
				unlockRef varchar(100) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
				sourceRef varchar(100) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
				latitude double DEFAULT NULL,
				longitude double DEFAULT NULL,
				docName varchar(200) NOT NULL,
				gazName varchar(200) NOT NULL,
				uriID int(11) NOT NULL,
				updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY docID (docID),
				KEY docName (docName),
				KEY gazName (gazName),
				KEY uriID (uriID),
				UNIQUE tokenID (tokenID)
			 ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
			 
			 CREATE TABLE IF NOT EXISTS gap_gazuris (
				id int(11) NOT NULL AUTO_INCREMENT,
				uri varchar(200) NOT NULL,
				label varchar(200) NOT NULL,
				latitude double DEFAULT NULL,
				longitude double DEFAULT NULL,
				updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY uri (uri),
				KEY label (label)
			 ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

			 
		  ";
		  $db->query($sql, 2);
	 }
	 
    function startDB(){
		  if(!$this->db){
				$db = Zend_Registry::get('db');
				$this->setUTFconnection($db);
				$this->db = $db;
		  }
		  else{
				$db = $this->db;
		  }
		  
		  return $db;
	 }
	 
	 function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
    }


}//end class


?>
