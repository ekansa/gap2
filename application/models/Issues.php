<?php

/**
* gets and creates data error reporting issues

*/
//ini_set("memory_limit", "512M");

class Issues {
 
	 public $db; //database connection object
	 public $postParams; //posted issues parameters

	 const pleiadesBase = "http://pleiades.stoa.org/places/"; //base uri for pleides places

	 
	 //get issues related to a place
	 function getPlaceIssues($uriID){
		  $document = false;
		  $docID = false;
		  
		  $db = $this->startDB();
		  $sql = "SELECT *
		  FROM gap_issues AS gi
		  WHERE gi.placeID = $uriID AND gi.active = 1
		  ORDER BY gi.updated;";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$docID = $result[0]["docID"];
		  }
		  
		  if(!$docID){
				$docID = $this->getDocumentAssociationByOldPlace($uriID);
		  }
		  
		  $docObj = new Document;
		  $document = $docObj->getByID($docID);
		  
		  $gazObj = new GazetteerRefs;
		  $place = $gazObj->getGapVisPlace($uriID);
		  
		  $output = array("docID" => $docID,
								"document" => $document,
								"place" => $place,
								"issues" => $result
								);
		  
		  return $output;
	 }
	 
	 
	 //get issues related to a token
	 function getTokenIssues($tokenID){
		  $paraID = false;
		  $context = false;
		  $place  = false;
		  
		  $db = $this->startDB();
		  $sql = "SELECT * FROM gap_issues WHERE tokenID = $tokenID  AND active = 1 ORDER BY updated;";
		  $result = $db->fetchAll($sql, 2);
		  
		  $tokensObj = new Tokens;
		  $tokenData = $tokensObj->getTokenByID($tokenID);
		  if(is_array($tokenData)){
				$token = $tokenData["token"];
				$paraID = $tokenData["paraID"];
				$pageID = $tokenData["pageID"];
				$docID =  $tokenData["docID"];
				$context = $tokensObj->getGapVisDocPage($docID, $pageID, $paraID, $tokenID);
				$place = $tokensObj->getPlaceByTokensID($tokenID);
				$docObj = new Document;
				$document = $docObj->getByID($docID);
		  }
				
		  $output = array("tokenID" => $tokenID,
								"token" => $token,
								"docID" => $docID,
								"document" => $document,
								"pageID" => $pageID,
								"context" => $context,
								"place" => $place,
								"issues" => $result
								);
		  
		  return $output;
	 }
	 
	 
	 
	 //boolean response if a token has any reported issues
	 function checkTokenIssues($tokenID){
		  $db = $this->startDB();
		  $sql = "SELECT id FROM gap_issues WHERE tokenID = $tokenID AND active = 1 LIMIT 1;";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				return true;
		  }
		  else{
				return false;
		  }
	 }
	 
	 
	 //boolean response if a gazzeteer ref has any reported issues
	 function checkPlaceIssues($placeID){
		  $db = $this->startDB();
		  $sql = "SELECT id FROM gap_issues WHERE placeID = $placeID AND active = 1 LIMIT 1;";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				return true;
		  }
		  else{
				return false;
		  }
	 }
	 
	 
	 
	 
	 //create a new issue record
	 function postIssue(){
    
		  $db = $this->startDB();
		  $postParams = $this->postParams;
	 
		  $data = array();
		  $data["active"] = true;
		  if(isset($postParams["ctf-problem"])){
				$data["note"] = $postParams["ctf-problem"];
		  }
		  if(isset($postParams["r-book-uri"])){
				//this is a work around to get the book-id. the book-id POSTed by the ajax request is wrong, so we're getting in from another parameter
				// here's the pattern expected in the r-book-uri parameter: http://gap2.alexandriaarchive.org/gapvis/index.html#book/1/read/1/462
				$refURI = $postParams["r-book-uri"];
				$refURIex = explode("#", $refURI);
				$refParams = $refURIex[1];
				$refParamsEx = explode("/", $refParams);
				$data["docID"] = $refParamsEx[1];
		  }
		  if(isset($postParams["token-id"])){
				$data["tokenID"] = $postParams["token-id"];
		  }
		  if(isset($postParams["page-id"])){
				$data["pageID"] = $postParams["page-id"];
		  }
		  if(isset($postParams["place-id"])){
				$data["placeID"] = $postParams["place-id"];
		  }
		  
		  if(count($data) == 6){
				$db->insert("gap_issues", $data);
				return true;
		  }
		  else{
				return false;
		  }
	 }//end function

	 //delete an issue
	 function deleteIssueByID($issueID){
		  $db = $this->startDB();
		  $where = "id = $issueID ";
		  $data = array("active" => false);
		  $db->update("gap_issues", $data , $where);
	 }

	 
	 //update an issue, this means copy a given issueID, change the note, then make the old issueID inactive
	 function updateIssueByID($issueID, $newNote){
		  $db = $this->startDB();
		  
		  $sql = "SELECT * FROM gap_issues WHERE id = $issueID LIMIT 1; ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$data = $result[0];
				unset($data["id"]);
				unset($data["updated"]);
				$data["note"] = $newNote;
				$data["oldID"] = $issueID;
				$db->insert("gap_issues", $data);
				$this->deleteIssueByID($issueID); //now make the old issueID inactive (deleted)
		  }
	 }
	 
	 
	 //changes references to a place to a new URI
	 function updateURIcloseIssues($oldURIid, $newURIid){
		  $db = $this->startDB();
		  
		  $data = array("uriID" => $newURIid);
		  $db->update("gap_gazrefs", $data, $where); //change the gazeteer refs to reflect the new URI
		  
		  $where = "placeID = $oldURIid ";
		  $data = array("active" => false);
		  $db->update("gap_issues", $data, $where); //change the issue tracking to note that the place with issues no longer has active issues
	 }
	 
	 
	 function getDocumentAssociationByOldPlace($oldURIid){
		  $db = $this->startDB();
		  $sql = "SELECT docID FROM gap_issues WHERE placeID = $oldURIid LIMIT 1; ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				return $result[0]["docID"];
		  }
		  else{
				return false;
		  }
	 }
	 
	 
	 


	 //startup the db
	 function startDB(){
		  if(!$this->db){
				$db = Zend_Registry::get('db');
				$db->getConnection();
				$this->setUTFconnection($db);
				$this->db = $db;
		  }
		  return $this->db;
	 }//end function

	 //make sure character encoding is set, so greek characters work
	 function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
	 } 
 
	 //a little check to avoid some SQL inject attacks
	 function less_security_check($input){
		  $badArray = array("#", "--");
		  foreach($badArray as $bad_word){
				if(stristr($input, $bad_word) != false){
					 $input = str_ireplace($bad_word, " ", $input);
				}
		  }
		  return $input;
	 }
 
 
	 //a little check to avoid some SQL inject attacks
	 function security_check($input){
		  $badArray = array("DROP", "SELECT", "#", "--", "DELETE", "INSERT", "UPDATE", "ALTER", "=");
		  foreach($badArray as $bad_word){
				if(stristr($input, $bad_word) != false){
					 $input = str_ireplace($bad_word, "XXXXXX", $input);
				}
		  }
		  return $input;
	 }
	 
	 
	 
}//end class

?>
