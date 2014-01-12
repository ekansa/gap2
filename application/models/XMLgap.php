<?php

/*
 
Class for adding, removing, retrieving and updating tokens

*/
class XMLgap {
 
    public $db; //database connection object
	 public $doc; // xml document
	 
	 const XMLfile = "C:\\GitHub\\gap2\\public\\export\\hdt_eng_capsule.xml";
	 
	 
	 function updateTokens($xmlTokens, $dbTokens){
		  $db = $this->startDB();
		  
		  $this->updateExistingTokenSort($xmlTokens, $dbTokens);
	 
		  $baseData = $dbTokens[0];
		  unset($baseData["id"]);
		  unset($baseData["oldID"]);
		  unset($baseData["tsort"]);
		  unset($baseData["token"]);
		  unset($baseData["updated"]);
		  
		  $this->saveXMLtokens($baseData, $xmlTokens);
	 }
	 
	 function saveXMLtokens($baseData, $xmlTokens, $sortFactor = 0){
		  $db = $this->startDB();
		  $baseData["sentID"] = "xml";
		  
		  if(isset($xmlTokens["tokens"])){
				$useTokens = $xmlTokens["tokens"];
		  }
		  else{
				$useTokens = $xmlTokens;
		  }
		  
		  foreach($useTokens as $tokenItem){
				
				$okToDadd = true;
				if(isset($tokenItem["DBalignment"])){
					 if($tokenItem["DBalignment"] != false){
						  $okToDadd = false;
					 }
				}
				if($okToDadd){
					 $data = $baseData;
					 $data["tsort"] = $tokenItem["index"] + $sortFactor;
					 $data["token"] = $tokenItem["token"];
					 $data["tokenID"] = "xml-".$tokenItem["index"];
					 $db->insert("gap_tokens_rev", $data);
				}
		  }
	 }
	 
	 
	 
	 
	 
	 
	 //update the sorting of existing tokens taking into account new tokesn from the xml
	 function updateExistingTokenSort($xmlTokens, $dbTokens){
		  $db = $this->startDB();
		  
		  //add sorting data to the existing records matched to XML
		  $i = 0;
		  foreach($xmlTokens["tokens"] as $tokenItem){
				$xmlIndex = $tokenItem["index"];
				$DBalignment = $tokenItem["DBalignment"];
				if($DBalignment != false){
					 $i = 0;
					 foreach($dbTokens as $dbToken){
						  if($dbToken["id"] == $DBalignment){
								$dbToken["xmlIndex"] = $xmlIndex;
								$dbTokens[$i] = $dbToken;
						  }
						  $i++;
					 }
				}
		  }
		  
		  //add sorting data to the existing records not matched to XML
		  $lastXMLindex = 0;
		  $i = 0;
		  foreach($dbTokens as $dbToken){
				if(isset($dbToken["xmlIndex"])){
					 $lastXMLindex = $dbToken["xmlIndex"];
				}
				else{
					 $dbToken["xmlIndex"] = $lastXMLindex + .01;
					 $dbTokens[$i] = $dbToken;
					 $lastXMLindex = $dbToken["xmlIndex"];
				}
				$i++;
		  }
		  
		  //do the database update
		  foreach($dbTokens as $dbToken){
				$data = array("tsort" =>  $dbToken["xmlIndex"]);
				$where = "id = ".$dbToken["id"];
				$db->update("gap_tokens_rev", $data, $where);
		  }
	 }
	 
	 
	 
	 
	 function allignAllTokens($xmlTokens, $dbTokens){
		  
		  $output = false;
		  $newXmlTokens = array();
		  $firstDBid = $dbTokens[0]["id"];
		  $lastDBid = $dbTokens[count($dbTokens)-1]["id"];
		  
		  
		  $xmlTokens = $this->makeXMLtokenCounts($xmlTokens);
		  $lastXMLindex = count($xmlTokens)-1;
		  
		  $firstCertainAlignmentXML = false;
		  $firstCertainAlignmentDB = false;
		  $lastCertainAlignmentXML = false;
		  $lastCertainAlignmentDB = false;
		  
		  $minDBid = 0;
		  foreach($xmlTokens as $xmlTokenItem){
				$actXMLindex = $xmlTokenItem["index"];
				$xmlToken = $xmlTokenItem["token"];
				$tokenCount = $xmlTokenItem["tokenCount"];
				$xmlTokenItem["c-alignment"] = false;
				$cleanXMLtoken = $this->tokenClean($xmlToken );
				if(strlen($cleanXMLtoken)>0 && $tokenCount < 2){
					 $DBid = $this->compareXMLandDBtokens($cleanXMLtoken, $dbTokens, $minDBid);
					 if($DBid){
						  $xmlTokenItem["c-alignment"] = $DBid;
						  $minDBid = $DBid;
						  if(!$firstCertainAlignmentXML){
								$firstCertainAlignmentXML = $actXMLindex;
								$firstCertainAlignmentDB = $DBid;
						  }
						  if($actXMLindex == $lastCertainAlignmentXML){
								$lastCertainAlignmentXML = $actXMLindex;
								$lastCertainAlignmentDB = $DBid;
						  }
					 }
				}
				
				$newXmlTokens[] = $xmlTokenItem;
		  }
		  
		  
		  $alignArray = false;
		  if($firstCertainAlignmentDB > $firstDBid){
				$minDBid = 0;
				$maxID = $firstCertainAlignmentDB;
				$i = $firstCertainAlignmentXML;
				while($i >= 0){
					 foreach($newXmlTokens as $xmlTokenItem){
						  $actXMLindex = $xmlTokenItem["index"];
						  if($i == $actXMLindex){
								$xmlToken = $xmlTokenItem["token"];
								$tokenCount = $xmlTokenItem["tokenCount"];
								$xmlTokenItem["lc-alignment"] = false;
								$cleanXMLtoken = $this->tokenClean($xmlToken );
								if(strlen($cleanXMLtoken)>0){
									 $DBid = $this->compareXMLandDBtokens($cleanXMLtoken, $dbTokens, $minDBid, $maxID);
									 if($DBid){
										  $xmlTokenItem["lc-alignment"] = $DBid;
										  $maxID = $DBid;
										  if($firstDBid ==  $DBid){
												$alignArray = array("xmlStart" => $actXMLindex, "DBidStart" =>  $DBid);
										  }
									 }
								}
								$newXmlTokens[$i] = $xmlTokenItem;
						  }
					 }
					 $i = $i - 1;
				}
				
		  }
		  else{
				$alignArray = array("xmlStart" => $firstCertainAlignmentXML, "DBidStart" =>  $firstCertainAlignmentDB);
		  }
		  
		  
		  
		  $i = 0;
		  if(is_array($alignArray)){
				$minDBid = $alignArray["DBidStart"];
				$minXMLindex =  $alignArray["xmlStart"];
		  }
		  else{
				$minDBid = 0;
				$minXMLindex = 0;
		  }
		   
		  foreach($newXmlTokens as $xmlTokenItem){
				$actXMLindex = $xmlTokenItem["index"];
				$xmlToken = $xmlTokenItem["token"];
				$addLLC = true;
				if(isset($xmlTokenItem["c-alignment"]) ){
					 if($xmlTokenItem["c-alignment"] != false){
						  $minDBid = $xmlTokenItem["c-alignment"];
						  $addLLC = false;
					 }
				}
				elseif(isset($xmlTokenItem["lc-alignment"]) ){
					 if($xmlTokenItem["lc-alignment"] != false){
						  $minDBid = $xmlTokenItem["lc-alignment"];
						  $addLLC = false;
					 }
				}
				else{
					 
				}
				
				if($addLLC){
					 $xmlTokenItem["llc-alignment"] = false;
					 $cleanXMLtoken = $this->tokenClean($xmlToken); //strip punctuation
					 if(strlen($cleanXMLtoken)>0){
						  $DBid = $this->compareXMLandDBtokens($cleanXMLtoken, $dbTokens, $minDBid);
						  if($DBid){
								if($actXMLindex > $minXMLindex){
									 $xmlTokenItem["llc-alignment"] = $DBid;
									 $minDBid = $DBid;
								}
						  }
					 }
					 $newXmlTokens[$i] = $xmlTokenItem;
				}
				$i++;
		  }
		  
		  $i=0;
		  foreach($newXmlTokens as $xmlTokenItem){
				$allAlignment = false;
				if(isset($xmlTokenItem["c-alignment"]) ){
					 if($xmlTokenItem["c-alignment"] != false){
						  $allAlignment = $xmlTokenItem["c-alignment"];
					 }
				}
				
				if(!$allAlignment && isset($xmlTokenItem["lc-alignment"]) ){
					 if($xmlTokenItem["lc-alignment"] != false){
						  $allAlignment = $xmlTokenItem["lc-alignment"];
					 }
				}
				
				if(!$allAlignment && isset($xmlTokenItem["llc-alignment"]) ){
					 if($xmlTokenItem["llc-alignment"] != false){
						  $allAlignment = $xmlTokenItem["llc-alignment"];
					 }
				}
				
				$xmlTokenItem["DBalignment"] = $allAlignment;
				$newXmlTokens[$i] = $xmlTokenItem;
		  $i++;
		  }
		  
		  
		  return array("alignment"=> $alignArray, "tokens" => $newXmlTokens);
	 }
	 
	 
	 function tokenClean($token){
		  
		  if(strstr($token, "[")){
				$cleanToken = $token;
		  }
		  elseif(strstr($token, "]")){
				$cleanToken = $token;
		  }
		  else{
				$cleanToken = preg_replace('/[^a-z]+/i', '', $token); //strip punctuation
		  }
		  
		  return $cleanToken;
	 }
	 
	 
	 
