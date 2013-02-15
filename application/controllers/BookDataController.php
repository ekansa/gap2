<?php
/** Zend_Controller_Action */
require_once 'Zend/Controller/Action.php';
//ini_set("max_execution_time", "0");
//error_reporting(0);
error_reporting(0);
ini_set("memory_limit", "256M");

class booksController extends Zend_Controller_Action
{
    
    public function viewAction(){
	
	$requestParams =  $this->_request->getParams();
	$host = App_Config::getHost();
	$bookFound = true;
	
	if(isset($requestParams['book'])){
	    
	    $bookObj = new Book;
	    $bookObj->initialize($requestParams['book']);
	    $bookObj->get_book_meta();
	    if(!$bookObj->bookURI){
		$bookFound = false;
	    }
	    else{
		$this->view->book = $bookObj;
	    }
	}
	else{
	    $bookFound = false;
	}
	
	if(!$bookFound){
	    $this->view->requestURI = $this->_request->getRequestUri(); 
	    return $this->render('404error');
	}
	
    }//end function
    
    
    public function summaryAction(){
	
	
	$requestParams =  $this->_request->getParams();
	$host = App_Config::getHost();
	$bookFound = true;
	
	if(isset($requestParams['book'])){
	    
	    $bookObj = new Book;
	    $bookObj->initialize($requestParams['book']);
	    $bookObj->get_book_meta();
	    $bookObj->getAllPlaces();
	    
	    if(!$bookObj->bookURI){
		$bookFound = false;
	    }
	    else{
		$this->view->book = $bookObj;
	    }
	}
	else{
	    $bookFound = false;
	}
	
	if(!$bookFound){
	    $this->view->requestURI = $this->_request->getRequestUri(); 
	    return $this->render('404error');
	}
	
	

    }//end function
    
    
    
    
    /*
    get JSON data on a book, as specified by Nick at
    https://github.com/nrabinowitz/gapvis/tree/master/stub_api
    */
    public function jsonAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	$requestParams =  $this->_request->getParams();
	$host = App_Config::getHost();
	
	$output = array("found" => $requestParams);
	
	if(isset($requestParams['book'])){
	    
	    $bookObj = new Book;
	    $bookObj->initialize($requestParams['book']);
	    $bookObj->get_book_meta();
	    $bookObj->getAllPlaces();
	    
	    if(!$bookObj->bookURI){
		//no book found with this ID
		$this->view->requestURI = $this->_request->getRequestUri(); 
		return $this->render('404error');
	    }
	    else{
		
	    }
	}
	else{
	    $books = new Books;
	    $books->get_all_books();
	    $output = $books->bookData;
	    unset($books);
	}
	
