<?php

/**
*
* Basic settings for interacting with the
* Edina API

*/
class Document {
 
	 public $db;
	 
	 public $id;
	 public $parserID;
	 public $batchID;
	 public $status;
	 public $title;
	 public $url;
	 public $created;
	 public $updated;
	 public $pLinks;
	 
	 
	 const docResultLinkSuffix = "/edina/doc-review?docID=";
	 
	 const docCacheLife = 7200; // cache lifetime, measured in seconds, 7200 = 2 hours
	 const docCache = "./doc_cache/"; // Directory where to put the cache files;
	 const DocCacheIDPrefix = "doc_";
	 
	 //get the data by ID, formated for GapVis
	 function getGapVisDataByID($id){
		 
		  $db = $this->startDB();
		  $id = App_Security::inputCheck($id);
		  $this->clearObsoleteCache();
		  $output = $this->checkCachedDoc($id);
		  
		  if(!$output){
				$sql = "SELECT gd.parserID,
				gd.batchID,
				gd.status,
				gd.title,
				gd.url,
				gd.created,
				gd.updated,
				gd.langCode,
				gl.lang_en,
				gl.lang
				FROM gap_documents AS gd
				JOIN gap_languages AS gl ON gd.langCode = gl.code
				WHERE gd.id = $id LIMIT 1; ";
				$result = $db->fetchAll($sql, 2);
				if($result){
					 
					 $gazRefObs = new GazetteerRefs;
					 $gazRefObs->updatePleiadesData();
					 
					 $this->id = $id;
					 $this->parserID = $result[0]["parserID"];
					 $this->batchID =  $result[0]["batchID"];
					 $this->status = $result[0]["status"];
					 $this->title = $result[0]["title"];
					 $this->url = $result[0]["url"];
					 $this->created = $result[0]["created"];
					 $this->updated = $result[0]["updated"];
					 $pageArray = $gazRefObs->getGapVisPageSummaryByDocID($id);
					 $placesArray = $gazRefObs->getGapVisPlaceSummaryByDocID($id);
						 
					 $output = array(
						  "id" => $id,
						  "title" => $result[0]["title"],
						  "uri" => $result[0]["url"],
						  "author" => "Analysed by the Edina/Unlock GeoParser",
						  "printed" => $result[0]["updated"],
						  "texts" => $this->getTranslatedVersions($id),
						  "sections" => $this->getDocumentSections($id),
						  "pages" => $pageArray,
						  "places" => $placesArray
					 );
					 
					 $this->cachedDocData($id, $output); //cache the result
				}
		  }//end case with no cached output
		  
		  return $output;
	 }
	 
	 
	 function clearObsoleteCache(){
		  $db = $this->startDB();
		  $sql = "SELECT * 
				FROM  gap_gazrefs 
				WHERE updated >= TIMESTAMPADD( HOUR , -2, NOW( ) ) 
				LIMIT 1";
				
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$frontendOptions = $frontendOptions = array(
				 'lifetime' => self::docCacheLife,
				 'automatic_serialization' => true
				);
			  
				$backendOptions = array(
					  'cache_dir' => self::docCache // Directory where to put the cache files
				 );
				$cache = Zend_Cache::factory('Core',
									  'File',
									  $frontendOptions,
									  $backendOptions);
				$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		  }
	 }
	 
	 
	 
	 function checkCachedDoc($id){
		  
		  $frontendOptions = $frontendOptions = array(
				 'lifetime' => self::docCacheLife,
				 'automatic_serialization' => true
		  );
		 
		  $backendOptions = array(
				 'cache_dir' => self::docCache // Directory where to put the cache files
			);
		 
		  $cache = Zend_Cache::factory('Core',
									  'File',
									  $frontendOptions,
									  $backendOptions);
		  
		  $cacheID = self::DocCacheIDPrefix.$id;
		  if(!$cache_result = $cache->load($cacheID)) {
				return false;
		  }
		  else{
				$output = Zend_Json::decode($cache_result);
				return $output;
		  }
	 }
	 
	 
	 function cachedDocData($id, $docData){
		  
		  $frontendOptions = $frontendOptions = array(
				 'lifetime' => self::docCacheLife,
				 'automatic_serialization' => true
		  );
		 
		  $backendOptions = array(
				 'cache_dir' => self::docCache // Directory where to put the cache files
			);
		 
		  $cache = Zend_Cache::factory('Core',
									  'File',
									  $frontendOptions,
									  $backendOptions);
		  
		  $cacheID = self::DocCacheIDPrefix.$id;
		  $docJSON = Zend_Json::encode($docData);
		  $cache->save($docJSON, $cacheID ); //save result to the cache
	 }
	 
	 
	 
	 //get language information and other translated versions of the given document
	 function getTranslatedVersions($id){
		  
		  $db = $this->startDB();
		  $id = App_Security::inputCheck($id);
		  
		  $sql = "SELECT gd.langCode AS lang, gl.lang_en AS label, gl.lang AS language, gd.id AS documentID  
		  FROM gap_documents AS gd
		  JOIN gap_languages AS gl ON gd.langCode = gl.code
		  WHERE gd.id = $id OR gd.transID = $id
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  return $result;
	 }
	 
	 //get document sections
	 function getDocumentSections($id){
		  
		  $db = $this->startDB();
		  $id = App_Security::inputCheck($id);
		  
		  $sql = "SELECT sectionID AS section, MIN(pageID) AS firstPage
		  FROM gap_tokens
		  WHERE docID = $id 
		  GROUP BY sectionID
		  ORDER BY sectionID
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  return $result;

	 }
	 
	 
	 
	 
	 function initialize(){
		  $db = $this->startDB();
		  $this->initializeTab(); //create the database table if it doesn't exist
		  return $db;
	 }
	 
	 //get the document by the ID assigned by the local application
	 function getByID($id){
		  $db = $this->initialize();
		  $id = App_Security::inputCheck($id);
		  $sql = "SELECT * FROM gap_documents WHERE id = $id LIMIT 1; ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$this->id = $id;
				$this->parserID = $result[0]["parserID"];
				$this->batchID =  $result[0]["batchID"];
				$this->status = $result[0]["status"];
				$this->title = $result[0]["title"];
				$this->url = $result[0]["url"];
				$this->created = $result[0]["created"];
				$this->updated = $result[0]["updated"];
				if(strlen($result[0]["pLinks"])>4){
					 $result[0]["pLinks"] = Zend_Json::decode($result[0]["pLinks"]);
					 $this->pLinks = $result[0]["pLinks"];
					 $this->makeResultGetLink();
				}
				else{
					 $this->pLinks = false;
				}
				return $result[0];
		  }
		  else{
				return false;
		  }
		  
	 }
	 
	 //create links for reviewing result data (since can't directly download without HTTP authentication)
	 function makeResultGetLink(){
		  
		  if(is_array($this->pLinks)){
				$newPlinks = array();
				foreach($this->pLinks as $link){
				
					 $actLink = array();
					 $linkEx = explode(".", $link); //exlode a link to get the file type
					 $lastExtension = $linkEx[(count($linkEx)-1)];
					 $formatParam = "";
					 $actLink["format"] = "JSON";
					 if($lastExtension == "json"){
						  $formatParam = "&format=json";
						  $actLink["format"] = "JSON";
					 }
					 elseif($lastExtension == "xml"){
						  $formatParam = "&format=xml";
						  $actLink["format"] = "XML";
					 }
					 elseif($lastExtension == "kml"){
						  $formatParam = "&format=kml";
						  $actLink["format"] = "KML";
					 }
					 $typeParam = "";
					 $actLink["type"] = "summary";
					 if(isset($linkEx[(count($linkEx)-2)])){
						  if($linkEx[(count($linkEx)-2)] == "lem"){
								$typeParam = "&type=lem";
								$actLink["type"] = "full text";
						  }
					 }

					 $actLink["local-href"] = App_Config::getHost().self::docResultLinkSuffix.$this->id.$formatParam.$typeParam;
					 $actLink["edina-href"] = $link;
					 $newPlinks[] = $actLink;
				}
				$this->pLinks = $newPlinks;
		  }
		  
	 }
	 
	 
	 
	 
	 //get the document by the ID assigned by the GeoParser
	 function getByParserID($parserID){
		  $db = $this->initialize();
		  $parserID = App_Security::inputCheck($parserID);
		  $sql = "SELECT * FROM gap_documents WHERE parserID = '$parserID' LIMIT 1; ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$this->id = $result[0]["id"];
				$this->parserID = $result[0]["parserID"];
				$this->batchID =  $result[0]["batchID"];
				$this->status = $result[0]["status"];
				$this->title = $result[0]["title"];
				$this->url = $result[0]["url"];
				$this->created = $result[0]["created"];
				$this->updated = $result[0]["updated"];
				if(strlen($result[0]["pLinks"])>4){
					 $result[0]["pLinks"] = Zend_Json::decode($result[0]["pLinks"]);
					 $this->pLinks = $result[0]["pLinks"];
					 $this->makeResultGetLink();
				}
				return $result[0];
		  }
		  else{
				return false;
		  }
		  
	 }
	 
	 
	 
	 function addDocument(){
		  $db = $this->initialize();
		  
		  if($this->validURL($this->url)){
				$newID = $this->getLastID() + 1;
				$data = array("id" => $newID,
								  "parserID" => "",
								  "batchID" =>  $this->batchID,
								  "status" => "submitted",
								  "title" => $this->title,
								  "url" => $this->url,
								  "created" =>  date("Y-m-d H:i:s")
								  );
				
				try{
					 $db->insert('gap_documents', $data);
					 return $newID;
				}
				catch (Exception $e) {
					 //echo (string)$e;
					 return false;
				}
		  }
		  else{
				return false;
		  }
	 }
	 
	 function validURL($url){
		  if(substr($url, 0, 7) == "http://" || substr($url, 0, 8) == "https://" ){
				return true;
		  }
		  else{
				return false;
		  }
	 }
	 
	 
	 
	 function updateStatus($status){
		  $db = $this->startDB();
		  $data = array("status" => $status);
		  $where = "id = ".$this->id;
		  $db->update("gap_documents", $data, $where);
	 }  
	 
	 function updateDataByBatchURL($data){
		  $db = $this->startDB();
		  $where = array();
		  $where[] = "batchID = ".$this->batchID;
		  $where[] = "url = '".$this->url."' OR url = '".str_replace("&amp;", "&", $this->url)."' ";
		  $db->update("gap_documents", $data, $where);
	 }
	 
	 
	 
	 
	 //get the ID for the last batch
	 function getLastID(){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT MAX(id) as maxID FROM gap_documents WHERE 1";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				return $result[0]["maxID"];
		  }
		  else{
				return 0;
		  }
	 }
	 
	
	 
	 
	 function initializeTab(){
		  
		  $db = $this->startDB();
		  $sql = "
		  CREATE TABLE IF NOT EXISTS gap_documents (
				id int(11) NOT NULL AUTO_INCREMENT,
				parserID varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
				batchID int(11),
				status varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
				title varchar(200) NOT NULL,
				url varchar(200) NOT NULL,
				created datetime NOT NULL,
				updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				pLinks text,
				PRIMARY KEY (id),
				UNIQUE parerID (parserID),
				INDEX batchID (batchID)
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
