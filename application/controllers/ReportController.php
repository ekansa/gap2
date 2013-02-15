<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
// set maximum execution time to no limit
ini_set("max_execution_time", "0");
//error_reporting(0);
ini_set("memory_limit", "5120M");
//@ini_set('display_errors', 0);

class ReportController extends Zend_Controller_Action
{   
    public function init() {
        /* Initialize action controller here */
    }
      
    public function indexAction()
    {
	$this->_helper->viewRenderer->setNoRender();
	echo "Nothing to see here!";
    }
    
    public function testAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	echo "Test";
	echo "
	<form method='post' action='issue' >
	    <br/>Message <input name='ctf-problem' type='text' />
	    <br/>ID <input name='place-id' type='text' />
	    <br/><input type='submit' />
	</form>
	";
	
	
    }
    
    public function issueAction(){
	$this->_helper->viewRenderer->setNoRender();
	$postParams =  $this->_request->getPost();
	
	//$postParams =  $this->_request->getParams();
	
	$db_params = OpenContext_OCConfig::get_db_config();
	$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
	
	if($postParams){
	    
	    $success = true;
	    $messageTxt = "";
	    
	    if(isset($postParams["place-id"])){
		$pleiadesID = $postParams["place-id"];
		$placeObj = new Places;
		$placeObj->placeTimeMapAPI($pleiadesID);
		$aboutPlace = false;
		if(strlen($placeObj->placeName)>1){
		    $aboutPlace = $placeObj->placeName;
		}
		
		if(!$aboutPlace){
		    $messageTxt = "You have noted an issue about Pleiades ID: $pleiadesID. ";
		}
		else{
		    $messageTxt = "You have noted an issue about '$aboutPlace' (Pleiades ID: $pleiadesID). ";
		}
		
	    }
	    
	    if(isset($postParams["ctf-problem"])){
		if(strlen($postParams["ctf-problem"])>2){
		    $problemReport = $postParams["ctf-problem"];
		    $lenProblem = strlen($problemReport);
		    if($lenProblem > 20){
			$shortProblem = substr($problemReport, 0, 20);
		    }
		    else{
			$shortProblem = $problemReport;
		    }
		    $messageTxt .= " The issue you reported: '".$shortProblem."...' has been recorded in our tracking system. Thank you for helping us improve the quality of these data!";
		}
		else{
		    $success = false;
		    $messageTxt .= " However, please throw us a bone, and include a brief description of the problem.";
		}
	    }
	    else{
		$success = false;
		$messageTxt .= "Please throw us a bone, and include a brief description of the problem.";
	    }
	    
	    
	    
	    
	    $postJSON = Zend_Json::encode($postParams);
	    $data = array("issue" => $postJSON);
	    
	    $db->insert("user_reports", $data);
	    
	    $message = array("success" => $success, "message" => $messageTxt);
	    $output = Zend_Json::encode($message);
	    
	    if(!$success){
		header("HTTP/1.0 403 Forbidden");
	    }
	    else{
		
		
		if(isset($requestParams["callback"])){
		    header('Content-Type: application/javascript; charset=utf8');
		    $output = $requestParams["callback"]."(".$output.");";
		    echo $output;
		}
		else{
		    header('Content-Type: application/json; charset=utf8');
		    header("Access-Control-Allow-Origin: *");
		    echo $output; //outputs JSON of a given book's word cloud
		}
	    }
	    
	}
	else{
	    
	    $message = array("success" => false, "message" => "Please POST data to report issues");
	    $output = Zend_Json::encode($message);
	    
	    header("HTTP/1.0 405 Method Not Allowed");
	    header("Allow: POST");
	    
	    if(isset($requestParams["callback"])){
		header('Content-Type: application/javascript; charset=utf8');
		$output = $requestParams["callback"]."(".$output.");";
		echo $output;
	    }
	    else{
		header('Content-Type: application/json; charset=utf8');
		header("Access-Control-Allow-Origin: *");
		echo $output; //outputs JSON of a given book's word cloud
	    }

	}
	
    }
 
 
 
}//end class

