<?php

require_once '../include/DbHandler.php';
require_once '../include/passwordHash.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();
date_default_timezone_set("Asia/Kolkata");
// User id from db - Global Variable
$user_id = NULL;

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route)
{
    session_start();
    
    // Getting request headers
    if (!function_exists('apache_request_headers')) {
        ///
        function apache_request_headers()
        {
            $arh     = array();
            $rx_http = '/\AHTTP_/';
            foreach ($_SERVER as $key => $val) {
                if (preg_match($rx_http, $key)) {
                    $arh_key    = preg_replace($rx_http, '', $key);
                    $rx_matches = array();
                    // do some nasty string manipulations to restore the original letter case
                    // this should work in most cases
                    $rx_matches = explode('_', $arh_key);
                    if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                        foreach ($rx_matches as $ak_key => $ak_val)
                            $rx_matches[$ak_key] = ucfirst($ak_val);
                        $arh_key = implode('-', $rx_matches);
                    }
                    $arh[$arh_key] = $val;
                }
            }
            return ($arh);
        }
        ///
    }
    ///
    $headers = apache_request_headers();
    
    
    $response = array();
    $app      = \Slim\Slim::getInstance();
    
    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();
        
        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"]   = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user_id = $db->getUserId($api_key);
        }
    } else if (isset($_SESSION)) {
        $db      = new DbHandler();
        $session = $db->getSession();
        // get the api key
        $api_key = $session['api_key'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"]   = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user_id = $db->getUserId($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"]   = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 */
/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */

$app->post('/register', 'authenticate', function() use ($app)
{
    
   
        $r = json_decode($app->request->getBody());
	verifyRequiredParams(array(
            'name',
        'password'));
   
        $user=array();
        $db  = new DbHandler();
        $user["name"]             = postParams($app->request->post("name"));
        $user["firstname"]             = postParams($app->request->post("firstname"));
        $user["lastname"]             = postParams($app->request->post("lastname"));
        $user["password"]             = postParams($app->request->post("password"));
        $user["role"]             = postParams($app->request->post("role"));
        $user["branch"]             = postParams($app->request->post("branch"));
        $user["phone"]             = postParams($app->request->post("phone"));

        $response=array();
        //first create entry
        $userCreate = $db->createUser($user);
        
       if ($userCreate["status"] == SUCCESS) {
           $response["error"]   = false;
            $response["status"]  = "success";
            $response["id"]      = $userCreate["id"];
            $response["message"] = "Successfully created User";
        } else {
            $response["error"]   = true;
            $response["status"]  = "error";
            $response["message"] = $userCreate["error"];
        }
        echoRespnse(201, $response);

});


$app->put('/user/:id', 'authenticate', function($id) use ($app)
{
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("user");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
	   if($params[$i] == "id"){
	     array_push($getdata,$id);
	   }else{
	     array_push($getdata,"");
	   }
	}
	$cityDetail = call_user_func_array(array($db,'getUser'), $getdata);

        if(sizeof($cityDetail)>0){
$params = $db->putFunctionParam("user");
		$updateField = array();
		$updateField["uid"] = $id;
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
			if(putParam($r,$params[$i])){
				array_push($putdata,putParam($r,$params[$i]));
			}else{
			}
	}
				array_push($putdata,"");
$editDetail = call_user_func_array(array($db,'editUser'), $putdata);
	
		if($editDetail['status'] == SUCCESS){
$response["error"] = false;
			$response["status"] ="success";
			$response["id"] =$id;
			$response["message"] = "Woot!,Successfully edited User information";
		}
		else{
			$response["error"] = true;
			$response["status"] ="error";
			$response["message"] = "Oops! An error occurred while editing User information";
			$response["err"]=$editDetail;
		}


        }else{
            $response["error"] = true;
	    $response["status"] ="error";
	    $response["message"] = "No User found with given ID";
        }
        echoRespnse(201, $response);
});

$app->delete('/user/:id', 'authenticate', function($id) use ($app)
{
    $r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("user");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
	   if($params[$i] == "id"){
	     array_push($getdata,$id);
	   }else{
	     array_push($getdata,"");
	   }
	}
	$cityDetail = call_user_func_array(array($db,'getUser'), $getdata);

        if(sizeof($cityDetail)>0){
$params = $db->putFunctionParam("city");
		$updateField = array();
		$updateField["uid"] = $id;
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
				array_push($putdata,"");

	}
				array_push($putdata,1);
$editDetail = call_user_func_array(array($db,'editUser'), $putdata);
	
		if($editDetail['status'] == SUCCESS){
$response["error"] = false;
			$response["status"] ="success";
			$response["id"] =$id;
			$response["message"] = "Woot!,Successfully deleted User information";
		}
		else{
			$response["error"] = true;
			$response["status"] ="error";
			$response["message"] = "Oops! An error occurred while deleting User information";
			$response["err"]=$editDetail;
		}


        }else{
            $response["error"] = true;
	    $response["status"] ="error";
	    $response["message"] = "No User found with given ID";
        }
        echoRespnse(201, $response);

});



$app->put('/resetpassword/:id', 'authenticate', function($id) use ($app)
{
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("user");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
	   if($params[$i] == "id"){
	     array_push($getdata,$id);
	   }else{
	     array_push($getdata,"");
	   }
	}


	$userDetail = call_user_func_array(array($db,'getUser'), $getdata);

        if(sizeof($userDetail)>0){
//if (passwordHash::check_password($user[0]["password"], $oldpassword)) {

                $password = putParam($r,"password");
$reset = $db->resetPassword($id,$password);
	
		if($reset['status'] == SUCCESS){
$response["error"] = false;
			$response["status"] ="success";
			$response["id"] =$id;
			$response["message"] = "Woot!,Successfully password reset";
		}
		else{
			$response["error"] = true;
			$response["status"] ="error";
			$response["message"] = "Oops! An error occurred while resetting password";
			$response["err"]=$reset;
		}

        }else{
            $response["error"] = true;
	    $response["status"] ="error";
	    $response["message"] = "No User found with given ID";
        }
        echoRespnse(201, $response);

});


$app->put('/changepassword', 'authenticate', function() use ($app)
{
    $response = array();
    // check for required params
    $json     = $app->request->getBody();
    $data     = json_decode($json, true);
    $r        = json_decode($app->request->getBody());
    if (!isset($r->data)) {
        verifyRequiredParams2(array(
            'data'
        ), $r);
        echoRespnse(400, "please enter fields inside data object");
    } else {
        
        
        $oldpassword = getParam2($r->data, "oldpassword");
        $newpassword = getParam2($r->data, "newpassword");

        $db   = new DbHandler();
$params = $db->getFunctionParam("user");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
			if($params[$i]=="uid"){
				array_push($getdata,$_SESSION["lrsoftw"]["uid"]);
			}else{
				array_push($getdata,"");
			}
	}
	
	$user = call_user_func_array(array($db,'getUser'), $getdata);
//        $user = $db->getUser($_SESSION["lrsoftw"]["uid"], "", "", "", "", "", "","", "", "", "", "", "","",0);
        if (sizeof($user) > 0) {
            if (passwordHash::check_password($user[0]["password"], $oldpassword)) {

$res = $db->resetPassword($_SESSION["lrsoftw"]["uid"],$newpassword);
 $response["error"]   = false;
                    $response["status"]  = "success";
                    $response["message"] = "Successfully updated password";
                if ($res == SUCCESS) {
                    $response["error"]   = false;
                    $response["status"]  = "success";
                    $response["message"] = "Successfully updated password";
                } else if ($res == FAILED) {
                    $response["error"]   = true;
                    $response["status"]  = "error";
                    $response["message"] = "Oops! An error occurred while updating password";
                }
            } else {
                $response["error"]   = true;
                $response["message"] = "Sorry Your Old Password is Wrong";
            }
        } else {
            $response["error"]   = true;
            $response["message"] = "Sorry No user exist with given ID";
        }
        
        
        echoRespnse(201, $response);
    }
});



$app->post('/city', 'authenticate', function() use ($app)
{
    
   
        $r = json_decode($app->request->getBody());
	verifyRequiredParams(array(
            'name'));

        $db  = new DbHandler();
        $city["name"]             = postParams($app->request->post("name"));
        $city["code"]             = postParams($app->request->post("code"));
        $city["source"]             = postParams($app->request->post("source"));
        $city["destination"]             = postParams($app->request->post("destination"));
        $city["mainstation"]             = postParams($app->request->post("mainstation"));
        $city["address"]             = postParams($app->request->post("address"));
        $city["phno"]             = postParams($app->request->post("phno"));
        $city["state"]             = postParams($app->request->post("state"));
$response=array();
        //first create entry
        $cityCreate = $db->createCity($city);
        if($city["destination"] && !$city["mainstation"]){
$params = $db->putFunctionParam("city");
		$updateField = array();
		$updateField["id"] = $cityCreate["id"];
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "mainstation"){
				array_push($putdata,$cityCreate["id"]);
			}else{
				array_push($putdata,"");
			}
	}
				array_push($putdata,"");
$editDetail = call_user_func_array(array($db,'editCity'), $putdata);
	
}
       if ($cityCreate["status"] == SUCCESS) {
           $response["error"]   = false;
            $response["status"]  = "success";
            $response["id"]      = $cityCreate["id"];
            $response["message"] = "Successfully created City";
        } else {
            $response["error"]   = true;
            $response["status"]  = "error";
            $response["message"] = $cityCreate["error"];
        }
        echoRespnse(201, $response);

});

$app->get('/city', 'authenticate', function() use($app){	
	$response = array();
	// fetching all products
	$db = new DbHandler();
		
	$params = $db->getFunctionParam("city");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
			if(getParams($app->request->get($params[$i]))){
				array_push($getdata,getParams($app->request->get($params[$i])));
			}else{
				array_push($getdata,"");
			}
	}
	
	$cityList = call_user_func_array(array($db,'getCity'), $getdata);	
	
	$cities=array();
	 
     
	$outputfields =  array("id","name","code","phno","source","destination","mainstation","mainstationname","state","statename","address","created","updated");
	
	$qryfields =array("id","name","code","phno","source","destination","mainstation","mainstationname","state","statename","address","created","updated");
	// looping through result and preparing tasks array
	for($i=0;$i<sizeOf($cityList);$i++){
		$tmp = array();		
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($cityList[$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $cityList[$i][$outputfields[$j]];
			}
		}
		array_push($cities, $tmp);
	}

	$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	$response['total'] = call_user_func_array(array($db,'getCity'), $getdata)[0]["count(*)"];
	$response["message"] = "Successfully fetched cities list";
	$response["count"] = sizeOf($cities);
	$response["city"] =$cities;
	echoRespnse(200, $response);
});	



$app->put('/city/:id', 'authenticate', function($id) use ($app)
{
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("city");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
	   if($params[$i] == "id"){
	     array_push($getdata,$id);
	   }else{
	     array_push($getdata,"");
	   }
	}
	$cityDetail = call_user_func_array(array($db,'getCity'), $getdata);

        if(sizeof($cityDetail)>0){
$params = $db->putFunctionParam("city");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){

			if(putParam($r,$params[$i]) || putParam($r,$params[$i]) == "0"){
				array_push($putdata,putParam($r,$params[$i]));
			}else{
				array_push($putdata,"");
			}
	}
				array_push($putdata,"");
$editDetail = call_user_func_array(array($db,'editCity'), $putdata);
	
		if($editDetail['status'] == SUCCESS){
$response["error"] = false;
			$response["status"] ="success";
			$response["id"] =$id;
			$response["message"] = "Woot!,Successfully edited City information";
		}
		else{
			$response["error"] = true;
			$response["status"] ="error";
			$response["message"] = "Oops! An error occurred while editing City information";
			$response["err"]=$editDetail;
		}


        }else{
            $response["error"] = true;
	    $response["status"] ="error";
	    $response["message"] = "No City found with given ID";
        }
        echoRespnse(201, $response);

});


$app->delete('/city/:id', 'authenticate', function($id) use ($app)
{
    $r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("city");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
	   if($params[$i] == "id"){
	     array_push($getdata,$id);
	   }else{
	     array_push($getdata,"");
	   }
	}
	$cityDetail = call_user_func_array(array($db,'getCity'), $getdata);

        if(sizeof($cityDetail)>0){
$params = $db->putFunctionParam("city");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
				array_push($putdata,"");

	}
				array_push($putdata,1);
$editDetail = call_user_func_array(array($db,'editCity'), $putdata);
	
		if($editDetail['status'] == SUCCESS){
$response["error"] = false;
			$response["status"] ="success";
			$response["id"] =$id;
			$response["message"] = "Woot!,Successfully deleted City information";
		}
		else{
			$response["error"] = true;
			$response["status"] ="error";
			$response["message"] = "Oops! An error occurred while deleting City information";
			$response["err"]=$editDetail;
		}


        }else{
            $response["error"] = true;
	    $response["status"] ="error";
	    $response["message"] = "No City found with given ID";
        }
        echoRespnse(201, $response);

});


$app->get('/state', 'authenticate', function() use($app){	
	$response = array();
	// fetching all products
	$db = new DbHandler();
		
	$params = $db->getFunctionParam("state");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
			if(getParams($app->request->get($params[$i]))){
				array_push($getdata,getParams($app->request->get($params[$i])));
			}else{
				array_push($getdata,"");
			}
	}
	
	$List = call_user_func_array(array($db,'getState'), $getdata);	
	
	$datas=array();
	 
     
	$outputfields =  array("id","name","code","phno","source","destination","mainstation","mainstationname","address","created","updated");
	
	$qryfields =array("id","name","code","phno","source","destination","mainstation","mainstationname","address","created","updated");
	// looping through result and preparing tasks array
	for($i=0;$i<sizeOf($List);$i++){
		$tmp = array();		
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($List[$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $List[$i][$outputfields[$j]];
			}
		}
		array_push($datas, $tmp);
	}

	$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	$response['total'] = call_user_func_array(array($db,'getState'), $getdata)[0]["count(*)"];
	$response["message"] = "Successfully fetched state list";
	$response["count"] = sizeOf($datas);
	$response["states"] =$datas;
	echoRespnse(200, $response);
});	



$app->post('/customer', 'authenticate', function() use ($app)
{
    
   
        $r = json_decode($app->request->getBody());
	verifyRequiredParams(array(
            'name','city'));

        $db  = new DbHandler();
$customer = array();
        $customer["name"]             = postParams($app->request->post("name"));
        $customer["gst"]             = postParams($app->request->post("gst"));
        $customer["aadhar"]             = postParams($app->request->post("aadhar"));
        $customer["email"]             = postParams($app->request->post("email"));
        $customer["address"]             = postParams($app->request->post("address"));
        $customer["city"]             = postParams($app->request->post("city"));
        $customer["state"]             = postParams($app->request->post("state"));
        $customer["phone"]             = postParams($app->request->post("phone"));
        $customer["fare"]             = postParams($app->request->post("fare"));
$response=array();
        //first create entry
        $customerCreate = $db->createCustomer($customer);
        
       if ($customerCreate["status"] == SUCCESS) {
           $response["error"]   = false;
            $response["status"]  = "success";
            $response["id"]      = $customerCreate["id"];
            $response["message"] = "Successfully created Customer";
        } else {
            $response["error"]   = true;
            $response["status"]  = "error";
            $response["message"] = $customerCreate["error"];
        }
        echoRespnse(201, $response);

});


