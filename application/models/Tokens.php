<?php

/*
 
Class for adding, removing, retrieving and updating tokens

*/
class Tokens {
 
    public $db; //database connection object
    
	 function getGapVisDocPage($docID, $pageID){
		  $output = false;
		  $db = $this->startDB();
		  $sql = "SELECT gt.token, gt.sentID, gt.pws, grefs.uriID, gt.paraID
					 FROM gap_tokens AS gt
					 LEFT JOIN gap_gazrefs AS grefs ON grefs.tokenID = gt.id
					 WHERE gt.docID = $docID AND gt.pageID = $pageID
					 ORDER BY gt.id
					 ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$output = "";
				$firstToken = true;
				$lastParaID = false;
				$prevToken = false;
				foreach($result as $row){
					 $paraID = $row["paraID"];
					 $token = $row["token"];
					 if(strlen($row["uriID"])>0){
						  $token = "<span class=\"place\" data-place-id=\"".$row["uriID"]."\" >".$token."</span>";
					 }
					 
					 if($lastParaID != $paraID){
						  $lastParaID = $paraID;
						  if($prevToken == "." || $prevToken == "!" || $prevToken == "?" || $prevToken == '”'){
								$output .= "<br/><br/>";
						  }
					 }
					 
					 if(!$row["pws"] || $firstToken){
						  $output .= $token;
					 }
					 else{
						  $output .= " ".$token;
					 }
					 
					 $prevToken = $token;
					 $firstToken = false;
				}
				$output = $this->GapVisTextDecoding($output);
		  }
	 
		  return $output;
	 }
	 
	 
	 function GapVisTextDecoding($string){
		  $entities = array("&#39;" => "'");
		  $puncts = array(".", ",", ":", ";", "?", "!", "' ", "'s ", '”');
		  //$entities = array("&#39;" => " ");
		  $string = htmlspecialchars_decode($string);
		  $string = html_entity_decode($string);
		  foreach($entities as $entKey => $value){
				$string = str_replace($entKey, $value, $string);
		  }
		  foreach($puncts as $punct){
				$string = str_replace(" ".$punct, $punct, $string);
		  }
		  
		  return $string;
	 }
	 
	 
	 
	 //check to see if the document is already in
	 function getTokensByDocID($docID){
		  $db = $this->startDB();
		  $sql = "SELECT * FROM gap_tokens WHERE docID = $docID ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				return $result;
		  }
		  else{
				return false;
		  }
	 }
	 
	 
	 function addRecord($data){
		  $db = $this->startDB();
		  
		  try{
				$db->insert('gap_tokens', $data);
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
		  $sql = "SELECT count(id) as idCount FROM gap_tokens WHERE docID = $docID ";
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
		  CREATE TABLE IF NOT EXISTS gap_tokens (
				id int(11) NOT NULL AUTO_INCREMENT,
				docID int(11) NOT NULL,
				batchID int(11) NOT NULL,
				pageID int(11) NOT NULL,
				paraID int(11) NOT NULL,
				sentID varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
				tokenID varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
				pws tinyint(1) NOT NULL,
				gazRef varchar(100) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
				token varchar(200) NOT NULL,
				updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY docID (docID),
				KEY pageID (pageID),
				KEY token (token),
				KEY gazRef (gazRef)
			 ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;
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