	 function makeXMLtokenCounts($xmlTokens){
		  
		  $output = false;
		  $newXmlTokens = array();
		  foreach($xmlTokens as $xmlTokenItem){
				$actXMLindex = $xmlTokenItem["index"];
				$xmlTokenItem["token"] = trim($xmlTokenItem["token"]);
				$xmlToken = $xmlTokenItem["token"];
				$tokenCount = 1;
				$cleanXMLtoken = $this->tokenClean($xmlToken );
				if(strlen($cleanXMLtoken)>0){
					 foreach($xmlTokens as $xmlTokenItemB){
						  if($actXMLindex != $xmlTokenItemB["index"]){
								$cleanXMLtokenB = $this->tokenClean($xmlTokenItemB["token"]);
								$cleanXMLtokenB = trim($cleanXMLtokenB);
								if($cleanXMLtokenB == $cleanXMLtoken){
									 $tokenCount++;
								}
						  }
					 }
				}
				$xmlTokenItem["tokenCount"] = $tokenCount;
				$newXmlTokens[] = $xmlTokenItem;
		  }
		  return $newXmlTokens;
	 }
	 
	 
	 
	 function simpleAllignTokens($xmlTokens, $dbTokens, $minDBid, $actXMLindex){
		  
		  $output = false;
		  foreach($xmlTokens as $xmlToken){
				$cleanXMLindex = $xmlToken["index"];
				$cleanXMLtoken = $this->tokenClean( $xmlToken["token"]); //strip punctuation
				if(strlen($cleanXMLtoken)>0){
					 if( $actXMLindex == $cleanXMLindex ){
						  $output = $this->compareXMLandDBtokens($cleanXMLtoken, $dbTokens, $minDBid);
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 
	 function compareXMLandDBtokens($cleanXMLtoken, $dbTokens, $minDBid = 0, $maxID = false){
		  $output = false;
		  $i = 0;
		  foreach($dbTokens as $dbTokenArray){
				$dbTokenID = $dbTokenArray["id"] + 0;
				$dbToken = $dbTokenArray["token"];
				if($dbTokenID > $minDBid){
					 if(!$maxID || $dbTokenID < $maxID){
						  $dbToken = $this->tokenClean($dbToken); //strip punctuation
						  if(strlen($dbToken)>0){
								if($dbToken == $cleanXMLtoken){
									 //found a match
									 $output = $dbTokenID;
									 break;
								}
								else{
									 if(substr($cleanXMLtoken, 0, 1) == "["){
										  if($dbToken == "["){
												$nextDBtoken = $this->tokenClean($dbTokens[$i + 1]["token"]);
												if($dbToken.$nextDBtoken == $cleanXMLtoken){
													 $output = $dbTokenID;
													 break;
												}
										  }
									 }
								}
						  }
					 }
				}
				$i++;
		  } 
		  return $output;
	 }
	 
	 
	 
	 
	 
	 function getDBtokens($book, $chapt, $section, $letterIndex){
		  $db = $this->startDB();
		  $structureTerm =  $this->makeDBstructureTerm($book, $chapt, $section, $letterIndex );
		  
		  $sql = "SELECT *
				FROM gap_tokens
				WHERE docID = 1 AND $structureTerm
				ORDER BY id
				";
				
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				return $result;
		  }
		  else{
				return false;
		  }
	 }
	 
	 
	 
	 function makeDBstructureTerm($book, $chapt, $section, $letterIndex ){
		  $lettersParts = array( 0=> "", 1=> "A", 2=> "B", 3=> "C", 4=> "D", 5=> "E", 6=> "F", 7=> "G", 8=> "H");
		  
		  /*
		  if(array_key_exists( $letterIndex, $lettersParts)){
				$letter = $lettersParts[$letterIndex];
		  }
		  else{
				$letter = $lettersParts[0];
		  }
		  
		  $structure = $book ."_".$chapt.$letter."_".$section;
		  $term = "structure = '$structure' ";
		  $term = "(".$term.")";
		  
		  */
		  $term = false;
		  foreach($lettersParts as $letter){
				$structure = $book ."_".$chapt.$letter."_".$section;
				if(!$term){
					 $term = "structure = '$structure' ";
				}
				else{
					 $term .= " OR structure = '$structure' ";
				}
		  }
		  
		  $term = "(".$term.")";
		  return $term;
	 }
	 
	 
	 function checkDBagainstXML(){
		  $db = $this->startDB();
		  $XMLdivs = $this->getXMLdivisions();
		  $CheckDivs = array();
		  $CheckDivs["good"] = 0;
		  $CheckDivs["badCount"] = 0;
		  $lastPart = false;
		  $letterIndex = 0;
		  foreach($XMLdivs as $actDiv){
				$book = $actDiv["book"];
				$chapt = $actDiv["chapt"];
				$section = $actDiv["section"];
				$part = $book."_".$chapt."_".$section;
				if($part != $lastPart){
					 $letterIndex = 0;
					 $lastPart = $part;
				}
				else{
					 $letterIndex++;
					 $lastPart = $part;
				}
				
				$XMLtokenCount = $actDiv["XMLtokenCount"];
				$structureTerm =  $this->makeDBstructureTerm($book, $chapt, $section, $letterIndex);
				
				$sql = "SELECT count(id) AS idCount, MIN(id) AS minID, sectionID AS book, pageID AS chapt, paraID AS section, structure
				FROM gap_tokens
				WHERE docID = 1 AND $structureTerm
				GROUP BY docID
				";
				$actDiv["sql"] = $sql;
				$result = $db->fetchAll($sql, 2);
				$DBtokenCount = false;
				if($result){
					 $DBtokenCount = $result[0]["idCount"] + 0;
					 $actDiv["DBtokenCount"] = $DBtokenCount;
					 $actDiv["minID"] = $result[0]["minID"] + 0;
					 if($DBtokenCount < $XMLtokenCount-5){
						  $actDiv["error"] = "-tokens";
						  $dbTokens = $this->getDBtokens($book, $chapt, $section, $letterIndex);
						  $actDiv["xmlTokens"] =  $this->allignAllTokens($actDiv["xmlTokens"], $dbTokens);
						  $this->updateTokens($actDiv["xmlTokens"], $dbTokens);
					 }
					 else{
						  $actDiv["error"] = false;
					 }
				}
				else{
					 $actDiv["DBtokenCount"] = $DBtokenCount;
					 $actDiv["error"] = "-section";
					 $baseData = $this->makeNewSectionBaseData($book, $chapt, $section, $letterIndex);
					 $actDiv["base"] = $baseData;
					 unset($baseData["sqls"]);
					 $this->saveXMLtokens($baseData, $actDiv["xmlTokens"], .1); //added a sort factor so that the new tokens are appended AFTER
				}
				
				if(!$actDiv["error"]){
					 $CheckDivs["good"]++; 
				}
				else{
					 $CheckDivs["badCount"]++;
					 $CheckDivs["bad"][] = $actDiv;
				}
				
		  }
		  
		  return $CheckDivs;
	 }
	 
	 //makes the baseData for adding new XML tokens
	 function makeNewSectionBaseData($book, $chapt, $section, $letterIndex){
		  $db = $this->startDB();
		  $baseData = array();
		  $offset = 1;
		  $continue = true;
		  $sqls = array();
		  while($continue){
				$structureTerm =  $this->makeDBstructureTerm($book, $chapt, $section  - $offset, $letterIndex );
				
				$sql = "SELECT *
				FROM gap_tokens
				WHERE docID = 1 AND $structureTerm
				ORDER BY id
				";
				
				$sqls[] = $sql;
				 
				$result = $db->fetchAll($sql, 2);
				if(!$result){
					 $continue = true;
					 $offset ++;
				}
				else{
					 $continue = false;
					 $baseData = $result[0];
					 unset($baseData["id"]);
					 unset($baseData["oldID"]);
					 unset($baseData["tsort"]);
					 unset($baseData["token"]);
					 unset($baseData["updated"]);
					 $baseData["paraID"] = $section;
					 $baseData["sqls"] = $sqls;
					 $baseData["structure"] = $book."_".$chapt."_".$section;
				}
				
		  }
		 
		  return $baseData;
	 }
	 
	 
	 
	 
	 function cleanXMLtext($text){
		  
		  $puncts = array(".", ",", ":", ";", "?", "!");
		  foreach($puncts as $punct){
				$text = str_replace($punct, $punct." ", $text);
		  }
		  
		  $text = str_replace("  ", " ", $text);
		  return $text;
	 }
	 
	 
	 
	 
	 function textArray(){
		  $db = $this->startDB();
		  $doc = $this->loadParseXML();
		  if($doc){
				
				$xpath = new DOMXpath($doc);
				
				$sql = "SELECT count(id) AS idCount, sectionID AS book, pageID AS chapt, paraID AS section, structure
				FROM gap_tokens
				WHERE docID = 1
				GROUP BY structure
				ORDER BY (sectionID * 100000) + pageID + (paraID / 1000)
				LIMIT 10;
				";
		  
				$result = $db->fetchAll($sql, 2);
				foreach($result as $row){
					 $dbTokenCount = $row["idCount"] + 0 ;
					 $book = $row["book"];
					 $chapt = $row["chapt"];
					 $section = $row["section"];
					 $structure = $row["structure"];
					 $xmlTokenCount = false;
					 $text = $this->getBookChapterSection($doc, $xpath, $book, $chapt, $section);
					 if($text){
						  $text  = trim($text);
						  $textEx = explode(" ", $text);
						  $xmlTokenCount = count($textEx);
						  unset($textEx);
						  unset($text);
					 }
					 
					 $textArray[$structure] = array("dbTokenCount" =>  $dbTokenCount, "xmlTokenCount" => $xmlTokenCount);
				}
				
		  }
		  return $textArray;
	 }
	 
	 function getXMLdivisions(){
		  $divisions = array();
		  $db = $this->startDB();
		  $doc = $this->loadParseXML();
		  if($doc){
				$xpath = new DOMXpath($doc);
				$query = "//book/@n";
				$bnodes = $xpath->query($query);
				$divisions = array();
				foreach ($bnodes  as $bnode) {
					 $book  = $bnode->nodeValue;
					 $query = "//book[@n='$book']/chapter/@n";
					 $cnodes = $xpath->query($query);
					 foreach ($cnodes   as $cnode) {
						  $chapt = $cnode->nodeValue;
						  $query = "//book[@n='$book']/chapter[@n='$chapt']/section/@n";
						  $snodes = $xpath->query($query);
						  foreach ($snodes   as $snode) {
								$section = $snode->nodeValue;
								
								$tokenArray = false;
								$xmlTokenCount = false;
								$text = $this->getBookChapterSection($doc, $xpath, $book, $chapt, $section);
								if($text){
									 $text  = trim($text);
									 $textEx = explode(" ", $text);
									 $xmlTokenCount = count($textEx);
									 
									 $tokenArray = array();
									 $i=0;
									 foreach($textEx as $XMLtoken){
										  $XMLtoken = trim($XMLtoken);
										  $tokenArray[$i] = array("index" => $i, "token" => $XMLtoken);
										  $i++;
									 }
									 
									 unset($textEx);
									 unset($text);
								}
								
								$divisions[] = array("book" => $book + 0,
															"chapt" => $chapt + 0 ,
															"section" => $section + 0,
															"XMLtokenCount" => $xmlTokenCount + 0,
															"xmlTokens" => $tokenArray);
						  }
					 }
				}
		  }
		  
		  return $divisions;
	 }
	 
	 
	 function getBookChapterSection($doc, $xpath, $book, $chapt, $section){
		  $text = false;
		  $query = "//book[@n='$book']/chapter[@n='$chapt']/section[@n='$section']";
		  $nodes = $xpath->query($query);
		  foreach ($nodes  as $node) {
				$text = $node->nodeValue;
				$text = $this->cleanXMLtext($text);
		  }
		  
		  return $text;
	 }
	 
	 
	 function loadParseXML(){
		  $doc = false;
		  $fileOK = file_exists(self::XMLfile);
		  if($fileOK){
				$doc = new DOMDocument();
				$doc->load(self::XMLfile);
		  }
		  return $doc;
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