$app->get('/customer', 'authenticate', function() use($app){	
	$response = array();
	// fetching all products
	$db = new DbHandler();
		
	$params = $db->getFunctionParam("customer");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
			if(getParams($app->request->get($params[$i]))){
				array_push($getdata,getParams($app->request->get($params[$i])));
			}else{
				array_push($getdata,"");
			}
	}
	
	$customerList = call_user_func_array(array($db,'getCustomer'), $getdata);	
	
	$customers=array();
	 
     
	$outputfields =  array("id","gst","aadhar","name","email","address","phone","city","cityname","state","statename","fare","created","updated");
	
	$qryfields =array("id","gst","aadhar","name","email","address","phone","city","cityname","state","statename","fare","created","updated");
	// looping through result and preparing tasks array
	for($i=0;$i<sizeOf($customerList);$i++){
		$tmp = array();		
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($customerList[$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $customerList[$i][$outputfields[$j]];
			}
		}
		array_push($customers, $tmp);
	}

	$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	$response['total'] = call_user_func_array(array($db,'getCustomer'), $getdata)[0]["count(*)"];
	$response["message"] = "Successfully fetched customers list";
	$response["count"] = sizeOf($customers);
	$response["customer"] =$customers;
	echoRespnse(200, $response);
});	


$app->get('/customer/:id', function($id) use($app){	
	$response = array();
	// fetching all products
	$db = new DbHandler();
	$params = $db->getFunctionParam("customer");
	$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "id"){
				array_push($getdata,$id);
			}else{
				array_push($getdata,"");
			}
	}
	$customerList = call_user_func_array(array($db,'getCustomer'), $getdata);	
	if(sizeof($customerList)>0){
	$customer=array();
	
		$outputfields =  array("id","gst","aadhar","name","email","address","phone","city","cityname","state","statename","fare","created","updated");

	$qryfields = array("id","gst","aadhar","name","email","address","phone","city","cityname","state","statename","fare","created","updated");// looping through result and preparing tasks array
	for($i=0;$i<sizeOf($customerList);$i++){
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($customerList[$i][$outputfields[$j]])){
			$customer[$qryfields[$j]] = $customerList[$i][$outputfields[$j]];
			}
		}
	}
	$response['status'] = "success";
	$response["message"] = "Successfully fetched customer detail";
	$response["customer"] =$customer;
	echoRespnse(200, $response);
	}else{
	$response['status'] = "error";
	$response["message"] = "No customer found with given detail.";
	$response["code"] = "NOTEXIST";
	echoRespnse(404, $response);
	}
	
});	



$app->put('/customer/:id', 'authenticate', function($id) use ($app)
{
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("customer");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
	   if($params[$i] == "id"){
	     array_push($getdata,$id);
	   }else{
	     array_push($getdata,"");
	   }
	}
	$customerDetail = call_user_func_array(array($db,'getCustomer'), $getdata);

        if(sizeof($customerDetail)>0){
$params = $db->putFunctionParam("customer");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
			if(putParam($r,$params[$i])){
				array_push($putdata,putParam($r,$params[$i]));
			}else{
				array_push($putdata,"");
			}
	}
				array_push($putdata,"");
$editDetail = call_user_func_array(array($db,'editCustomer'), $putdata);
	
		if($editDetail['status'] == SUCCESS){
$response["error"] = false;
			$response["status"] ="success";
			$response["id"] =$id;
			$response["message"] = "Woot!,Successfully edited Customer information";
		}
		else{
			$response["error"] = true;
			$response["status"] ="error";
			$response["message"] = "Oops! An error occurred while editing Customer information";
			$response["err"]=$editDetail;
		}


        }else{
            $response["error"] = true;
	    $response["status"] ="error";
	    $response["message"] = "No City found with given ID";
        }
        echoRespnse(201, $response);

});

$app->post('/memo', 'authenticate', function() use ($app)
{
    
   
        $r = json_decode($app->request->getBody());
	verifyRequiredParams(array(
            'date','driver','lorryno'));
$memo = array();
        $db  = new DbHandler();
        $memo["date"]             = postParams($app->request->post("date"));
        $memo["owner"]             = postParams($app->request->post("owner"));
        $memo["driver"]             = postParams($app->request->post("driver"));
        $memo["lorryno"]             = postParams($app->request->post("lorryno"));
        $memo["agent"]             = postParams($app->request->post("agent"));
        $memo["phone"]             = postParams($app->request->post("phone"));
        $memo["source"]             = postParams($app->request->post("source"));
        $memo["destination"]             = postParams($app->request->post("destination"));
        $memo["advance"]             = postParams($app->request->post("advance"));
        $memo["freight"]             = postParams($app->request->post("freight"));
        $memo["paid"]             = postParams($app->request->post("paid"));
		        $memo["despatchtime"]             = postParams($app->request->post("despatchtime"));
        $lrid             = postParams($app->request->post("lrid"));



if(!$memo["paid"]){
$memo["paid"] = $memo["advance"];
}
$response=array();

        //first create entry
        $memoCreate = $db->createMemo($memo);
        
       if ($memoCreate["status"] == SUCCESS) {
       if($lrid)
for($j=0;$j<sizeof($lrid);$j++){

$params = $db->putFunctionParam("lr");
		$updateField = array();
		$updateField["id"] = $lrid[$j];
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "memo"){
				array_push($putdata,$memoCreate["id"]);
			}else{if($params[$i] == "status"){
				array_push($putdata,2);
			}else{
				array_push($putdata,"");
}
			}
	}
				array_push($putdata,"");
		
		
		$editDetail = call_user_func_array(array($db,'editLr'), $putdata);
}
           $response["error"]   = false;
            $response["status"]  = "success";
            $response["id"]      = $memoCreate["id"];
            $response["message"] = "Successfully created Memo";
        } else {
            $response["error"]   = true;
            $response["status"]  = "error";
            $response["message"] = $memoCreate["error"];
        }
        echoRespnse(201, $response);

});

$app->get('/memo', 'authenticate', function() use($app){	
	$response = array();
	// fetching all products
	$db = new DbHandler();
		
	$params = $db->getFunctionParam("memo");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
			if(getParams($app->request->get($params[$i]))){
				array_push($getdata,getParams($app->request->get($params[$i])));
			}else{
				array_push($getdata,"");
			}
	}
	
	$memoList = call_user_func_array(array($db,'getMemo'), $getdata);	
	
	$memos=array();
	 
     
	$outputfields =  array("id","date","owner","driver","agent","lorryno","phone","source","sourcename","scode","sourcestate","destination","destinationname","destinationstate","dcode","advance","freight","returnon","created","updated");
	
	$qryfields =array("id","date","owner","driver","agent","lorryno","phone","source","sourcename","scode","sourcestate","destination","destinationname","destinationstate","dcode","advance","freight","returnon","created","updated");
	// looping through result and preparing tasks array
	for($i=0;$i<sizeOf($memoList);$i++){
		$tmp = array();		
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($memoList[$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $memoList[$i][$outputfields[$j]];
			}
		}
		$tmp["totalPkg"]=0;
        $params = $db->getFunctionParam("lr");
	$getLrdata = array();
		for($l=0;$l<sizeof($params);$l++){
			if($params[$l] == "memo"){
				array_push($getLrdata,$memoList[$i]["id"]);
			}else{
if($params[$l] == "fields"){
				array_push($getLrdata,"sum(parcelcount) as parcelcount");
			}else if($params[$l] == "memopurpose"){
				array_push($getLrdata,true);
			}else{
				array_push($getLrdata,"");
			}
}
	}
	$lrList = call_user_func_array(array($db,'getLr'), $getLrdata);	
if(sizeof($lrList)>0){
$tmp["totalPkg"]= $lrList[0]["parcelcount"];
}

$tmp["totalWeight"]=0;

	$getLrdata = array();
		for($l=0;$l<sizeof($params);$l++){
			if($params[$l] == "memo"){
				array_push($getLrdata,$memoList[$i]["id"]);
			}else{
if($params[$l] == "fields"){
				array_push($getLrdata,"sum(weight) as weight");
			}else if($params[$l] == "memopurpose"){
				array_push($getLrdata,true);
			}else{
				array_push($getLrdata,"");
			}
}
	}
	$lrList = call_user_func_array(array($db,'getLr'), $getLrdata);	
if(sizeof($lrList)>0){
$tmp["totalWeight"]= $lrList[0]["weight"];
}
$tmp["toPay"]=0;

	$getLrdata = array();
		for($l=0;$l<sizeof($params);$l++){
			if($params[$l] == "memo"){
				array_push($getLrdata,$memoList[$i]["id"]);
			}else{
if($params[$l] == "fields"){
				array_push($getLrdata,"sum(total) as amount");
			}else if($params[$l] == "memopurpose"){
				array_push($getLrdata,true);
			}else{if($params[$l] == "paymentmode"){
				array_push($getLrdata,1);
			}else{
				array_push($getLrdata,"");
}
			}
}
	}
	$lrList = call_user_func_array(array($db,'getLr'), $getLrdata);	
if(sizeof($lrList)>0){
$tmp["toPay"]= $lrList[0]["amount"];
}

$tmp["paid"]=0;

	$getLrdata = array();
		for($l=0;$l<sizeof($params);$l++){
			if($params[$l] == "memo"){
				array_push($getLrdata,$memoList[$i]["id"]);
			}else{
if($params[$l] == "fields"){
				array_push($getLrdata,"sum(total) as amount");
			}else if($params[$l] == "memopurpose"){
				array_push($getLrdata,true);
			}else{if($params[$l] == "paymentmode"){
				array_push($getLrdata,2);
			}else{
				array_push($getLrdata,"");
}
			}
}
	}
	$lrList = call_user_func_array(array($db,'getLr'), $getLrdata);	
if(sizeof($lrList)>0){
$tmp["paid"]= $lrList[0]["amount"];
}
$fields=["source","destination"];
for($k=0;$k<sizeOf($fields);$k++){
		$ct=array();
	$distinctList= $db->getDistinct("lrdetail",$fields[$k],"memo=".$memoList[$i]['id']);
        for($c=0;$c<sizeOf($distinctList);$c++){
		array_push($ct,$distinctList[$c][$fields[$k]]);
			
	}$tmp[$fields[$k]] = $ct;
}

array_push($memos, $tmp);
	}

	$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;

	$response['status'] = "success";
	$response['total'] = call_user_func_array(array($db,'getMemo'), $getdata)[0]["count(*)"];
	$response["message"] = "Successfully fetched memos list";
	$response["count"] = sizeOf($memos);
	$response["memos"] =$memos;
	echoRespnse(200, $response);
});	




$app->get('/memo/:id', function($id) use($app){	
	$response = array();
	// fetching all products
	$db = new DbHandler();
	$params = $db->getFunctionParam("memo");
	$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "id"){
				array_push($getdata,$id);
			}else{
				array_push($getdata,"");
			}
	}
	$memoList = call_user_func_array(array($db,'getMemo'), $getdata);	
	if(sizeof($memoList)>0){
	$memo=array();
	
	$outputfields =   array("id","date","returnon","owner","driver","agent","lorryno","phone","source","sourcename","scode","sourcestate","destination","destinationname","destinationstate","dcode","advance","freight","paid","despatchtime","created","updated");
	
		$qryfields = array("id","date","returnon","owner","driver","agent","lorryno","phone","source","sourcename","scode","sourcestate","destination","destinationname","destinationstate","dcode","advance","freight","paid","despatchtime","created","updated");
	// looping through result and preparing tasks array
	for($i=0;$i<sizeOf($memoList);$i++){
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($memoList[$i][$outputfields[$j]])){
			$memo[$qryfields[$j]] = $memoList[$i][$outputfields[$j]];
			}
		}
	}
$memo["lr"] = array();

	$params = $db->getFunctionParam("lr");
	$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "memo"){
				array_push($getdata,$id);
			} else if($params[$i] == "sort_by"){
				array_push($getdata,"lrno");
			} else if($params[$i] == "sort_order"){
				array_push($getdata,"asc");
			} else if($params[$i] == "memopurpose"){
				array_push($getdata,true);
			}else{
				array_push($getdata,"");
			}
	}
	$lrList = call_user_func_array(array($db,'getLr'), $getdata);	
	$outputfields =  array("id","date","lrno","consigner","consignergst","consignee","consigneegst","scode","dcode","origin","mainstationname","mainstationcode","description","source","parcelcount","weight","freight","loaded","paymentmode","ewaybill","invoiceno","lrtype","total","deliverydate","valueamount");
	
	$qryfields =array("id","date","lrno","consigner","consignergst","consignee","consigneegst","scode","dcode","origin","mainstationname","mainstationcode","description","source","parcelcount","weight","freight","loaded","paymentmode","ewaybill","invoiceno","lrtype","total","deliverydate","valueAmount");
	
	for($i=0;$i<sizeOf($lrList);$i++){
	$tmp = array();
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($lrList[$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $lrList[$i][$outputfields[$j]];
			}
		}
		array_push($memo["lr"],$tmp);
	}


//expenses

$memo["expenses"] =array();
$params = $db->getFunctionParam("truckexpenses");
	$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "memo"){
				array_push($getdata,$id);
			} else{
				array_push($getdata,"");
			}
	}
	$expenseList = call_user_func_array(array($db,'getTruckExpenses'), $getdata);	
	$outputfields =  array("id","name","amount");
	
	$qryfields = array("id","name","amount");
	for($i=0;$i<sizeOf($expenseList);$i++){
	$tmp = array();
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($expenseList[$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $expenseList[$i][$outputfields[$j]];
			}
		}
		array_push($memo["expenses"],$tmp);
	}


$memo["hire"] =array();
$params = $db->getFunctionParam("lorryhire");
	$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "memo"){
				array_push($getdata,$id);
			} else{
				array_push($getdata,"");
			}
	}
	$hireList = call_user_func_array(array($db,'getLorryHire'), $getdata);	
	$outputfields =  array("id","origin","destination","amount","initialreceived","received");
	
	$qryfields = array("id","origin","destination","amount","initialreceived","received");
	for($i=0;$i<sizeOf($hireList );$i++){
	$tmp = array();
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($hireList [$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $hireList [$i][$outputfields[$j]];
			}
		}
		array_push($memo["hire"],$tmp);
	}
	
	
	
$memo["collections"] =array();
$params = $db->getFunctionParam("topaycollection");
	$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "memo"){
				array_push($getdata,$id);
			} else{
				array_push($getdata,"");
			}
	}

	$List = call_user_func_array(array($db,'getToPayCollection'), $getdata);	
	$outputfields =  array("id","date","branch","branchname","branchcode","amount","person","mode","modename","description","bank","banktype","typeid","ifsc");
	
	$qryfields =  array("id","date","branch","branchname","branchcode","amount","person","mode","modename","description","bank","banktype","typeid","ifsc");
	for($i=0;$i<sizeOf($List);$i++){
	$tmp = array();
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($List [$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $List [$i][$outputfields[$j]];
			}
		}
		array_push($memo["collections"],$tmp);
	}

	$memo["inwards"] =array();
$params = $db->getFunctionParam("inward");
	$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "memo"){
				array_push($getdata,$id);
			} else{
				array_push($getdata,"");
			}
	}

	$List = call_user_func_array(array($db,'getInwards'), $getdata);	
	$outputfields =  array("id","date","branch","branchname","branchcode","amount");
	
	$qryfields =  array("id","date","branch","branchname","branchcode","amount");
	for($i=0;$i<sizeOf($List);$i++){
	$tmp = array();
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($List [$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $List [$i][$outputfields[$j]];
			}
		}
		array_push($memo["inwards"],$tmp);
	}
	
	$memo["commisions"] =array();
