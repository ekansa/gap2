<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
ini_set("max_execution_time", "0");
//error_reporting(0);
//ini_set("memory_limit", "5120M");

class referencesController extends Zend_Controller_Action
{
    
    public function viewAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	$db_params = OpenContext_OCConfig::get_db_config();
	$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
	
	$requestParams =  $this->_request->getParams();
	$host = App_Config::getHost();
	
	$frontendOptions = array(
                'lifetime' => 72000, // cache lifetime, measured in seconds, 7200 = 2 hours
                'automatic_serialization' => true
        );
                
        $backendOptions = array(
            'cache_dir' => './bookdata/' // Directory where to put the cache files
        );
                
        $cache = Zend_Cache::factory('Core',
                             'File',
                             $frontendOptions,
                             $backendOptions);
	
	if(isset($requestParams["book"])){
	    $cache_id = "comp_".md5($requestParams["book"]);
	}
	
	
	$outJSON = false;
	
	if(!$cache_result = $cache->load($cache_id)) {
	    $reference = new Reference;
	    $reference->initialize($requestParams);
	    if($reference->get_refs()){
		$outJSON = Zend_Json::encode($reference->tmPlaces);
		$cache->save($outJSON, $cache_id);
	    }
	}
	else{
	    $outJSON = $cache_result;
	}
	
	
	if(!$outJSON){
	    $this->view->requestURI = $this->_request->getRequestUri(); 
	    return $this->render('404error');
	}else{
	    
	    $offset = 60 * 60 * 60 * 60;
	    $expire = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset). " GMT";
	    //$encoding = $this->check_compress_ok($_SERVER['HTTP_ACCEPT_ENCODING']);
	    $encoding = false;
	    header('Content-Type: application/json; charset=utf8');
	    echo $outJSON;
	
	    /*
	    if($encoding){
		header('Content-Type: application/json; charset=utf8');
		header ("Cache-Control:max-age=290304000, public");
		header ($expire);
		header('Content-Encoding: '.$encoding);
		print("\x1f\x8b\x08\x00\x00\x00\x00\x00");
		print gzcompress($outJSON, 9);
	    }
	    else{
		header('Content-Type: application/json; charset=utf8');
		echo $outJSON;
	    }
	    */
	}
	
	

    }//end function
    
    
    
    
    private function check_compress_ok($HTTP_ACCEPT_ENCODING){
    
        if( headers_sent() ){
            $encoding = false;
        }elseif( strpos($HTTP_ACCEPT_ENCODING, 'x-gzip') !== false ){
            $encoding = 'x-gzip';
        }elseif( strpos($HTTP_ACCEPT_ENCODING,'gzip') !== false ){
            $encoding = 'gzip';
        }else{
            $encoding = false;
        }
        return $encoding; 
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    public function jsonAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	$db_params = OpenContext_OCConfig::get_db_config();
	$db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
	$db->getConnection();
	
	$requestParams =  $this->_request->getParams();
	$host = App_Config::getHost();
	
	$reference = new Reference;
	$reference->initialize($requestParams);
	if($reference->get_refs()){
	    
	    header('Content-Type: application/json; charset=utf8');
	    //echo Zend_Json::encode($reference->placeTokens);
	    echo Zend_Json::encode($reference->tmPlaces);
	}
	else{
	    $this->view->requestURI = $this->_request->getRequestUri(); 
	    return $this->render('404error');
	}
	

    }//end function
    

   
}

