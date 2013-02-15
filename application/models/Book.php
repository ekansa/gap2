<?php

/**
* gets data about a book

*/
//ini_set("memory_limit", "512M");

class Book {
 
public $book;
public $page;

public $pageOutput;	//output for JSON api, page of a book for JSON
public $wordSummary;	//output for JSON api, book word/tag cloud
public $bookPlaces;	//output for JSON api, all places in the book
public $JSONpageArray; 	//output for JSON api, for all pages (with places) in the book
public $JSONplacesArray; //output for JSON api, for all places in the book

public $pageOffset; //page offset from metadata


public $bookTitle;
public $bookPageOffset;
public $bookAuthors;
public $bookDate;
public $bookMeta;
public $bookURI;
public $bookMaxPage;
public $repositoryID;

public $allPlaces;



public $db; //database connection object


const pleiadesBase = "http://pleiades.stoa.org/places/"; //base uri for pleides places

function initialize($book){
    $this->book = $this->security_check($book);
}


function get_book_meta($db = false){
    
    if(!$db){
		  $this->startDB();
		  $db = $this->db;
    }
    
    $sql = "SELECT * FROM parser_books WHERE id = '".$this->book."' LIMIT 1;";
    
    $result = $db->fetchAll($sql, 2);
    if($result){ 
        $this->bookTitle = $result[0]["title"];
        $this->bookPageOffset = $result[0]["googPageOffset"];
        $this->bookAuthors = $result[0]["authors"];
        $this->bookDate = $result[0]["date"];
        $this->bookMeta = $result[0]["metadata"];
        $this->bookURI = $result[0]["uri"];
		  $this->pageOffset = $result[0]["googPageOffset"];
		  $this->repositoryID = $result[0]["repositoryID"];
		  
		  if($result[0]["maxPage"] == 0){
				$this->updateBookMax();
		  }
		  else{
				$this->bookMaxPage = $result[0]["maxPage"];
		  }
		  
		  return true;
    }
    else{
        $this->bookTitle = false;
        $this->bookPageOffset = false;
        $this->bookAuthors = false;
        $this->bookDate = false;
        $this->bookMeta = false;
        $this->bookURI = false;
		  return false;
    }
    
}//end function

function updateBookMax(){
    
    $db = $this->db;
    
    $sql = "SELECT MAX(parser_output_all.bookPartID) as pageMax
	    FROM parser_output_all
	    WHERE parser_output_all.bookID = '".$this->book."'
	    GROUP BY parser_output_all.bookID
	    ";
    
    $result = $db->fetchAll($sql, 2);
    if($result){
	$pageMax = $result[0]["pageMax"];
	$data = array("maxPage" => $pageMax );
	$where = " bookID = '".$this->book."' ";
	$db->update("parser_books", $data, $where);
	$this->bookMaxPage = $pageMax;
    }
    
}


function getAllPlaces(){
    
    $db = $this->db;
    
    $sql = "SELECT pleiades_plus.name, parser_final_tokens.pleiadesID, COUNT(parser_final_tokens.id) AS placeCount,
	    pleiades_plus.pleiades_centroid_x AS lon,
            pleiades_plus.pleiades_centroid_y AS lat
	    FROM parser_final_tokens
	    JOIN pleiades_plus ON pleiades_plus.pl_id = parser_final_tokens.pleiadesID
	    WHERE parser_final_tokens.bookID = '".$this->book."'
	    AND parser_final_tokens.pleiadesID != 0
	    GROUP BY parser_final_tokens.pleiadesID
	    ORDER BY COUNT(parser_final_tokens.id) DESC
	    ";
    
    $sql = "SELECT DISTINCT pleiades_places.title AS name, parser_final_tokens.pleiadesID,
	    parser_final_tokens.pleiadesID, COUNT(parser_final_tokens.id) AS placeCount,
	    pleiades_places.reprLon AS lon,
            pleiades_places.reprLat AS lat
	    FROM parser_final_tokens
	    JOIN pleiades_places ON pleiades_places.id = parser_final_tokens.pleiadesID
	    WHERE parser_final_tokens.bookID = '".$this->book."'
	    AND parser_final_tokens.pleiadesID != 0
	    GROUP BY parser_final_tokens.pleiadesID
	    ORDER BY COUNT(parser_final_tokens.id) DESC
	    ";
	    
	    
    $result = $db->fetchAll($sql, 2);
    if($result){
	$this->allPlaces = $result;
    }
    else{
	$this->allPlaces = false;
    }
}




function getAllPlacesJSON(){
    
    $db = $this->db;
    
    $sql = "SELECT DISTINCT pleiades_plus.name, parser_final_tokens.pleiadesID,
	    pleiades_plus.pleiades_centroid_x AS lon,
            pleiades_plus.pleiades_centroid_y AS lat
	    FROM parser_final_tokens
	    JOIN pleiades_plus ON pleiades_plus.pl_id = parser_final_tokens.pleiadesID
	    WHERE parser_final_tokens.bookID = '".$this->book."'
	    AND parser_final_tokens.pleiadesID != 0
	    AND (pleiades_plus.pleiades_centroid_x + pleiades_plus.pleiades_centroid_y)>0
	    ";
	    
    $sql = "SELECT DISTINCT pleiades_places.title AS name, parser_final_tokens.pleiadesID,
	    pleiades_places.reprLon AS lon,
            pleiades_places.reprLat AS lat
	    FROM parser_final_tokens
	    JOIN pleiades_places ON pleiades_places.id = parser_final_tokens.pleiadesID
	    WHERE parser_final_tokens.bookID = '".$this->book."'
	    AND parser_final_tokens.pleiadesID != 0
	    AND (pleiades_places.reprLon + pleiades_places.reprLat)>0
	    ";
	
    $result = $db->fetchAll($sql, 2);
    if($result){
	$finalPlaceArray = array();
	$IndexPleiadesID = array();
	
	$placeNumber = 1;
	
	foreach($result as $row){
	    
	    $placeName = $row["name"];
	    $lat = $row["lat"] + 0;
	    $lon = $row["lon"] + 0;
	    $pleiadesID = $row["pleiadesID"]+0;
	    
	    $finalPlaceArray[] = array("id" => $pleiadesID, "title" => $placeName, "ll" => array($lat,$lon) );
	    /*
	    if(!array_key_exists($pleiadesID, $IndexPleiadesID)){
		//$finalPlaceArray[] = array("id" => $placeNumber, "title" => $placeName, "pleiades" => $pleiadesID, "ll" => array($lat,$lon) );
		$finalPlaceArray[] = array("id" => $pleiadesID, "title" => $placeName, "ll" => array($lat,$lon) );
		$IndexPleiadesID[$pleiadesID] = $placeNumber;
		$placeNumber++;
	    }
	    */
	    
	}
	
	unset($result);
	
	$this->JSONplacesArray = $finalPlaceArray;
	unset($finalPlaceArray);
    }    
    
    
    $sql = "SELECT parser_final_tokens.pleiadesID,
	    parser_final_tokens.bookPartID
	    FROM parser_final_tokens
	    WHERE parser_final_tokens.bookID = '".$this->book."'
	    AND parser_final_tokens.pleiadesID != 0
	    ORDER BY parser_final_tokens.bookPartID
	    ";
    
    $result = $db->fetchAll($sql, 2);
    if($result){
	
	$pagesBegin = array();
	foreach($result as $row){
	    
	    $pleiadesID = $row["pleiadesID"];
	    $pageNum = $row["bookPartID"];
	    $pageNum = $this->convertPageOffset($pageNum);
	    
	    $pagesBegin[$pageNum][] = $pleiadesID + 0;
	    /*
	    if(array_key_exists($pleiadesID, $IndexPleiadesID)){
		$pagesBegin[$pageNum][] = $IndexPleiadesID[$pleiadesID];
	    }
	    */
	    
	}
	unset($result);
	
	
	$finalPageArray = array();
	foreach($pagesBegin as $pageKey => $pleiadesIDs){
	  $finalPageArray[] = array("id" => $pageKey, "places" => $pleiadesIDs);
	}
	
	
	unset($pagesBegin);
	$this->JSONpageArray = $finalPageArray;
	unset($finalPageArray);
    }
    
}





function getBookPageData($db = false){
    
    if(!$db){
	$this->startDB();
	$db = $this->db;
    }
    
    $pageOutput = array("text"=>false);
    $book = $this->security_check($this->book);
    $page = $this->security_check($this->page);
    $rawPage = $page ;
    $page = $this->convertPageToRaw($page);
    
    if(is_numeric($book) && is_numeric($page)){
	$sql = "SELECT token, pleiadesID
	    FROM parser_final_tokens
	    WHERE parser_final_tokens.bookID = $book
	    AND 	parser_final_tokens.bookPartID = $page
	    ORDER BY parser_final_tokens.id
	    LIMIT 1000
	    ;";
	
	$sql = "SELECT token, pleiadesID
	    FROM parser_final_tokens
	    WHERE parser_final_tokens.bookID = $book
	    AND parser_final_tokens.bookPartID = $page
	    LIMIT 1000
	    ;";
	    
	    
	 $result = $db->fetchAll($sql, 2);
    }
    else{
	$result = false;
    }
    
    if($result){
	$output = "";
        $previousToken = "";
	
	foreach($result as $row){
	    $token = $row["token"]; 
	    //$tokenID = $row["tokenID"];
            $pleiadesID = $row["pleiadesID"];
                    
	    if($pleiadesID != 0){
		$pleiadesURI = "http://pleiades.stoa.org/places/".$pleiadesID;
		$token = "<span class=\"place\" data-place-id=\"$pleiadesID\" >".$token."</span>";
	    }
                    
	    $token = $this->formatSpaces($previousToken, $token);
	    if($token == "- "){
		$token = "";
	    }
                    
	    $output .= $token;
	}
	
	unset($result);
	
	$output = str_replace(" . ", ". ", $output);
	$output = str_replace(" , ", ", ", $output);
	
	$pageOutput["text"] = $output;
	
    }
    elseif(stristr($this->bookURI, "google")){
	
	//get the page from a text file.
	
	$repositoryID = $this->repositoryID;
	$repositoryFile = "txt_".$repositoryID;
	
	//00000402
	$txtFileNumberString = $page;
	while(strlen($txtFileNumberString)<8){
	    $txtFileNumberString = "0".$txtFileNumberString;
	}
	$repositoryFile = $repositoryFile."/".$txtFileNumberString.".txt";
	
	$textString = $this->get_textFile($repositoryFile);
	if($textString != false){
	    $pageOutput["text"] = $textString;
	}
	
    }
    
    
    
    $imageURI = false;
    if(stristr($this->bookURI, "google")){
	    
	$sigData = $this->get_sig($book, $rawPage);
	if($sigData != false){
	    $imageURI = "http://books.google.com/books?id=".$sigData["googleID"]."&pg=PA".$rawPage."&img=1&zoom=3&hl=en&sig=".trim($sigData["sig"])."&ci=0%2C0%2C1000%2C2000&edge=0";
	}
    }
    $pageOutput["image"] = $imageURI;
    
    $this->pageOutput = $pageOutput;
}



function get_textFile($repositoryFile){
	
	$sFilename  = './booktexts/'.$repositoryFile;
	
	$fp = fopen($sFilename, 'r');
	
	if (!file_exists($sFilename)){
	    return false;
	}
	else{
	    $rHandle = fopen($sFilename, 'r');
	    if (!$rHandle){
		return false;
	    }
	    else{
		
		$sData = '';
		while(!feof($rHandle))
		    $sData .= fread($rHandle, filesize($sFilename));
		fclose($rHandle);
		unset($rHandle);
		return $sData;
	    } 
	}
        
    }





function get_sig($book, $page){
    $db = $this->db;
    $sql = "SELECT * FROM sigs WHERE bookID = '".$book."' and page = ".$page." LIMIT 1;";
    
    $result = $db->fetchAll($sql, 2);
    if($result){
        return $result[0];
    }
    else{
        return false;
    }
    
}




function formatSpaces($previousToken, $token){
	
	//$punctToken = mb_ereg_match("/[^!-~]/", $token);
	//$prevquoteToken = mb_ereg_match(".*'", $previousToken);
	
	
	$addspace = false;
	$alphaText =  mb_ereg_match("[[:alnum:]]", $token);
        if(substr($token, 0, 5) == "<span"){
            $alphaText = true;
        }

	if($alphaText){
		$addspace = true;
	}
	
	if(mb_stristr($token, "(")){
		$token = str_replace("( ", "(", $token);
		$addspace = true;
	}
	
	$previousLen = mb_strlen($previousToken);
	$lastPrevious = mb_substr($previousToken, $previousLen-1, 1);
	//$token = "[".$lastPrevious."]".$token;
	
	if($lastPrevious == " "){
		$addspace = false;
	}
	elseif($lastPrevious == "("){
		$addspace = false;
	}
	elseif($lastPrevious == ":"){
		$addspace = true;
	}
	
	if(strlen($token) == 1){
	    if(ord($token)>= 66 && ord($token)<= 90 && $token != "I"){
		$addspace = false;
	    }
	}
	
	
	$prevAlpha =  mb_ereg_match("[[:alnum:]]", $previousToken);
	if($previousLen == 1 && !$prevAlpha){
		//$addspace = false;
	}
	
	if($prevQuote){
		$addspace = false;
	}
	
	if($addspace){
		return " ".$token;
	}
	else{
		return $token;
	}
	
}//end function



//get book word summary
function getBookWordData($db = false){
    
    if(!$db){
	$this->startDB();
	$db = $this->db;
    }
    
    $wordSummary = array();
    $book = $this->security_check($this->book);
    
    if(is_numeric($book)){
	$sql = "SELECT count(parser_final_tokens.id) as tokenCount, parser_final_tokens.token as word
	    FROM parser_final_tokens
	    LEFT JOIN gb_stop_words ON  LCASE(parser_final_tokens.token) = gb_stop_words.word
	    WHERE parser_final_tokens.bookID = $book
	    AND gb_stop_words.id IS NULL
	    AND CHAR_LENGTH(parser_final_tokens.token) > 2
	    GROUP BY LCASE(parser_final_tokens.token)
	    ORDER BY count(parser_final_tokens.id) DESC
	    ;";
	    
	 $result = $db->fetchAll($sql, 2);
    }
    else{
	$result = false;
    }
    
    if($result){
	
	foreach($result as $row){
	    $token = $row["word"];
	    $tokenCount = $row["tokenCount"] + 0;
	    $cleanToken = $this->charClean($token);
            
	    if(!$this->isStopWord($cleanToken, $db ) && strlen($cleanToken)>=2 && $tokenCount >= 15){
		
		$wordArray = array($cleanToken, $tokenCount);
		$wordSummary[] = $wordArray;
	    }
	}
	
	unset($result);
	
    }
    
    $this->wordSummary = $wordSummary;
}


function charClean($token){
    
    $token =  mb_ereg_replace("\W", "", $token);
    $token =  mb_ereg_replace("\d", "", $token);
    $token =  mb_ereg_replace("\s", "", $token);
    
    $lcToken = strtolower($token);
    $badArray = array("ing", "tion", "nians", "tions", "nings", "ans", "ibid", "pre", "fon", "cf");
    
    if(in_array($lcToken, $badArray)){
	$token = false;
    }
    
    
    return $token;
}





function isStopWord($token, $db){
    
    $token = strtolower($token);
    
    $sql = "SELECT * FROM gb_stop_words WHERE word LIKE '$token' LIMIT 1; ";
    
    $result = $db->fetchAll($sql, 2);
    if($result){
	unset($result);
	return true;
    }
    else{
	return false;
    }
    
}





function convertPageOffset($rawPage){
    
    $newPage = $rawPage;
    if($this->pageOffset >0 && $this->book != 0){
	$newPage = $rawPage - ($this->pageOffset) + 1;
	
	if($newPage <= 0){
	    //$newPage = $rawPage;
	}
	
    }
    
    return $newPage;
}


function convertPageToRaw($requestPage){
    
    $newPage = $requestPage;
    if($this->pageOffset >0 && $this->book != 0){
	$newPage = $requestPage + ($this->pageOffset) - 1;
    }
    
    return $newPage;
}













function getOtherBooks(){
    
}




//startup the db
function startDB(){
	$db = Zend_Registry::get('db');
	$db->getConnection();
	$this->setUTFconnection($db);
	$this->db = $db;
}//end function

//make sure character encoding is set, so greek characters work
function setUTFconnection($db){
	$sql = "SET collation_connection = utf8_unicode_ci;";
	$db->query($sql, 2);
	$sql = "SET NAMES utf8;";
	$db->query($sql, 2);
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