$params = $db->getFunctionParam("commision");
	$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "memo"){
				array_push($getdata,$id);
			} else{
				array_push($getdata,"");
			}
	}

	$List = call_user_func_array(array($db,'getCommisions'), $getdata);	
	$outputfields =  array("id","date","branch","branchname","branchcode","percentage");
	
	$qryfields =  array("id","date","branch","branchname","branchcode","percentage");
	for($i=0;$i<sizeOf($List);$i++){
	$tmp = array();
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($List [$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $List [$i][$outputfields[$j]];
			}
		}
		array_push($memo["commisions"],$tmp);
	}
	
	$response['status'] = "success";
	$response["message"] = "Successfully fetched memo detail";
	$response["memo"] =$memo;
	echoRespnse(200, $response);
	}else{
	$response['status'] = "error";
	$response["message"] = "No memo found with given detail.";
	$response["code"] = "NOTEXIST";
	echoRespnse(404, $response);
	}
	
});	


$app->get('/memocollection', 'authenticate', function() use($app){	
	$response = array();
	// fetching all products
	$db = new DbHandler();
		
	$params = $db->getFunctionParam("memo");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
			if(getParams($app->request->get($params[$i]))){
				array_push($getdata,getParams($app->request->get($params[$i])));
			}else{
				array_push($getdata,"");
			}
	}
	
	$List = call_user_func_array(array($db,'getMemo'), $getdata);	
	
	$data=array();
	 
     
	$outputfields =   array("id","date","returnon","owner","driver","agent","lorryno","phone","source","sourcename","scode","sourcestate","destination","destinationname","destinationstate","dcode","advance","freight","paid","created","updated");
	
		$qryfields = array("id","date","returnon","owner","driver","agent","lorryno","phone","source","sourcename","scode","sourcestate","destination","destinationname","destinationstate","dcode","advance","freight","paid","created","updated");
	// looping through result and preparing tasks array
	for($i=0;$i<sizeOf($List);$i++){
		$tmp = array();		
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($List[$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $List[$i][$outputfields[$j]];
			}
		}
		
		$params = $db->getFunctionParam("topaycollection");
	$getdata = array();
	for($k=0;$k<sizeof($params);$k++){
			if($params[$k] == "memo"){
				array_push($getdata,$List[$i]["id"]);
			}else{
				array_push($getdata,"");
			}
	}
	
	$List1 = call_user_func_array(array($db,'getToPayCollection'), $getdata);	
	
	
	
	$tmp["collections"]=array();
	 
     
	$outputfields =  array("id","memo","date","branch","branchname","branchcode","amount","person","mode","modename","description","bank","banktype","typeid","ifsc","created","updated");
	
	$qryfields =array("id","memo","date","branch","branchname","branchcode","amount","person","mode","modename","description","bank","banktype","typeid","ifsc","created","updated");
	// looping through result and preparing tasks array
	for($k=0;$k<sizeOf($List1);$k++){
		$tmp1 = array();		
		for($j1 = 0;$j1<sizeof($qryfields);$j1++){
		if(isset($List1[$k][$outputfields[$j1]])){
			$tmp1[$qryfields[$j1]] = $List1[$k][$outputfields[$j1]];
			}
		}
		array_push($tmp["collections"], $tmp1);
	}
		array_push($data, $tmp);
	}

	$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	$response['total'] = call_user_func_array(array($db,'getToPayCollection'), $getdata)[0]["count(*)"];
	$response["message"] = "Successfully fetched lorry hire list";
	$response["count"] = sizeOf($data);
	$response["collections"] =$data;
	echoRespnse(200, $response);
});	

$app->put('/memo/:id', 'authenticate', function($id) use ($app)
{
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("memo");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
	   if($params[$i] == "id"){
	     array_push($getdata,$id);
	   }else{
	     array_push($getdata,"");
	   }
	}
	$memoDetail = call_user_func_array(array($db,'getMemo'), $getdata);

        if(sizeof($memoDetail)>0){
$params = $db->putFunctionParam("memo");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
			if(putParam($r,$params[$i])){
				array_push($putdata,putParam($r,$params[$i]));
			}else{
				array_push($putdata,"");
			}
	}
				array_push($putdata,"");

$editDetail = call_user_func_array(array($db,'editMemo'), $putdata);

		if($editDetail['status'] == SUCCESS){

	$expenses = putParam($r,"expenses");
	$hire = putParam($r,"hire");
	$payments = putParam($r,"payments");



/*
	$params = $db->getFunctionParam("truckexpenses");
	$getexpensedata = array();
	for($k=0;$k<sizeof($params);$k++){
		if($params[$k] == "memo"){
			array_push($getexpensedata,$id);
		}else{
			array_push($getexpensedata,"");
		}
	}
	
	$oldexpenses = call_user_func_array(array($db,'getTruckExpenses'), $getexpensedata);

	$outputfields = array("id","name","amount","direction");
	for($j=0;$j<sizeOf($oldexpenses);$j++){	
		$action = "";
		$deletedExpense = true;//assume that this charge is been deleted
		$updateExpense = false;//assume that no changes is to be done for this charge
if($expenses){
		$expensedata = array();
		$ind = getEditObject($oldexpenses[$j]["id"],$expenses);
		if($ind>=0){//if charge exist then get the index
			$deletedExpense = false;// ass charge found so less chance of deleting
			$expensedata = json_decode(json_encode($expenses), True);
			for($k=0;$k<sizeOf($outputfields);$k++){
				if($oldexpenses[$j][$outputfields[$k]] != $expensedata[$ind][$outputfields[$k]]){
					$updateExpense = true;
					$action = "update";
					break;
				}
			}
			if($updateExpense){//if($action == "update")
				$params = $db->putFunctionParam("truckexpenses");
				$updateField = array();
				$updateField["id"] = $oldexpenses[$j]["id"];
				$putdata=array();
				array_push($putdata,$updateField);
				for($i=0;$i<sizeof($params);$i++){
					if($params[$i]!="lrid"){
						array_push($putdata,$expensedata[$ind][$params[$i]]);
					}else{
						array_push($putdata,"");
					}
				}
				array_push($putdata,"");
				$editDetail = call_user_func_array(array($db,'editTruckExpenses'), $putdata);
			}
			unset($expenses[$ind]);
			$expenses =  array_values($expenses);
		}else{
			$deletedExpense = true;
		}}
		else{//if they removed the product from array
			$deletedExpense = true;
			$action = "delete";
		}
		
		if($deletedExpense){
			$params = $db->putFunctionParam("truckexpenses");
			$updateField = array();
			$updateField["id"] = $oldexpenses[$j]["id"];
			$putdata=array();
			array_push($putdata,$updateField);
			for($i=0;$i<sizeof($params);$i++){
				array_push($putdata,"");
			}
			array_push($putdata,1);
			$editDetail = call_user_func_array(array($db,'editTruckExpense'), $putdata);
		}	
	}
	$expenses = json_decode(json_encode($expenses), True);

	if($expenses && sizeof($expenses)>0){
		for($i=0;$i<sizeof($expenses);$i++){
			$chargesid = $db->createTruckExpense($id,$expenses[$i]);
		}
	}*/

if($expenses){
	$opParam="truckexpenses";$mainId="memo";
	$outputfields = array("id","name","amount","direction");$getFunction="getTruckExpenses";$syncdata=$expenses;$putFunction="editTruckExpense";
	$syncData = updateTable($db,$opParam,$mainId,$outputfields,$getFunction,$syncdata,$putFunction,$id);
	if($syncData && sizeof($syncData)>0){
		for($i=0;$i<sizeof($syncData);$i++){
			$chargesid = $db->createTruckExpense($id, $syncData[$i]);
		}	
	}
}

if($hire){
	$opParam="lorryhire";$mainId="memo";
	$outputfields = array("id","origin","destination","amount","direction");$getFunction="getLorryHire";$syncdata=$hire;$putFunction="editLorryHire";
	$syncData = updateTable($db,$opParam,$mainId,$outputfields,$getFunction,$syncdata,$putFunction,$id);

	if($syncData && sizeof($syncData)>0){
		for($i=0;$i<sizeof($syncData);$i++){
			$db->createLorryHire($id, $syncData[$i]);
		}	
	}
}

if($payments){
	$opParam="branchpayment";$mainId="memo";
	$outputfields = array("id","type","amount","cid","chequeno","bank","ifsc","date");$getFunction="getBranchPayment";$syncdata=$payments;$putFunction="editBranchPayment";
	$syncData = updateTable($db,$opParam,$mainId,$outputfields,$getFunction,$syncdata,$putFunction,$id);
	if($syncData && sizeof($syncData)>0){
		for($i=0;$i<sizeof($syncData);$i++){
			$db->createBranchPayment($id, $syncData[$i]);
		}	
	}
}
$response["error"] = false;
			$response["status"] ="success";
			$response["id"] =$id;
			$response["message"] = "Woot!,Successfully edited Memo information";
		}
		else{
			$response["error"] = true;
			$response["status"] ="error";
			$response["message"] = "Oops! An error occurred while editing Memo information";
			$response["err"]=$editDetail;
		}


        }else{
            $response["error"] = true;
	    $response["status"] ="error";
	    $response["message"] = "No Memo found with given ID";
        }
        echoRespnse(201, $response);

});


$app->delete('/memo/:id', 'authenticate', function($id) use ($app)
{
    $r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("memo");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
	   if($params[$i] == "id"){
	     array_push($getdata,$id);
	   }else{
	     array_push($getdata,"");
	   }
	}
	$memoDetail = call_user_func_array(array($db,'getMemo'), $getdata);

        if(sizeof($memoDetail)>0){

	$params = $db->getFunctionParam("lr");
	$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "memo"){
				array_push($getdata,$id);
			}else{
				array_push($getdata,"");
			}
	}
	$lrList = call_user_func_array(array($db,'getLr'), $getdata);	
        if(sizeof($lrList)==0){

$params = $db->putFunctionParam("memo");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
				array_push($putdata,"");

	}
				array_push($putdata,1);
$editDetail = call_user_func_array(array($db,'editMemo'), $putdata);
	
		if($editDetail['status'] == SUCCESS){

$response["error"] = false;
			$response["status"] ="success";
			$response["id"] =$id;
			$response["message"] = "Woot!,Successfully deleted Memo";
		}
		else{
			$response["error"] = true;
			$response["status"] ="error";
			$response["message"] = "Oops! An error occurred while deleting Memo information";
			$response["err"]=$editDetail;
		}}else{
$response["error"] = true;
			$response["status"] ="error";
			$response["message"] = "Please remove lr from this memo and then delete memo";
}


        }else{
            $response["error"] = true;
	    $response["status"] ="error";
	    $response["message"] = "No Memo found with given ID";
        }
        echoRespnse(201, $response);

});

$app->put('/merge/:id', 'authenticate', function($id) use ($app)
{
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("memo");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
	   if($params[$i] == "id"){
	     array_push($getdata,$id);
	   }else{
	     array_push($getdata,"");
	   }
	}
	$memoDetail = call_user_func_array(array($db,'getMemo'), $getdata);

        if(sizeof($memoDetail)>0){
$memos = putParam($r,"memos");
for($j=0;$j<sizeof($memos);$j++){
          $params = $db->putFunctionParam("lr");
		$updateField = array();
		$updateField["memo"] = $memos[$j];
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "memo"){
				array_push($putdata,$id);
			}else{
				array_push($putdata,"");
			}
	}
				array_push($putdata,"");

$editDetail = call_user_func_array(array($db,'editLr'), $putdata);

$params = $db->putFunctionParam("memo");
		$updateField = array();
		$updateField["id"] = $memos[$j];
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
			
				array_push($putdata,"");
	}
				array_push($putdata,1);

$editDetail = call_user_func_array(array($db,'editMemo'), $putdata);

}

		//if($editDetail['status'] == SUCCESS){
$response["error"] = false;
			$response["status"] ="success";
			$response["id"] =$id;
			$response["message"] = "Woot!,Successfully edited Memo information";
		/*}
		else{
			$response["error"] = true;
			$response["status"] ="error";
			$response["message"] = "Oops! An error occurred while editing Memo information";
			$response["err"]=$editDetail;
		}*/


        }else{
            $response["error"] = true;
	    $response["status"] ="error";
	    $response["message"] = "No Memo found with given ID";
        }
        echoRespnse(201, $response);

});

$app->post('/truckexpense', 'authenticate', function() use ($app)
{
    
   
        $r = json_decode($app->request->getBody());
	//verifyRequiredParams(array(
           // 'hires'));

        $db  = new DbHandler();
        $expenses = postParams($app->request->post("expenses"));
      
$response=array();
        
		$dataCreate="";//first create entry
       $dataCreate = $db->createTruckExpense($expenses);
     
       if ($dataCreate["status"] == SUCCESS) {
           $response["error"]   = false;
            $response["status"]  = "success";
            //$response["id"]      = $dataCreate["id"];
            $response["message"] = "Successfully created expenses ";
        } else {
            $response["error"]   = true;
            $response["status"]  = "error";
            $response["message"] = $dataCreate["error"];
        }
        echoRespnse(201, $response);

});



$app->post('/lorryhire', 'authenticate', function() use ($app)
{
    
   
        $r = json_decode($app->request->getBody());
	//verifyRequiredParams(array(
           // 'hires'));

        $db  = new DbHandler();
        $hires = postParams($app->request->post("hires"));
      
$response=array();
        
		$dataCreate="";//first create entry
       $dataCreate = $db->createLorryHire($hires);
     
       if ($dataCreate["status"] == SUCCESS) {
           $response["error"]   = false;
            $response["status"]  = "success";
            //$response["id"]      = $dataCreate["id"];
            $response["message"] = "Successfully created Lorry Hire";
        } else {
            $response["error"]   = true;
            $response["status"]  = "error";
            $response["message"] = $dataCreate["error"];
        }
        echoRespnse(201, $response);

});


$app->get('/lorryhire', 'authenticate', function() use($app){	
	$response = array();
	// fetching all products
	$db = new DbHandler();
		
	$params = $db->getFunctionParam("lorryhire");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
			if(getParams($app->request->get($params[$i]))){
				array_push($getdata,getParams($app->request->get($params[$i])));
			}else{
				array_push($getdata,"");
			}
	}
	
	$List = call_user_func_array(array($db,'getLorryHire'), $getdata);	
	
	$data=array();
	 
     
	$outputfields =  array("id","memo","origin","destination","amount","received","date","created","updated");
	
	$qryfields =array("id","memo","origin","destination","amount","received","date","created","updated");
	// looping through result and preparing tasks array
	for($i=0;$i<sizeOf($List);$i++){
		$tmp = array();		
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($List[$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $List[$i][$outputfields[$j]];
			}
		}
		array_push($data, $tmp);
	}

	$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	$response['total'] = call_user_func_array(array($db,'getLorryHire'), $getdata)[0]["count(*)"];
	$response["message"] = "Successfully fetched lorry hire list";
	$response["count"] = sizeOf($data);
	$response["lorryhires"] =$data;
	echoRespnse(200, $response);
});	