	header('Content-Type: application/json; charset=utf8');
	header("Access-Control-Allow-Origin: *");
	echo Zend_Json::encode($output);
	
    }//end function
    
    
    //JSON for text from a given page
    public function bookpagejsonAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	$requestParams =  $this->_request->getParams();
	$host = App_Config::getHost();
	
	$output = false;
	
	if(isset($requestParams['book']) && isset($requestParams['page'])){
	    
	    $output = array();
	    $output["book"] = $requestParams['book'];
	    $output["page"] =  $requestParams['page'];
	    
	    $bookObj = new Book;
	    $bookObj->initialize($requestParams['book']);
	    $bookObj->book = $requestParams['book'];
	    $bookObj->page = $requestParams['page'];
	    $bookObj->get_book_meta();
	    $bookObj->getBookPageData();
	    $output = $bookObj->pageOutput;
	}
	
	//$output["memory"] = memory_get_usage(true);
	header('Content-Type: application/json; charset=utf8');
	header("Access-Control-Allow-Origin: *");
	echo Zend_Json::encode($output);
	
    }//end function
    
    
    //JSON for generating a tag cloud of words
    public function bookwordsjsonAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	$requestParams =  $this->_request->getParams();
	$host = App_Config::getHost();
	
	$output = false;
	
	if(isset($requestParams['book'])){
	    
	    $bookObj = new Book;
	    $bookObj->initialize($requestParams['book']);
	    $bookObj->book = $requestParams['book'];
	    $bookObj->get_book_meta();
	    $bookObj->getBookWordData();
	    
	    $output = $bookObj->wordSummary;
	
	}
	
	//$output["memory"] = memory_get_usage(true);
	header('Content-Type: application/json; charset=utf8');
	header("Access-Control-Allow-Origin: *");
	echo Zend_Json::encode($output);
	
    }//end function
    
    
    
     
    
    
    public function allAction(){
	
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
	
	$requestURI = $this->_request->getRequestUri(); // for testing
	$requestURI = str_replace("/", "_", $requestURI);
	$requestURI = str_replace(".", "_", $requestURI);
	
	if(isset($requestParams["callback"])){
	    $requestURI = str_replace("?", "", $requestURI);
	    $requestURI = str_replace("callback=".$requestParams["callback"], "", $requestURI);
	}
	
	
	
	$cache_id = $requestURI;
	
	if(isset($requestParams['avar'])){
	    $aVar = $requestParams['avar'];
	    if($aVar == ".json"){
		
		$books = new Books;
		$books->get_all_books();
		$outputArray = $books->bookData;
		unset($books);
		$output = Zend_Json::encode($outputArray);
		$this->_helper->viewRenderer->setNoRender();
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
	    elseif(is_numeric($aVar)){
		
		$book = $aVar;
		
		if(isset($requestParams['bvar'])){
		    
		   if($requestParams['bvar'] == "words.json"){
			
			if(!$cache_result = $cache->load($cache_id)) {
			    $bookObj = new Book;
			    $bookObj->initialize($book);
			    $bookObj->book = $book;
			    $bookObj->get_book_meta();
			    $bookObj->getBookWordData();
			    
			    $outputArray = $bookObj->wordSummary;
			    $output = Zend_Json::encode($outputArray);
			    $cache->save($output, $cache_id);
			}
			else{
			    $output = $cache_result;
			}
			
			$this->_helper->viewRenderer->setNoRender();
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
		    elseif($requestParams['bvar'] == "page"){
			
			$failPageRequest = true;
			
			if(isset($requestParams['cvar'] )){
			  if(stristr($requestParams['cvar'], ".json")){
			    $page = str_replace(".json", "", $requestParams['cvar']);
			    if(is_numeric($page)){
				$failPageRequest = false;
				
				$bookObj = new Book;
				$bookObj->initialize($book);
				$bookObj->book = $book;
				$bookObj->page = $page;
				$bookObj->get_book_meta();
				$bookObj->getBookPageData();
				$outputArray = $bookObj->pageOutput;
				$output = Zend_Json::encode($outputArray);
				
				$this->_helper->viewRenderer->setNoRender();
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
			}
			
			
			if($failPageRequest){
			    $this->view->requestURI = $this->_request->getRequestUri(); 
			    return $this->render('404error');
			}
		    }
		}
		else{
		
		    $bookObj = new Book;
		    $bookObj->initialize($book);
		    $bookObj->get_book_meta();
		    $bookObj->getAllPlaces();
		    
		    if(!$bookObj->bookURI){
			$this->view->requestURI = $this->_request->getRequestUri(); 
			return $this->render('404error');
		    }
		    else{
			$this->view->book = $bookObj;
			return $this->render('summary');
		    }
		}
		
	    }
	    elseif((strlen($aVar)>5) && stristr($aVar, ".json")){
		$book = str_replace(".json", "", $requestParams['avar']);
		
		if(is_numeric($book)){ 
		    
		    if(!$cache_result = $cache->load($cache_id)) {
		    $bookObj = new Book;
		    $bookObj->initialize($book);
		    $bookObj->get_book_meta();
		    $bookObj->getAllPlacesJSON();
		    
			$outputArray = array("id" =>  $bookObj->book,
					"title" => $bookObj->bookTitle,
					"uri" => $bookObj->bookURI,
					"author" => $bookObj->bookAuthors,
					"printed" => $bookObj->bookDate,
					"pages" => $bookObj->JSONpageArray,
					"places" => $bookObj->JSONplacesArray
			);
			
			$output = Zend_Json::encode($outputArray);
			$cache->save($output, $cache_id);
		    }
		    else{
			$output = $cache_result;
		    }
		    
		    
		    $this->_helper->viewRenderer->setNoRender();
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
		else{
		    $this->view->requestURI = $this->_request->getRequestUri(); 
		    return $this->render('404error');
		}
		
	    }
	    else{
		$this->view->requestURI = $this->_request->getRequestUri(); 
		return $this->render('404error');
	    }
	}
	else{
	    $this->_helper->viewRenderer->setNoRender();
	    echo "Index of books";
	}      
	
    }//end function
    
    
    

   
}

