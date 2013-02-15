<?php

/**
* Unified Schema For Places Text Results

*/
class Geoparser_PlacesText {
 
    const GeoUsername = "ekansa"; //geonames API name
    const APIsleep = .5; //
    const GeoNamesBaseURI = "http://www.geonames.org/";
    const pt_version = 1; //version number for places text standard output
    
    function SEASR_to_pt($seasrResult){
	
	$ptResult = array();
	$ptResult["version"] = self::pt_version;
	$ptResult["geo-parser"] = "SEASR";
	$tempEntities = array();
	foreach($seasrResult["geoNER"]["entities"] as $entity){
	
	    $textRef = array("offset" => $entity["offset"],
					      "offsetRef" => $entity["offsetRef"],
					      "sentenceId" => $entity["sentenceId"]
					      );
	
	    $tempKey = trim($entity["text"])."-".$entity["geo"]["lat"]."-".$entity["geo"]["lon"];
	    if(!array_key_exists($tempKey, $tempEntities)){
		$newEntity = array("text" => $entity["text"]);
		$newEntity["uri"] = $this->search_geoNameURI($entity["geo"]["lat"], $entity["geo"]["lon"], $entity["text"]);
		$newEntity["geo"] = array("lat" => $entity["geo"]["lat"], "lon" => $entity["geo"]["lon"]);
		$newEntity["textRefs"][] = $textRef;
		$tempEntities[$tempKey] = $newEntity;
	    }
	    else{
		$tempEntities[$tempKey]["textRefs"][] = $textRef; 
	    }
	}
	
	$entities = array();
	foreach($tempEntities as $key => $actEnt){
	    $entities[] = $actEnt;
	}
	
	$ptResult["entities"] = $entities;
	return $ptResult;
    }
    
    
    function Edina_to_pt($edinaResult){
	
	$ptResult = array();
	$ptResult["version"] = self::pt_version;
	$ptResult["geo-parser"] = "Edina";
	
	$sortedEnts = array();
	$edinaPlaces = $edinaResult[0]["places"];
	//echo print_r($edinaPlaces);
	
	foreach($edinaPlaces as $placeName => $placeArray){
	    
	    foreach($placeArray as $pArray){
		if(isset($pArray["id"])){
		    $numID = str_replace("rb", "", $pArray["id"]);
		    $sortedEnts[$numID] = $placeName;
		}
	    }
	}
	
	ksort($sortedEnts);
	
	$entities = array();
	foreach($sortedEnts as $tokenID => $placeName){
	    $entity = array();
	    $entity["text"] = $placeName;
	    
	    foreach($edinaPlaces[$placeName] as $pArray){
		if(isset($pArray["lat"]) && isset($pArray["long"]) && isset($pArray["gazref"]) ){
		    if(stristr($pArray["gazref"], "geonames") || $pArray["gazetteer"] == "GeoNames"){
			$lat = $pArray["lat"]+0;
			$lon = $pArray["long"] +0;
			$geoNamesID = str_replace("geonames:", "", $pArray["gazref"]);
			$geoNamesID = str_replace("unlock:", "", $geoNamesID );
			$entity["uri"] = $this->geoNameURI($geoNamesID);
			$entity["geo"] = array("lat" => $lat, "lon" => $lon);
			break;
		    }
		}
	    }
	    
	    $entity["textRefs"][] = array("tokenID" => $tokenID);
	    $entities[] = $entity;
	}
	
	$ptResult["entities"] = $entities;
	return $ptResult;
    }
    
    
    //constructs full Geonames URI
    function geoNameURI($geoNameID){
	return self::GeoNamesBaseURI.$geoNameID;
    }
    
    //uses the geoname API to find nearby places to a lat lon
    function search_geoNameURI($lat, $lon, $name = false){
	
	
	$output = false;
	sleep(self::APIsleep);
	$geoNamesURI = "http://api.geonames.org/findNearbyJSON?lat=".$lat."&lng=".$lon."&username=".self::GeoUsername;
	$jsonString = file_get_contents($geoNamesURI);
	
	$results = json_decode($jsonString, true);
	
	if($name != false){
	    $search = new ApproximateSearch;
	    $search->prepSearch($name, 2);
	}
	
	foreach($results["geonames"] as $rec){
	    
	    $matchFound = false;
	    $GeoNames = array();
	    $GeoNames = $rec["countryName"];
	    $GeoNames = $rec["toponymName"];
	    $GeoNames = $rec["name"];
	    $GeoNames = $rec["adminName1"];
	    
	    if($name != false){
		foreach($GeoNames as $string){
		    $matches = $search->search($string);
		    if(count($matches)>0){
			$matchFound = true;
		    }
		}
	    }
	    else{
		$matchFound = true;
	    }
	    
	    if($matchFound){
		$output = $this->geoNameURI($rec["geonameId"]);
		break;
	    }
	    
	}
	
	return $output;
    }



}//end class








?>