$app->post('/topaycollection', 'authenticate', function() use ($app)
{
    
   
        $r = json_decode($app->request->getBody());
	//verifyRequiredParams(array(
           // 'hires'));

        $db  = new DbHandler();
        $collection["date"] = postParams($app->request->post("date"));
		$collection["memo"] = postParams($app->request->post("memo"));
		$collection["branch"] = postParams($app->request->post("branch"));
		$collection["amount"] = postParams($app->request->post("amount"));
		$collection["person"] = postParams($app->request->post("person"));
		$collection["mode"] = postParams($app->request->post("mode"));
		$collection["description"] = postParams($app->request->post("description"));
		$collection["bank"] = postParams($app->request->post("bank"));
		$collection["banktype"] = postParams($app->request->post("banktype"));
		$collection["typeid"] = postParams($app->request->post("typeid"));
		$collection["ifsc"] = postParams($app->request->post("ifsc"));
      
$response=array();
        
		$dataCreate="";//first create entry
       $dataCreate = $db->createToPayCollection($collection);
     
       if ($dataCreate["status"] == SUCCESS) {
           $response["error"]   = false;
            $response["status"]  = "success";
            //$response["id"]      = $dataCreate["id"];
            $response["message"] = "Successfully saved to pay collection";
        } else {
            $response["error"]   = true;
            $response["status"]  = "error";
            $response["message"] = $dataCreate["error"];
        }
        echoRespnse(201, $response);

});


$app->get('/topaycollection', 'authenticate', function() use($app){	
	$response = array();
	// fetching all products
	$db = new DbHandler();
		
	$params = $db->getFunctionParam("topaycollection");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
			if(getParams($app->request->get($params[$i]))){
				array_push($getdata,getParams($app->request->get($params[$i])));
			}else{
				array_push($getdata,"");
			}
	}
	
	$List = call_user_func_array(array($db,'getToPayCollection'), $getdata);	
	
	$data=array();
	 
     
	$outputfields =  array("id","memo","date","branch","branchname","branchcode","amount","person","mode","modename","description","bank","banktype","typeid","ifsc","created","updated");
	
	$qryfields =array("id","memo","date","branch","branchname","branchcode","amount","person","mode","modename","description","bank","banktype","typeid","ifsc","created","updated");
	// looping through result and preparing tasks array
	for($i=0;$i<sizeOf($List);$i++){
		$tmp = array();		
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($List[$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $List[$i][$outputfields[$j]];
			}
		}
		array_push($data, $tmp);
	}

	$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	$response['total'] = call_user_func_array(array($db,'getToPayCollection'), $getdata)[0]["count(*)"];
	$response["message"] = "Successfully fetched lorry hire list";
	$response["count"] = sizeOf($data);
	$response["collections"] =$data;
	echoRespnse(200, $response);
});	



$app->post('/inward', 'authenticate', function() use ($app)
{
    
   
        $r = json_decode($app->request->getBody());
	//verifyRequiredParams(array(
           // 'hires'));

        $db  = new DbHandler();
        $inwards = postParams($app->request->post("inwards"));
      
$response=array();
        
		$dataCreate="";//first create entry
       $dataCreate = $db->createInward($inwards);
     
       if ($dataCreate["status"] == SUCCESS) {
           $response["error"]   = false;
            $response["status"]  = "success";
            //$response["id"]      = $dataCreate["id"];
            $response["message"] = "Successfully created Inward Amount";
        } else {
            $response["error"]   = true;
            $response["status"]  = "error";
            $response["message"] = $dataCreate["error"];
        }
        echoRespnse(201, $response);

});


$app->put('/inward/:id', 'authenticate', function($id) use ($app)
{
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("inward");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
	   if($params[$i] == "id"){
	     array_push($getdata,$id);
	   }else{
	     array_push($getdata,"");
	   }
	}
	$cityDetail = call_user_func_array(array($db,'getInwards'), $getdata);

        if(sizeof($cityDetail)>0){
$params = $db->putFunctionParam("inward");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){

			if(putParam($r,$params[$i]) || putParam($r,$params[$i]) == "0"){
				array_push($putdata,putParam($r,$params[$i]));
			}else{
				array_push($putdata,"");
			}
	}
				array_push($putdata,"");
$editDetail = call_user_func_array(array($db,'editInward'), $putdata);
	
		if($editDetail['status'] == SUCCESS){
$response["error"] = false;
			$response["status"] ="success";
			$response["id"] =$id;
			$response["message"] = "Woot!,Successfully edited inward information";
		}
		else{
			$response["error"] = true;
			$response["status"] ="error";
			$response["message"] = "Oops! An error occurred while editing inward information";
			$response["err"]=$editDetail;
		}


        }else{
            $response["error"] = true;
	    $response["status"] ="error";
	    $response["message"] = "No City found with given ID";
        }
        echoRespnse(201, $response);

});



$app->post('/commision', 'authenticate', function() use ($app)
{
    
   
        $r = json_decode($app->request->getBody());
	//verifyRequiredParams(array(
           // 'hires'));

        $db  = new DbHandler();
        $commisions = postParams($app->request->post("commisions"));
      
$response=array();
        
		$dataCreate="";//first create entry
       $dataCreate = $db->createCommision($commisions);
     
       if ($dataCreate["status"] == SUCCESS) {
           $response["error"]   = false;
            $response["status"]  = "success";
            //$response["id"]      = $dataCreate["id"];
            $response["message"] = "Successfully created Commisions";
        } else {
            $response["error"]   = true;
            $response["status"]  = "error";
            $response["message"] = $dataCreate["error"];
        }
        echoRespnse(201, $response);

});


$app->put('/commision/:id', 'authenticate', function($id) use ($app)
{
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("commision");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
	   if($params[$i] == "id"){
	     array_push($getdata,$id);
	   }else{
	     array_push($getdata,"");
	   }
	}
	$cityDetail = call_user_func_array(array($db,'getCommisions'), $getdata);

        if(sizeof($cityDetail)>0){
$params = $db->putFunctionParam("commision");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){

			if(putParam($r,$params[$i]) || putParam($r,$params[$i]) == "0"){
				array_push($putdata,putParam($r,$params[$i]));
			}else{
				array_push($putdata,"");
			}
	}
				array_push($putdata,"");
$editDetail = call_user_func_array(array($db,'editCommision'), $putdata);
	
		if($editDetail['status'] == SUCCESS){
$response["error"] = false;
			$response["status"] ="success";
			$response["id"] =$id;
			$response["message"] = "Woot!,Successfully edited commision information";
		}
		else{
			$response["error"] = true;
			$response["status"] ="error";
			$response["message"] = "Oops! An error occurred while editing commision information";
			$response["err"]=$editDetail;
		}


        }else{
            $response["error"] = true;
	    $response["status"] ="error";
	    $response["message"] = "No City found with given ID";
        }
        echoRespnse(201, $response);

});


$app->put('/loading', 'authenticate', function() use ($app){
$r = json_decode($app->request->getBody());
	$db = new DbHandler();
        $loadedData             = json_decode(json_encode(putParam($r,"loadedData")),True);
$params = $db->putFunctionParam("lr");
		$updateField = array();

for($i = 0;$i<sizeof($loadedData);$i++){
		$updateField["id"] = $loadedData[$i]["id"];
		$putdata=array();
		array_push($putdata,$updateField);
		for($j=0;$j<sizeof($params);$j++){
			if($params[$j] == "loaded"){
if($loadedData[$i]["loaded"]){
				array_push($putdata,1);}else{
array_push($putdata,"EMPTY_PARAM");
}
			}else{
				array_push($putdata,"");
			}
	}
				array_push($putdata,"");
		
		
		$editDetail = call_user_func_array(array($db,'editLr'), $putdata);

}
$response = array();
           $response["error"]   = false;
            $response["status"]  = "success";
            $response["message"] = "Successfully updated loading sheet";
	echoRespnse(200, $response);     
});

$app->post('/addlr/:id', 'authenticate', function($id) use ($app)
{
        $r = json_decode($app->request->getBody());
        $lrid             = postParams($app->request->post("lrno"));

for($j=0;$j<sizeof($lrid);$j++){
$db= new DbHandler();
$params = $db->putFunctionParam("lr");
		$updateField = array();
		$updateField["id"] = $lrid[$j];
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "memo"){
				array_push($putdata,$id);
			}else{if($params[$i] == "status"){
				array_push($putdata,2);
			}else{
				array_push($putdata,"");
}
			}
	}
				array_push($putdata,"");
		

		$editDetail = call_user_func_array(array($db,'editLr'), $putdata);
}
$response = array();
           $response["error"]   = false;
            $response["status"]  = "success";
            $response["id"]      = $id;
            $response["message"] = "Successfully added lr to Memo";
	echoRespnse(200, $response);       

    
});


$app->post('/removelr/:id', 'authenticate', function($id) use ($app)
{  $r = json_decode($app->request->getBody());
        $lrid             = postParams($app->request->post("lrno"));

$db=new DbHandler();  
	$params = $db->getFunctionParam("memo");
	$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "id"){
				array_push($getdata,$id);
			}else{
				array_push($getdata,"");
			}
	}
	$memoOldList = call_user_func_array(array($db,'getMemo'), $getdata);	
	if(sizeof($memoOldList)>0){


 $nextdate = "";
if(date("D", strtotime('+1 day')) == "Sun"){
$nextdate = date('Y-m-d', strtotime(' +2 day'));
}else{
$nextdate = date('Y-m-d', strtotime(' 1 day'));
}

$params = $db->getFunctionParam("memo");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
if($params[$i] == "date"){

array_push($getdata,postParams($nextdate));
}else if($params[$i] == "destination"){
				array_push($getdata,$memoOldList[0]["destination"]);
			}else if($params[$i] == "source"){
array_push($getdata,$memoOldList[0]["source"]);
}else{
array_push($getdata,"");
}

}
$mid=0;	
	$memoList = call_user_func_array(array($db,'getMemo'), $getdata);
/*if(sizeof($memoList)==0){
$memo = array();
$fields = array("owner","driver","lorryno","agent","phone","advance","freight","paid");
for($i=0;$i<sizeof($fields);$i++){
$memo[$fields[$i]] = "";
}
$memo["date"] = postParams($nextdate);
$memo["source"] = postParams($memoOldList[0]["source"]);
$memo["destination"] = postParams($memoOldList[0]["destination"]);
  //first create entry
        $memoCreate = $db->createMemo($memo);
$mid= $memoCreate["id"];
}else{
$mid = $memoList[0]["id"];
}
*/
for($j=0;$j<sizeof($lrid);$j++){
$db= new DbHandler();
$params = $db->putFunctionParam("lr");
		$updateField = array();
		$updateField["id"] = $lrid[$j];
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "memo"){
				array_push($putdata,"EMPTY_PARAM");
			}else{if($params[$i] == "status"){
				array_push($putdata,1);
			}else{
				array_push($putdata,"");
}
			}
	}
				array_push($putdata,"");


		$editDetail = call_user_func_array(array($db,'editLr'), $putdata);
}
$response = array();
           $response["error"]   = false;
            $response["status"]  = "success";
            $response["id"]      = $id;
            $response["message"] = "Successfully removed lr from Memo";
	echoRespnse(200, $response);       


}else{
$response['status'] = "error";
	$response["message"] = "No memo found with given detail.";
	$response["code"] = "NOTEXIST";
	echoRespnse(404, $response);
}    
});








$app->get('/user', 'authenticate', function() use($app){	
	$response = array();
	// fetching all products
	$db = new DbHandler();
		
	$params = $db->getFunctionParam("user");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
			if(getParams($app->request->get($params[$i]))){
				array_push($getdata,getParams($app->request->get($params[$i])));
			}else{
				array_push($getdata,"");
			}
	}
	
	$userList = call_user_func_array(array($db,'getUser'), $getdata);	
	
	$users=array();
	 
     
	$outputfields = array("uid","name","firstname","lastname","phone","role","branch","type","created","branchname");
	$qryfields = array("uid","name","firstname","lastname","phone","role","branch","type","created","branchname");
	
	// looping through result and preparing tasks array
	for($i=0;$i<sizeOf($userList);$i++){
		$tmp = array();		
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($userList[$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $userList[$i][$outputfields[$j]];
			}
		}
		array_push($users, $tmp);
	}

	$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	$response['total'] = call_user_func_array(array($db,'getUser'), $getdata)[0]["count(*)"];
	$response["message"] = "Successfully fetched users list";
	$response["count"] = sizeOf($users);
	$response["users"] =$users;
	echoRespnse(200, $response);
});	



$app->get('/role', 'authenticate', function() use ($app)
{
    $response = array();
    
    $rid          = getParam($app->request->get('rid'));
    $type        = getParam($app->request->get('type'));
	
  
	$fields = getParams($app->request->get('fields'));
	$sort_by = getParams($app->request->get('sort_by'));
	$sort_order = getParams($app->request->get('sort_order'));
	$limit = getParams($app->request->get('limit'));
	$offset = getParams($app->request->get('offset'));
    
    $result = "";
    $db     = new DbHandler();
    
    $roleList   = $db->getRoles($rid,$type,$fields,$sort_by,$sort_order,$limit,$offset,0);
    $roles = array();
   
   
	
	
	
	$outputfields = array("rid","type");
	$qryfields = array("rid","type");
	
	// looping through result and preparing tasks array
	for($i=0;$i<sizeOf($roleList);$i++){
		$tmp = array();		
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($roleList[$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $roleList[$i][$outputfields[$j]];
			}
		}
		array_push($roles, $tmp);
	}
	
    $response["count"]   = sizeof($roles);
    $response["roles"]    = $roles;
    $response["message"] = "successfully fetched roles";
    $response["status"]  = "success";
    echoRespnse(200, $response);
});


$app->get('/charges', 'authenticate', function() use($app){	
	$response = array();
	// fetching all products
	$db = new DbHandler();
		
	$params = $db->getFunctionParam("charges");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
			if(getParams($app->request->get($params[$i]))){
				array_push($getdata,getParams($app->request->get($params[$i])));
			}else{
				array_push($getdata,"");
			}
	}
	
	$chargeList = call_user_func_array(array($db,'getCharges'), $getdata);	
	
	$charges=array();
	
	$outputfields =  array("id","name","default");
	
	$qryfields =array("id","name","default");
	// looping through result and preparing tasks array
	for($i=0;$i<sizeOf($chargeList);$i++){
		$tmp = array();		
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($chargeList[$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $chargeList[$i][$outputfields[$j]];
			}
		}
		array_push($charges, $tmp);
	}

	$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	$response['total'] = call_user_func_array(array($db,'getCharges'), $getdata)[0]["count(*)"];
	$response["message"] = "Successfully fetched charges list";
	$response["count"] = sizeOf($charges);
	$response["charges"] =$charges;
	echoRespnse(200, $response);
});	



$app->get('/date', function() use ($app)
{
    $dateformat = new DateTime('now', new DateTimeZone('Asia/Calcutta'));
    $date       = $dateformat->format('d/m/Y');
    $hour       = $dateformat->format('H');
    
    if ($hour < 6) {
        $date = date('d/m/Y', strtotime(' -1 day'));
    }
    $response["status"] = "success";
    $response["date"]   = $date;
    echoRespnse(200, $response);
    
});

