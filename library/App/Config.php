<?php

class App_Config {
	
	public static function getHost(){
		return "http://".$_SERVER['SERVER_NAME']; //get the host name.
	}
	
}//end class declaration

?>
