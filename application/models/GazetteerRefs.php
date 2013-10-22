<?php

/*
 
Class for adding, removing, retrieving and updating tokens

*/
class GazetteerRefs {
 
    
    public $db; //database connection object
    
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
	 
	 
	 
	 
	 function OLDgetGapVisPageSummaryByDocID($docID){
		 
		  $sentencesPerPage = $this->sentencesPerPage();
		  $db = $this->startDB();
		  $sql = "SELECT gt.pageID, grefs.id, gt.sentID, grefs.uriID, 
					 grefs.latitude AS pLat, grefs.longitude AS pLong, gazuris.uri, 
					 gazuris.latitude, gazuris.longitude
					 FROM gap_gazrefs AS grefs
					 JOIN gap_tokens AS gt ON grefs.tokenID = gt.id
					 JOIN gap_gazuris AS gazuris ON grefs.uriID = gazuris.id
					 WHERE grefs.docID = $docID
					 AND (gazuris.latitude !=0 AND gazuris.longitude !=0)
					 ORDER BY gt.pageID, grefs.tokenID
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
					 $actPage["places"][] = $row["uriID"];
				}
				/*
				if(count($actPage["places"])>=0){
					 $pagePlaces[] = $actPage;
				}
				*/
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
								if(isset($jsonData["title"])){
									 $data = array("label" => $jsonData["title"]);
									 $where = " uri = '$baseURI' ";
									 $db->update("gap_gazuris", $data, $where);
									 $update = true;
								}
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
