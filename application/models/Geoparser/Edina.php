<?php

/*

This class just stores and has some functions for getting basic settings
for interacting with the Edina API

The values for these settings are stored in the config.ini file.

*/
class Geoparser_Edina {
 
	 
	 function get_API_URI(){
		  $registry = Zend_Registry::getInstance();
		  return $registry->config->edina->config->APIuri;
    }
    
    function get_requestHost(){
		  $registry = Zend_Registry::getInstance();
		  return $registry->config->edina->config->requestHost;
    }
    
    function get_user(){
		  $registry = Zend_Registry::getInstance();
		  return $registry->config->edina->config->user;
    }

    function get_password(){
		  $registry = Zend_Registry::getInstance();
		  return $registry->config->edina->config->password;
    }
    
    function get_userKey(){
		  $registry = Zend_Registry::getInstance();
		  return $registry->config->edina->config->userKey;
    }

}//end class


?>
