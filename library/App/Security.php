<?php

/*
 
 Some functions to at least do a little bit to help with database security / SQL injections

*/
class App_Security {
 
 
    //a little check to avoid some SQL inject attacks
	 public static function inputCheck($input){
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
