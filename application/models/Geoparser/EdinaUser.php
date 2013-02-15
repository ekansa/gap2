<?php

/*
 Interacts with the Edina API
 to create and manage users. It's only developed
 enough to create a user. Additional functions will be needed to rename and delete users
*/

class Geoparser_EdinaUser {
 
//database content 
public $user;
public $password;

private $APIuri; //base uri API
private $requestHost; //requester Host


//prepare general API settings
function prepSettings(){
    $endiaPrep = new Geoparser_Edina;
    $this->APIuri = $endiaPrep->get_API_URI();
    $this->requestHost = $endiaPrep->get_requestHost();
    $this->user = $endiaPrep->get_user();
    $this->password = $endiaPrep->get_password();
}



//this function creates a new user, defined by the public variable $user
function createUser(){

    $userURI = $this->APIuri."/users/".$this->user;
    
    $client = new Zend_Http_Client($userURI);
    //$client->setHeaders('Host', $this->requestHost);
    $client->setHeaders('Accept', 'application/json');
    $client->setHeaders('AUTHORIZATION', $this->password);
    
    $response = $client->request("POST"); //send the request, using the POST method
    return $response;
}






}//end class

?>