$app->post('/lr', 'authenticate', function() use ($app)
{
//check required params are given or not(step1)
$r = json_decode($app->request->getBody());
	verifyRequiredParams(array(
            'date',
            'paymentMode',
            'parcelCount',
            'source',
            'destination',
            'consigner',
            'consignee',
            'valueAmount'));


        $db  = new DbHandler();
  
        $response = array();
		//step2 : find the next lrno

		
		
       
  /*  $dateformat = new DateTime('now', new DateTimeZone('Asia/Calcutta'));

    
    
    
        $fromdate = date('Y-m-d', strtotime(' -1 months'));
    
 
		$lrno             = postParams($r, "lrno");
$oldlr =  $db->getlr("", $lrno,"","", $fromdate, "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "","","");

if(sizeOf($oldlr)>0){
 $response["error"]   = true;
            $response["status"]  = "error";
            $response["message"] = "LR exist with given no. in last month. so please give new lr number";
}else{*/

$lr = array();	
 $lr["source"]           = postParams($app->request->post("source"));      
$session = $db->getSession();
if(($session["role"] == 2) && ($lr["source"] != $session["branch"])){
$lr["source"] = $session["branch"];
}
        $lr["lrtype"]             = postParams($app->request->post("lrtype"));
$data=array();
if($lr["lrtype"] == 1){
$data =  getNextLRNo($db,$lr["source"]);
$lr["lrno"]             =$data["lrno"];
}else{
        $lr["lrno"]             = postParams($app->request->post("lrno"));
}



        $lr["self"]             = postParams($app->request->post("self"));
        $lr["date"]             = postParams($app->request->post("date"));
        $lr["paymentMode"]      = postParams($app->request->post("paymentMode"));
        $lr["parcelCount"]      = postParams($app->request->post("parcelCount"));
        $lr["weight"]           = postParams($app->request->post("weight"));

        $lr["destination"]      = postParams($app->request->post("destination"));
        $lr["consigner"]        = postParams($app->request->post("consigner"));
        $lr["consignergst"]        = postParams($app->request->post("consignergst"));
        $lr["consignee"]        = postParams($app->request->post("consignee"));
        $lr["consigneegst"]        = postParams($app->request->post("consigneegst"));
        $lr["consignerAddress"] = postParams($app->request->post("consignerAddress"));
        $lr["consigneeAddress"] = postParams($app->request->post("consigneeAddress"));
        $lr["fromphno"]       = postParams($app->request->post("fromphno"));
        $lr["tophno"]         = postParams($app->request->post("tophno"));
        $lr["description"]      = postParams($app->request->post("description"));
        $lr["valueAmount"]      = postParams($app->request->post("valueAmount"));
        $lr["freight"]          = postParams($app->request->post("freight"));

        $lr["total"]            = postParams($app->request->post("total"));
        $lr["pettubadi"]            = postParams($app->request->post("pettubadi"));
        $lr["refby"]            = postParams($app->request->post("refby"));
		$lr["status"]            = postParams($app->request->post("status"));
        $lr["taxpaidby"]        = postParams($app->request->post("taxPaidBy"));
        $lr["package"]        = postParams($app->request->post("package"));
        $lr["uid"]              = $_SESSION['lrsoftw']['uid'];
        $lr["payment"]          = postParams($app->request->post("payment"));
		$lr["deliverydate"]          = postParams($app->request->post("deliveryDate"));
		$lr["deliverytype"]          = postParams($app->request->post("deliveryType"));
		$lr["memo"]          = postParams($app->request->post("memo"));
$lr["loaded"]          = postParams($app->request->post("loaded"));
		$lr["deliveryCharge"]          = postParams($app->request->post("deliveryCharge"));
$lr["ewaybill"]          = postParams($app->request->post("ewaybill"));
$lr["invoiceno"]          = postParams($app->request->post("invoiceno"));
		$charges          = postParams($app->request->post("charges"));
		
        if ($lr["paymentMode"] == 2) {
            $lr["payment"] = 1;
        }
        if (!$lr["status"]) {
           $lr["status"] = 1;
        }
		
			$lr["total"] = round($lr["freight"],2);
		

		$charges = json_decode(json_encode($charges),True);
       
        

        //first create entry '$frieghtCharges','$otherChargesType','$otherCharge','$serviceCharge'
        $lrCreate = $db->createLr($lr);
        if ($lrCreate["status"] == SUCCESS) {
if($lr["lrtype"] == 1){
setNextLRNo($db,$lr["source"],$data["field"],$data["next"]);
}
if($lr["consignergst"]){
//save or edit customer info
$customer = array();
$customer["gst"] = $lr["consignergst"];
$customer["name"] = $lr["consigner"];
$customer["address"] = $lr["consignerAddress"];
$customer["city"] = $lr["source"];
$customer["phone"] = $lr["fromphno"];


$params = $db->getFunctionParam("customer");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "gst"){
				array_push($getdata,$customer["gst"]);
			}else{
				array_push($getdata,"");
			}
	}

	$customerList = call_user_func_array(array($db,'getCustomer'), $getdata);	
if(sizeof($customerList)>0){//check whether to edit or not
if(($customerList[0]["name"] != $customer["name"]) || ($customerList[0]["address"] != $customer["address"]) ||($customerList[0]["city"] != $customer["city"]) || ($customerList[0]["phone"] != $customer["phone"])){
$params = $db->putFunctionParam("customer");

		$updateField = array();
		$updateField["id"] = $customerList[0]["id"];
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
			if(($params[$i] == "name" || $params[$i] == "city" || $params[$i] == "phone" || $params[$i] == "address") && $customer[$params[$i]]){
				array_push($putdata,$customer[$params[$i]]);
			}else{
				array_push($putdata,"");
			}
	}
				array_push($putdata,"");
	

		$editDetail = call_user_func_array(array($db,'editCustomer'), $putdata);

}


}else{
  $customerCreate = $db->createCustomer($customer);
}
}
//consignee details
if($lr["consigneegst"]){      
$customer["gst"] = $lr["consigneegst"];
$customer["name"] = $lr["consignee"];
$customer["address"] = $lr["consigneeAddress"];
$customer["city"] = $lr["destination"];
$customer["phone"] = $lr["tophno"];

$getdata[1] = $customer["gst"];
$params = $db->getFunctionParam("customer");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "gst"){
				array_push($getdata,$customer["gst"]);
			}else{
				array_push($getdata,"");
			}
	}

$customerList = call_user_func_array(array($db,'getCustomer'), $getdata);	
if(sizeof($customerList)>0){//check whether to edit or not
if(($customerList[0]["name"] != $customer["name"]) || ($customerList[0]["address"] != $customer["address"]) ||($customerList[0]["city"] != $customer["city"]) || ($customerList[0]["phone"] != $customer["phone"])){
$params = $db->putFunctionParam("customer");
		$updateField = array();
		$updateField["id"] = $customerList[0]["id"];
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
			if(isset($customer[$params[$i]])){
				array_push($putdata,$customer[$params[$i]]);
				}else{
								array_push($putdata,"");
								}
			
	}
				array_push($putdata,"");
		
		
		$editDetail = call_user_func_array(array($db,'editCustomer'), $putdata);
}


}else{
  $customerCreate = $db->createCustomer($customer);
}
}

			if($charges){
			for($i=0;$i<sizeof($charges);$i++){
				$chargesid = $db->createOtherCharges($lrCreate["id"], $charges[$i]);
				$lr["total"] += round($charges[$i]["amount"],2);

			}
			}
			
			//update total in lr detail
			$params = $db->putFunctionParam("lr");
		$updateField = array();
		$updateField["id"] = $lrCreate["id"];
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "total"){
				array_push($putdata,$lr["total"]);
			}else{
				array_push($putdata,"");
			}
	}
				array_push($putdata,"");
		
		
		$editDetail = call_user_func_array(array($db,'editLr'), $putdata);
		//update total in lr detail end

	$response["error"]   = false;
            $response["status"]  = "success";
            $response["id"]      = $lrCreate["id"];
            $response["message"] = "Successfully generated LR";
        } else {
            $response["error"]   = true;
            $response["status"]  = "error";
            $response["message"] = $lrCreate["error"];
        }
        //}

        echoRespnse(201, $response);
});


$app->get('/lr', function() use($app){	
	$response = array();
	// fetching all products
	$db = new DbHandler();
		
	$params = $db->getFunctionParam("lr");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
	if(getParams($app->request->get($params[$i]))){
				array_push($getdata,getParams($app->request->get($params[$i])));
			}else{
			if(getParams($app->request->get($params[$i])) == "0"){
				array_push($getdata,0);
			}else{
				array_push($getdata,"");
}
}	}
	
	$lrList = call_user_func_array(array($db,'getLr'), $getdata);	
	
	$lr=array();
	
	$outputfields =  array("id","lrno","self","date","paymentmode","paymenttype","parcelcount","weight","origin","source","baddress","destination","dest","scode","dcode","mainstation","mainstationcode","consigner","consignergst","consignee","consigneegst","consigneraddress","consigneeaddress","fromphno","tophno","description","valueamount","freight","deliverycharge","deliverydate","total","pettubadi","dstatus","statusid","status","taxpaidby","taxpaidbyvalue","package","payment","paymentid","memo","memodate","memostation","lorryno","refby","ewaybill","invoiceno","deliverytype","cancel","created_by","lrtype","created","updated");
	
	$qryfields =array("id","lrno","self","date","paymentMode","paymenttype","parcelCount","weight","origin","source","baddress","destination","dest","scode","dcode","mainstation","mainstationcode","consigner","consignergst","consignee","consigneegst","consignerAddress","consigneeAddress","fromphno","tophno","description","valueAmount","freight","deliveryCharge","deliveryDate","total","pettubadi","dstatus","statusid","status","taxPaidBy","taxPaidByPerson","package","payment","paymentid","memo","memodate","memostation","lorryno","refby","ewaybill","invoiceno","deliveryType","cancel","created_by","lrtype","created","updated");
	// looping through result and preparing tasks array
	for($i=0;$i<sizeOf($lrList);$i++){
		$tmp = array();		
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($lrList[$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $lrList[$i][$outputfields[$j]];
			}
		}
		
		$tmp["charges"] = array();
		$charges= array();
	$params = $db->getFunctionParam("othercharges");
	$getchargedata = array();
		for($k=0;$k<sizeof($params);$k++){
			if($params[$k] == "lrid"){
				array_push($getchargedata,$lrList[$i]["id"]);
			}else{
				array_push($getchargedata,"");
			}
	}
	$chargeList = call_user_func_array(array($db,'getOtherCharges'), $getchargedata);	
	$outputChargefields =  array("id","name","type","amount","created","updated");
	
	$qryChargefields =array("id","name","type","amount","created","updated");
	for($k=0;$k<sizeOf($chargeList);$k++){
	
		for($j = 0;$j<sizeof($qryChargefields);$j++){
		if(isset($chargeList[$k][$outputChargefields[$j]])){
			$charges[$qryChargefields[$j]] = $chargeList[$k][$outputChargefields[$j]];
			}
		}
		array_push($tmp["charges"], $charges);
	}
		array_push($lr, $tmp);
	}

	$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	$response['total'] = call_user_func_array(array($db,'getLr'), $getdata)[0]["count(*)"];
	$response["message"] = "Successfully fetched lr list";
	$response["count"] = sizeOf($lr);
	$response["lr"] =$lr;
	echoRespnse(200, $response);
});	


$app->get('/lr/:id', function($id) use($app){	
	$response = array();
	// fetching all products
	$db = new DbHandler();
	$params = $db->getFunctionParam("lr");
	$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "id"){
				array_push($getdata,$id);
			}else{
				array_push($getdata,"");
			}
	}
	$lrList = call_user_func_array(array($db,'getLr'), $getdata);	
	if(sizeof($lrList)>0){
	$lr=array();

	$outputfields =  array("id","lrno","self","date","paymentmode","paymenttype","parcelcount","weight","origin","source","baddress","destination","dest","scode","dcode","mainstationname","mainstationcode","consigner","consignergst","consignee","consigneegst","consigneraddress","consigneeaddress","fromphno","tophno","description","valueamount","freight","deliverycharge","deliverydate","total","pettubadi","dstatus","statusid","status","taxpaidby","taxpaidbyvalue","package","payment","paymentid","memo","memostation","memodate","lorryno","refby","ewaybill","invoiceno","deliverytype","cancel","lrtype","created_by","created","updated");
	
		$qryfields =array("id","lrno","self","date","paymentMode","paymenttype","parcelCount","weight","origin","source","baddress","destination","dest","scode","dcode","mainstationname","mainstationcode","consigner","consignergst","consignee","consigneegst","consignerAddress","consigneeAddress","fromphno","tophno","description","valueAmount","freight","deliveryCharge","deliveryDate","total","pettubadi","dstatus","statusid","status","taxPaidBy","taxpaidbyvalue","package","payment","paymentid","memo","memostation","memodate","lorryno","refby","ewaybill","invoiceno","deliveryType","cancel","lrtype","created_by","created","updated");
	// looping through result and preparing tasks array
	for($i=0;$i<sizeOf($lrList);$i++){
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($lrList[$i][$outputfields[$j]])){
			$lr[$qryfields[$j]] = $lrList[$i][$outputfields[$j]];
			}
		}
	}
$lr["charges"] = array();
	$params = $db->getFunctionParam("othercharges");
	$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "lrid"){
				array_push($getdata,$id);
			}else{
				array_push($getdata,"");
			}
	}
	$chargeList = call_user_func_array(array($db,'getOtherCharges'), $getdata);	
	$outputfields =  array("id","name","type","amount","created","updated");
	
	$qryfields =array("id","name","type","amount","created","updated");
	for($i=0;$i<sizeOf($chargeList);$i++){
	$tmp = array();
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($chargeList[$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $chargeList[$i][$outputfields[$j]];
			}
		}
		array_push($lr["charges"],$tmp);
	}
	$response['status'] = "success";
	$response["message"] = "Successfully fetched lr detail";
	$response["lrdetail"] =$lr;
	echoRespnse(200, $response);
	}else{
	$response['status'] = "error";
	$response["message"] = "No lr found with given detail.";
	$response["code"] = "NOTEXIST";
	$response["lrdetail"] = array();
	echoRespnse(200, $response);
	}
	
});	

