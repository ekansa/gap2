<?php

/**
* gets data about all of the documents

*/

class Documents {
 
	 public $documentData;
	 public $db; //database connection object

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
