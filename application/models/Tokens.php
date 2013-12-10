<?php

/*
 
Class for adding, removing, retrieving and updating tokens

*/
class Tokens {
 
    public $db; //database connection object
    public $sectionID; //current section
	 
	 
	 function getGapVisDocPage($docID, $pageID, $paraID = false, $specificPlace = false){
		  $output = false;
		  $db = $this->startDB();
		  
		  $paraTerm = " ";
		  if($paraID != false){
				$paraTerm = " AND gt.paraID = '$paraID ' ";
		  }
		  
		  $sql = "SELECT gt.id, gt.token, gt.sentID, gt.pws, grefs.uriID, gt.paraID, gt.sectionID
					 FROM gap_tokens AS gt
					 LEFT JOIN gap_gazrefs AS grefs ON grefs.tokenID = gt.id
					 WHERE gt.docID = $docID AND gt.pageID = $pageID
					 $paraTerm
					 ORDER BY gt.id
					 ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$this->sectionID = $result[0]["sectionID"];
				$output = "";
				$firstToken = true;
				$lastParaID = false;
				$prevToken = false;
				$issuesObj = new Issues;
				foreach($result as $row){
					 $paraID = $row["paraID"];
					 $token = $row["token"];
					 $tokenID = $row["id"];
					 if(strlen($row["uriID"])>0){
						  if(!$specificPlace){
								$token = "<span data-token-id=\"".$tokenID."\" class=\"place\" data-place-id=\"".$row["uriID"]."\" >".$token."</span>";
								/*
								if($issuesObj->checkTokenIssues($tokenID)){
									 $token .= "<sup><a href=\"../report/token-issues/".$tokenID."\" target=\"_blank\"  >[**]</a></sup>";
								}
								*/
								if($issuesObj->checkPlaceIssues($row["uriID"])){
									 $token .= "<sup><a href=\"../report/place-issues/".$row["uriID"]."\" target=\"_blank\" title=\"Problem reported on this place\" >[*]</a></sup>"; 
								}
						  }
						  elseif($specificPlace == $tokenID){
								$token = "<span id=\"t-".$tokenID."\" class=\"place\">".$token."</span>";
						  }
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
	 
	 //get token by ID
	 function getTokenByID($tokenID){
		  $db = $this->startDB();
		  $sql = "SELECT * FROM gap_tokens WHERE id = $tokenID LIMIT 1; ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				return $result[0];
		  }
		  else{
				return false;
		  }
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
	 
	 //check to see if the document is already in
	 function getPlaceByTokensID($tokenID){
		  $db = $this->startDB();
		  $sql = "SELECT grefs.id AS gazRefID, grefs.uriID, gazuris.uri, gazuris.label, gazuris.latitude, gazuris.longitude
					 FROM gap_gazrefs AS grefs
					 JOIN gap_gazuris AS gazuris ON grefs.uriID = gazuris.id
					 WHERE grefs.tokenID = $tokenID
					 LIMIT 1;
					 ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				return $result[0];
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
	 
	 function tokenStructure($docID){
		  $db = $this->startDB();
		  $sql = "SELECT id, structure FROM gap_tokens WHERE docID = $docID  ORDER BY id ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$lastStructurePage = 0;
				$lastDataPage = 0;
			
				$lastSection = 0;
				foreach($result as $row){
					 $id = $row["id"];
					 $structure = $row["structure"];
					 $where = " id = $id ";
					 
					 $strEx = explode("_", $structure);
					 $actSection = $strEx[0];
					 $actStructurePage =  $strEx[1];
					 
					 if($actStructurePage < $lastDataPage){
						  if($actStructurePage == $lastStructurePage){
								$actDataPage = $lastDataPage;
						  }
						  else{
								$actDataPage = $lastDataPage + 1;
						  }
					 }
					 else{
						  $actDataPage = $actStructurePage;
					 }
					 
					 
					 $data = array("sectionID" => $actSection,
										"pageID" => $actDataPage,
										"paraID" => $strEx[2]);
					 $db->update("gap_tokens", $data, $where);
					 $lastDataPage = $actDataPage;
					 $lastStructurePage = $actStructurePage;
				}
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