$app->put('/lr/:id', function($id) use ($app) {
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$bankEntry = array();
	$params = $db->getFunctionParam("lr");
	$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "id"){
				array_push($getdata,$id);
			}else{
				array_push($getdata,"");
			}
	}
	$lrDetail = call_user_func_array(array($db,'getLr'), $getdata);

	if(sizeOf($lrDetail)>0){
$data = array();
if($lrDetail[0]["lrtype"] == putParam($r,"lrtype")){
 if(putParam($r,"lrtype") == 1){
  if(($lrDetail[0]["source"] != putParam($r,"source")) && putParam($r,"source")){
   $data =  getNextLRNo($db,putParam($r,"source"));
  }else{
   $data["lrno"] = putParam($r,"lrno");
  }
 }else{
   $data["lrno"] = putParam($r,"lrno");
}
}

if($lrDetail[0]["lrtype"] != putParam($r,"lrtype")){

if(putParam($r,"lrtype") == 1){
   $data =  getNextLRNo($db,putParam($r,"source"));

}else{
   $data["lrno"] = putParam($r,"lrno");

}
}


		$params = $db->putFunctionParam("lr");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
if($params[$i] == "lrno"){
				array_push($putdata,$data["lrno"]);
}else{
			if(putParam($r,$params[$i]) || putParam($r,$params[$i]) == 0){
				array_push($putdata,putParam($r,$params[$i]));
			}else{
				array_push($putdata,"");
			}
	}}
				array_push($putdata,"");
		
		
		$editDetail = call_user_func_array(array($db,'editLr'), $putdata);
	
		if($editDetail['status'] == SUCCESS){
$charges = putParam($r,"charges");
if($charges){
	
	$params = $db->getFunctionParam("othercharges");
	$getchargedata = array();
		for($k=0;$k<sizeof($params);$k++){
			if($params[$k] == "lrid"){
				array_push($getchargedata,$id);
			}else{
				array_push($getchargedata,"");
			}
	}
	$oldcharges = call_user_func_array(array($db,'getOtherCharges'), $getchargedata);	
	
		$outputfields = array("id","name","type","amount","created","updated");
		for($j=0;$j<sizeOf($oldcharges);$j++){
			$action = "";
			$deletedCharge = true;//assume that this charge is been deleted
			$updateCharge = false;//assume that no changes is to be done for this charge
			if($charges){//if charge is provided then find in the list
				$chargedata = array();
				$ind = getEditObject($oldcharges[$j]["id"],$charges);
				if($ind>=0){//if charge exist then get the index
				$deletedCharge = false;// ass charge found so less chance of deleting
					$chargedata = json_decode(json_encode($charges), True);
					
					for($k=0;$k<sizeOf($outputfields);$k++){
						
						if($oldcharges[$j][$outputfields[$k]] != $chargedata[$ind][$outputfields[$k]]){
							$updateCharge = true;
							$action = "update";
							break;
						}
					}
					if($updateCharge){//if($action == "update")
						$params = $db->putFunctionParam("othercharges");
		$updateField = array();
		$updateField["id"] = $oldcharges[$j]["id"];
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
		if($params[$i]!="lrid"){
			//if($params[$i] == "total"){
				array_push($putdata,$chargedata[$ind][$params[$i]]);
				}else{
				array_push($putdata,"");
				}
			//}else{
				//array_push($putdata,"");
			//}
	}
				array_push($putdata,"");
		
		
		$editDetail = call_user_func_array(array($db,'editOtherCharges'), $putdata);
		//update total in lr detail end
		
						//$editProduct= $db->editSalesInvoiceProducts($productdata[$ind]["id"],"",$productdata[$ind]["prid"],"",$productdata[$ind]["qty"],$productdata[$ind]["price"],$productdata[$ind]["free"],$productdata[$ind]["dis"],"");
					}
					
					unset($charges[$ind]);
					$charges =  array_values($charges);
				}
				else{
					$deletedCharge = true;
				}
				}
				else{//if they removed the product from array
				$deletedCharge = true;
				$action = "delete";
			}	
			if($deletedCharge){//if($action == "delete"){//if product is deleted
				$params = $db->putFunctionParam("othercharges");
		$updateField = array();
		$updateField["id"] = $oldcharges[$j]["id"];
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
		
				array_push($putdata,"");
	
	}
				array_push($putdata,1);
		
		
		$editDetail = call_user_func_array(array($db,'editOtherCharges'), $putdata);
		
			}
			
		}
		$charges = json_decode(json_encode($charges), True);
			
		if($charges && sizeof($charges)>0){
		for($i=0;$i<sizeof($charges);$i++){
		$chargesid = $db->createOtherCharges($id, $charges[$i]);
		}
			
		}
}

if($lrDetail[0]["lrtype"] == putParam($r,"lrtype")){
	if(putParam($r,"lrtype") == 1){
	if(($lrDetail[0]["source"] != putParam($r,"source")) && putParam($r,"source")){
				setNextLRNo($db,putParam($r,"source"),$data["field"],$data["next"]);
				$params = $db->getFunctionParam("city");	
				$getdata = array();
				for($i=0;$i<sizeof($params);$i++){
					if($params[$i] == "id"){
						array_push($getdata,$lrDetail[0]["source"]);
					}else{
						array_push($getdata,"");
					}
				}

				$cityList = call_user_func_array(array($db,'getCity'), $getdata);
				$nextvacant= array();

				if($cityList[0]["vacant"]){
					$nextvacant = explode(",",$cityList[0]["vacant"]);
					array_push($nextvacant,$lrDetail[0]["lrno"]);
				}else{
					$nextvacant= array($lrDetail[0]["lrno"]);				
				}
				setNextLRNo($db,$lrDetail[0]["source"],"vacant",join(",",$nextvacant));
			}

	}else{

	}
}

if(($lrDetail[0]["lrtype"] != putParam($r,"lrtype")) && putParam($r,"lrtype")){
	if(putParam($r,"lrtype") == 1){
setNextLRNo($db,putParam($r,"source"),$data["field"],$data["next"]);
	}else{

$params = $db->getFunctionParam("city");
		$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "id"){
				array_push($getdata,$lrDetail[0]["source"]);
			}else{
				array_push($getdata,"");
			}
		}

		$cityList = call_user_func_array(array($db,'getCity'), $getdata);
$nextvacant= array();

if($cityList[0]["vacant"]){
$nextvacant = explode(",",$cityList[0]["vacant"]);

array_push($nextvacant,$lrDetail[0]["lrno"]);

}else{
$nextvacant= array($lrDetail[0]["lrno"]);
}

setNextLRNo($db,$lrDetail[0]["source"],"vacant",join(",",$nextvacant));

}}



if(putParam($r,"consignergst")){
//save or edit customer info
$customer = array();
$customer["gst"] = putParam($r,"consignergst");
$customer["name"] = putParam($r,"consigner");
$customer["address"] = putParam($r,"consignerAddress");
$customer["city"] = putParam($r,"source");
$customer["phone"] = putParam($r,"fromphno");


$params = $db->getFunctionParam("customer");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "gst"){
				array_push($getdata,$customer["gst"]);
			}else{
				array_push($getdata,"");
			}
	}

	$customerList = call_user_func_array(array($db,'getCustomer'), $getdata);	
		

if(sizeof($customerList)>0){//check whether to edit or not
if(($customerList[0]["name"] != $customer["name"]) || ($customerList[0]["address"] != $customer["address"]) ||($customerList[0]["city"] != $customer["city"]) || ($customerList[0]["phone"] != $customer["phone"])){
$params = $db->putFunctionParam("customer");

		$updateField = array();
		$updateField["id"] = $customerList[0]["id"];
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
if(isset($customer[$params[$i]])){
				array_push($putdata,$customer[$params[$i]]);
				}else{
								array_push($putdata,"");
				}
	}
				array_push($putdata,"");
		

		$editDetail = call_user_func_array(array($db,'editCustomer'), $putdata);

}


}else{
  $customerCreate = $db->createCustomer($customer);
}
}
if(putParam($r,"consigneegst")){
//consignee details
$customer["gst"] = putParam($r,"consigneegst");
$customer["name"] = putParam($r,"consignee");
$customer["address"] = putParam($r,"consigneeAddress");
$customer["city"] = putParam($r,"destination");
$customer["phone"] = putParam($r,"tophno");


$getdata[1] = $customer["gst"];
$customerList = call_user_func_array(array($db,'getCustomer'), $getdata);	
if(sizeof($customerList)>0){//check whether to edit or not
if(($customerList[0]["name"] != $customer["name"]) || ($customerList[0]["address"] != $customer["address"]) ||($customerList[0]["city"] != $customer["city"]) || ($customerList[0]["phone"] != $customer["phone"])){
$params = $db->putFunctionParam("customer");
		$updateField = array();
		$updateField["id"] = $customerList[0]["id"];
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
			if(isset($customer[$params[$i]])){
				array_push($putdata,$customer[$params[$i]]);
				}else{
								array_push($putdata,"");
				}
			
	}
				array_push($putdata,"");
		
		
		$editDetail = call_user_func_array(array($db,'editCustomer'), $putdata);
}


}else{
  $customerCreate = $db->createCustomer($customer);
}
}






			$response["error"] = false;
			$response["status"] ="success";
			$response["id"] =$id;
			$response["message"] = "Woot!,Successfully edited lr information";
		}
		else{
			$response["error"] = true;
			$response["status"] ="error";
			$response["message"] = "Oops! An error occurred while editing lr information";
			$response["err"]=$editDetail;
		}
	}else{
		$response["error"] = true;
		$response["status"] ="error";
		$response["message"] = "No LR found with given ID";
	}
	echoRespnse(200, $response);
});


$app->put('/lrs', function() use ($app) {
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	
		$params = $db->putFunctionParam("lr");
		$updateData = putParam($r,"updateData");
		for($u=0;$u<sizeof($updateData);$u++){
		$updateField = array();
		$updateField[putParam($r,"updateField")] = $updateData[$u];
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){

			if(putParam($r,$params[$i]) || putParam($r,$params[$i]) == 0){
				array_push($putdata,putParam($r,$params[$i]));
			}else{
				array_push($putdata,"");
			}
	}
				array_push($putdata,"");
		
		
		$editDetail = call_user_func_array(array($db,'editLr'), $putdata);
}
		
			$response["error"] = false;
			$response["status"] ="success";
		
			$response["message"] = "Woot!,Successfully edited lr information";
		
	echoRespnse(200, $response);
});

$app->get('/lrcharges', function() use($app){	
 $db = new DbHandler();	
	$params = $db->getFunctionParam("othercharges");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
			if(getParams($app->request->get($params[$i]))){
				array_push($getdata,getParams($app->request->get($params[$i])));
			}else{
				array_push($getdata,"");
			}
	}
	$List= call_user_func_array(array($db,'getOtherCharges'), $getdata);
$datas=array();

     
	$outputfields =   array("id","name","type","amount","lrno","date","total","taxpaidby","created","updated");
	
	$qryfields = array("id","name","type","amount","lrno","date","total","taxpaidby","created","updated");
	// looping through result and preparing tasks array
	for($i=0;$i<sizeOf($List);$i++){
		$tmp = array();		
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($List[$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $List[$i][$outputfields[$j]];
			}
		}
		array_push($datas, $tmp);
	}

	$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	$response['total'] = call_user_func_array(array($db,'getOtherCharges'), $getdata)[0]["count(*)"];
	$response["message"] = "Successfully fetched charges list";
	$response["count"] = sizeOf($datas);
	$response["charges"] =$datas;
	echoRespnse(200, $response);	
});

$app->post('/payment/:id', 'authenticate', function($id) use ($app){
     $db = new DbHandler();	
	$params = $db->getFunctionParam("lr");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "id"){
				array_push($getdata,$id);
			}else{
				array_push($getdata,"");
			}
	}
	
	$lrList = call_user_func_array(array($db,'getLr'), $getdata);	
echoRespnse(200, $lrList);
});


$app->delete('/payment/:id', 'authenticate', function($id) use ($app)
{
    $db = new DbHandler();	
$response= array();
	$params = $db->getFunctionParam("lr");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "id"){
				array_push($getdata,$id);
			}else{
				array_push($getdata,"");
			}
	}
	
	$lrList = call_user_func_array(array($db,'getLr'), $getdata);	

if(sizeof($lrList)>0){
if($lrList[0]["paymentid"]){
$paymentId = $lrList[0]["paymentid"];
$params = $db->putFunctionParam("lr");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
if($params[$i] == "paymentId"){
				array_push($putdata,-1);
}else{
				array_push($putdata,"");
			}
	}
				array_push($putdata,"");
		
		
		$editDetail = call_user_func_array(array($db,'editLr'), $putdata);

			$response["error"] = false;
			$response["status"] ="success";
			$response["id"] =$id;
			$response["message"] = "Woot!,Successfully deleted payment receipt";
}else{
$response["error"] = true;
		$response["status"] ="error";
		$response["message"] = "Payment is not enabled for given LR";

}
}else{
$response["error"] = true;
		$response["status"] ="error";
		$response["message"] = "No LR found with given ID";
}
 
    echoRespnse(200, $response);
});

$app->get('/payment/:lrid', 'authenticate', function($id) use ($app){
 $db = new DbHandler();	
$response= array();
	$params = $db->getFunctionParam("lr");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "id"){
				array_push($getdata,$id);
			}else{
				array_push($getdata,"");
			}
	}
	
	$lrList = call_user_func_array(array($db,'getLr'), $getdata);	
if(sizeof($lrList)>0){
if(!$lrList[0]["paymentid"]){//generate paymentid

    $params = $db->getFunctionParam("city");
		$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "id"){
				array_push($getdata,$lrList[0]["destination"]);
			}else{
				array_push($getdata,"");
			}
		}

		$cityList = call_user_func_array(array($db,'getCity'), $getdata);	
$payment=$cityList[0]["payment"];
		if(!$payment){$payment=1;}
$limits=array();
		
		$params = $db->getFunctionParam("limit");
		$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "table"){
				array_push($getdata,"lrdetail");
			}else{
			if($params[$i] == "field"){
				array_push($getdata,"payment");
			}else{
				array_push($getdata,"");
				}
			}
		}
	
		$limitList = call_user_func_array(array($db,'getLimit'), $getdata);	
		if($payment>$limitList[0]["size"] || !$payment){
		$payment=1;
		}
$now = new DateTime();
$deliverydate =  $now->format('Y-m-d');

$params = $db->putFunctionParam("lr");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "paymentId"){
				array_push($putdata,$payment);
			}else{
if($params[$i] == "deliveryDate"){
				array_push($putdata,$deliverydate);
			}else{
				array_push($putdata,"");
}
			}
	}
				array_push($putdata,"");
		
		
		$editDetail = call_user_func_array(array($db,'editLr'), $putdata);
$lrList[0]["paymentid"] = $payment;
setNextLRNo($db,$lrList[0]["destination"],"payment",++$payment);

}


	
	$lr=array();
	$outputfields =  array("id","lrno","self","date","paymentmode","paymenttype","parcelcount","weight","origin","source","destination","dest","scode","dcode","consigner","consignergst","consignee","consigneegst","consigneraddress","consigneeaddress","fromphno","tophno","description","valueAmount","freight","deliverycharge","deliverydate","total","dstatus","statusid","status","taxpaidby","taxpaidbyvalue","payment","paymentid","memo","refby","created_by","created","updated");
	
		$qryfields =array("id","lrno","self","date","paymentMode","paymenttype","parcelCount","weight","origin","source","destination","dest","scode","dcode","consigner","consignergst","consignee","consigneegst","consignerAddress","consigneeAddress","fromphno","tophno","description","valueAmount","freight","deliveryCharge","deliveryDate","total","dstatus","statusid","status","taxPaidBy","taxpaidbyvalue","payment","paymentid","memo","refby","created_by","created","updated");
	// looping through result and preparing tasks array
	for($i=0;$i<sizeOf($lrList);$i++){
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($lrList[$i][$outputfields[$j]])){
			$lr[$qryfields[$j]] = $lrList[$i][$outputfields[$j]];
			}
		}
	}
$lr["charges"] = array();
	$params = $db->getFunctionParam("othercharges");
	$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "lrid"){
				array_push($getdata,$id);
			}else{
				array_push($getdata,"");
			}
	}
	$chargeList = call_user_func_array(array($db,'getOtherCharges'), $getdata);	
	$outputfields =  array("id","name","type","amount","created","updated");
	
	$qryfields =array("id","name","type","amount","created","updated");
	for($i=0;$i<sizeOf($chargeList);$i++){
	$tmp = array();
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($chargeList[$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $chargeList[$i][$outputfields[$j]];
			}
		}
		array_push($lr["charges"],$tmp);
	}
	$response['status'] = "success";
	$response["message"] = "Successfully fetched lr detail";
	$response["lrdetail"] =$lr;
	echoRespnse(200, $response);
	

}else{
$response['status'] = "error";
	$response["message"] = "No lr found with given detail.";
	$response["code"] = "NOTEXIST";
 echoRespnse(404, $response);
}

});

