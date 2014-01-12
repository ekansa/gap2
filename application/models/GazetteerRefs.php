<?php

/*
 
Class for adding, removing, retrieving and updating tokens

*/
class GazetteerRefs {
 
    
    public $db; //database connection object
    public $errors = false; //array of errors
	 
	 
	 //default sentences per-page (defined in the config.ini file)
	 function sentencesPerPage(){
		  $registry = Zend_Registry::getInstance();
		  return $registry->config->books->config->sentsPerPage;
    }
	 
	 //get a page summary to format in JSON for GapVis
	 function getGapVisPageSummaryByDocID($docID){
		 
		  $sentencesPerPage = $this->sentencesPerPage();
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT gt.pageID
					 FROM gap_tokens AS gt
					 WHERE gt.docID = $docID
					 ORDER BY gt.pageID
					 ";
					 
		  
		  $result = $db->fetchAll($sql, 2);
		  $sentPages = array();
		  $pagePlaces = array();
		  if($result){
				$previousPage = false;
				foreach($result as $row){
					 $currentPage = $row["pageID"];
					 if($currentPage != $previousPage){
						  if($previousPage != false){
								$pagePlaces[] = $actPage;
								unset($actPage);
						  }
						  $actPage = array("id" => $currentPage, "places" => array());
						  $previousPage = $currentPage;
					 }
					 
					 $sql = "SELECT gt.pageID, grefs.id, gt.sentID, grefs.uriID, 
					 grefs.latitude AS pLat, grefs.longitude AS pLong, gazuris.uri, 
					 gazuris.latitude, gazuris.longitude
					 FROM gap_gazrefs AS grefs
					 JOIN gap_tokens AS gt ON grefs.tokenID = gt.id
					 JOIN gap_gazuris AS gazuris ON grefs.uriID = gazuris.id
					 WHERE grefs.docID = $docID AND gt.pageID = $currentPage
					 AND (gazuris.latitude !=0 AND gazuris.longitude !=0)
					 AND grefs.active = 1
					 ORDER BY grefs.tokenID";
					 
					 $resultB = $db->fetchAll($sql, 2);
					 if($resultB){
						  foreach($resultB as $rowB){
								$actPage["places"][] = $rowB["uriID"];
						  }
					 }
				}
				$pagePlaces[] = $actPage;
		  }
		  return $pagePlaces;
	 }
	 
	 
	 
	 
	 
	 //get a list of places identified in a document
	 function getGapVisPlaceSummaryByDocID($docID){
		  
		  $db = $this->startDB();
		  $sql = "SELECT grefs.id, grefs.docName,
					 grefs.gazName, grefs.uriID, grefs.latitude AS pLat, grefs.longitude AS pLong, gazuris.uri, gazuris.label,
					 gazuris.latitude, gazuris.longitude
					 FROM gap_gazrefs AS grefs
					 JOIN gap_gazuris AS gazuris ON grefs.uriID = gazuris.id
					 JOIN gap_tokens AS gt ON grefs.tokenID = gt.id
					 WHERE grefs.docID = $docID
					 AND (gazuris.latitude !=0 AND gazuris.longitude !=0)
					 AND grefs.active = 1
					 GROUP BY grefs.uriID
					 ORDER BY grefs.tokenID
				
					 ";
					 
				$sql = "SELECT grefs.id, grefs.docName,
					 grefs.gazName, grefs.uriID, grefs.latitude AS pLat, grefs.longitude AS pLong, gazuris.uri, gazuris.label,
					 gazuris.latitude, gazuris.longitude
					 FROM gap_gazrefs AS grefs
					 JOIN gap_gazuris AS gazuris ON grefs.uriID = gazuris.id
					 JOIN gap_tokens AS gt ON grefs.tokenID = gt.id
					 WHERE grefs.docID = $docID
					 AND grefs.active = 1
					 GROUP BY grefs.uriID
					 ORDER BY grefs.tokenID
				
					 ";
					 
		  $result = $db->fetchAll($sql, 2);
		  $places = array();
		  if($result){
				foreach($result as $row){
					 if(strlen($row["docName"]) < strlen($row["gazName"])){
						  $title = $row["docName"];
					 }
					 else{
						  $title = $row["gazName"];
					 }
					 $title = $row["label"];
					 $actPlace = array("id" => $row["uriID"],
											 "uri" => $row["uri"],
											 "title" => $title,
											 "ll" => array($row["latitude"]+0, $row["longitude"]+0)
											 );
					 $places[] = $actPlace;
				}
		  }
		  return $places;
	 }
	 
