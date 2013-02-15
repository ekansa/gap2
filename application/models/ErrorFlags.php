<?php

/**
* gets data about places, give list of book references to places

*/
class ErrorFlags {
 
//database content 
public $id;
public $bookID;
public $bookPartID;
public $tokenID;
public $placeID;
public $status;
public $error_type;
public $user_email;
public $text_string;
public $note;
public $update;

public $db; //database connection object



//does what it sounds
function getFlagByID($id){
    
    $id = $this->security_check($id); 
    $this->startDB();
    $db = $this->db;
    
    $sql = "SELECT * FROM parser_error_flags WHERE id = $id LIMIT 1";
    
    $result =  $db->fetchAll($sql, 2);
    if($result){
	$this->id = $result[0]["id"];
	$this->bookID = $result[0]["bookID"];
	$this->bookPartID = $result[0]["bookPartID"];
	$this->tokenID = $result[0]["tokenID"];
	$this->placeID = $result[0]["placeID"];
	$this->status = $result[0]["status"];
	$this->error_type = $result[0]["error_type"];
	$this->user_email = $result[0]["user_email"];
	$this->text_string = $result[0]["tet_string"];
	$this->note = $result[0]["note"];
	$this->update = $result[0]["update"];
	
	return $result[0];
    }
    else{
	return false;
    }
    
}// end function



function createNew(){
    
    if(!$this->db){ 
	$this->startDB();
    }
    $db = $this->db;
    
    if(!$this->tokenID){
	$this->getTokenID($this->text_string);
    }
    
    $data = array("bookID" => $this->bookID,
		  "bookPartID" => $this->bookPartID,
		  "tokenID" => $this->tokenID,
		  "placeID" => $this->placeID,
		  "status" => $this->status,
		  "error_type" => $this->error_type,
		  "user_email" => $this->user_email,
		  "text_string" => $this->text_string,
		  "note" => $this->note);

    $db->insert("parser_error_flags", $data);
    
    return $this->getMaxID(); // return the highest ID
    
}// end function


//get the max, highest ID
function getMaxID(){
    
    if(!$this->db){ 
	$this->startDB();
    }
    $db = $this->db;
    
    $sql = "SELECT MAX(id) as idMax FROM parser_error_flags ";
    
    $result =  $db->fetchAll($sql, 2);
    if($result){
	if($result[0]["idMax"]>0){
	    $this->id = $result[0]["idMax"];
	}
	else{
	    $this->id = 0;
	}
	return $this->id;
    }
    else{
	return false;
    }
}



function getTokenID($token){
    
    if(!$this->db){ 
	$this->startDB();
    }
    $db = $this->db;
    
    $condition = " WHERE token = '$token' ";
    if($this->bookID){
	$condition .= " AND bookID = ".$this->bookID;
    }
    if($this->bookPartID){
	$condition .= " AND bookPartID = ".$this->bookPartID;
    }
    if($this->placeID){
	$condition .= " AND pleiadesID = ".$this->placeID;
    }
    
    $sql = "SELECT id FROM parser_final_tokens ".$condition;
    
    $result =  $db->fetchAll($sql, 2);
    if($result){
	$this->tokenID = $result[0]["id"];
	return $this->tokenID;
    }
    else{
	return false;
    }
}//end function




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