$app->get('/payment_delete/:lrid', 'authenticate', function($id) use ($app){
 $response = array();
    $db       = new DbHandler();
$lr       = $db->getlrDelivery($id, "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "","a.paymentid","desc");
  $session             = $db->getSession();
if(isset($session['uid']))
	{
		$role = $session['role'];
		$branch = $session['branch'];
		
	}
if($role >1){
			$destination = $branch;
		}else{
		$destination = $lr[0]["destination"];
		}
if($lr[0]["destination"] ==$destination){
if(!$lr[0]["paymentid"]){//generate paymentid
$paymentid = 0;


$pid       = $db->getlrDelivery("", "", "", "", "", "", "", "", "", $lr[0]["destination"], "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "","a.paymentid","desc");
$paymentid = ++$pid[0]['paymentid'];
$now = new DateTime();
$deliverydate =  $now->format('Y-m-d');

 $res = $db->updatelr($id, "", "", "", "", "", "", "", "", "", "", "", "", "", "", "","", "", "", "", "", "", "", 3, "",$paymentid,$deliverydate);

}
$lr       = $db->getlrDelivery($id, "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "","a.paymentid","desc");
$paymentdetail = array();
// looping through result and preparing tasks array
    for ($i = 0; $i < sizeOf($lr); $i++) {
        $tmp                     = array();
        $tmp["id"]               = $lr[$i]["id"];
        $tmp["lrno"]             = $lr[$i]["lrno"];
        $tmp["self"]             = $lr[$i]["self"];
        $tmp["date"]             = $lr[$i]["date"];
        $tmp["paymentMode"]      = $lr[$i]['paymentmode'];
        $tmp["paymenttype"]      = $lr[$i]['paymenttype'];
        $tmp["parcelCount"]      = $lr[$i]['parcelcount'];
        $tmp["weight"]           = $lr[$i]["weight"];
        $tmp["sourcename"]       = $lr[$i]["origin"];
        $tmp["source"]           = $lr[$i]["source"];
        $tmp["destination"]      = $lr[$i]["destination"];
        $tmp["destinationname"]  = $lr[$i]["dest"];
        $tmp["scode"]            = $lr[$i]["scode"];
        $tmp["dcode"]            = $lr[$i]["dcode"];
        $tmp["sphno"]            = $lr[$i]["sphno"];
        $tmp["dphno"]            = $lr[$i]["dphno"];
        $tmp["consigner"]        = $lr[$i]["consigner"];
        $tmp["consignee"]        = $lr[$i]["consignee"];
        $tmp["consignerAddress"] = $lr[$i]["consigneraddress"];
        $tmp["consigneeAddress"] = $lr[$i]["consigneeaddress"];
        $tmp["fromNumber"]       = $lr[$i]["fromphno"];
        $tmp["toNumber"]         = $lr[$i]["tophno"];
        $tmp["description"]      = $lr[$i]["description"];
        $tmp["valueAmount"]      = $lr[$i]["valueamount"];
        $tmp["freight"]          = $lr[$i]["freight"];
        $tmp["otherCharges"]     = $lr[$i]["othercharges"];
        $tmp["otherChargesType"] = $lr[$i]["otherchargestype"];
        $tmp["serviceCharge"]    = $lr[$i]["servicecharge"];
 $tmp["deliveryCharge"]    = $lr[$i]["deliverycharge"];        
        $tmp["total"]            = $lr[$i]["total"];
        $tmp["payment"]          = $lr[$i]["payment"];
        $tmp["dstatus"]          = $lr[$i]["dstatus"];
        $tmp["statusid"]         = $lr[$i]["statusid"];
        $tmp["created"]          = $lr[$i]["created"];
        $tmp["refby"]            = $lr[$i]["refby"];
        $tmp["paymentid"]            = $lr[$i]["paymentid"];
        $tmp["deliverydate"]            = $lr[$i]["deliverydate"];

		$charges      = $db->getLrCharges("", $id, "","","","", "", "");
		$tmp["charges"] = array();

		for($j=0;$j<sizeof($charges);$j++){
			array_push($tmp["charges"],$charges[$j]);
		}
        array_push($paymentdetail, $tmp);
    }
$response["payment"] = $paymentdetail;
    $response["message"] = "successfully fetched payment detail ";
    $response["status"]  = "success";
     
}else{

    $response["message"] = "You are not eligible to create payment for this ";
    $response["status"]  = "error";
}
   
    echoRespnse(200, $response);
});

$app->delete('/lr/:id', 'authenticate', function($id) use ($app)
{
    $r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("lr");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
	   if($params[$i] == "id"){
	     array_push($getdata,$id);
	   }else{
	     array_push($getdata,"");
	   }
	}
	$lrDetail = call_user_func_array(array($db,'getLr'), $getdata);

        if(sizeof($lrDetail)>0){
$params = $db->putFunctionParam("lr");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
				array_push($putdata,"");

	}
				array_push($putdata,1);
$editDetail = call_user_func_array(array($db,'editLr'), $putdata);
	
		if($editDetail['status'] == SUCCESS){
if($lrDetail[0]["lrtype"]==1){
//set the lr no as vacant to use next time
$params = $db->getFunctionParam("city");
		$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "id"){
				array_push($getdata,$lrDetail[0]["source"]);
			}else{
				array_push($getdata,"");
			}
		}

		$cityList = call_user_func_array(array($db,'getCity'), $getdata);
$nextvacant= array();

if($cityList[0]["vacant"]){
$nextvacant = explode(",",$cityList[0]["vacant"]);

array_push($nextvacant,$lrDetail[0]["lrno"]);

}else{
$nextvacant= array($lrDetail[0]["lrno"]);
}


setNextLRNo($db,$lrDetail[0]["source"],"vacant",join(",",$nextvacant));
}
$response["error"] = false;
			$response["status"] ="success";
			$response["id"] =$id;
			$response["message"] = "Woot!,Successfully deleted LR information";
		}
		else{
			$response["error"] = true;
			$response["status"] ="error";
			$response["message"] = "Oops! An error occurred while deleting LR information";
			$response["err"]=$editDetail;
		}


        }else{
            $response["error"] = true;
	    $response["status"] ="error";
	    $response["message"] = "No LR found with given ID";
        }
        echoRespnse(201, $response);

});


$app->get('/distinct', function() use($app){	
	$response = array();
	// fetching all products
	$db = new DbHandler();
		
$entity = getParams($app->request->get("entity"));
$field = getParams($app->request->get("field"));
$where = getParams($app->request->get("where"));
	
	
	$distinctList = $db->getDistinct($entity,$field,$where);
	
	$data=array();
	
	
	for($i=0;$i<sizeOf($distinctList);$i++){
		$tmp = array();		



		
				array_push($data,$distinctList[$i][$field]);
			
	}
	

	
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getLr'), $getdata)[0]["count(*)"];
	$response["message"] = "Successfully fetched lr list";
	$response["count"] = sizeOf($data);
	$response[$field] =$data;
	echoRespnse(200, $response);
});	

$app->get('/export', 'authenticate', function() use ($app)
{
    $response = array();
    
    $db = new DbHandler();
    
    $id          = getParam($app->request->get('id'));
    $lrno        = getParam($app->request->get('lrno'));
    $self        = getParam($app->request->get('self'));
    $to_date     = getParam($app->request->get('to_date'));
    $from_date   = getParam($app->request->get('from_date'));
    $paymentmode = getParam($app->request->get('paymentmode'));
    
    $parcelcount      = getParam($app->request->get('parcelcount'));
    $weight           = getParam($app->request->get('weight'));
    $source           = getParam($app->request->get('source'));
    $destination      = getParam($app->request->get('destination'));
    $consigner        = getParam($app->request->get('consigner'));
    $consignee        = getParam($app->request->get('consignee'));
    $consigneraddress = getParam($app->request->get('consigneraddress'));
    $consigneeaddress = getParam($app->request->get('consigneeaddress'));
    $fromphno         = getParam($app->request->get('fromphno'));
    $tophno           = getParam($app->request->get('tophno'));
    $description      = getParam($app->request->get('description'));
    $valueamount      = getParam($app->request->get('valuemaount'));
    $freight          = getParam($app->request->get('freight'));
    $othercharges     = getParam($app->request->get('othercharges'));
    $otherchargestype = getParam($app->request->get('otherchargestype'));
    $servicecharge    = getParam($app->request->get('servicecharge'));
    $from_total       = getParam($app->request->get('from_total'));
    $to_total         = getParam($app->request->get('to_total'));
    $status           = getParam($app->request->get('status'));
    $payment          = getParam($app->request->get('payment'));
    $limit            = getParam($app->request->get('limit'));
    $offset           = getParam($app->request->get('offset'));
    $deliverydate           = getParam($app->request->get('deliverydate'));
    
    
    $db            = new DbHandler();
    $lr            = $db->getlr($id, $lrno, $self, $to_date, $from_date, $paymentmode, $parcelcount, $weight, $source, $destination, $consigner, $consignee, $consigneraddress, $consigneeaddress, $fromphno, $tophno, $description, $valueamount, $freight, $othercharges, $otherchargestype, $servicecharge, $from_total, $to_total, $status, $payment,$deliverydate, $limit, $offset,"","");
    $lrdetail      = array();
    $fieldsHeading = array(
        "Id",
        "L.R. No",
        "Self",
        "Date",
        "Parcel count",
        "Weight",
        "Consigner",
        "Origin",
        "From phno",
        "Consigner Address",
        "Consignee",
        "Destination",
        "To phno",
        "Consigner Address",
        "Description",
        "Value Amount",
        "Freight",
        "Service Charge",
        "Other charges",
        "Total Amount",
        "Payment Status",
        "Ref. by",
        "Delivery Status",
        "Payment Type",
        "Created at",
        "Updated at"
    );
    $fields        = array(
        "id",
        "lrno",
        "self",
        "date",
        "parcelcount",
        "weight",
        "consigner",
        "origin",
        "fromphno",
        "consigneraddress",
        "consignee",
        "dest",
        "tophno",
        "consigneraddress",
        "description",
        "valueamount",
        "freight",
        "servicecharge",
        "othercharges",
        "total",
        "payment",
        "refby",
        "dstatus",
        "paymenttype",
        "created",
        "updated"
    );
    $filename      = "lrdatafrom" . $from_date . "to" . $to_date . ".csv";
    $filepath      = "../../export/" . $filename;
    
    $fp = fopen($filepath, "w");
    
    $seperator = "";
    $comma     = "";
    
    for ($i = 0; $i < sizeof($fieldsHeading); $i++) {
        $seperator .= $comma . '' . str_replace('', '""', $fieldsHeading[$i]);
        $comma = ",";
    }
    
    $seperator .= "\n";
    fputs($fp, $seperator);
    for ($i = 0; $i < sizeOf($lr); $i++) {
        $tmp       = array();
        $seperator = "";
        $comma     = "";
        
        for ($j = 0; $j < sizeof($fields); $j++) {
            $seperator .= $comma . '' . str_replace('', '""', $lr[$i][$fields[$j]]);
            $comma = ",";
        }
        
        $seperator .= "\n";
        fputs($fp, $seperator);
    }
    fclose($fp);
    $response["count"]    = sizeOf($lr);
    $response["filename"] = "export/" . $filename;
    $response["status"]   = "success";
    $response["message"]  = "Total " . sizeof($lr) . " records been exported";
    echoRespnse(200, $response);
});



$app->get('/ctdexport', 'authenticate', function() use ($app)
{
    $response = array();
    
    $db = new DbHandler();
$params = $db->getFunctionParam("lr");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
	if(getParams($app->request->get($params[$i]))){
				array_push($getdata,getParams($app->request->get($params[$i])));
			}else{
			if(getParams($app->request->get($params[$i])) == "0"){
				array_push($getdata,0);
			}else{
				array_push($getdata,"");
}
}	
}
	$lr = call_user_func_array(array($db,'getLr'), $getdata);	
	

	
	  $fieldsHeading = array(
        "Sl",
       
        "Consignor GST",
"Name",
"Address",
 "Consignee GST",
"Name",
"Address",
 "Date of Booking",
 "Date of Delivery",
"No. of items",
 "Commodity",
        "Goods Value(Rs.)"
    );
    $fields        = array(
        "id",
        
        "consignergst",
        "consigner",
        "consigneraddress",
        "consigneegst",
        "consignee",
        "consigneeaddress",
        "date",
        "deliverydate",
        "parcelcount",
        "description",
        "valueamount"
    );

$from_date=json_decode(getParams($app->request->get("date")), True)["value"];
$to_date=json_decode(getParams($app->request->get("date")), True)["value1"];
    $filename      = "lrdatafrom" . $from_date . "to" . $to_date . ".csv";
    $filepath      = "../../export/" . $filename;
    
    $fp = fopen($filepath, "w");
    
    $seperator = "";
    $comma     = "";
     fputcsv($fp, $fieldsHeading);
    for ($i = 0; $i < sizeOf($lr); $i++) {
        $tmp       = array();
        $seperator = "";
        $comma     = "";
        $lr[$i]["id"] = $i+1;
$lrno = "-".$lr["$i"]["lrno"];
$lr["$i"]["lrno"] = $lr[$i]["scode"];
if($lr[$i]["lrtype"] == 2){
$lr["$i"]["lrno"].= "M";

}
$lr["$i"]["lrno"].=$lrno;
$temp=array();
        for ($j = 0; $j < sizeof($fields); $j++) {
            array_push($temp,$lr[$i][$fields[$j]]);
        }
     fputcsv($fp, $temp);   
        
    }
    fclose($fp);
    $response["count"]    = sizeOf($lr);
    $response["filename"] = "export/" . $filename;
    $response["status"]   = "success";
    $response["message"]  = "Total " . sizeof($lr) . " records been exported";
    echoRespnse(200, $response);
});


$app->get('/session', function()
{$response=array();
    $db                  = new DbHandler();
    $session             = $db->getSession();
    $response["uid"]     = $session['uid'];
    $response["name"]    = $session['name'];
    $response["firstname"]    = $session['firstname'];
    $response["lastname"]    = $session['lastname'];
    $response["phone"]    = $session['phone'];
    $response["role"]    = $session['role'];
    $response["rolename"]    = $session['rolename'];
    $response["branch"]    = $session['branch'];
    $response['branchname']    = $session['branchname'];
    $response['mstation']    = $session['mstation'];
    $response['firm']    = $session['firm'];
    $response['gstin']    = $session['gstin'];
	$response['address']    = $session['address'];
    echoRespnse(200, $response);
});
/**
 * Verifying required params posted or not
 */


$app->get('/paymentmode', 'authenticate', function() use($app){	
	$response = array();
	// fetching all products
	$db = new DbHandler();
		
	$params = $db->getFunctionParam("paymentmode");
	$getdata = array();
	for($i=0;$i<sizeof($params);$i++){
			if(getParams($app->request->get($params[$i]))){
				array_push($getdata,getParams($app->request->get($params[$i])));
			}else{
				array_push($getdata,"");
			}
	}

	$List = call_user_func_array(array($db,'getPaymentMode'), $getdata);	

	$data=array();
	 
     
	$outputfields =  array("id","type","created","updated");
	
	$qryfields =array("id","type","created","updated");
	// looping through result and preparing tasks array
	for($i=0;$i<sizeOf($List);$i++){
		$tmp = array();		
		for($j = 0;$j<sizeof($qryfields);$j++){
		if(isset($List[$i][$outputfields[$j]])){
			$tmp[$qryfields[$j]] = $List[$i][$outputfields[$j]];
			}
		}
		array_push($data, $tmp);
	}

	$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	$response['total'] = call_user_func_array(array($db,'getPaymentMode'), $getdata)[0]["count(*)"];
	$response["message"] = "Successfully fetched payment mode list";
	$response["count"] = sizeOf($data);
	$response["paymentmodes"] =$data;
	echoRespnse(200, $response);
});	

