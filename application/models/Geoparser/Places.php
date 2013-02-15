<?php

/**
* gets data about places, give list of book references to places

*/
class Geoparser_Places {
 
//database content 
public $placeURI;
public $pleiadesID;
public $placeBookRefs;
public $placeName;
public $placeLat;
public $placeLon;

public $browsePrevURI;
public $browsePrevName;
public $browseNextURI;
public $browseNextName;

public $similarPlaces;

public $OCdata; //open context data


public $timeMapOutput; //output for TimeMap API

public $db; //database connection object


const pleiadesBase = "http://pleiades.stoa.org/places/"; //base uri for pleides places

function initialize($requestParams){
    
    $this->pleiadesID = false;
    $this->placeBookRefs = false;
    $this->placeName = false;
    $this->placeLat = false;
    $this->placeLon = false;
    $this->OCdata = false;
    
    if(isset($requestParams['uri'])){
	$this->placeURI = $this->security_check($requestParams['uri']);
        if(stristr($this->placeURI, self::pleiadesBase)){
            $this->pleiadesID = str_replace(self::pleiadesBase, "", $this->placeURI);
            $this->pleiadesID = preg_replace('/[^0-9]/', '', $this->pleiadesID);
        }
    }
    else{
	$this->placeURI = false;
    }
    
}


function get_books(){
	
        $pleiadesID = $this->pleiadesID;
        
	$this->startDB();
	$db = $this->db;
	
	if($pleiadesID !=false){
	    
            /*
            $sql = "SELECT parser_output_all.bookID, count(parser_output_all.id) as tokenCount,
                    parser_books.uri, parser_books.title, parser_books.authors, parser_books.date, parser_books.metadata,
                    parser_books.created, parser_books.updated
                    FROM parser_output_all
                    LEFT JOIN parser_books ON parser_books.bookID = parser_output_all.bookID
                    WHERE parser_output_all.pleiadesID = '$pleiadesID'
                    GROUP BY parser_output_all.bookID
                    ORDER BY count(parser_output_all.id) DESC
            ";
            
            SELECT parser_final_tokens.bookID, count(parser_final_tokens.id) as tokenCount,
                    parser_books.uri, parser_books.title, parser_books.authors, parser_books.date, parser_books.metadata,
                    parser_books.created, parser_books.updated
                    FROM parser_final_tokens
                    LEFT JOIN parser_books ON parser_books.id = parser_final_tokens.bookID
                    WHERE parser_final_tokens.pleiadesID = 157894
                    GROUP BY parser_final_tokens.bookID
                    ORDER BY count(parser_final_tokens.id) DESC
            
            
            
            157894
            */
            
            $sql = "SELECT parser_final_tokens.bookID as id, count(parser_final_tokens.id) as tokenCount,
                    parser_books.uri, parser_books.title, parser_books.authors, parser_books.date,
                    parser_books.created, parser_books.updated
                    FROM parser_final_tokens
                    LEFT JOIN parser_books ON parser_books.id = parser_final_tokens.bookID
                    WHERE parser_final_tokens.pleiadesID = '$pleiadesID'
                    GROUP BY parser_final_tokens.bookID
                    ORDER BY count(parser_final_tokens.id) DESC
            ";
            
            
            $result = $db->fetchAll($sql, 2);
            if($result){
                $this->placeBookRefs = $result;
                $this->placeGeo = $this->getPleiadesLocation($pleiadesID);
                return true;
            }
            else{
                return false;
            }
        }
        else{
            return false;
        }
	
}//end function





function getPleiadesLocation($pleiadesID){
    $db = $this->db;
    $sql = "SELECT pleiades_places.title AS name, pleiades_places.reprLon AS lon, pleiades_places.reprLat AS lat
    FROM pleiades_places
    WHERE pleiades_places.id = '$pleiadesID'
    LIMIT 1;
    ";
    
    $result = $db->fetchAll($sql, 2);
    if($result){
        $this->placeName = $result[0]["name"];
        $this->placeLat = $result[0]["lat"];
        $this->placeLon = $result[0]["lon"];
        return true;
    }
    else{
        return false;
    }
    
}


function getBrowsePlaces(){
    
    $pleiadesID = $this->pleiadesID;
    
    $db = $this->db;
    $sql = "SELECT parser_final_tokens.pleiadesID, pleiades_plus.name
            FROM parser_final_tokens
            LEFT JOIN pleiades_plus ON pleiades_plus.pl_id = parser_final_tokens.pleiadesID
            WHERE parser_final_tokens.pleiadesID < '$pleiadesID' AND parser_final_tokens.pleiadesID != 0
            ORDER BY parser_final_tokens.pleiadesID DESC
            LIMIT 1
            ";
            
            
    $result = $db->fetchAll($sql, 2);
    if($result){
        $this->browsePrevURI = self::pleiadesBase.$result[0]["pleiadesID"];
        $this->browsePrevName = $result[0]["name"];
    }
    else{
        $this->browsePrevURI = false;
        $this->browsePrevName = false;
    }
    
    
    $sql = "SELECT parser_final_tokens.pleiadesID, pleiades_plus.name
            FROM parser_final_tokens
            LEFT JOIN pleiades_plus ON pleiades_plus.pl_id = parser_final_tokens.pleiadesID
            WHERE parser_final_tokens.pleiadesID > '$pleiadesID'
            ORDER BY parser_final_tokens.pleiadesID
            LIMIT 1
            ";
            
            
    $result = $db->fetchAll($sql, 2);
    if($result){
        $this->browseNextURI = self::pleiadesBase.$result[0]["pleiadesID"];
        $this->browseNextName = $result[0]["name"];
    }
    else{
        $this->browseNextURI = false;
        $this->browseNextName = false;
    }
    
}


function getSimilarPlaces(){
    
    $pleiadesID = $this->pleiadesID;
    
    $db = $this->db;
    $sql = "SELECT pleiades_plus.pl_id AS pleiadesID, pleiades_plus.name
            FROM  pleiades_plus
            JOIN parser_final_tokens ON pleiades_plus.pl_id = parser_final_tokens.pleiadesID
            WHERE pleiades_plus.pl_id != '$pleiadesID'
            AND pleiades_plus.name LIKE '".$this->placeName."'
            GROUP BY pleiades_plus.pl_id
            ORDER BY pleiades_plus.pl_id
            ";
            
           //echo  $sql;
            
    $result = $db->fetchAll($sql, 2);
    //$result =  false;
    if($result){
        $output = array();
        foreach($result as $row){
            $actOut = array();
            $actOut["uri"] = self::pleiadesBase.$row["pleiadesID"];
            $actOut["name"] = $row["name"];
            $output[] = $actOut;
        }
        $this->similarPlaces = $output;
    }
    else{
        $this->similarPlaces = false;
    }

}



//get related data from open context in JSON
function get_related_OC(){
    
    $ocURI = "http://opencontext.org/sets/.json?targURI=".urlencode($this->placeURI);
    
    $frontendOptions = array(
                    'lifetime' => 72000, // cache lifetime, measured in seconds, 7200 = 2 hours
                    'automatic_serialization' => true
                    );
                    
    $backendOptions = array(
            'cache_dir' => './cache/' // Directory where to put the cache files
            );
            
    $cache = Zend_Cache::factory('Core',
                         'File',
                         $frontendOptions,
                         $backendOptions);
    
    //$cache_id = md5($request_hasher); not needed, id is unique
    $cache_id = md5($ocURI);
    if(!$cache_result = $cache->load($cache_id)) {
        @$jsonData = file_get_contents($ocURI);
        $cache->save($jsonData, $cache_id);
    }
    else{
        $jsonData = $cache_result;
    }
    
    @$ocObject = Zend_Json::decode($jsonData);
    if($ocObject != false){
        if($ocObject["numFound"] == 0){
            $ocObject = false;
        }
    }
    
    $this->OCdata = $ocObject;

}//end function




function placeTimeMapAPI($pleiadesID = false){
    
    if($pleiadesID == false){
        $pleiadesID = $this->pleiadesID;
    }
    else{
        $this->pleiadesID = $pleiadesID;
    }
    
    $pleiadesID = $this->security_check($pleiadesID);
    $timeMapOutput = false;
        
    $this->startDB();
    $db = $this->db;
	
    if($pleiadesID != false){
    
        $sql = "SELECT name, pleiades_centroid_x as lat, pleiades_centroid_y as lon
        FROM pleiades_plus
        WHERE pl_id = '$pleiadesID'
        LIMIT 1;
        ";
        
        $sql = "SELECT title AS name, reprLat as lat, reprLon as lon
        FROM pleiades_places
        WHERE id = '$pleiadesID'
        LIMIT 1;
        ";
        
        //echo $sql;
        $result = $db->fetchAll($sql, 2);
        if($result){
            $this->placeName = $result[0]["name"];
            $this->placeLat = $result[0]["lat"]+0;
            $this->placeLon = $result[0]["lon"]+0;
            
            $timeMapOutput = array("ll" => array(($result[0]["lat"]+0), ($result[0]["lon"]+0)),
                                   "uri" => (self::pleiadesBase.$pleiadesID),
                                   "id" => $pleiadesID+0,
                                   "title" => $result[0]["name"]
                                   );
            
        }
    }
    
     $this->timeMapOutput = $timeMapOutput;
    
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
