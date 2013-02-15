<?php

/**
* gets data about all of the documents

*/

class Documents {
 
	 public $documentData;
	 public $db; //database connection object

	 
	 //get a list of documents that have references to a given URI
	 function getGapVisWithPlaceURI($uri){
		  $db = $this->startDB();
		  $gazObj = New GazetteerRefs;
		  $IDgazURI = $gazObj->URIgetID($uri);
		  $data = $this->getGapVisWithPlaceID($IDgazURI);
		  return $data;
	 }
	 
	 
	 //get a list of documents containing references to a given place ($IDgazURI is an integer ID for a given place uri)
	 function getGapVisWithPlaceID($IDgazURI){
		  $db = $this->startDB();
		  
		  $sql = "SELECT gd.id, gd.title, gd.url AS uri, COUNT( gazrefs.id ) AS tokenCount, gb.title AS batchTtile, gd.updated
		  FROM gap_documents AS gd
		  JOIN gap_batches AS gb ON gb.id = gd.batchID
		  JOIN gap_gazrefs AS gazrefs ON gd.id = gazrefs.docID
		  WHERE gd.status = 'complete'
		  AND gazrefs.uriID = $IDgazURI
		  GROUP BY gazrefs.docID
		  ;";
		  
		  $documentData = array();
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				foreach($result as $row){
					 $document = array();
					
					 $document = array(
						  "id" => $row["id"],
						  "tokenCount" => $row["tokenCount"],
						  "title" => $row["title"],
						  "uri" => str_replace("&", "&amp;", $row["uri"]),
						  "author" => $row["batchTtile"],
						  "printed" => $row["updated"]
					 );
					 
					 $documentData[] = $document;
		  
				}//end loop through results
		  }
		  $this->documentData =  $documentData;
		  return $documentData;
	 }
	 
	 
	 function getAllComplete(){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT gd.id, gd.title, gd.url AS uri, gb.title AS batchTtile, gd.updated
		  FROM gap_documents AS gd
		  JOIN gap_batches AS gb ON gb.id = gd.batchID
		  WHERE gd.status = 'complete'
		  ;";
	 
		  $documentData = array();
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				foreach($result as $row){
					 $document = array();
					
					 $document = array(
						  "id" => $row["id"],
						  "title" => $row["title"],
						  "uri" => $row["uri"],
						  "author" => $row["batchTtile"],
						  "printed" => $row["updated"]
					 );
					 
					 $documentData[] = $document;
				}
				$this->documentData =  $documentData;
		  }
		  
		  $this->documentData =  $documentData;
		  return $documentData;
	 }//end function


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