$app->post('/login', function() use ($app)
{
    //	require_once 'passwordHash.php';
   
    $r = json_decode($app->request->getBody());
	verifyRequiredParams(array('name', 'password'));
    
    $response = array();
    $db = new DbHandler();
	  
	$today = date("Y-m-d H:i:s");
$date = "2019-04-20 00:00:00";

if ($date > $today) {
	$name = postParams($app->request->post("name"));
	$password = postParams($app->request->post("password"));
$user     = $db->getOneRecord("select *,a.name as name,c.name as branchname,f.name as firm from users a LEFT JOIN role  b on a.role=b.rid LEFT JOIN city c on a.branch=c.id join firm f where a.name='$name'");
    if ($user != NULL) {
        if (passwordHash::check_password($user['password'], $password)) {
            $response['status']    = "success";
            $response['message']   = 'Logged in successfully.';
            $response['name']      = $user['name'];
            $response['uid']       = $user['uid'];
            $response['createdAt'] = $user['created'];
            if (!isset($_SESSION)) {
                
                session_start();
            }
$_SESSION['lrsoftw'] = array();
            $_SESSION['lrsoftw']['uid']     = $user['uid'];
            $_SESSION['lrsoftw']['api_key'] = $user["api_key"]; //echo $_SESSION['api_key'];
            $_SESSION['lrsoftw']['name']    = $user['name'];
            $_SESSION['lrsoftw']['firstname']    = $user['firstname'];
            $_SESSION['lrsoftw']['lastname']    = $user['lastname'];
            $_SESSION['lrsoftw']['phone']    = $user['phone'];
            $_SESSION['lrsoftw']['role']    = $user['role'];
            $_SESSION['lrsoftw']['branch']    = $user['branch'];
            $_SESSION['lrsoftw']['branchname']    = $user['branchname'];
            $_SESSION['lrsoftw']['rolename']       = $user['type'];
            $_SESSION['lrsoftw']['firm']       = $user['firm'];
            $_SESSION['lrsoftw']['gstin']       = $user['gstin'];
			        $_SESSION['lrsoftw']['address']       = $user['address'];
       $_SESSION["lrsoftw"]['mstation'] = $user['mstation'];
        } else {
            $response['status']  = "error";
            $response['message'] = 'Login failed. Incorrect credentials';
        }
    } else {
        $response['status']  = "error";
        $response['message'] = 'No such user is registered';
    }
	}else{
	   $response['status']  = "error";
        $response['message'] = 'Your trial is been expired';
		}
    echoRespnse(200, $response);
});

$app->get('/logout', function()
{
    $db                  = new DbHandler();
    $session             = $db->destroySession();
    $response["status"]  = "info";
    $response["message"] = "Logged out successfully";
    echoRespnse(200, $response);
});


$app->post('/repairdata', 'authenticate', function() use ($app)
{
    $db         = new DbHandler();
    $dateformat = new DateTime('now', new DateTimeZone('Asia/Calcutta'));
    $month      = $dateformat->format('m') - 3;
    $year       = $dateformat->format('Y');
    if ($month < 1) {
        $month = 12 + $month;
        $year--;
    }
    $from_date = "";
    $to_date   = $year . "-" . $month . "-1";
	
    $lr            = $db->getlr("", "", "", $to_date, $from_date, "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "","","a.id","asc");
	
    $lrdetail      = array();
    $fieldsHeading = array(
         "Id",
        "L.R. No",
        "Self",
        "Date",
        "Payment Mode",
        "Parcel count",
        "Weight",
        "Consigner",
        "Origin",
        "From phno",
        "Consigner Address",
        "Consignee",
        "Destination",
        "To phno",
        "Consigner Address",
        "Description",
        "Value Amount",
        "Freight",
        "Service Charge",
        "Delivery Charge",
        "Other Charges",
        "Other Charges Type",
        "Total Amount",
        "Payment Status",
        "Payment ID",
       "Payment Type",
        "Ref. by",
        "Delivery Status",
        "Delivery Date",
        "Deleted",
        "Created at",
        "Updated at"
    );
    $fields        = array(
        "id",
        "lrno",
        "self",
        "date",
"paymentmode",
        "parcelcount",
        "weight",
        "consigner",
        "origin",
        "fromphno",
        "consigneraddress",
        "consignee",
        "dest",
        "tophno",
        "consigneraddress",
        "description",
        "valueamount",
        "freight",
        "servicecharge",
        "deliverycharge",
        "othercharges",
        "otherchargestype",
        "total",
        "payment",
        "paymentid",
        "paymenttype",
        "refby",
        "dstatus",
        "deliverydate",
        "deleted",
        "created",
        "updated"
    );
	
	
    $filename      = "lrdatafrom" . $from_date . "to" . $to_date . ".csv";
    $filepath      = "../../backup/" . $filename;
    backupData($fieldsHeading,$fields,$filepath,$lr);
    
	$charges            = $db->getLrCharges("", "", "","", $to_date, $from_date, "", "");
	
	$fieldsHeading = array(
        "Id",
        "L.R. ID",
		 "L.R. No",
        "Type ID",
		"Type Name",
        "Amount",
        "Created at",
        "Updated at"
    );
    $fields        = array(
        "id",
        "lrid",
		"lrno",
        "type",
		"chargename",
        "amount",
        "created",
        "updated"
    );
	
	
    $filename      = "chargesdatafrom" . $from_date . "to" . $to_date . ".csv";
    $filepath      = "../../backup/" . $filename;
    backupData($fieldsHeading,$fields,$filepath,$charges);
  //  for($i=0;$i<sizeof($charges);$i++){
	// echo $charges[$i]["id"];
	$db->deleteLrChargesPermanent("","","","",$from_date,$to_date);
	//}
     $db->deleteLrPermanent("", "", "", $to_date, $from_date, "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "");
    $db->backupLrTable();
});

$app->get('/distinctlr', 'authenticate', function() use ($app)
{
    $db = new DbHandler();
    $lr = $db->getDistinctlr();
    
    $response            = array();
    $response['status']  = "error";
    $response['message'] = 'No such user is registered';
    $response['data']    = $lr;
    echoRespnse(200, $response);
});



$app->post('/contact', function() use ($app)
{
    // check for required params
    
    
    verifyRequiredParams(array(
        'name',
        'email',
        'phone',
        'subject',
        'message'
    ));
    $response  = array();
    // reading post params
    $name      = $app->request->post('name');
    $email     = $app->request->post('email');
    $phone     = $app->request->post('phone');
    $subject   = $app->request->post('subject');
    $message   = $app->request->post('message');
    $db        = new DbHandler();
    $contactUs = $db->createContact($name, $email, $phone, $subject, $message);
    if ($contactUs > 0) {
        $response["error"]     = false;
        $response["message"]   = "Thanks for contacting us. We will get to you soon";
        $response["enquiryId"] = $contactUs;
    } else {
        $response["error"]   = true;
        $response["message"] = "Oops! An error occurred while submiting form";
    }
    // echo json response
    echoRespnse(201, $response);
});

$app->post('/format', 'authenticate', function() use ($app){
  $db        = new DbHandler();
    $format = $db->format();
 $response["error"]     = false;
        $response["message"]   = "Succesfully formatted";
 echoRespnse(201, $response);

});

/*function verifyRequiredParams($required_fields)
{
    $error          = false;
    $error_fields   = "";
    $request_params = array();
    $request_params = $_REQUEST;
    
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {

        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
    
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response            = array();
        $app                 = \Slim\Slim::getInstance();
        $response["error"]   = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}
*/


function verifyRequiredParams($required_fields) {

    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
	
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}


function verifyRequiredParams2($required_fields, $request_params)
{
    $error        = false;
    $error_fields = "";
    foreach ($required_fields as $field) {
        if (!isset($request_params->$field) || strlen(trim($request_params->$field)) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
    
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response            = array();
        $app                 = \Slim\Slim::getInstance();
        $response["status"]  = "error";
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(200, $response);
        $app->stop();
    }
}



function verifyJsonRequiredParams($required_fields, $request_params)
{
    $error        = false;
    $error_fields = "";
    
    //$request_params = array();
    //$request_params = $_REQUEST;
    //echo json_encode($_REQUEST);
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
    
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response            = array();
        $app                 = \Slim\Slim::getInstance();
        $response["error"]   = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email)
{
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"]   = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response)
{
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);
    
    // setting response content type to json
    $app->contentType('application/json');
    
    echo json_encode($response);
}

function getParam($param)
{
    if (isset($param))
        return $param;
    else
        return "";
}

function getParams($param){
	if(isset($param)){
		if(json_decode($param)){
		}
		return $param;
	}
	else{
		return "";	
	}
}
function getParam2($data, $param)
{
    if (isset($data->$param))
        return $data->$param;
    else
        return "";
}


function postParams($param){
	if(isset($param)){
		if($param==""){
			return "";
	}
	return $param;
	}
	else{
		return "";
	}
}

function putParam($data,$param){
	if(isset($data->{$param})){
		if($data->{$param}==""){
			if(gettype($data->{$param}) == "boolean" ){
				return $data->{$param};
			}
			return "EMPTY_PARAM";
		}
		return $data->{$param};
	}
	else{
		return "";
	}
}


function getJsonParam($jsondata, $param)
{
    if (isset($jsondata->$param))
        return $jsondata[$param];
    else
        return "";
}

function getEditObject($param,$dest){


foreach($dest as $key => $obj){
if(isset($obj->id) && ($obj->id == $param)){
return $key;
}
}
return -1;
}

function backupData($fh,$f,$fpath,$data){
$fp = fopen($fpath, "w");

    $seperator = "";
    $comma     = "";
    
    for ($i = 0; $i < sizeof($fh); $i++) {
        $seperator .= $comma . '' . str_replace('', '""', $fh[$i]);
        $comma = ",";
    }
    
    $seperator .= "\n";
    fputs($fp, $seperator);
    for ($i = 0; $i < sizeOf($data); $i++) {
        $tmp       = array();
        $seperator = "";
        $comma     = "";
        
        for ($j = 0; $j < sizeof($f); $j++) {
            $seperator .= $comma . '' . str_replace('', '""', $data[$i][$f[$j]]);
            $comma = ",";
        }
        
        $seperator .= "\n";
        fputs($fp, $seperator);
    }
    fclose($fp);
    
	}

function getNextLRNo($db,$source){
$res = array();
$lrno="";
$res["field"] = "lrno";
    $params = $db->getFunctionParam("city");
		$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "id"){
				array_push($getdata,$source);
			}else{
				array_push($getdata,"");
			}
		}

		$cityList = call_user_func_array(array($db,'getCity'), $getdata);
	
		if($cityList[0]["vacant"]){
                    $vacant = explode(",",$cityList[0]["vacant"]);
$res["lrno"] = $vacant[0];

$res["field"] = "vacant";
unset($vacant[0]);
					$vacant =  array_values($vacant);
$res["next"] = join(",",$vacant);
if(!$res["next"]){$res["next"] = "EMPTY_PARAM";}
                }else{
		$lrno=$cityList[0]["lrno"];
		if(!$lrno){$lrno=1;}
$res["lrno"] = $lrno;
$res["next"] = $lrno+1;
}

		$limits=array();
		
		$params = $db->getFunctionParam("limit");
		$getdata = array();
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == "table"){
				array_push($getdata,"lrdetail");
			}else{
			if($params[$i] == "field"){
				array_push($getdata,"lrno");
			}else{
				array_push($getdata,"");
				}
			}
		}
	
		$limitList = call_user_func_array(array($db,'getLimit'), $getdata);	
		if($res["lrno"]>$limitList[0]["size"] || !$res["lrno"]){
		$res["lrno"]=1;
$res["next"]=2;
		}



return $res;
}

function setNextLRNo($db,$source,$field,$value){
//save the next lr no
		$params = $db->putFunctionParam("city");
		$updateField = array();
		$updateField["id"] = $source;
		$putdata=array();
		array_push($putdata,$updateField);
		for($i=0;$i<sizeof($params);$i++){
			if($params[$i] == $field){
				array_push($putdata,$value);
			}else{
				array_push($putdata,"");
			}
	}
				array_push($putdata,"");
		

		$editDetail = call_user_func_array(array($db,'editCity'), $putdata);
		
}

function updateTable($db,$opParam,$mainId,$outputfields,$getFunction,$syncdata,$putFunction,$id){
	$params = $db->getFunctionParam($opParam);
	$getparamdata = array();//$getexpensedata = array();
	for($k=0;$k<sizeof($params);$k++){
		if($params[$k] == $mainId){
			array_push($getparamdata,$id);
		}else{
			array_push($getparamdata,"");
		}
	}
	
	$olddata = call_user_func_array(array($db,$getFunction), $getparamdata);

	
	for($j=0;$j<sizeOf($olddata);$j++){	
		$action = "";
		$deletedData = true;//assume that this charge is been deleted
		$updateData = false;//assume that no changes is to be done for this charge
if($syncdata){
		$newdata = array();
		$ind = getEditObject($olddata[$j]["id"],$syncdata);
		if($ind>=0){//if charge exist then get the index
			$deletedData = false;// ass charge found so less chance of deleting
			$newdata = json_decode(json_encode($syncdata), True);
			for($k=0;$k<sizeOf($outputfields);$k++){
			if(!isset($newdata[$ind][$outputfields[$k]])){
			$newdata[$ind][$outputfields[$k]] = "";
			}
			if(!isset($olddata[$j][$outputfields[$k]])){
			$olddata[$j][$outputfields[$k]] = "";
			}
				if($olddata[$j][$outputfields[$k]] != $newdata[$ind][$outputfields[$k]]){
					$updateData = true;
					$action = "update";
					break;
				}
			}
			if($updateData){//if($action == "update")
				$params = $db->putFunctionParam($opParam);
				$updateField = array();
				$updateField["id"] = $olddata[$j]["id"];
				$putdata=array();
				array_push($putdata,$updateField);
				for($i=0;$i<sizeof($params);$i++){
				if(!isset($newdata[$ind][$params[$i]])){
				$newdata[$ind][$params[$i]] = "";
				}
					if($params[$i]!=$mainId){
						array_push($putdata,$newdata[$ind][$params[$i]]);
					}else{
						array_push($putdata,"");
					}
				}
				array_push($putdata,"");
				$editDetail = call_user_func_array(array($db,$putFunction), $putdata);
			}
			unset($syncdata[$ind]);
			$syncdata =  array_values($syncdata);
		}else{
			$deletedData = true;
		}}
		else{//if they removed the product from array
			$deletedData = true;
			$action = "delete";
		}
		
		if($deletedData){
			$params = $db->putFunctionParam($opParam);
			$updateField = array();
			$updateField["id"] = $olddata[$j]["id"];
			$putdata=array();
			array_push($putdata,$updateField);
			for($i=0;$i<sizeof($params);$i++){
				array_push($putdata,"");
			}
			array_push($putdata,1);
			$editDetail = call_user_func_array(array($db,$putFunction), $putdata);
		}	
	}
	$syncdata = json_decode(json_encode($syncdata), True);
return $syncdata;
}


$app->run();
?>