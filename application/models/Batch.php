<?php

/**
*
* Basic settings for interacting with the
* Edina API

*/
class Batch {
 
	 public $db;
	 
	 public $id;
	 public $parserID;
	 public $status;
	 public $title;
	 public $note;
	 public $created;
	 public $updated;
	 
	 public $documents; //array of documents belonging to a batch;
	 
	 const docLinkSuffix = "/geoparse/doc-review?docID=";
	 
	 
	 function initialize(){
		  $db = $this->startDB();
		  $this->initializeTab(); //create the database table if it doesn't exist
		  return $db;
	 }
	 
	 
	 
	 function getByID($id){
		  $db = $this->initialize();
		  $id = App_Security::inputCheck($id);
		  $sql = "SELECT * FROM gap_batches WHERE id = $id LIMIT 1; ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$this->id = $id;
				$this->parserID = $result[0]["parserID"];
				$this->status = $result[0]["status"];
				$this->title = $result[0]["title"];
				$this->note = $result[0]["note"];
				$this->created = $result[0]["created"];
				$this->updated = $result[0]["updated"];
				$this->getBatchDocuments($this->id);
				return $result[0];
		  }
		  else{
				return false;
		  }
		  
	 }
	 
	 function getByParserID($parserID){
		  $db = $this->initialize();
		  $parserID = App_Security::inputCheck($parserID);
		  $sql = "SELECT * FROM gap_batches WHERE parserID = '$parserID' LIMIT 1; ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$this->id = $result[0]["id"];
				$this->parserID = $result[0]["parserID"];
				$this->status = $result[0]["status"];
				$this->title = $result[0]["title"];
				$this->note = $result[0]["note"];
				$this->created = $result[0]["created"];
				$this->updated = $result[0]["updated"];
				$this->getBatchDocuments($this->id);
				return $result[0];
		  }
		  else{
				return false;
		  }
		  
	 }
	 
	 
	 function getBatchDocuments($batch){
		  
		  $db = $this->startDB();
		  $sql = "SELECT * FROM gap_documents WHERE batchID = $batch";
		  $result = $db->fetchAll($sql, 2);
		  $output = false;
		  if($result){
				$output = array();
				$allDone = true;
				foreach($result as $row){
					 $actOut = $row;
					 $actOut["doc-rev-href"] = App_Config::getHost().self::docLinkSuffix.$row["id"];
					 if(strlen($row["pLinks"])>4){
						  $actOut["pLinks"] = Zend_Json::decode($row["pLinks"]);	  
					 }
					 else{
						  $allDone = false;
					 }
					 $output[] = $actOut;
				}
				
				if($allDone){
					 $this->updateStatus("completed");
				}
		  }
	 
		  $this->documents = $output;
		  return $output;
	 }
	 
	 
	 
	 function addBatch(){
		  $db = $this->initialize();
		  
		  $newID = $this->getLastID() + 1;
		  $parserID = $this->makeDateHashParserID($newID);
		  $data = array("id" => $newID,
							 "parserID" => $parserID,
							 "status" => "submitted",
							 "title" => $this->title,
							 "note" => $this->note,
							 "created" =>  date("Y-m-d H:i:s")
							 );
		  
		  try{
				$this->id = $newID;
				$this->parserID = $parserID;
				$db->insert('gap_batches', $data);
				return $newID;
		  }
		  catch (Exception $e) {
				$this->id = false;
				$this->parserID = false;
				return false;
		  }
		  
	 }
	 
	 function updateStatus($status){
		  $db = $this->startDB();
		  $data = array("status" => $status);
		  $where = "id = ".$this->id;
		  $db->update("gap_batches", $data, $where);
	 }  
	 
	 
	 
	 //get the ID for the last batch
	 function getLastID(){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT MAX(id) as maxID FROM gap_batches WHERE 1";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				return $result[0]["maxID"];
		  }
		  else{
				return 0;
		  }
	 }
	 
	
	 //make a parser batch id based on part of the hash of the current date 
	 function makeDateHashParserID($id){
		  
		  $date = date("Y-m-d H:i:s");
		  $dateHash = sha1($date);
		  $prefix = substr($dateHash, 0, 5)."-";
		  $parserID = $this->makeParserID($id, $prefix);
		  return $parserID;
	 }  
 
	 //generate an id for geoparser batch
	 function makeParserID($id, $prefix="batch-"){
		  $prefix .= chr(rand(97,122))."-";
		  return $prefix.$id."-";
	 }
	 
	 
	 
	 function initializeTab(){
		  
		  $db = $this->startDB();
		  $sql = "
		  CREATE TABLE IF NOT EXISTS gap_batches (
				id int(11) NOT NULL AUTO_INCREMENT,
				parserID varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
				status varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
				title varchar(200) NOT NULL,
				created datetime NOT NULL,
				updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				note text NOT NULL,
				PRIMARY KEY (id),
				UNIQUE parerID (parserID)
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