	 //get the ID for a given uri;
	 function URIgetID($uri){
		  $db = $this->startDB();
		  $sql = "SELECT id FROM gap_gazuris WHERE uri = '$uri' LIMIT 1; ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				return $result[0]["id"];
		  }
		  else{
				return false;
		  }
	 }
	 
	 //get the ID for a given URI or make one
	 function URIgetMakeID($uri){
		  $id = $this->URIgetID($uri);
		  if(!$id){
				$db = $this->startDB();
				$data = array("uri" => $uri);
				$db->insert("gap_gazuris", $data);
				$id = $db->lastInsertId();
		  }
		  return $id;
	 }
	 
	 //get the URI for a given uri-id;
	 function IDgetURI($uriID){
		  $db = $this->startDB();
		  $sql = "SELECT uri FROM gap_gazuris WHERE id = $uriID LIMIT 1; ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				return $result[0]["uri"];
		  }
		  else{
				return false;
		  }
	 }
	 
	 //get the URI for a given uri-id;
	 function getGapVisPlace($IDgazURI){
		  $db = $this->startDB();
		  
		  $sql = "SELECT grefs.docName,
					 grefs.gazName, grefs.uriID, grefs.latitude AS pLat, grefs.longitude AS pLong, gazuris.uri, gazuris.label,
					 gazuris.latitude, gazuris.longitude 
		  FROM gap_gazuris AS gazuris
		  JOIN gap_gazrefs AS grefs ON  gazuris.id = grefs.uriID
		  WHERE gazuris.id = $IDgazURI
		  LIMIT 1; ";
		  
		  $actPlace = false;
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				
				foreach($result as $row){
					 if(strlen($row["docName"]) < strlen($row["gazName"])){
						  $title = $row["docName"];
					 }
					 else{
						  $title = $row["gazName"];
					 }
					 $title = $row["label"];
					 $actPlace = array("id" => $row["uriID"],
											 "uri" => $row["uri"],
											 "title" => $title,
											 "ll" => array($row["latitude"]+0, $row["longitude"]+0)
											 );
					 break;
				}
		  }
		  return $actPlace;
	 }
	 
	 //get a list of all URIs actively used in a document
	 function getListofURIs($docID = false){
		  
		  $db = $this->startDB();
		  
		  $docTerm = "";
		  if($docID != false){
				$docTerm = " AND grefs.docID = $docID ";
		  }
		  
		  $sql = "SELECT 
		  gazuris.uri, gazuris.label, gazuris.id, gazuris.latitude, gazuris.longitude
		  FROM gap_gazuris AS gazuris
		  JOIN gap_gazrefs AS grefs ON  gazuris.id = grefs.uriID
		  WHERE grefs.active = 1 $docTerm
		  GROUP BY gazuris.id
		  ORDER BY gazuris.label
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				return $result;
		  }
		  else{
				return false;
		  }
		  
	 }
	
	 
	 
	 
	 
	 //change a token's old place references to not active. add a new active place reference
	 function updatePlaceReference($tokenID, $docID, $placeURI){
		  $output = false;
		  $uriID = false;
		  if(!$placeURI){
				//no place URI, so we're just turning off gaz references to a given token
				$this->deactiveTokenGazRefs($tokenID); //simply deactivate all place references associated with a given token
				$output = true;
		  }
		  else{
				//we've got a place URI, so now we need to associate it with a given place
				$gazURIobj = new GazetteerURIs; 
				$uriID = $gazURIobj->getOrAddPlaceRecord($placeURI);
				if(!$uriID){
					 $this->noteError(implode(" ", $gazURIobj->errors));
				}
				else{
					 $this->deactiveTokenGazRefs($tokenID); //simply deactivate all place references associated with a given token
					 $data = array("tokenID" => $tokenID,
										"docID" => $docID,
										"active" => true,
										"gazRef" => "user input",
										"uriID" => $uriID
										);
					 
					 $ok = $this->addRecord($data);
					 if(!$ok){
						  $this->noteError("Error in adding new gaz reference: ".(implode(" ", $data)));
					 }
					 else{
						  $output = true;
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 //deactivates place references for a given token.
	 function deactiveTokenGazRefs($tokenID){
		  $db = $this->startDB();
		  $where = "tokenID = $tokenID";
		  $data = array("active" => false);
		  $db->update("gap_gazrefs", $data, $where);
	 }
	 
	 
	 
	 
	 
	 
	 //get pleaides Label data
	 function updatePleiadesData(){
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT uri FROM gap_gazuris WHERE label = '' AND uri LIKE 'http://pleiades.stoa.org/%' ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				foreach($result as $row){
					 $update = false;
					 $baseURI = $row["uri"];
					 $jsonURI = $baseURI . "/json";
					 $jsonURI = str_replace("//json", "/json", $jsonURI); //just in case of trailing slash
					 sleep(.5);
					 @$jsonStringData = file_get_contents($jsonURI);
					 if($jsonStringData ){
						  @$jsonData = Zend_Json::decode($jsonStringData);
						  if(is_array($jsonData)){
								$data = array();
								if(isset($jsonData["title"])){
									 $data["label"]= $jsonData["title"];
								}
								if(isset($jsonData["reprPoint"])){
									 $data["longitude"]= $jsonData["reprPoint"][0];
									 $data["latitude"]= $jsonData["reprPoint"][1];
								}
								$where = " uri = '$baseURI' ";
								$db->update("gap_gazuris", $data, $where);
								$update = true;
						  }
					 }
					 if(!$update){
						  $data = array("label" => "[Unknown place label]");
						  $where = " uri = '$baseURI' ";
						  $db->update("gap_gazuris", $data, $where);
					 }
					 
				}
		  }
		  
	 }
	 
	 //fixing bad decimal data.	 
	 function fixMissingDecimals(){
		  $db = $this->startDB();
		  $sql = "SELECT * FROM gap_gazuris WHERE (latitude > 90 OR latitude < -90) OR (longitude > 180 OR  longitude < -180) ;";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				foreach($result as $row){
					 $actLat = $row["latitude"];	 
					 $actLon = $row["longitude"];	 
					 if($actLat > 90 || $actLat < -90){
						  if(abs($actLat) > 10000){
								$actLat = $actLat / 1000;
						  }
					 }
					 if($actLon > 180 || $actLon < -180){
						  if(abs($actLon) > 10000){
								$actLon = $actLon / 1000;
						  }
					 }
					 $id = $row["id"];
					 $where = "id = $id";
					 $data = array("latitude" => $actLat, "longitude" => $actLon);
					 $db->update("gap_gazuris", $data, $where);
				}
		  }
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
