<?php
//date_default_timezone_set("Asia/Kolkata");
/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 */
class DbHandler {
    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `users` table method ------------------ */


	public function createUser($user) {
        require_once 'passwordHash.php';
        $response = array();

        // First check if user already existed in db
      //  if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PasswordHash::hash($user["password"]);

            // Generating API key
            $api_key = $this->generateApiKey();

            // insert query
			$stmt = $this->conn->prepare("INSERT INTO users(name,firstname,lastname,api_key,phone,password,role,branch,lastlogin) values(?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssssss", $user["name"],$user["firstname"],$user["lastname"],$api_key,$user["phone"],$password_hash,$user["role"],$user["branch"],$user["lastlogin"] );

            $result = $stmt->execute();
			
             if($stmt->error)
		{
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
		}
        if ($result) {//product created
			$res["id"] = $this->conn->insert_id;
			$res["status"] = SUCCESS;
        }
		return $res;//$this->conn->error;

        return $response;
    }
							// $uid,$name,$firstname,$lastname,$api_key,$phone,$password,$role,$branch,$created,$updated,$fields,$sort_by,$sort_order,$limit,$offset,$totalcount);
	public function getUser($uid,$name,$firstname,$lastname,$api_key,$phone,$password,$role,$branch,$created,$updated,$fields,$sort_by,$sort_order,$limit,$offset,$totalcount){
		$parameter=array($uid,$firstname,$lastname,$name,$api_key,$phone,$password,$role,$branch,$created,$updated);
		$columns=array("a.uid","a.name","a.firstname","a.lastname","a.api_key","a.phone","a.password","a.role","a.branch","a.created","a.updated");
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);
		
		$sql="where a.deleted=0 and ";
		
		$sql	=$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array("created","updated","id");
		$sortAssigner = array("a.created","a.updated","a.id");
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		$basicFields = "a.name as name";
		if(!$fields)
		$fields = "*,".$basicFields;
		else
		$fields = $basicFields.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}
		
		return $this->getQuery("SELECT ".$fields." FROM users a left join role b on a.role=b.id ".$sql." ".$sortingSql." ".$limitSql,$bind_string,$bind_param);
		
	
	}


	public function editUser($update,$name,$firstname,$lastname,$phone,$role,$branch,$deleted){
	
		$parameter=array($name,$firstname,$lastname,$phone,$role,$branch,$deleted);
		
		$sqlrows=array("name =?","firstname=?","lastname=?","phone=?","role=?","branch=?","deleted=?");
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter)	;
		$bind_param = $this->bindParam($parameter)	;
		
		
		$sql=substr($sql, 0, -1);		
			$updatedata=array();
			foreach($update as $key => $val){
				array_push($updatedata,$key." = '".$val."'");
			}
			$whereSql = " where ".join(' and ',$updatedata);

			$stmtR= $this->putQuery("UPDATE  users SET ".$sql." ".$whereSql,$bind_string,$bind_param);
			return true;
	}

public function resetPassword($id,$password){
	
		 require_once 'passwordHash.php';

            $password_hash = PasswordHash::hash($password);

$stmt = $this->conn->prepare("update users set password= '$password_hash' WHERE uid = '$id'");
$result = $stmt->execute();
        if($stmt->error)
		{
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
		}
        if ($result) {//product created
			$res["status"] = SUCCESS;
        }
		return $res;
	}


		
	public function updateUser($id,$name,$api_key,$phone,$password,$role,$branch){
		$password_hash="";
		if($password){
			require_once 'passwordHash.php';
			$response = array();
	
			// First check if user already existed in db
		  //  if (!$this->isUserExists($email)) {
				// Generating password hash
				$password_hash = PasswordHash::hash($password);
				}
			$updated=date("Y-m-d H:i:s");
			$parameter=array($name,$api_key,$phone,$password_hash,$role,$branch);
			$sqlrows=array("name =?","api_key=?","phone=?","password=?","role=?","branch=?");
			$qry=array();
			$qry=$this->frameSql($parameter,$sqlrows);
			$a_params = array();
			$bind_string = $this->bindString($parameter)	;
			$bind_param = $this->bindParam($parameter)	;
			$sql="";
			if(count($qry)>0)
			{
				for($i=0;$i<count($qry);$i++){
					$sql.=" ".$qry[$i]." ,";
				}
			}	
			$sql=substr($sql, 0, -1);		
		
			$stmt = $this->conn->prepare("UPDATE  users SET ".$sql." where uid='$id'");
			$n = count($bind_string);
			if($n>0){
				$param_type = '';
					for($i = 0; $i < $n; $i++) {
					$param_type .= $bind_string[$i];
				}
	 
				/* with call_user_func_array, array params must be passed by reference */
				$a_params[] = & $param_type;
				for($i = 0; $i < $n; $i++) {
					/* with call_user_func_array, array params must be passed by reference */
					$a_params[] = & $bind_param[$i];
				}
	 
				/* Prepare statement */
				if($stmt === false) {		
					trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $this->conn->errno . ' ' . $this->conn->error, E_USER_ERROR);
				}
				/* use call_user_func_array, as $stmt->bind_param('s', $param); does not accept params array */
				call_user_func_array(array($stmt, 'bind_param'), $a_params);
			}
			/* Execute statement */
			
			$result=$stmt->execute();
			$stmt->close();
			if($result)
			return SUCCESS;
			return FAILED;
		}




	public function createdrcr($received){
	
		$created = date("y-m-d h:i:s");
		$updated = date("y-m-d h:i:s");
//echo json_encode($received);
		$stmt = $this->conn->prepare("INSERT INTO drcr(date,customer,credit,debit,showdaybook,note,note1,forint,collectedby,paymentmode,creditexp,crid,chitfundid,created,updated) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ");
		$stmt->bind_param("sssssssssssssss",$received["date"],$received["customer"],$received["credit"],$received["debit"],$received["showdaybook"],$received["note"],$received["note1"],$received["forint"],$received["collectedby"],$received["paymentmode"],$received["creditexp"],$received["crid"],$received["chitfundid"],$created,$updated);
		$result = $stmt->execute();
		if($stmt->error){
			echo $stmt->error;
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
		}
		if($result){
			$res["id"]=$this->conn->insert_id;
			$res["status"]= SUCCESS;
		}
		return $res;
	}

	public function updateFirm($lastbackup){
	
		$parameter=array($lastbackup);
		$sqlrows=array("lastbackup=?");
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter)	;
		$bind_param = $this->bindParam($parameter)	;
		
		
		$sql=substr($sql, 0, -1);		
		$stmtR= $this->putQuery("UPDATE  firm SET ".$sql,$bind_string,$bind_param);
	}



	public function createInterest($interest){
	
		$created = date("y-m-d h:i:s");
		$updated = date("y-m-d h:i:s");
		$stmt = $this->conn->prepare("INSERT INTO interest(date,customer,credit,debit,paymentmode,note,note1,created,updated) values(?,?,?,?,?,?,?,?,?) ");
		$stmt->bind_param("sssssssss",$interest["date"],$interest["customer"],$interest["credit"],$interest["debit"],$interest["paymentmode"],$interest["note"],$interest["note1"],$created,$updated);
		$result = $stmt->execute();
		if($stmt->error){
			echo $stmt->error;
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
		}
		if($result){
			$res["id"]=$this->conn->insert_id;
			$res["status"]= SUCCESS;
		}
		return $res;
	}

	public function getInterest($id,$date,$customer,$customername,$credit,$credittotal,$debit,$debittotal,$paymentmode,$paymentmodename,$note,$note1,$created,$updated,$fields,$sort_by,$sort_order,$groupBy,$limit,$offset,$totalcount){

		$parameter=array($id,$date,$customer,$customername,$credit,$credittotal,$debit,$debittotal,$paymentmode,$paymentmodename,$note,$note1,$created,$updated);
		$columns=array("a.id","a.date","a.customer","c.customername","a.credit","a.credittotal","a.debit","a.debittotal","a.paymentmode","p.modename","a.note","a.note1","a.created","a.updated");
		
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		//$sqlrows=array("a.id=?","a.id>=?","a.id<?","a.party =?","b.name = ?","a.store=?","a.number1=?","a.number2=?","a.mode=?","date(date) >=?","date(date) <=?","date(duedate) >=?","date(duedate) <=?","date(a.updated)>=?","date(a.updated)<=?","date(a.created)>=?","date(a.created)<=?");
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql="where a.deleted=0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id , CONCAT(c.firstname,' ' ,c.lastname) as customername,a.note as note,a.paymentmode as paymentmode,p.modename as paymentmodename,a.deleted as deleted ";
		// $showss = false;
		// if($fields){
		// 	$showss = true;
		// }
		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = " ";
		if($groupBy){
			$groupSql = " group by ".$groupBy;
		}
		// if($showss){
		// 	echo "SELECT ".$fields." from  `interest` a ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
		// }
		// echo "SELECT ".$fields." from  `interest` a LEFT JOIN customers c on a.customer = c.id LEFT JOIN pmtrans p on interest-"."a.id = p.tableid and a.paymentmode=50 ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
		// echo "SELECT ".$fields." from  `interest` a LEFT JOIN customers c on a.customer = c.id ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
		return	$this->getQuery("SELECT ".$fields." from  `interest` a LEFT JOIN customers c on a.customer = c.id LEFT JOIN paymentmodes p on a.paymentmode = p.id ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
		//LEFT JOIN pmtrans p on a.id = p.tableid and a.paymentmode=50 
	}

	public function editInterest($update,$date,$customer,$credit,$debit,$paymentmode,$pmid,$note,$note1,$deleted){
		$updated=date("Y-m-d H:i:s");
		$parameter=array($date,$customer,$credit,$debit,$paymentmode,$pmid,$note,$note1,$deleted,$updated);
		$sqlrows=array("date=?","customer=?","credit=?","debit=?","paymentmode=?","pmid=?","note=?","note1=?","deleted=?","updated=?");//db
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);
		
		
		$sql=substr($sql, 0, -1);		
			$updatedata=array();
			foreach($update as $key => $val){
				array_push($updatedata,$key." = '".$val."'");
			}
			
			$whereSql = " where ".join(' and ',$updatedata);
			//echo "UPDATE  drcr SET ".$sql." ".$whereSql;
			$stmtR= $this->putQuery("UPDATE interest SET ".$sql." ".$whereSql,$bind_string,$bind_param);
			return true;
	}

	
	public function createInterestRows($intid,$interestrows){
		$created=date("Y-m-d H:i:s");
		$updated=date("Y-m-d H:i:s");
			$res = array();
			$res["status"]  = SUCCESS;
			for($c=0;$c<sizeof($interestrows);$c++){
				// if($interestrows[$c]["op"] == 1){
				// 	$interestrows[$c]["debit"] = 0;
				// }
				if($interestrows[$c]["credit"] >0 || $interestrows[$c]["debit"]>0){
					$stmt=$this->conn->prepare("INSERT INTO interestrows(intid,date,credit,debit,note,days,intamount,op,created,updated) values(?,?,?,?,?,?,?,?,?,?) ");
					$stmt->bind_param("ssssssssss",$intid,$interestrows[$c]["date"],$interestrows[$c]["credit"],$interestrows[$c]["debit"],$interestrows[$c]["note"],$interestrows[$c]["days"],$interestrows[$c]["intamount"],$interestrows[$c]["op"],$created,$updated);
					$result = $stmt->execute();
					if($stmt->error){
						$res["error"] = $stmt->error;
						$res["status"] = FAILED;
					}	
				}
		}
		return $res;
	}
	
	public function getInterestRows($id,$intid,$date,$credit,$debit,$note,$days,$intamount,$op,$created,$updated,$fields,$sort_by,$sort_order,$limit,$offset,$totalcount){
						
		$parameter=array($id,$intid,$date,$credit,$debit,$note,$days,$intamount,$op,$created,$updated);
		$columns=array("a.id","a.intid","a.date","a.credit","a.debit","a.note","a.days","a.intamount","a.op","a.created","a.updated");
		
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		//$sqlrows=array("a.id=?","a.id>=?","a.id<?","a.party =?","b.name = ?","a.store=?","a.number1=?","a.number2=?","a.mode=?","date(date) >=?","date(date) <=?","date(duedate) >=?","date(duedate) <=?","date(a.updated)>=?","date(a.updated)<=?","date(a.created)>=?","date(a.created)<=?");
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql="where a.deleted=0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id ";
		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = " ";
		/*		if($groupBy){
			$groupSql = " group by ".$groupBy;
		}*/
		return	$this->getQuery("SELECT ".$fields." from  `interestrows` a ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
		
	}




	//start payment mode
	public function createPaymentMode($Paymentmode){		
		$created=date("Y-m-d H:i:s");
		$updated=date("Y-m-d H:i:s");
	
		$stmt=$this->conn->prepare("INSERT INTO paymentmodes(modename,opbal,created,updated) values(?,?,?,?)");
        $stmt->bind_param("ssss", $Paymentmode["modename"],$Paymentmode["opbal"],$created, $updated);
        $result = $stmt->execute();
	
		if($stmt->error)
		{
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
		}
        if ($result) {//product created
			$res["id"] = $this->conn->insert_id;
			$res["status"] = SUCCESS;
        }
		return $res;
	}

	
	
	public function getPaymentmodes($id,$modename,$opbal,$created,$updated,$fields,$sort_by,$sort_order,$limit,$offset,$totalcount){

		$parameter=array($id,$modename,$opbal,$created,$updated);
		$columns=array("a.id","a.modename","a.opbal","a.created","a.updated");
		
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		//$sqlrows=array("a.id=?","a.id>=?","a.id<?","a.party =?","b.name = ?","a.store=?","a.number1=?","a.number2=?","a.mode=?","date(date) >=?","date(date) <=?","date(duedate) >=?","date(duedate) <=?","date(a.updated)>=?","date(a.updated)<=?","date(a.created)>=?","date(a.created)<=?");
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql="where a.deleted=0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id,a.modename as modename, a.opbal as opbal";

		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = "group by a.id";
		/*if($groupBy){
				$groupSql = " group by ".$groupBy;
			}*/
		//echo "SELECT ".$fields." from  `customers` a left join customers b on a.hami = b.id  ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
		return	$this->getQuery("SELECT ".$fields." from  `paymentmodes` a ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
			
	}


	public function editPaymentmode($update,$modename,$opbal,$deleted){
		$updated=date("Y-m-d H:i:s");
		$parameter=array($modename,$opbal,$deleted,$updated);
		$sqlrows=array("modename=?","opbal=?","deleted=?","updated=?");//db
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter)	;
		$bind_param = $this->bindParam($parameter)	;
		
	
		$sql=substr($sql, 0, -1);		
			$updatedata=array();
			foreach($update as $key => $val){
				array_push($updatedata,$key." = '".$val."'");
			}
			//echo json_encode($updatedata);
			$whereSql = " where ".join(' and ',$updatedata);
			// echo "UPDATE  customers SET ".$sql." ".$whereSql;
			$stmtR= $this->putQuery("UPDATE  paymentmodes SET ".$sql." ".$whereSql,$bind_string,$bind_param);
			return true;
		}

	//end payment mode

		//start daybook
		public function createDaybook($Daybook){		
			$created=date("Y-m-d H:i:s");
			$updated=date("Y-m-d H:i:s");
		
			$stmt=$this->conn->prepare("INSERT INTO daybook(date,note,note1,created,updated) values(?,?,?,?,?)");
			$stmt->bind_param("sssss", $Daybook["date"],$Daybook["note"],$Daybook["note1"],$created, $updated);
			$result = $stmt->execute();
		
			if($stmt->error)
			{
				$res["error"] = $stmt->error;
				$res["status"] = FAILED;
			}
			if ($result) {//product created
				$res["id"] = $this->conn->insert_id;
				$res["status"] = SUCCESS;
			}
			return $res;
		}
	
		
		
		public function getDaybook($id,$date,$note,$note1,$created,$updated,$fields,$sort_by,$sort_order,$limit,$offset,$totalcount){
			// echo $date.$note.$note1;
			$parameter=array($id,$date,$note,$note1,$created,$updated);
			$columns=array("a.id","a.date","a.note","a.note1","a.created","a.updated");
			
			
			$sqlrows=array();
			$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
			
			$sqlrows = $data["row"];
			$parameter = $data["parameter"];
		
			
			//$sqlrows=array("a.id=?","a.id>=?","a.id<?","a.party =?","b.name = ?","a.store=?","a.number1=?","a.number2=?","a.mode=?","date(date) >=?","date(date) <=?","date(duedate) >=?","date(duedate) <=?","date(a.updated)>=?","date(a.updated)<=?","date(a.created)>=?","date(a.created)<=?");
			
			$qry=array();
			$a_params = array();
			$bind_string = $this->bindString($parameter);
			$bind_param = $this->bindParam($parameter);
	
			$sql="where a.deleted=0 and ";
			$sql =$this->frameSql($sql,$parameter,$sqlrows);
			$sql.=	$data['extra'];	
			$sql=substr($sql, 0, -4);		
			$sortFields = array();
			$sortAssigner = array();
			$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
			$limitSql = $this->limitQuery($offset,$limit);
			
			$seperateField = "a.id as id,a.date as date, a.note as note,a.note1 as note1";
	
			if(!$fields)
			$fields = "*,".$seperateField;
			else
			$fields = $seperateField.",".$fields;
			
			if($totalcount){
				$fields = "count(*)";
			}	
			
			$groupSql = "group by a.id";
			/*if($groupBy){
					$groupSql = " group by ".$groupBy;
				}*/
			// echo "SELECT ".$fields." from  `daybook` a ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
			return	$this->getQuery("SELECT ".$fields." from  `daybook` a ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
				
		}
	
	
		public function editDaybook($update,$date,$note,$note1,$deleted){
			$updated=date("Y-m-d H:i:s");
			$parameter=array($date,$note,$note1,$deleted,$updated);
			$sqlrows=array("date=?","note=?","note1=?","deleted=?","updated=?");//db
			$sql="";
			$sql =$this->framePutSql($sql,$parameter,$sqlrows);
			$a_params = array();
			$bind_string = $this->bindString($parameter)	;
			$bind_param = $this->bindParam($parameter)	;
			
		
			$sql=substr($sql, 0, -1);		
				$updatedata=array();
				foreach($update as $key => $val){
					array_push($updatedata,$key." = '".$val."'");
				}
				//echo json_encode($updatedata);
				$whereSql = " where ".join(' and ',$updatedata);
				// echo "UPDATE  customers SET ".$sql." ".$whereSql;
				$stmtR= $this->putQuery("UPDATE  daybook SET ".$sql." ".$whereSql,$bind_string,$bind_param);
				return true;
			}
	
		//end daybook
	

	//start collector

	public function createCollector($Collector){		
		$created=date("Y-m-d H:i:s");
		$updated=date("Y-m-d H:i:s");
	
		$stmt=$this->conn->prepare("INSERT INTO collectors(name,phone,status,uid,created,updated) values(?,?,?,?,?,?)");
        $stmt->bind_param("ssssss",$Collector["name"],$Collector["phone"],$Collector["status"],$Collector["uid"],$created, $updated);
        $result = $stmt->execute();
	
		if($stmt->error)
		{
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
		}
        if ($result) {//product created
			$res["id"] = $this->conn->insert_id;
			$res["status"] = SUCCESS;
        }
		return $res;
	}

	
	public function getCollectors($id,$name,$phone,$status,$uid,$username,$uidfullname,$api_key,$uidphone,$created,$updated,$fields,$sort_by,$sort_order,$group_by,$limit,$offset,$totalcount){

		$parameter=array($id,$name,$phone,$status,$uid,$username,$uidfullname,$api_key,$uidphone,$created,$updated);
		$columns=array("a.id","a.name","a.phone","a.status","a.uid","b.name","b.uidfullname","b.api_key","b.uidphone","a.created","a.updated");
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql="where a.deleted=0 and b.deleted=0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id,a.name as name,a.phone as phone ,a.status as status,b.name as username ,CONCAT(b.firstname,' ' ,b.lastname) as uidfullname,b.api_key as api_key,b.phone as uidphone ";

		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = " group by a.id ";
		if($group_by){
				$groupSql = " group by ".$group_by;
			}
		// echo "SELECT ".$fields." from  `collectors` a left join `users` b on a.uid=b.id ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
		return	$this->getQuery("SELECT ".$fields." from  `collectors` a left join `users` b on a.uid=b.uid ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
			
	}


	public function editCollector($update,$name,$phone,$status,$uid,$deleted){
		// echo $name.$phone.$status.$uid;
		$updated=date("Y-m-d H:i:s");
		$parameter=array($name,$phone,$status,$uid,$deleted,$updated);
		$sqlrows=array("name=?","phone=?","status=?","uid=?","deleted=?","updated=?");//db
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter)	;
		$bind_param = $this->bindParam($parameter)	;
		
	
		$sql=substr($sql, 0, -1);		
			$updatedata=array();
			foreach($update as $key => $val){
				array_push($updatedata,$key." = '".$val."'");
			}
			//echo json_encode($updatedata);
			$whereSql = " where ".join(' and ',$updatedata);
			// echo "UPDATE  customers SET ".$sql." ".$whereSql;
			$stmtR= $this->putQuery("UPDATE  collectors SET ".$sql." ".$whereSql,$bind_string,$bind_param);
			
			return true;
	}

	//end collector

	//start collector amount
	public function createCollectorAmount($Amount){		
		$created=date("Y-m-d H:i:s");
		$updated=date("Y-m-d H:i:s");
		// echo "date".$Amount["date"],"collector".$Amount["collector"],"amount".$Amount["amount"],"collectiondate".$Amount["collectiondate"],"note".$Amount["note"];
		$stmt=$this->conn->prepare("INSERT INTO collectoramount(date,collector,amount,collectiondate,note,created,updated) values(?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssss",$Amount["date"],$Amount["collector"],$Amount["amount"],$Amount["collectiondate"],$Amount["note"],$created, $updated);
        $result = $stmt->execute();
	
		if($stmt->error)
		{
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
		}
        if ($result) {//product created
			$res["id"] = $this->conn->insert_id;
			$res["status"] = SUCCESS;
        }
		return $res;
	}

										
	public function getCollectorsAmount($id,$date,$collector,$amount,$collectiondate,$uid,$username,$uidfullname,$api_key,$uidphone,$created,$updated,$fields,$sort_by,$sort_order,$group_by,$limit,$offset,$totalcount){

		$parameter=array($id,$date,$collector,$amount,$collectiondate,$uid,$username,$uidfullname,$api_key,$uidphone,$created,$updated);
		$columns=array("a.id","a.date","a.collector","a.amount","a.collectiondate","b.uid","c.name","c.uidfullname","c.api_key","c.phone","a.created","a.updated");
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql="where a.deleted=0 and b.deleted=0 and c.deleted = 0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id,a.date as date,a.collector as collector ,a.amount as amount,a.collectiondate as collectiondate,b.uid as uid,c.name as username ,CONCAT(c.firstname,' ' ,c.lastname) as uidfullname,c.api_key as api_key,c.phone as uidphone ";

		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = " group by a.id ";
		if($group_by){
				$groupSql = " group by ".$group_by;
			}
		// echo "SELECT ".$fields." from  `collectoramount` a left join `collectors` b on a.collector = b.id left join `users` c on b.uid= c.uid ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
		return	$this->getQuery("SELECT ".$fields." from  `collectoramount` a left join `collectors` b on a.collector = b.id left join `users` c on b.uid= c.uid ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
			
	}


	public function editCollectorAmount($update,$name,$phone,$status,$uid,$deleted){
		// echo $name.$phone.$status.$uid;
		$updated=date("Y-m-d H:i:s");
		$parameter=array($name,$phone,$status,$uid,$deleted,$updated);
		$sqlrows=array("name=?","phone=?","status=?","uid=?","deleted=?","updated=?");//db
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter)	;
		$bind_param = $this->bindParam($parameter)	;
		
	
		$sql=substr($sql, 0, -1);		
			$updatedata=array();
			foreach($update as $key => $val){
				array_push($updatedata,$key." = '".$val."'");
			}
			//echo json_encode($updatedata);
			$whereSql = " where ".join(' and ',$updatedata);
			// echo "UPDATE  customers SET ".$sql." ".$whereSql;
			$stmtR= $this->putQuery("UPDATE  collectoramount SET ".$sql." ".$whereSql,$bind_string,$bind_param);
			
			return true;
	}	
	
	//end collector amount

	//start yesno

	public function getYesno($id,$name){

		$parameter=array($id,$name);
		$columns=array("a.id","a.name");
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql=" where a.deleted = 0 ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		// $sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		// $limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id,a.name as name";

		// if(!$fields)
		// $fields = "*,".$seperateField;
		// else
		// $fields = $seperateField.",".$fields;
		
		// if($totalcount){
		// 	$fields = "count(*)";
		// }	
		
		// $groupSql = " group by a.id ";
		// if($group_by){
		// 		$groupSql = " group by ".$group_by;
		// 	}
		return	$this->getmultiRecord("SELECT *, a.id as id,a.name as name from  `yesno` a ");
			
	}

	//end yesno 

	//start multipm

	public function createPmTrans($pmtrans){		
		$created=date("Y-m-d H:i:s");
		$updated=date("Y-m-d H:i:s");
		// echo json_encode($pmtrans);
		$stmt=$this->conn->prepare("INSERT INTO pmtrans(date,tablename,tableid,paymentmode,credit,debit,created,updated) values(?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssss",$pmtrans["date"],$pmtrans["tablename"],$pmtrans["tableid"],$pmtrans["paymentmode"],$pmtrans["credit"],$pmtrans["debit"],$created, $updated);
        $result = $stmt->execute();
	
		if($stmt->error)
		{
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
		}
        if ($result) {//product created
			$res["id"] = $this->conn->insert_id;
			$res["status"] = SUCCESS;
        }
		return $res;
	}


	public function getPmTrans($id,$date,$tablename,$tableid,$paymentmode,$paymentmodename,$credit,$debit,$created,$updated,$fields,$sort_by,$sort_order,$group_by,$limit,$offset,$totalcount){
							// $id,$date,$tablename,$tableid,$paymentmode,$paymentmodename,$credit,$debit,$created,$updated,$fields,$sort_by,$sort_order,$group_by,$limit,$offset,$totalcount
		$parameter=array($id,$date,$tablename,$tableid,$paymentmode,$paymentmodename,$credit,$debit,$created,$updated);
		$columns=array("a.id","a.date","a.tablename","a.tableid","a.paymentmode","b.modename","a.credit","a.debit","a.created","a.updated");
		
		// $tablename = "";
		// $tbname = array();
		// $tbname = explode('-', $tableid);
		// $tablename = $tbname[0];

		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
		//$sqlrows=array("a.id=?","a.id>=?","a.id<?","a.party =?","b.name = ?","a.store=?","a.number1=?","a.number2=?","a.mode=?","date(date) >=?","date(date) <=?","date(duedate) >=?","date(duedate) <=?","date(a.updated)>=?","date(a.updated)<=?","date(a.created)>=?","date(a.created)<=?");
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql="where a.deleted=0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		// echo $tablename;
		$seperateField = "a.id as id,a.date as date,a.tablename as tablename,a.tableid as tableid ,a.paymentmode as paymentmode,b.modename as paymentmodename,a.credit as credit,a.debit as debit";
		// if($tablename == "drcr"){
		// 	$seperateField .= ",t.credit as tablecredit, t.debit as tabledebit";
		// }else{
		// 	$seperateField .= ",t.credit as tablecredit, t.debit as tabledebit";
		// }
		// echo json_encode($fields);
		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = "";
		if($group_by){
			$groupSql = " group by ".$group_by;
		}

		$qrystr = "";
		if($tablename){
			$tablename = "LEFT JOIN ".$tablename." t on a.tableid = t.id  ";
		}

		// echo "SELECT ".$fields." from  `pmtrans` a LEFT JOIN paymentmodes b on a.paymentmode = b.id ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
		return	$this->getQuery("SELECT ".$fields." from  `pmtrans` a LEFT JOIN paymentmodes b on a.paymentmode = b.id ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
		//LEFT JOIN `".$tablename."` b on a.tableid = b.id
			
	}

	
	public function editPmTrans($update,$date,$tablename,$tableid,$paymentmode,$credit,$debit,$deleted){
		$updated=date("Y-m-d H:i:s");
		$parameter=array($date,$tablename,$tableid,$paymentmode,$credit,$debit,$deleted,$updated);
		$sqlrows=array("date=?","tablename=?","tableid=?","paymentmode=?","credit=?","debit=?","deleted=?","updated=?");//db
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter)	;
		$bind_param = $this->bindParam($parameter)	;
		
	
		$sql=substr($sql, 0, -1);		
			$updatedata=array();
			foreach($update as $key => $val){
				array_push($updatedata,$key." = '".$val."'");
			}
			//echo json_encode($updatedata);
			$whereSql = " where ".join(' and ',$updatedata);
			// echo "UPDATE  customers SET ".$sql." ".$whereSql;
			$stmtR= $this->putQuery("UPDATE  pmtrans SET ".$sql." ".$whereSql,$bind_string,$bind_param);
			return true;

	}

	//end multipm

	//start


	public function createCfCustomer($Customer){		
		$created=date("Y-m-d H:i:s");
		$updated=date("Y-m-d H:i:s");
	
		$stmt=$this->conn->prepare("INSERT INTO cfcustomers(fname,lname,phone,first,firstid,created,updated) values(?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssss", $Customer["fname"],$Customer["lname"],$Customer["phone"],$Customer["first"],$Customer["firstid"],$created, $updated);
        $result = $stmt->execute();
	
		if($stmt->error)
		{
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
		}
        if ($result) {//product created
			$res["id"] = $this->conn->insert_id;
			$res["status"] = SUCCESS;
        }
		return $res;
	}


	public function getCfCustomers($id,$fname,$lname,$phone,$first,$firstid,$firstidname,$created,$updated,$fields,$sort_by,$sort_order,$limit,$offset,$totalcount){

		$parameter=array($id,$fname,$lname,$phone,$first,$firstid,$firstidname,$created,$updated);
		$columns=array("a.id","a.fname ","a.lname ","a.phone","a.first","a.firstid","b.firstidname","a.created","a.updated");
		
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		//$sqlrows=array("a.id=?","a.id>=?","a.id<?","a.party =?","b.name = ?","a.store=?","a.number1=?","a.number2=?","a.mode=?","date(date) >=?","date(date) <=?","date(duedate) >=?","date(duedate) <=?","date(a.updated)>=?","date(a.updated)<=?","date(a.created)>=?","date(a.created)<=?");
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql="where a.deleted=0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id,a.fname as fname, a.lname as lname , CONCAT(b.fname,' ' ,b.lname) as firstidname ,a.phone as phone , a.first as first , a.firstid as firstid ";
		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = "group by a.id";
		/*if($groupBy){
				$groupSql = " group by ".$groupBy;
			}*/
		//echo "SELECT ".$fields." from  `customers` a left join customers b on a.hami = b.id  ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
		return	$this->getQuery("SELECT ".$fields." from  `cfcustomers` a LEFT JOIN `cfcustomers` b on a.firstid = b.id ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
			
	}

	public function editCfCustomer($update,$fname,$lname,$phone,$first,$firstid,$deleted){
		$updated=date("Y-m-d H:i:s");
		$parameter=array($fname,$lname,$phone,$first,$firstid,$deleted,$updated);
		$sqlrows=array("fname=?","lname=?","phone=?","first=?","firstid=?","deleted=?","updated=?");//db
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter)	;
		$bind_param = $this->bindParam($parameter)	;
		
	
		$sql=substr($sql, 0, -1);		
			$updatedata=array();
			foreach($update as $key => $val){
				array_push($updatedata,$key." = '".$val."'");
			}
			//echo json_encode($updatedata);
			$whereSql = " where ".join(' and ',$updatedata);
			// echo "UPDATE  customers SET ".$sql." ".$whereSql;
			$stmtR= $this->putQuery("UPDATE  cfcustomers SET ".$sql." ".$whereSql,$bind_string,$bind_param);
			return true;

	}

	public function getCfChiti($id,$date,$chitiname,$code,$no,$amount,$created,$updated,$fields,$sort_by,$sort_order,$limit,$offset,$totalcount){

		$parameter=array($id,$date,$chitiname,$code,$no,$amount,$created,$updated);
		$columns=array("a.id","a.date","a.chitiname","a.code","a.no","a.amount","a.created","a.updated");
		
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		//$sqlrows=array("a.id=?","a.id>=?","a.id<?","a.party =?","b.name = ?","a.store=?","a.number1=?","a.number2=?","a.mode=?","date(date) >=?","date(date) <=?","date(duedate) >=?","date(duedate) <=?","date(a.updated)>=?","date(a.updated)<=?","date(a.created)>=?","date(a.created)<=?");
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql="where a.deleted=0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id ";
		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = "group by a.id";
		/*if($groupBy){
				$groupSql = " group by ".$groupBy;
			}*/
		//echo "SELECT ".$fields." from  `customers` a left join customers b on a.hami = b.id  ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
			return	$this->getQuery("SELECT ".$fields." from  `cfchiti` a ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
			
	}

	public function createCfChitiCustomer($Customer){		
		$created=date("Y-m-d H:i:s");
		$updated=date("Y-m-d H:i:s");
	
		$stmt=$this->conn->prepare("INSERT INTO cfchiticustomers(chiti,customer,cusno,created,updated) values(?,?,?,?,?)");
        $stmt->bind_param("sssss", $Customer["chiti"],$Customer["customer"],$Customer["cusno"],$created, $updated);
        $result = $stmt->execute();
	
		if($stmt->error)
		{
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
		}
        if ($result) {//product created
			$res["id"] = $this->conn->insert_id;
			$res["status"] = SUCCESS;
        }
		return $res;
	}
	
	public function getCfChitiCustomers($id,$chiti,$chitiname,$code,$customer,$customername,$cusno,$created,$updated,$fields,$sort_by,$sort_order,$groupBy,$limit,$offset,$totalcount){

		$parameter=array($id,$chiti,$chitiname,$code,$customer,$customername,$cusno,$created,$updated);
		$columns=array("a.id","a.chiti","a.chitiname","b.code","a.customer","c.customername","a.cusno","a.created","a.updated");
		
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		// echo json_encode($data);
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
		
		//$sqlrows=array("a.id=?","a.id>=?","a.id<?","a.party =?","b.name = ?","a.store=?","a.number1=?","a.number2=?","a.mode=?","date(date) >=?","date(date) <=?","date(duedate) >=?","date(duedate) <=?","date(a.updated)>=?","date(a.updated)<=?","date(a.created)>=?","date(a.created)<=?");
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql="where a.deleted=0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id , CONCAT(c.fname,' ',c.lname) as customername ,b.code as code";
		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		if($groupBy){
			$groupSql = " group by ".$groupBy;
		}else{
			$groupSql = "group by a.id";
		}
		// echo "SELECT ".$fields." from  `cfchiticustomers` a LEFT JOIN cfchiti b on a.chiti=b.id LEFT JOIN cfcustomers c on a.customer=c.id ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
			return	$this->getQuery("SELECT ".$fields." from  `cfchiticustomers` a LEFT JOIN cfchiti b on a.chiti=b.id LEFT JOIN cfcustomers c on a.customer=c.id ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
			
	}


	public function editCfChitiCustomer($update,$chiti,$customer,$cusno,$deleted){
		$updated=date("Y-m-d H:i:s");
		$parameter=array($chiti,$customer,$cusno,$deleted,$updated);
		$sqlrows=array("chiti=?","customer=?","cusno=?","deleted=?","updated=?");//db
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter)	;
		$bind_param = $this->bindParam($parameter)	;
		
	
		$sql=substr($sql, 0, -1);		
			$updatedata=array();
			foreach($update as $key => $val){
				array_push($updatedata,$key." = '".$val."'");
			}
			$whereSql = " where ".join(' and ',$updatedata);
			$stmtR= $this->putQuery("UPDATE  cfchiticustomers SET ".$sql." ".$whereSql,$bind_string,$bind_param);
			return true;

	}

	//cfpaata start
	public function createCfPaata($paata){		
		$created=date("Y-m-d H:i:s");
		$updated=date("Y-m-d H:i:s");
	
		$stmt=$this->conn->prepare("INSERT INTO cfpaata(date,chiti,no,customer,paata,payamount,repayamount,sripalcomm,sowjicomm,created,updated) values(?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssssssss", $paata["date"],$paata["chiti"],$paata["no"],$paata["customer"],$paata["paata"],$paata["payamount"],$paata["repayamount"],$paata["sripalcomm"],$paata["sowjicomm"],$created, $updated);
        $result = $stmt->execute();
	
		if($stmt->error)
		{
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
		}
        if ($result) {//product created
			$res["id"] = $this->conn->insert_id;
			$res["status"] = SUCCESS;
        }
		return $res;
	}

	
	public function getCfPaata($id,$date,$chiti,$chitiname,$code,$no,$customer,$customername,$cusno,$paata,$payamount,$repayamount,$sripalcomm,$sowjicomm,$created,$updated,$fields,$sort_by,$sort_order,$groupBy,$limit,$offset,$totalcount){

		$parameter=array($id,$date,$chiti,$chitiname,$code,$no,$customer,$customername,$cusno,$paata,$payamount,$repayamount,$sripalcomm,$sowjicomm,$created,$updated);
		$columns=array("a.id","a.date","a.chiti","b.chitiname","b.code","a.no","a.customer","c.customername","b.cusno","a.paata","a.payamount","a.repayamount","a.sripalcomm","a.sowjicomm","a.created","a.updated");
		
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		//$sqlrows=array("a.id=?","a.id>=?","a.id<?","a.party =?","b.name = ?","a.store=?","a.number1=?","a.number2=?","a.mode=?","date(date) >=?","date(date) <=?","date(duedate) >=?","date(duedate) <=?","date(a.updated)>=?","date(a.updated)<=?","date(a.created)>=?","date(a.created)<=?");
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql=" where a.deleted=0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id,a.date as date,a.chiti as chiti,a.no as no,a.customer as customer,CONCAT(c.fname,' ',c.lname) as customername ,a.paata as paata,a.payamount as payamount,a.repayamount as repayamount,a.sripalcomm as sripalcomm,a.sowjicomm as sowjicomm,d.chitiname as chitiname,b.cusno as cusno";
		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = "";
		if($groupBy){
				$groupSql = " group by ".$groupBy;
		}
		// echo "SELECT ".$fields." from  `cfpaata` a LEFT JOIN cfchiticustomers b on a.customer=b.id LEFT JOIN cfcustomers c on b.customer = c.id LEFT JOIN cfchiti d on a.chiti= d.id".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
		return	$this->getQuery("SELECT ".$fields." from  `cfpaata` a LEFT JOIN cfchiticustomers b on a.customer=b.id LEFT JOIN cfcustomers c on b.customer = c.id LEFT JOIN cfchiti d on a.chiti= d.id".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
			
	}

	public function editCfPaata($update,$date,$chiti,$no,$customer,$paata,$payamount,$repayamount,$sripalcomm,$sowjicomm,$deleted){
		$updated=date("Y-m-d H:i:s");
		$parameter=array($date,$chiti,$no,$customer,$paata,$payamount,$repayamount,$sripalcomm,$sowjicomm,$deleted,$updated);
		$sqlrows=array("date=?","chiti=?","no=?","customer=?","paata=?","payamount=?","repayamount=?","sripalcomm=?","sowjicomm=?","deleted=?","updated=?");//db
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter)	;
		$bind_param = $this->bindParam($parameter)	;
		
	
		$sql=substr($sql, 0, -1);		
			$updatedata=array();
			foreach($update as $key => $val){
				array_push($updatedata,$key." = '".$val."'");
			}
			//echo json_encode($updatedata);
			$whereSql = " where ".join(' and ',$updatedata);
			// echo "UPDATE  customers SET ".$sql." ".$whereSql;
			$stmtR= $this->putQuery("UPDATE  cfpaata SET ".$sql." ".$whereSql,$bind_string,$bind_param);
			return true;

	}
	//cfpaata end



	public function createCfTrans($cftrans){		
		$created=date("Y-m-d H:i:s");
		$updated=date("Y-m-d H:i:s");
	
		$stmt=$this->conn->prepare("INSERT INTO cftrans(date,chiti,paata,customer,debit,credit,paymentmode,pmid,note,created,updated) values(?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssssssss", $cftrans["date"],$cftrans["chiti"],$cftrans["paata"],$cftrans["customer"],$cftrans["debit"],$cftrans["credit"],$cftrans["paymentmode"],$cftrans["pmid"],$cftrans["note"],$created, $updated);
        $result = $stmt->execute();
	
		if($stmt->error)
		{
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
		}
        if ($result) {//product created
			$res["id"] = $this->conn->insert_id;
			$res["status"] = SUCCESS;
        }
		return $res;
	}

	
	public function getCfTrans($id,$date,$chiti,$chitiname,$code,$paata,$paataamt,$customer,$customername,$cusno,$maincus,$debit,$credit,$paymentmode,$pmid,$note,$payamount,$repayamount,$paatano,$created,$updated,$fields,$sort_by,$sort_order,$groupBy,$limit,$offset,$totalcount){

		$parameter=array($id,$date,$chiti,$chitiname,$code,$paata,$paataamt,$customer,$customername,$cusno,$debit,$credit,$paymentmode,$pmid,$note,$payamount,$repayamount,$paatano,$created,$updated);
		$columns=array("a.id","a.date","a.chiti","d.chitiname","d.code","a.paata","p.paata","a.customer","c.customername","b.cusno","a.debit","a.credit","a.paymentmode","a.pmid","a.note","p.payamount","p.repayamount", "p.no","created","updated");
		
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		//$sqlrows=array("a.id=?","a.id>=?","a.id<?","a.party =?","b.name = ?","a.store=?","a.number1=?","a.number2=?","a.mode=?","date(date) >=?","date(date) <=?","date(duedate) >=?","date(duedate) <=?","date(a.updated)>=?","date(a.updated)<=?","date(a.created)>=?","date(a.created)<=?");
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql=" where a.deleted=0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id,a.date as date,a.paymentmode as paymentmode,a.pmid as pmid,a.note as note,a.chiti as chiti,a.customer as customer,CONCAT(c.fname,' ' ,c.lname) as customername ,d.chitiname as chitiname,d.code as code,b.cusno as cusno,p.payamount as payamount, p.repayamount as repayamount,p.no as paatano,b.cusno as cusno ,a.paata as paata, p.paata as paataamt";
		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = "";
		if($groupBy){
				$groupSql = " group by ".$groupBy;
		}
		// echo "SELECT ".$fields." from  `cftrans` a LEFT JOIN cfchiticustomers b on a.customer=b.id LEFT JOIN cfcustomers c on b.customer = c.id LEFT JOIN customers m on c.maincus = m.id LEFT JOIN cfchiti d on a.chiti= d.id LEFT JOIN cfpaata p on a.paata=p.id".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
		return	$this->getQuery("SELECT ".$fields." from  `cftrans` a LEFT JOIN cfchiticustomers b on a.customer=b.id LEFT JOIN cfcustomers c on b.customer = c.id LEFT JOIN customers m on c.maincus = m.id LEFT JOIN cfchiti d on a.chiti= d.id LEFT JOIN cfpaata p on a.paata=p.id".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
			
	}

	public function editCfTrans($update,$date,$chiti,$paata,$customer,$debit,$credit,$paymentmode,$pmid,$note,$deleted){
		// echo "update".json_encode($update),"date".json_encode($date),"chiti".json_encode($chiti),"paata".json_encode($paata),"customer".json_encode($customer),"credit".json_encode($credit),"debit".json_encode($debit),"paymentmode".json_encode($paymentmode),"pmid".json_encode($pmid),"note".json_encode($note),"deleted".json_encode($deleted);
		$updated=date("Y-m-d H:i:s");
		$parameter=array($date,$chiti,$paata,$customer,$debit,$credit,$paymentmode,$pmid,$note,$deleted,$updated);
		$sqlrows=array("date=?","chiti=?","paata=?","customer=?","debit=?","credit=?","paymentmode=?","pmid=?","note =?","deleted=?","updated=?");//db
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);
		
	
		$sql=substr($sql, 0, -1);		
			$updatedata=array();
			foreach($update as $key => $val){
				array_push($updatedata,$key." = '".$val."'");
			}
			//echo json_encode($updatedata);
			$whereSql = " where ".join(' and ',$updatedata);
			// echo "UPDATE  customers SET ".$sql." ".$whereSql;
			$stmtR= $this->putQuery("UPDATE  cftrans SET ".$sql." ".$whereSql,$bind_string,$bind_param);
			return true;
		}

	//end


	public function createCustomer($Customer){		
		$created=date("Y-m-d H:i:s");
		$updated=date("Y-m-d H:i:s");
	
		$stmt=$this->conn->prepare("INSERT INTO customers(firstname,lastname,phoneno,hami,ishami,chitfund,aadhar,passbook,debitcard,cheque,pnote,greensheet,note,forint,intrate,created,updated) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssisssssssssssss", $Customer["FName"],$Customer["LName"],$Customer["Phone"], $Customer["Hami"], $Customer["IsHami"], $Customer["chitfund"], $Customer["aadhar"],$Customer["passbook"],$Customer["debitcard"], $Customer["cheque"], $Customer["pnote"],$Customer["greensheet"],$Customer["note"],$Customer["forint"],$Customer["intrate"],$created, $updated);
        $result = $stmt->execute();
	
		if($stmt->error)
		{
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
		}
        if ($result) {//product created
			$res["id"] = $this->conn->insert_id;
			$res["status"] = SUCCESS;
        }
		return $res;
	}

	public function createchitfund($chitfund){		
		$created=date("Y-m-d H:i:s");
		$updated=date("Y-m-d H:i:s");
	
		$stmt=$this->conn->prepare("INSERT INTO chitfund(customer,date,amount,type,created,updated) values(?,?,?,?,?,?)");
        $stmt->bind_param("ssssss", $chitfund["customer"],$chitfund["date"],$chitfund["amount"], $chitfund["type"],$created, $updated);
        $result = $stmt->execute();
	
		if($stmt->error)
		{
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
		}
        if ($result) {//product created
			$res["id"] = $this->conn->insert_id;
			$res["status"] = SUCCESS;
        }
		return $res;
	}


	public function createsowji($sowji){		
		$created=date("Y-m-d H:i:s");
		$updated=date("Y-m-d H:i:s");
	
		$stmt=$this->conn->prepare("INSERT INTO sowji(date,chiti,amount,note,created,updated) values(?,?,?,?,?,?)");
        $stmt->bind_param("ssssss", $sowji["date"],$sowji["chiti"],$sowji["amount"], $sowji["note"],$created, $updated);
        $result = $stmt->execute();
	
		if($stmt->error)
		{
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
		}
        if ($result) {//product created
			$res["id"] = $this->conn->insert_id;
			$res["status"] = SUCCESS;
        }
		return $res;
	}



	public function updatesowji($amount){		
		$created=date("Y-m-d H:i:s");
		$updated=date("Y-m-d H:i:s");
	
		$stmt = $this->conn->prepare("UPDATE  sowji SET amount=amount +'$amount' ");	   
        $result = $stmt->execute();
	
		if($stmt->error)
		{
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
		}
        if ($result) {//product created
			$res["id"] = $this->conn->insert_id;
			$res["status"] = SUCCESS;
        }
		return $res;
	}


	public function deletechiti($id){
		$parameter=array($id);
		//echo json_encode($parameter);
	$columns=array("id");
	
	/*$sqlrows=array();
	$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
	echo json_encode($data);
	
	$sqlrows = $data["row"];
	$parameter = $data["parameter"];

	$qry=array();
	$a_params = array();
	$bind_string = $this->bindString($parameter);
	$bind_param = $this->bindParam($parameter);
$dateRange= $this->getSession();
	$sql="where ";
	$sql =$this->frameSql($sql,$parameter,$sqlrows);
	$sql.=	$data['extra'];	
	$sql=substr($sql, 0, -4);	*/
	

	return $this->putQuery("delete from  chiti where id =" .$parameter);

	return $res;
	
}
	
/*public function deleteCustomer($id){
			$parameter=array($id);
		$columns=array("id");
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		//echo json_encode($data);
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);
$dateRange= $this->getSession();
		$sql="where ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		

		return $this->putQuery("delete from  customer ".$sql,$bind_string,$bind_param);
	
		return $res;
	} */

								//  $id,$firstname,$lastname,$phoneno,$hami,$ishami,$chitfund,$hamifirstname,$hamilastname,$hamiphoneno,$aadhar,$passbook,$debitcard,$cheque,$pnote,$greensheet,$note,$forint,$intrate,$created,$updated,$fields,$sort_by,$sort_order,$limit,$offset,$totalcount
	public function getcustomers($id,$firstname,$lastname,$fullname,$phoneno,$hami,$ishami,$chitfund,$hamifirstname,$hamilastname,$hamiphoneno,$aadhar,$passbook,$debitcard,$cheque,$pnote,$greensheet,$note,$forint,$intrate,$created,$updated,$fields,$sort_by,$sort_order,$group_by,$limit,$offset,$totalcount){

		$parameter=array($id,$firstname,$lastname,$fullname,$phoneno,$hami,$ishami,$chitfund,$hamifirstname,$hamilastname,$hamiphoneno,$aadhar,$passbook,$debitcard,$cheque,$pnote,$greensheet,$note,$forint,$intrate,$created,$updated);
		$columns=array("a.id","a.firstname ","a.lastname","a.fullname","a.phoneno","a.hami","a.ishami","a.chitfund","a.hamifirstname","a.hamilastname","a.hamiphoneno ","a.aadhar","a.passbook","a.debitcard","a.cheque","a.pnote","a.greensheet","a.note","a.forint","a.intrate","a.created","a.updated");
		
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		//$sqlrows=array("a.id=?","a.id>=?","a.id<?","a.party =?","b.name = ?","a.store=?","a.number1=?","a.number2=?","a.mode=?","date(date) >=?","date(date) <=?","date(duedate) >=?","date(duedate) <=?","date(a.updated)>=?","date(a.updated)<=?","date(a.created)>=?","date(a.created)<=?");
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql="where a.deleted=0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		// echo json_encode($sortingSql);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id,a.firstname as firstname, a.lastname as lastname,CONCAT(a.firstname,' ' ,a.lastname) as fullname, a.phoneno as phoneno,a.hami as hami, a.ishami as ishami,b.firstname as hamifirstname, b.lastname as hamilastname,b.phoneno as hamiphoneno,a.aadhar as aadhar,a.passbook as passbook,a.debitcard as debitcard,a.cheque as cheque,a.pnote as pnote,a.greensheet as greensheet , a.note as note,a.forint as forint,a.intrate as intrate,a.chitfund as chitfund";
		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = "group by a.id";
		if($group_by){
			$groupSql = " group by ".$group_by;
		}
	// echo "SELECT ".$fields." from  `customers` a left join customers b on a.hami = b.id  ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
	return	$this->getQuery("SELECT ".$fields." from  `customers` a left join customers b on a.hami = b.id  ".$sql." ".$groupSql." ".$sortingSql." ".$limitSql,$bind_string, $bind_param);
		
	}





	public function getdrcr($id,$date,$customer,$customername,$haminame,$credit,$credittotal,$debit,$debittotal,$showdaybook,$note,$note1,$forint,$collectedby,$paymentmode,$creditexp,$crid,$chitfundid,$created,$updated,$fields,$sort_by,$sort_order,$group_by,$limit,$offset,$totalcount){
		$parameter = array($id,$date,$customer,$customername,$haminame,$credit,$credittotal,$debit,$debittotal,$showdaybook,$note,$note1,$forint,$collectedby,$paymentmode,$creditexp,$crid,$chitfundid,$created,$updated);
		$columns = array("a.id","a.date","a.customer","a.customername","a.haminame","a.credit","a.credittotal","a.debit","a.debittotal","a.showdaybook","a.note","a.note1","a.forint","a.collectedby","a.paymentmode","a.creditexp","a.crid","a.chitfundid","a.created","a.updated");

		$sqlrows = array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		$sqlrows = $data["row"];
		
		$parameter = $data["parameter"];
		$qry = array();
		$a_params = array();
		$bind_string =$this->bindString($parameter);
		$bind_param =$this->bindParam($parameter);

		$sql = "where a.deleted =0 and";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id , CONCAT(b.firstname,' ' ,b.lastname) as customername , CONCAT(c.firstname, ' ' ,c.lastname)as haminame,a.note as note,a.creditexp as creditexp,a.forint as forint,p.modename as paymentmodename,a.paymentmode as paymentmode ";
		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = "";
		if($group_by){
			$groupSql = " group by ".$group_by;
		}
		
	// echo "SELECT ".$fields." from `drcr`a left join customers b on a.customer=b.id left join customers c on b.hami=c.id LEFT JOIN paymentmodes p on a.paymentmode = p.id ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
	return	$this->getQuery("SELECT ".$fields." from `drcr`a left join customers b on a.customer=b.id left join customers c on b.hami=c.id LEFT JOIN paymentmodes p on a.paymentmode = p.id ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
		
	}

	
	public function getcoldrcr($id,$date,$customer,$customername,$haminame,$credit,$credittotal,$debit,$debittotal,$showdaybook,$note,$note1,$forint,$collectedby,$paymentmode,$creditexp,$crid,$chitfundid,$created,$updated,$fields,$sort_by,$sort_order,$group_by,$limit,$offset,$totalcount){
		$parameter = array($id,$date,$customer,$customername,$haminame,$credit,$credittotal,$debit,$debittotal,$showdaybook,$note,$note1,$forint,$collectedby,$paymentmode,$creditexp,$crid,$chitfundid,$created,$updated);
		$columns = array("a.id","a.date","a.customer","a.customername","a.haminame","a.credit","a.credittotal","a.debit","a.debittotal","a.showdaybook","a.note","a.note1","a.forint","a.collectedby","a.paymentmode","a.creditexp","a.crid","a.chitfundid","a.created","a.updated");

		$sqlrows = array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		$sqlrows = $data["row"];
		
		$parameter = $data["parameter"];
		$qry = array();
		$a_params = array();
		$bind_string =$this->bindString($parameter);
		$bind_param =$this->bindParam($parameter);

		$sql = "where a.deleted =0 and";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id,a.customer as customer , CONCAT(b.firstname,' ' ,b.lastname) as customername , CONCAT(c.firstname, ' ' ,c.lastname)as haminame,a.note as note,a.creditexp as creditexp,a.forint as forint,p.modename as paymentmodename,a.paymentmode as paymentmode,a.collectedby as collectedby,a.pmid as pmid,ROUND(a.credit) as credit,ROUND(a.debit) as debit,a.date as date,a.created as created,a.updated as updated,m.credit as pmcredit,n.name as collectorname ";
		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = "";
		if($group_by){
			$groupSql = " group by ".$group_by;
		}
		
	// echo "SELECT ".$fields." from `drcr`a left join customers b on a.customer=b.id left join customers c on b.hami=c.id and c.deleted = 0 LEFT JOIN paymentmodes p on a.paymentmode = p.id and p.deleted = 0 left join pmtrans m on a.id = m.tableid and m.tablename = 'drcr' and m.paymentmode = 1 and m.deleted = 0 LEFT JOIN collectors n on a.collectedby = n.id  and n.deleted = 0 ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
	return	$this->getQuery("SELECT ".$fields." from `drcr`a left join customers b on a.customer=b.id left join customers c on b.hami=c.id and c.deleted = 0 LEFT JOIN paymentmodes p on a.paymentmode = p.id and p.deleted = 0 left join pmtrans m on a.id = m.tableid and m.tablename = 'drcr' and m.paymentmode = 1 and m.deleted = 0 LEFT JOIN collectors n on a.collectedby = n.id  and n.deleted = 0 ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
		
	}

	public function getsowji($id,$date,$chiti,$amount,$note,$amounttotal,$created,$updated,$fields,$sort_by,$sort_order,$group_by,$limit,$offset,$totalcount){
	
		$parameter = array($id,$date,$chiti,$amount,$note,$amounttotal,$created,$updated);
		$columns = array("a.id","a.date","a.chiti","a.amount","a.note","a.amounttotal","a.created","a.updated");

		$sqlrows = array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		$sqlrows = $data["row"];
		
		$parameter = $data["parameter"];
		$qry = array();
		$a_params = array();
		$bind_string =$this->bindString($parameter);
		$bind_param =$this->bindParam($parameter);

		$sql = "where a.deleted =0 and";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id,a.date as date,a.chiti as chiti,a.amount as amount,a.note as note ";
		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = "";
		if($group_by){
			$groupSql = " group by ".$group_by;
		}
		
	//echo "SELECT ".$fields." from  `sowji` a left join  chiti b on a.chiti = b.id ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
	return	$this->getQuery("SELECT ".$fields." from  `sowji` a left join  chiti b on a.chiti = b.id ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
		
	}


public function editcustomer($update,$firstname,$lastname,$phoneno,$hami,$ishami,$chitfund,$aadhar,$passbook,$debitcard,$cheque,$pnote,$greensheet,$note,$forint,$intrate,$deleted){

		$updated=date("Y-m-d H:i:s");
		$parameter=array($firstname,$lastname,$phoneno,$hami,$ishami,$chitfund,$aadhar,$passbook,$debitcard,$cheque,$pnote,$greensheet,$note,$forint,$intrate,$deleted,$updated);
		$sqlrows=array("firstname=?","lastname=?","phoneno=?","hami=?","ishami=?","chitfund=?","aadhar=?","passbook=?","debitcard=?","cheque=?","pnote=?","greensheet=?","note=?","forint=?","intrate=?","deleted=?","updated=?",);//db
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter)	;
		$bind_param = $this->bindParam($parameter)	;
		
	
		$sql=substr($sql, 0, -1);		
			$updatedata=array();
			foreach($update as $key => $val){
				array_push($updatedata,$key." = '".$val."'");
			}
			//echo json_encode($updatedata);
			$whereSql = " where ".join(' and ',$updatedata);
			// echo "UPDATE  customers SET ".$sql." ".$whereSql;
			$stmtR= $this->putQuery("UPDATE  customers SET ".$sql." ".$whereSql,$bind_string,$bind_param);
			return true;

	}

	public function editcollection($update,$chiti,$date,$amount,$notes,$sowji,$suri,$fullandevi,$received,$reverseid,$receivedfrom,$rcvddate,$deleted){

	
		$updated=date("Y-m-d H:i:s");
		$parameter=array($chiti,$date,$amount,$notes,$sowji,$suri,$fullandevi,$received,$reverseid,$receivedfrom,$rcvddate,$deleted,$updated);
		$sqlrows=array("chiti=?","date=?","amount=?","notes=?","sowji=?","suri=?","fullandevi=?","received=?","reverseid=?","receivedfrom=?","rcvddate=?","deleted=?","updated=?",);//db
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter)	;
		$bind_param = $this->bindParam($parameter)	;
		
		
		$sql=substr($sql, 0, -1);		
			$updatedata=array();
			foreach($update as $key => $val){
				array_push($updatedata,$key." = '".$val."'");
			}
			
			$whereSql = " where ".join(' and ',$updatedata);
			
			$stmtR= $this->putQuery("UPDATE  collection SET ".$sql." ".$whereSql,$bind_string,$bind_param);
			return true;

	}

	public function editreceived($update,$customer,$amount,$rcvddate,$asalu,$asaluid,$chiti,$colid,$collectedby,$paymentmode,$pmid,$note,$deleted){
		// echo $customer.$amount.$rcvddate.$asalu.$asaluid.$chiti.$colid,$collectedby,$paymentmode,$pmid.$note.$deleted;
		$updated=date("Y-m-d H:i:s");
		$parameter=array($customer,$amount,$rcvddate,$asalu,$asaluid,$chiti,$colid,$collectedby,$paymentmode,$pmid,$note,$deleted,$updated);
		$sqlrows=array("customer=?","amount=?","rcvddate=?","asalu=?","asaluid=?","chiti=?","colid=?","collectedby=?","paymentmode=?","pmid=?","note=?","deleted=?","updated=?",);//db
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter)	;
		$bind_param = $this->bindParam($parameter)	;
		
		
		$sql=substr($sql, 0, -1);		
			$updatedata=array();
			foreach($update as $key => $val){
				array_push($updatedata,$key." = '".$val."'");
			}
			
			$whereSql = " where ".join(' and ',$updatedata);
			// echo "UPDATE received SET ".$sql." ".$whereSql;
			$stmtR= $this->putQuery("UPDATE received SET ".$sql." ".$whereSql,$bind_string,$bind_param);
			// echo $stmtR;
			return true;
	}

	public function editasalu($update,$date,$customer,$amount,$chitiamount,$chiti,$note,$deleted){

	
		$updated=date("Y-m-d H:i:s");
		$parameter=array($date,$customer,$amount,$chitiamount,$chiti,$note,$deleted,$updated);
		$sqlrows=array("date=?","customer=?","amount=?","chitiamount=?","chiti=?","note=?","deleted=?","updated=?");//db
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter)	;
		$bind_param = $this->bindParam($parameter)	;
		
		
		$sql=substr($sql, 0, -1);		
			$updatedata=array();
			foreach($update as $key => $val){
				array_push($updatedata,$key." = '".$val."'");
			}
			
			$whereSql = " where ".join(' and ',$updatedata);
			//echo "UPDATE  chiti SET ".$sql." ".$whereSql;
			$stmtR= $this->putQuery("UPDATE  asalu SET ".$sql." ".$whereSql,$bind_string,$bind_param);
			// echo $stmtR;
			// $res = new array("status": SUCCESS);
			return true;
	}
	
	
		
	public function editchiti($update,$customer,$code,$date,$tYpe,$advintmonths,$iscountable,$chitiamount,$paymentmode,$pmid,$pdwtm,$interestrate,$ccomm,$ccommpaymentmode,$suriccomm,$sowji,$suri,$fullandevi,$reverse,$revcash,$status,$note,$irregular,$deleted){

	
		$updated=date("Y-m-d H:i:s");
		$parameter=array($customer,$code,$date,$tYpe,$advintmonths,$iscountable,$chitiamount,$paymentmode,$pmid,$pdwtm,$interestrate,$ccomm,$ccommpaymentmode,$suriccomm,$sowji,$suri,$fullandevi,$reverse,$revcash,$status,$note,$irregular,$deleted,$updated);
		$sqlrows=array("customer=?","code=?","date=?","tYpe=?","advintmonths=?","iscountable=?","amount=?","paymentmode=?","pmid=?","pdwtm=?","interestrate=?","ccomm=?","ccommpaymentmode=?","suriccomm=?","sowji=?","suri=?","fullandevi=?","reverse=?","revcash=?","status=?","note=?","irregular=?","deleted=?","updated=?");//db
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter)	;
		$bind_param = $this->bindParam($parameter)	;
		
		
		$sql=substr($sql, 0, -1);		
			$updatedata=array();
			foreach($update as $key => $val){
				array_push($updatedata,$key." = '".$val."'");
			}
			
			$whereSql = " where ".join(' and ',$updatedata);
			//echo "UPDATE  chiti SET ".$sql." ".$whereSql;
			$stmtR= $this->putQuery("UPDATE  chiti SET ".$sql." ".$whereSql,$bind_string,$bind_param);
			return true;
	}
	
	public function editchitfund($update,$customer,$date,$amount,$type,$status,$deleted){

	
		$updated=date("Y-m-d H:i:s");
		$parameter=array($customer,$date,$amount,$type,$status,$deleted,$updated);
		$sqlrows=array("customer=?","date=?","amount=?","type=?","status=?","deleted=?","updated=?");//db
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter)	;
		$bind_param = $this->bindParam($parameter)	;
		
		
		$sql=substr($sql, 0, -1);		
			$updatedata=array();
			foreach($update as $key => $val){
				array_push($updatedata,$key." = '".$val."'");
			}
			
			$whereSql = " where ".join(' and ',$updatedata);
			//echo "UPDATE  chitfund SET ".$sql." ".$whereSql;
			$stmtR= $this->putQuery("UPDATE  chitfund SET ".$sql." ".$whereSql,$bind_string,$bind_param);
			return true;
	}


	public function editdrcr($update,$date,$customer,$debit,$credit,$forint,$collectedby,$paymentmode,$pmid,$creditexp,$crid,$chitfundid,$showdaybook,$note,$note1,$deleted){
		$updated=date("Y-m-d H:i:s");
		$parameter=array($date,$customer,$debit,$credit,$forint,$collectedby,$paymentmode,$pmid,$creditexp,$crid,$chitfundid,$showdaybook,$note,$note1,$deleted,$updated);
		$sqlrows=array("date=?","customer=?","debit=?","credit=?","forint=?","collectedby=?","paymentmode=?","pmid=?","creditexp=?","crid=?","chitfundid=?","showdaybook=?","note=?","note1=?","deleted=?","updated=?");//db
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);
		
		
		$sql=substr($sql, 0, -1);		
			$updatedata=array();
			foreach($update as $key => $val){
				array_push($updatedata,$key." = '".$val."'");
			}
			
			$whereSql = " where ".join(' and ',$updatedata);
			//echo "UPDATE  drcr SET ".$sql." ".$whereSql;
			$stmtR= $this->putQuery("UPDATE  drcr SET ".$sql." ".$whereSql,$bind_string,$bind_param);
			return true;
	}
	
		
		

		





																							           						
	public function getchiti($id,$customer,$code,$date,$tYpe,$advintmonths,$iscountable,$chitiamount,$paymentmode,$paymentmodename,$pmid,$pdwtm,$interestrate,$ccomm,$ccommpaymentmode,$ccommpaymentmodename,$suriccomm,$sowji,$suri,$fullandevi,$reverse,$revcash,$status,$note,$irregular,$hami,$haminame,$created,$updated,$fields,$sort_by,$sort_order,$group_by,$limit,$offset,$totalcount){

		$parameter=array($id,$customer,$code,$date,$tYpe,$advintmonths,$iscountable,$chitiamount,$paymentmode,$paymentmodename,$pmid,$pdwtm,$interestrate,$ccomm,$ccommpaymentmode,$ccommpaymentmodename,$suriccomm,$sowji,$suri,$fullandevi,$reverse,$revcash,$status,$note,$irregular,$hami,$haminame,$created,$updated);
		$columns=array("a.id","a.customer","a.code","a.date","a.tYpe","a.advintmonths","a.iscountable","a.chitiamount","a.paymentmode","d.modename","a.pmid","a.pdwtm","a.interestrate","a.ccomm","a.ccommpaymentmode","d.modename","a.suriccomm","a.sowji","a.suri","a.fullandevi","a.reverse","a.revcash","a.status","a.note","a.irregular","b.hami","a.haminame","a.created","a.updated");
		
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		//$sqlrows=array("a.id=?","a.id>=?","a.id<?","a.party =?","b.name = ?","a.store=?","a.number1=?","a.number2=?","a.mode=?","date(date) >=?","date(date) <=?","date(duedate) >=?","date(duedate) <=?","date(a.updated)>=?","date(a.updated)<=?","date(a.created)>=?","date(a.created)<=?");
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql="where a.deleted=0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id,CONCAT(b.firstname,' ',b.lastname) as customername,a.code as code,a.note as note,b.hami as hami,CONCAT(c.firstname,' ',c.lastname) as haminame,a.paymentmode as paymentmode,d.modename as paymentmodename,a.pmid as pmid,a.amount as amount,e.modename as ccommpaymentmodename,a.irregular as irregular";
		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = "";
		if($group_by){
			$groupSql = " group by ".$group_by;
		}
		// echo "SELECT ".$fields." from  `chiti` a LEFT JOIN customers b on a.customer=b.id LEFT JOIN customers c on b.hami=c.id  ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
		return	$this->getQuery("SELECT ".$fields." from  `chiti` a LEFT JOIN customers b on a.customer=b.id LEFT JOIN customers c on b.hami=c.id LEFT JOIN paymentmodes d on a.paymentmode = d.id LEFT JOIN paymentmodes e on a.ccommpaymentmode = e.id ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
		
	}
 

	public function getchitfund($id,$customer,$date,$type,$amount,$created,$updated,$fields,$sort_by,$sort_order,$limit,$offset,$totalcount){

		$parameter=array($id,$customer,$date,$type,$amount,$created,$updated);
		$columns=array("a.id","a.customer ","a.date ","a.type","a.amount","a.created","a.updated");
		
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		//$sqlrows=array("a.id=?","a.id>=?","a.id<?","a.party =?","b.name = ?","a.store=?","a.number1=?","a.number2=?","a.mode=?","date(date) >=?","date(date) <=?","date(duedate) >=?","date(duedate) <=?","date(a.updated)>=?","date(a.updated)<=?","date(a.created)>=?","date(a.created)<=?");
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql="where a.deleted=0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id,CONCAT(b.firstname,' ',b.lastname) as customername";
		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = "";
/*		if($groupBy){
			$groupSql = " group by ".$groupBy;
		}*/
	//echo"SELECT ".$fields." from  `chiti` a LEFT JOIN customers b on a.customer=b.id   ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
	return	$this->getQuery("SELECT ".$fields." from  `chitfund` a LEFT JOIN customers b on a.customer=b.id   ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
		
	}

	public function createchiti($chiti){
		$created=date("Y-m-d H:i:s");
		$updated=date("Y-m-d H:i:s");
		
		$stmt=$this->conn->prepare("INSERT INTO chiti(customer,code,date,type,advintmonths,iscountable,amount,paymentmode,pmid,pdwtm,interestrate,ccomm,ccommpaymentmode,suriccomm,sowji,suri,fullandevi,reverse,revcash,status,note,irregular,created,updated) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssssssssssssssssssss",$chiti["customer"],$chiti["code"],$chiti["dAte"],$chiti["tYpe"],$chiti["advintmonths"],$chiti["iscountable"],$chiti["aMount"],$chiti["paymentmode"],$chiti["pmid"],$chiti["pdwtm"],$chiti["interestrate"],$chiti["ccomm"],$chiti["ccommpaymentmode"],$chiti["suriccomm"],$chiti["sowji"],$chiti["suri"],$chiti["fullandevi"],$chiti["reverse"],$chiti["revcash"],$chiti["status"],$chiti["note"],$chiti["irregular"],$created, $updated);
        $result = $stmt->execute();
	
		if($stmt->error)
		{
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
		}
        if ($result) {//product created
			$res["id"] = $this->conn->insert_id;
			$res["status"] = SUCCESS;
        }
		return $res;
	}

	public function createreceived($colid,$received){		
		// echo "coll".$colid.json_encode($received);
		$created=date("Y-m-d H:i:s");
		$updated=date("Y-m-d H:i:s");
	
		$stmt=$this->conn->prepare("INSERT INTO received(customer,amount,rcvddate,asalu,asaluid,chiti,colid,collectedby,paymentmode,pmid,note,created,updated) values(?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssssssssss",$received["customer"],$received["amount"],$received["rcvddate"],$received["asalu"],$received["asaluid"],$received["chiti"],$colid,$received['collectedby'],$received["paymentmode"],$received["pmid"],$received["note"],$created,$updated);
        $result = $stmt->execute();
	
		if($stmt->error)
		{
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
		}
        if ($result) {//product created
			$res["id"] = $this->conn->insert_id;
			$res["status"] = SUCCESS;
        }
		return $res;
	}


	//   $id,$customer,$amount,$customername,$rcvddate,$asalu,$asaluid,$asaluchiti,$chiti,$colid,$colamt,$code,$sowjicomm,$suricomm,$fullandevicomm,$note,$created,$updated,$fields,$sort_by,$sort_order,$group_by,$limit,$offset,$totalcount
	public function getreceivedamount($id,$customer,$amount,$customername,$rcvddate,$asalu,$asaluid,$asaluchiti,$chiti,$colid,$colamt,$coldate,$code,$sowjicomm,$suricomm,$fullandevicomm,$collectedby,$collectorname,$paymentmode,$paymentmodename,$pmid,$note,$created,$updated,$fields,$sort_by,$sort_order,$group_by,$limit,$offset,$totalcount){

		$parameter=array($id,$customer,$amount,$customername,$rcvddate,$asalu,$asaluid,$chiti,$colid,$colamt,$coldate,$code,$sowjicomm,$suricomm,$fullandevicomm,$collectedby,$collectorname,$paymentmode,$paymentmodename,$pmid,$note,$created,$updated);
		$columns=array("a.id","a.customer","a.amount","a.customername","a.rcvddate","a.asalu","a.asaluid","a.chiti","a.colid","c.amount","c.date","d.code","c.sowji","c.suri","c.fullandevi","a.collectedby","q.name","a.paymentmode","p.modename","a.pmid","a.note","a.created","a.updated");
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		//$sqlrows=array("a.id=?","a.id>=?","a.id<?","a.party =?","b.name = ?","a.store=?","a.number1=?","a.number2=?","a.mode=?","date(date) >=?","date(date) <=?","date(duedate) >=?","date(duedate) <=?","date(a.updated)>=?","date(a.updated)<=?","date(a.created)>=?","date(a.created)<=?");
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql="where a.deleted=0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id , CONCAT(b.firstname, ' ', b.lastname) as customername ,a.customer as customer,a.paymentmode as paymentmode,p.modename as paymentmodename,a.pmid as pmid,a.note as note ,c.amount as colamt,a.amount as amount , a.colid as colid,a.rcvddate as rcvddate,d.code as code,c.sowji as sowjicomm,c.suri as suricomm,c.fullandevi as fullandevicomm,s.chiti as asaluchiti,a.chiti as chiti,c.date as coldate,a.collectedby as collectedby,q.name as collectorname ";
		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = "";
		if($group_by){
			$groupSql = " group by ".$group_by;
		}
		// if($chiti == 106){
		// 	echo "SELECT ".$fields." from `received`a left join customers b on a.customer=b.id LEFT JOIN collection c on a.colid= c.id LEFT JOIN chiti d on a.chiti = d.id LEFT JOIN asalu s on a.asaluid = s.id ".$sql." ".$groupSql." ".$sortingSql." ".$limitSql;
		// }
		// echo "SELECT ".$fields." from `received`a left join customers b on a.customer=b.id LEFT JOIN collection c on a.colid= c.id LEFT JOIN chiti d on a.chiti = d.id LEFT JOIN asalu s on a.asaluid = s.id LEFT JOIN paymentmodes p on a.paymentmode = p.id LEFT JOIN collectors q on a.collectedby = q.id ".$sql." ".$groupSql." ".$sortingSql." ".$limitSql;
	return $this->getQuery("SELECT ".$fields." from `received`a left join customers b on a.customer=b.id LEFT JOIN collection c on a.colid= c.id LEFT JOIN chiti d on a.chiti = d.id LEFT JOIN asalu s on a.asaluid = s.id LEFT JOIN paymentmodes p on a.paymentmode = p.id and p.deleted = 0 LEFT JOIN collectors q on a.collectedby = q.id ".$sql." ".$groupSql." ".$sortingSql." ".$limitSql,$bind_string, $bind_param);
		
	}


					// return array("id","customer","amount","customername","rcvddate","asalu","asaluid","asaluchiti","chiti","colid","colamt","coldate","code","sowjicomm","suricomm","fullandevicomm","collectedby","paymentmode","paymentmodename","pmid","note","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
	public function getcolreceived($id,$customer,$amount,$customername,$rcvddate,$asalu,$asaluid,$asaluchiti,$chiti,$colid,$colamt,$coldate,$code,$sowjicomm,$suricomm,$fullandevicomm,$collectedby,$collectorname,$paymentmode,$paymentmodename,$pmid,$note,$created,$updated,$fields,$sort_by,$sort_order,$group_by,$limit,$offset,$totalcount){
		// echo "id".$id,"customer".$customer,"amount".$amount,"customername".$customername,"rcvddate".$rcvddate,"asalu".$asalu,"asaluid".$asaluid,"asaluchiti".$asaluchiti,"chiti".$chiti,"colid".$colid,"colamt".$colamt,"coldate".$coldate,"code".$code,"sowjicomm".$sowjicomm,"suricomm".$suricomm,"fullandevicomm".$fullandevicomm,"collectedby".$collectedby,"collectorname".$collectorname,"paymentmode".$paymentmode,"paymentmodename".$paymentmodename,"pmid".$pmid,"note".$note,"created".$created,"updated".$updated,"fields".$fields,"sort_by".$sort_by,"sort_order".$sort_order,"group_by".$group_by,"limit".$limit,"offset".$offset,"totalcount".$totalcount;
		$parameter=array($id,$customer,$amount,$customername,$rcvddate,$asalu,$asaluid,$chiti,$colid,$colamt,$coldate,$code,$sowjicomm,$suricomm,$fullandevicomm,$collectedby,$collectorname,$paymentmode,$paymentmodename,$pmid,$note,$created,$updated);
		$columns=array("a.id","a.customer","a.amount","a.customername","a.rcvddate","a.asalu","a.asaluid","a.chiti","a.colid","c.amount","c.date","d.code","c.sowji","c.suri","c.fullandevi","a.collectedby","q.name","a.paymentmode","p.modename","a.pmid","a.note","a.created","a.updated");
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		//$sqlrows=array("a.id=?","a.id>=?","a.id<?","a.party =?","b.name = ?","a.store=?","a.number1=?","a.number2=?","a.mode=?","date(date) >=?","date(date) <=?","date(duedate) >=?","date(duedate) <=?","date(a.updated)>=?","date(a.updated)<=?","date(a.created)>=?","date(a.created)<=?");
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql="where a.deleted=0 and b.deleted = 0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id,m.pmcredit as pmcredit , CONCAT(b.firstname, ' ', b.lastname) as customername ,a.customer as customer,a.amount as amount,a.paymentmode as paymentmode,p.modename as paymentmodename,a.pmid as pmid,a.note as note ,c.amount as colamt, d.tYpe as tYpe, a.colid as colid,a.rcvddate as rcvddate,d.code as code,c.sowji as sowjicomm,c.suri as suricomm,c.fullandevi as fullandevicomm,s.chiti as asaluchiti,a.chiti as chiti,c.date as coldate,a.collectedby as collectedby,q.name as collectorname ";
		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = "";
		if($group_by){
			$groupSql = " group by ".$group_by;
		}
		// if($chiti == 106){
		// 	echo "SELECT ".$fields." from `received`a left join customers b on a.customer=b.id LEFT JOIN collection c on a.colid= c.id LEFT JOIN chiti d on a.chiti = d.id LEFT JOIN asalu s on a.asaluid = s.id ".$sql." ".$groupSql." ".$sortingSql." ".$limitSql;
		// }
		//echo "SELECT ".$fields." from `received`a left join customers b on a.customer=b.id LEFT JOIN collection c on a.colid= c.id and c.deleted = 0 LEFT JOIN chiti d on a.chiti = d.id and d.deleted = 0 LEFT JOIN asalu s on a.asaluid = s.id and s.deleted = 0 LEFT JOIN paymentmodes p on a.paymentmode = p.id and p.deleted = 0 LEFT JOIN (SELECT x.id as id,sum(y.credit) as pmcredit, y.tableid as pmid from received x left join pmtrans y on x.id = y.tableid and y.tablename = 'received' and y.paymentmode = 1 and y.deleted = 0 group by y.tableid) m on a.id = m.id left join collectors q on a.collectedby = q.id ".$sql." ".$groupSql." ".$sortingSql." ".$limitSql;
		return $this->getQuery("SELECT ".$fields." from `received`a left join customers b on a.customer=b.id LEFT JOIN collection c on a.colid= c.id and c.deleted = 0 LEFT JOIN chiti d on a.chiti = d.id and d.deleted = 0 LEFT JOIN asalu s on a.asaluid = s.id and s.deleted = 0 LEFT JOIN paymentmodes p on a.paymentmode = p.id and p.deleted = 0 LEFT JOIN (SELECT x.id as id,sum(y.credit) as pmcredit, y.tableid as pmid from received x left join pmtrans y on x.id = y.tableid and y.tablename = 'received' and y.paymentmode = 1 and y.deleted = 0 group by y.tableid) m on a.id = m.id left join collectors q on a.collectedby = q.id ".$sql." ".$groupSql." ".$sortingSql." ".$limitSql,$bind_string, $bind_param);
							//SELECT *,SUM(a.amount) as amount,SUM(b.credit) as credit,SUM(b.debit) as debit FROM `received` a left join pmtrans b on a.id = b.tableid and b.paymentmode = 1 WHERE collectedby = 1 GROUP by a.pmid;	
							
	}
									
									


	public function getcollection($id,$chiti,$code,$tYpe,$status,$chitiamount,$chitidate,$date,$amount,$notes,$sowji,$sowjitotal,$suri,$fullandevi,$iscountable,$received,$reverseid,$receivedfrom,$customer,$customerFL,$hami,$haminame,$rcvddate,$created,$updated,$fields,$sort_by,$sort_order,$group_by,$limit,$offset,$totalcount){
								  
		$parameter=array($id,$chiti,$code,$tYpe,$status,$chitiamount,$chitidate,$date,$amount,$notes,$sowji,$sowjitotal,$suri,$fullandevi,$iscountable,$received,$reverseid,$receivedfrom,$customer,$customerFL,$hami,$haminame,$rcvddate,$created,$updated);
		$columns=array("a.id","a.chiti ","b.code","b.tYpe","b.status","b.amount","b.date","a.date","a.amount","a.notes","a.sowji","a.sowjitotal","a.suri","a.fullandevi","b.iscountable","a.received","a.reverseid","a.receivedfrom","b.customer","a.customerFL","c.hami","a.haminame","a.rcvddate","a.created","a.updated");
		
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		//$sqlrows=array("a.id=?","a.id>=?","a.id<?","a.party =?","b.name = ?","a.store=?","a.number1=?","a.number2=?","a.mode=?","date(date) >=?","date(date) <=?","date(duedate) >=?","date(duedate) <=?","date(a.updated)>=?","date(a.updated)<=?","date(a.created)>=?","date(a.created)<=?");
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql="where a.deleted=0 and b.deleted = 0 and c.deleted=0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	

		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id,b.customer as customer,CONCAT(c.firstname,' ',c.lastname) as customerFL,b.code as code,b.tYpe as tYpe,b.status as status,b.amount as chitiamount,b.date as chitidate, a.date as date, a.amount as amount, a.notes as notes ,a.sowji as sowji,a.suri as suri , a.fullandevi as fullandevi, a.received as received,a.rcvddate as rcvddate,a.receivedfrom as receivedfrom,CONCAT(d.firstname, ' ' , d.lastname ) as haminame,a.chiti as chiti ";
		
		$groupSql = "";
		if($group_by){
			$groupSql = " group by ".$group_by;
			$seperateField .= ", GROUP_CONCAT(a.id) as colids";
		}

		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
	
		//echo "SELECT ".$fields." from  `collection` a left join chiti b on a.chiti = b.id left JOIN customers c on b.customer=c.id left JOIN customers d on c.hami=d.id LEFT JOIN received r on a.id = r.colid LEFT JOIN paymentmodes p on r.paymentmode = p.id ".$sql." " .$groupSql. "  ".$sortingSql."  ".$limitSql;
		return	$this->getQuery("SELECT ".$fields." from  `collection` a left join chiti b on a.chiti = b.id left JOIN customers c on b.customer=c.id left JOIN customers d on c.hami=d.id ".$sql." " .$groupSql. "  ".$sortingSql."  ".$limitSql,$bind_string, $bind_param);

	}




	
	public function getFirms($id,$name,$address,$city,$postal,$state,$gst,$aadhar,$phno,$initialcash,$balance,$bank,$bankbranch,$accno,$accname,$bankinitialbalance,$bankbalance,$created,$updated,$fields,$sort_by,$sort_order,$limit,$offset,$totalcount){

		$parameter=array($id,$name,$address,$city,$postal,$state,$gst,$initialcash,$balance,$bank,$bankbranch,$accno,$accname,$bankinitialbalance,$bankbalance,$created,$updated);
		$columns=array("a.id","a.name ","a.address ","a.city","a.postal","a.state","a.gst ","a.initialcash","a.balance+a.initialcash","b.name","b.branch","b.accno","b.accname","b.initialbalance","b.balance+b.initialbalance","a.created","a.updated");
		
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		//$sqlrows=array("a.id=?","a.id>=?","a.id<?","a.party =?","b.name = ?","a.store=?","a.number1=?","a.number2=?","a.mode=?","date(date) >=?","date(date) <=?","date(duedate) >=?","date(duedate) <=?","date(a.updated)>=?","date(a.updated)<=?","date(a.created)>=?","date(a.created)<=?");
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql="where a.deleted=0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id,a.initialcash+a.cashinhand as presentbalance,a.name as name,a.created as created,a.updated as updated,c.name as statename,c.code as statecode";
		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = "group by a.id";
/*		if($groupBy){
			$groupSql = " group by ".$groupBy;
		}*/
	//echo "SELECT ".$fields." from  firm a  LEFT JOIN bank b on a.id = b.firmid ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql
	return	$this->getQuery("SELECT ".$fields." from  firm a  LEFT JOIN bank b on a.id = b.firmid LEFT JOIN state c on a.state=c.id ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
		
	}
 


	public function editFirm($update,$name,$address,$city,$postal,$state,$aadhar,$gst,$phno1,$phno2,$initialcash,$initialbankbalance,$balance,$bankbalance,$deleted){
	
		$updated=date("Y-m-d H:i:s");
		$parameter=array($name,$address,$city,$postal,$state,$aadhar,$gst,$phno1,$phno2,$initialcash,$initialbankbalance,$balance,$bankbalance,$deleted);
		$sqlrows=array("name=?","address=?","city=?","postal=?","state=?","aadhar=?","gst=?","phno1=?","phno2=?","initialcash=?","initialbankbalance=?","balance=?","bankbalance=?","deleted=?");
		$sql="";
		$sql =$this->framePutSql($sql,$parameter,$sqlrows);
		$a_params = array();
		$bind_string = $this->bindString($parameter)	;
		$bind_param = $this->bindParam($parameter)	;
		
		
		$sql=substr($sql, 0, -1);		
			$updatedata=array();
			foreach($update as $key => $val){
				array_push($updatedata,$key." = '".$val."'");
			}$whereSql="";
if(sizeof($updatedata)>0){
			$whereSql = " where ".join(' and ',$updatedata);
}

			$stmtR= $this->putQuery("UPDATE  firm SET ".$sql." ".$whereSql,$bind_string,$bind_param);
			return true;
		}

 	
// public function getOneRecord($query) {
//         $r = $this->conn->query($query.' LIMIT 1') or die($this->conn->error.__LINE__);
//         return $result = $r->fetch_assoc();    
//     }
	 


public function getSession(){
	if (!isset($_SESSION)) {
		session_start();
	}
	$sess = array();
	// echo json_encode($_SESSION);
	if(isset($_SESSION["finance"]['uid'])){
		$sess["uid"] = $_SESSION["finance"]['uid'];
		$sess["name"] = $_SESSION["finance"]['name'];
		$sess["firstname"] = $_SESSION["finance"]['firstname'];
		$sess["lastname"] = $_SESSION["finance"]['lastname'];
		$sess["api_key"] = $_SESSION["finance"]["api_key"];
		$sess['collector'] = $_SESSION['finance']['collector'];
		// $sess["from"] = $_SESSION["finance"]["from"];
		// $sess["to"] = $_SESSION["finance"]["to"];
		$sess["firm"] = $_SESSION["finance"]['firm'];
		// $sess["gstin"] = $_SESSION["finance"]['gstin'];
		// $sess["address"] = $_SESSION["finance"]['address'];
		// $sess["code"] = $_SESSION['code'];
		// $sess["db"]= $_SESSION['db'];
	}else{
		$sess["uid"] = '';
		$sess["name"] = 'Guest';
		$sess["api_key"] = '';
		$sess["collector"] = '';
		$sess["firstname"] = "";
		$sess["lastname"] = "";
		$sess["from"] = "";
		$sess["to"] = "";

		$data = $this->getOneRecord("select * from firm");
		$sess["firm"] = $data["name"];
		$sess["gstin"] = $data["gstin"];
		$sess["address"] = $data["address"];
		$sess["code"] =  $data['code'];
		$sess["db"]= "" ;
	}
	return $sess;
}	
	
	
	
	public function destroySession(){
		if (!isset($_SESSION)) {
		session_start();
		}
		if(isset($_SESSION) && isset($_SESSION['finance']))
		{
			unset($_SESSION['finance']);
			unset($_SESSION);
			$info='info';
			if(isSet($_COOKIE[$info]))
			{
				setcookie ($info, '', time() - $cookie_time);
			}
			$msg="Logged Out Successfully...";
		}
		else
		{
			$msg = "Not logged in...";
		}
		return $msg;
	}
 
 public function isValidApiKey($api_key) {
	if (!isset($_SESSION)) {
			session_start();
		}
		$subsql = "";
		if(isset($_SESSION["account"]) && isset($_SESSION["account"]["uid"])){
		$subsql = " and uid = ".$_SESSION["account"]["uid"];
		}
        $stmt = $this->conn->prepare("SELECT uid,role from users WHERE api_key = ? ".$subsql);
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
	public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT uid FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }
   
   
   //helper function
   private function frameSqlRows($param,$col,$rows){
	$rows=array();
	$extrawhere="";
	$k1=0;
	$param1=array();

		for($k=0;$k<sizeof($col);$k++){
	
		$data=$param[$k];
		if(isset($param[$k])){ 
		if(json_decode($param[$k]) && !intval($param[$k]) && (gettype($param[$k]) == "string" )){
			$data = json_decode($param[$k]);
			$temp = array();
		foreach($data as $key => $val){
			$temp[$key] = $val;
		}
		if(($temp["op"] == "=")&&($temp["value"] || gettype($temp["value"] == "number"))){
			array_push($rows,$col[$k] ."= ?");
			$param1[$k1++] = $temp["value"];
		}
		if(($temp["op"] == "!=")&&($temp["value"] || gettype($temp["value"] == "number"))){
			array_push($rows,$col[$k] ."!= ?");
			$param1[$k1++] = $temp["value"];
		}
		if(($temp["op"] == "like")&&$temp["value"]){
			array_push($rows,$col[$k] ." like ?");
			$param1[$k1++] = $temp["value"];
		}
		if(($temp["op"] == ">")&&$temp["value"]){
			array_push($rows,$col[$k] ." > ?");
			$param1[$k1++] = $temp["value"];
		}
		if(($temp["op"] == ">=")&&$temp["value"]){
			array_push($rows,$col[$k] ." >=?");
			$param1[$k1++] = $temp["value"];
		}
		if(($temp["op"] == "<")&&$temp["value"]){
			array_push($rows,$col[$k] ." < ?");
			$param1[$k1++] = $temp["value"];
		}
		if(($temp["op"] == "<=")&&$temp["value"]){
			array_push($rows,$col[$k] ." <= ?");
			$param1[$k1++] = $temp["value"];
		}
		if(($temp["op"] == "In")&&$temp["value"]){
			//$extrawhere.=$col[$k]." IN (";
			$extrawhere.=" ".$col[$k]." IN (".$temp["value"].") and";
			//for($v=0;$v<sizeof($temp["value"]);$v++){
			//$extrawhere.= $temp["value"][$v].",";
			//}
			//$extrawhere=substr($extrawhere, 0, -1);		
			//$extrawhere.= ") and ";
		}
		if(($temp["op"] == "Not In")&&$temp["value"]){
			$extrawhere.=" ".$col[$k]." NOT IN (".$temp["value"].") and";
		}
		if(($temp["op"] == "Between")&&$temp["value"]){
			$extrawhere.=" ".$col[$k]." Between '".$temp["value"] . "' and '".$temp["value1"]. "' and ";
		}
		if(($temp["op"] == "Not Between")&&$temp["value"]){
			$extrawhere.=" ".$col[$k]." Not Between '".$temp["value"] . "' and '".$temp["value1"]. "' and ";
		}	
		if(($temp["op"] == "Starts With")&&$temp["value"]){
			array_push($rows,$col[$k] ." like ?");
			$param1[$k1++] = $temp["value"] . "%";
		}
		if(($temp["op"] == "Ends With")&&$temp["value"]){
			array_push($rows,$col[$k] ." like ?");
			$param1[$k1++] = "%".$temp["value"];
		}
		if(($temp["op"] == "Consist of")&&$temp["value"]){
			array_push($rows,$col[$k] ." like ?");
			$param1[$k1++] = "%".$temp["value"]."%";
		}
		if(($temp["op"] == "Not Consist")&&$temp["value"]){
			array_push($rows,$col[$k] ." not like ?");
			$param1[$k1++] = "%".$temp["value"]."%";
		}
		
		/*if(($temp["op"] == "Between")&&$temp["value"]){
			array_push($rows,$col[$k] ." between ? ");
			$param1[$k1++] = $temp["value"];
		}*/
		//echo $param[$k];
	}else{
	array_push($rows,$col[$k] ."= ?");
	
	$param1[$k1++] = $param[$k];
	}
		}
		
	}
	
	$response = array();
	$response["row"] = $rows;
	$response["parameter"] = $param1;
	$response["extra"]=$extrawhere;
return $response;	
	}	
	
	private function frameSql($sqlString,$parameter,$sqlparam){
		$param=array();
		for($k=0;$k<sizeof($parameter);$k++){
			if($parameter[$k]!=""){
				array_push($param,$sqlparam[$k]);
			}
		}
		if(count($param)>0)
		{
			for($i=0;$i<count($param);$i++){
				$sqlString.=" ".$param[$i]." and";
			}
		}	
		return $sqlString;
	}	
	
	private function framePutSql($sqlString,$parameter,$sqlparam){
		$param=array();
		for($k=0;$k<sizeof($parameter);$k++){
			if($parameter[$k]!="" || gettype($parameter[$k]) != "string"|| $parameter[$k]=="0"  ){
				array_push($param,$sqlparam[$k]);
			}
		}
		if(count($param)>0)
		{
			for($i=0;$i<count($param);$i++){
				$sqlString.=" ".$param[$i]." ,";
			}
		}	
		return $sqlString;
	}	
	
	private function bindString($parameter){
		$bind=array();
		for($i=0;$i<sizeof($parameter);$i++){
				if($parameter[$i]!="" || gettype($parameter[$i])!= "string"){
					$type=gettype($parameter[$i]);
					array_push($bind,$type[0]);
				}
			}
		return $bind;
	}
	
	private function bindParam($parameter){
		$bind=array();
		for($i=0;$i<sizeof($parameter);$i++){
				if($parameter[$i]!="" || gettype($parameter[$i])!= "string") {
				if($parameter[$i] == "EMPTY_PARAM"){
					array_push($bind,"");
				}
				else{
				array_push($bind,$parameter[$i]);
				}
			}
		}
		return $bind;
	}
	
	public function sortQuery($by,$order,$fields,$assigner){
		$res = "";
		if($by){//if given sorting field name
			$by = explode(",",$by);//seperate fields into array using ,
			if($order){
				$order = explode(",",$order);//if given sorting order then split them to by seperating commas
			}
			$res = "order by ";
			for($i= 0 ;$i<sizeof($by);$i++){
				$sort = "desc";
				if(isset($order[$i])){
					$sort =$order[$i];
				}
				$byf = $this->isExistInArray($by[$i],$fields,$assigner);
				if(!$byf){
					$byf = $by[$i];
				}
				$res.= $byf." ".$sort." , ";
			}
			$res=substr($res, 0, -2);	
		}
		return $res;
	}
	
	public function limitQuery($off,$size){
		$res =" limit ";
		if($off){
			$res.= $off.", ";
		}
		if(!$size){
			$size = 3000;
		}
		$res.= $size;
		return $res;
	}


	

    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }

    /* ------------- `tasks` table method ------------------ */

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
	
	public function createcollection($collection){
		
		$created=date("Y-m-d H:i:s");
		$updated=date("Y-m-d H:i:s");
		for($i=0;$i<sizeof($collection);$i++){
		//echo "i:" .$i ;
		$stmt=$this->conn->prepare("INSERT INTO collection(chiti,date,amount,notes,sowji,suri,fullandevi,received,reverseid,receivedfrom,rcvddate,created,updated) values(?,?,?,?,?,?,?,?,?,?,?,?,?)");
		$stmt->bind_param("sssssssssssss", $collection[$i]["chiti"],$collection[$i]["date"],$collection[$i]["amount"],$collection[$i]["notes"],$collection[$i]["sowji"],$collection[$i]["suri"],$collection[$i]["fullandevi"],$collection[$i]["received"],$collection[$i]["reverseid"],$collection[$i]["receivedfrom"],$collection[$i]["rcvddate"],$created, $updated);
			
        $result = $stmt->execute();
		
		if($stmt->error)
		{
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
			echo $stmt->error;
		}
        if ($result) {//product created
			$res["id"] = $this->conn->insert_id;
			$res["status"] = SUCCESS;
			//echo $this->conn->insert_id;
        }
		}
		
		return $res;
	
	
	}

	public function createasalu($asalu){

		$created=date("Y-m-d H:i:s");
		$updated=date("Y-m-d H:i:s");
		$stmt=$this->conn->prepare("INSERT INTO asalu(date,customer,amount,chitiamount,chiti,note,created,updated) values(?,?,?,?,?,?,?,?)");
		$stmt->bind_param("ssssssss", $asalu["date"],$asalu["customer"],$asalu["amount"],$asalu["chitiamount"],$asalu["chiti"],$asalu["note"],$created, $updated);
			
        $result = $stmt->execute();
		
		if($stmt->error)
		{
			$res["error"] = $stmt->error;
			$res["status"] = FAILED;
			echo $stmt->error;
		}
        if ($result) {//product created
			$res["id"] = $this->conn->insert_id;
			$res["status"] = SUCCESS;
			//echo $this->conn->insert_id;
        }
		
		
		return $res;
	
	
	}

	public function getasalu($id,$date,$customer,$amount,$chitiamount,$chiti,$code,$customername,$note,$paymentmode,$paymentmodename,$pmid,$rcvdid,$rcvdnote,$created,$updated,$fields,$sort_by,$sort_order,$groupBy,$limit,$offset,$totalcount){

		$parameter=array($id,$date,$customer,$amount,$chitiamount,$chiti,$code,$customername,$note,$paymentmode,$paymentmodename,$pmid,$rcvdid,$rcvdnote,$created,$updated);
		$columns=array("a.id","a.date ","a.customer ","a.amount","a.chitiamount","a.chiti","b.code","a.customername","a.note","r.paymentmode","p.modename","r.pmid","r.rcvdid","r.note","a.created","a.updated");
		
		
		$sqlrows=array();
		$data = $this->frameSqlRows($parameter,$columns,$sqlrows);
		
		$sqlrows = $data["row"];
		$parameter = $data["parameter"];
	
		
		//$sqlrows=array("a.id=?","a.id>=?","a.id<?","a.party =?","b.name = ?","a.store=?","a.number1=?","a.number2=?","a.mode=?","date(date) >=?","date(date) <=?","date(duedate) >=?","date(duedate) <=?","date(a.updated)>=?","date(a.updated)<=?","date(a.created)>=?","date(a.created)<=?");
		
		$qry=array();
		$a_params = array();
		$bind_string = $this->bindString($parameter);
		$bind_param = $this->bindParam($parameter);

		$sql="where a.deleted=0 and ";
		$sql =$this->frameSql($sql,$parameter,$sqlrows);
		$sql.=	$data['extra'];	
		$sql=substr($sql, 0, -4);		
		$sortFields = array();
		$sortAssigner = array();
		$sortingSql = $this->sortQuery($sort_by,$sort_order,$sortFields,$sortAssigner);
		$limitSql = $this->limitQuery($offset,$limit);
		
		$seperateField = "a.id as id,a.date as date, a.customer as customer, a.amount as amount,a.chitiamount as chitiamount,a.chiti as chiti,b.code as code,CONCAT(c.firstname, ' ' ,c.lastname) as customername,a.note as note,r.paymentmode as paymentmode,r.pmid as pmid,r.id as rcvdid,r.note as rcvdnote,p.modename as paymentmodename,r.collectedby as collectedby";
		if(!$fields)
		$fields = "*,".$seperateField;
		else
		$fields = $seperateField.",".$fields;
		
		if($totalcount){
			$fields = "count(*)";
		}	
		
		$groupSql = "";
		if($groupBy){
			$groupSql = " group by ".$groupBy;
		}
		// echo "SELECT ".$fields." from  asalu a left join chiti b on a.chiti=b.id  left join customers c on a.customer=c.id left join received r on a.id = r.asaluid LEFT JOIN paymentmodes p on r.paymentmode = p.id ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql;
		return	$this->getQuery("SELECT ".$fields." from  asalu a left join chiti b on a.chiti=b.id  left join customers c on a.customer=c.id left join received r on a.id = r.asaluid LEFT JOIN paymentmodes p on r.paymentmode = p.id ".$sql." ".$sortingSql."  ".$groupSql." ".$limitSql,$bind_string, $bind_param);
		
	}

	private function getQuery($qry,$bind_string, $bind_param){
	
	//$stmt = $this->conn->prepare("SELECT ".$fields." FROM paymentin a left join party b on a.party=b.id left join paymentmode c on a.mode=c.id LEFT JOIN cheque e on e.payment=a.id LEFT JOIN banktransfer f on f.payment=a.id LEFT JOIN bank g on f.acc = g.id ".$sql." ".$sortingSql." ".$limitSql);
		$stmt = $this->conn->prepare($qry);
		$n = count($bind_string);
		if($n>0){
			$param_type = '';
				for($i = 0; $i < $n; $i++) {
				$param_type .= $bind_string[$i];
			}
 
			/* with call_user_func_array, array params must be passed by reference */
			$a_params[] = & $param_type;
 			for($i = 0; $i < $n; $i++) {
				/* with call_user_func_array, array params must be passed by reference */
				$a_params[] = & $bind_param[$i];
			}
 
			/* Prepare statement */
			if($stmt === false) {		
				trigger_error('Wrong SQL: query Error: ' . $this->conn->errno . ' ' . $this->conn->error, E_USER_ERROR);
			}
			/* use call_user_func_array, as $stmt->bind_param('s', $param); does not accept params array */
			call_user_func_array(array($stmt, 'bind_param'), $a_params);
		}
		/* Execute statement */
		$stmt->execute();
		return $this->fetch($stmt);
		 $stmt->close();
	}	
	
	private function putQuery($qry,$bind_string, $bind_param){
		$stmt = $this->conn->prepare($qry);
		$n = count($bind_string);
		if($n>0){
			$param_type = '';
				for($i = 0; $i < $n; $i++) {
				$param_type .= $bind_string[$i];
			}
 
			/* with call_user_func_array, array params must be passed by reference */
			$a_params[] = & $param_type;
			for($i = 0; $i < $n; $i++) {
				/* with call_user_func_array, array params must be passed by reference */
				$a_params[] = & $bind_param[$i];
			}
 
			/* Prepare statement */
			if($stmt === false) {		
				trigger_error('Wrong SQL:  Error: ' . $this->conn->errno . ' ' . $this->conn->error, E_USER_ERROR);
				//trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $this->conn->errno . ' ' . $this->conn->error, E_USER_ERROR);
			}
			/* use call_user_func_array, as $stmt->bind_param('s', $param); does not accept params array */
			call_user_func_array(array($stmt, 'bind_param'), $a_params);
		}
		/* Execute statement */
		
		$result=$stmt->execute();
        $stmt->close();
        if($result)
		return SUCCESS;
		return FAILED;
	}	
	
	public function isExistInArray($ord,$field,$assigne){
		for($j = 0;$j<sizeOf($field);$j++){
			if($field[$j] == $ord){
				return $assigne[$j];
			}
		}
		return "";
	}
	public function fetch($result)
	{    
		$array = array();
		if($result instanceof mysqli_stmt)
		{
			$result->store_result();
			$variables = array();
			$data = array();
			$meta = $result->result_metadata();
			while($field = $meta->fetch_field())
				$variables[] = &$data[$field->name]; // pass by reference
        
			call_user_func_array(array($result, 'bind_result'), $variables);
			$i=0;
			while($result->fetch())
			{
				$array[$i] = array();
				foreach($data as $k=>$v)
					$array[$i][$k] = $v;
				$i++;
            
            // don't know why, but when I tried $array[] = $data, I got the same one result in all rows
			}
		}
		else
		if($result instanceof mysqli_result)
		{
			while($row = $result->fetch_assoc())
            $array[] = $row;
		}
		return $array;
	}


	//$user     = $db->getOneRecord("select *,a.name as name,f.name as firm , b.name as type from users a LEFT JOIN role  b on a.role=b.id LEFT JOIN firm f on a.firm=f.id where a.name='$name'");
	// public function getOneRecord($query) {
	// 	// echo json_encode($query);
    //     $r = $this->conn->query($query.' LIMIT 100') or die($this->conn->error.__LINE__);
	// 	return $result = mysqli_fetch_all($r, MYSQLI_ASSOC);

    //     // return $result = $r->fetch_all($r);    
    // }

	function sendTelegram($msg, $group)
	{
		// echo "hi";
		if ($group == "main") {
			$url = "https://api.telegram.org/bot6235347930:AAFnxXPu5wkZRvGNbbpB2JEelNWf6Zx6l0w/sendMessage?chat_id=-1001858134013&text=" . $msg;

		} else if ($group == "full") {
			$url = "https://api.telegram.org/bot6235347930:AAFnxXPu5wkZRvGNbbpB2JEelNWf6Zx6l0w/sendMessage?chat_id=-1001858134013&text=" . $msg;

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
	}



	public function getmultiRecord($query) {
		// echo json_encode($query.' LIMIT 100');
        $r = $this->conn->query($query.' LIMIT 100') or die($this->conn->error.__LINE__);
		return $result = mysqli_fetch_all($r, MYSQLI_ASSOC);

        // return $result = $r->fetch_all($r);    
    }
	
	public function getdynamicRecord($query,$limit) {
		// echo ($query.' LIMIT '. $limit);
        $r = $this->conn->query($query.' LIMIT '. $limit) or die($this->conn->error.__LINE__);
		return $result = mysqli_fetch_all($r, MYSQLI_ASSOC);

        // return $result = $r->fetch_all($r);    
    }

	public function getOneRecord($query) {
        $r = $this->conn->query($query.' LIMIT 1') or die($this->conn->error.__LINE__);
        return $result = $r->fetch_assoc();    
    }	
	
	public function getFunctionParam($object){
		if($object == "customers"){
			return array("id","firstname","lastname","fullname","phoneno","hami","ishami","chitfund","hamifirstname","hamilastname","hamiphoneno","aadhar","passbook","debitcard","cheque","pnote","greensheet","note","forint","intrate","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
		}
		if($object == "cfcustomers"){
			return array("id","fname","lname","phone","first","firstid","firstidname","created","updated","fields","sort_by","sort_order","limit","offset","totalcount");
		}
		if($object == "user"){//charge name
			return array("uid","name","firstname","lastname","api_key","phone","password","role","branch","created","updated","fields","sort_by","sort_order","limit","offset","totalcount");
		}
		if($object == "cfchiti"){
			return array("id","date","chitiname","code","no","amount","created","updated","fields","sort_by","sort_order","limit","offset","totalcount");
		}
		if($object == "cfchiticustomers"){
			return array("id","chiti","chitiname","code","customer","customername","cusno","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
		}					
		if($object == "cfpaata"){
			return array("id","date","chiti","chitiname","code","no","customer","customername","cusno","paata","payamount","repayamount","sripalcomm","sowjicomm","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
		}
		if($object == "cftrans"){
			return array("id","date","chiti","chitiname","code","paata","paataamt","customer","customername","cusno","maincus","debit","credit","paymentmode","pmid","note","payamount","repayamount", "paatano", "created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
		}
		if($object == "chiti"){
			return array("id","customer","code","date","tYpe","advintmonths","iscountable","chitiamount","paymentmode","paymentmodename","pmid","pdwtm","interestrate","ccomm","ccommpaymentmode","ccommpaymentmodename","suriccomm","sowji","suri","fullandevi","reverse","revcash","status","note","irregular","hami","haminame","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");							  
		}	
		if($object == "interest"){
			return array("id","date","customer","customername","credit","credittotal","debit","debittotal","paymentmode","paymentmodename","note","note1","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
		}
		if($object == "interestrows"){
			return array("id","intid","date","credit","debit","note","days","intamount","op","created","updated","fields","sort_by","sort_order","limit","offset","totalcount");
		}					
		if($object == "chitfund"){
			return array("id","customer","date","amount","type","status","customername","created","updated","fields","sort_by","sort_order","limit","offset","totalcount");
		}
		if($object == "collection"){
			return array("id","chiti","code","tYpe","status","chitiamount","chitidate","date","amount","notes","sowji","sowjitotal","suri","fullandevi","iscountable","received","reverseid","receivedfrom","customer","customerFL","hami","haminame","rcvddate","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
						//  "id","chiti","code","tYpe","status","chitiamount","chitidate","date","amount","notes","sowji","sowjitotal","suri","fullandevi","iscountable","received","reverseid","receivedfrom","customer","customerFL","hami","haminame","rcvddate","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount"
		}
		if($object == "receivedamount"){
			return array("id","customer","amount","customername","rcvddate","asalu","asaluid","asaluchiti","chiti","colid","colamt","coldate","code","sowjicomm","suricomm","fullandevicomm","collectedby","collectorname","paymentmode","paymentmodename","pmid","note","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
		}
		if($object == "drcr"){
			return array("id","date","customer","customername","haminame","credit","credittotal","debit","debittotal","showdaybook","note","note1","forint","collectedby","paymentmode","creditexp","crid","chitfundid","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
		}
		if($object == "asalu"){
			return array("id","date","customer","amount","chitiamount","chiti","code","customername","note","paymentmode","paymentmodename","pmid","rcvdid","rcvdnote","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
		}
		if($object == "sowji"){
			return array("id","date","chiti","amount","note","amounttotal","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
		}
		if($object == "paymentmodes"){
			return array("id","modename","opbal","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
		}
		if($object == "pmtrans"){
			return array("id","date","tablename","tableid","paymentmode","paymentmodename","credit","debit","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
		}
		if($object == "daybook"){
			return array("id","date","note","note1","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
		}
		if($object == "collectors"){
			return array("id","name","phone","status","uid","username","uidfullname","api_key","uidphone","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
		}
		if($object == "collectorsamount"){
			return array("id","date","collector","amount","collectiondate","uid","username","uidfullname","api_key","uidphone","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
		}
		// if($object == "collectionreport"){
			// return array("id","date","collector","amount","collectiondate","uid","username","uidfullname","api_key","uidphone","created","updated","fields","sort_by","sort_order","group_by","limit","offset","totalcount");
		// }
		if($object == "yesno"){
			return array("id","name");
		}
	}
	
			
function putFunctionParam($object){
	if($object == "customers"){
		return array("firstName","lastName","phoneNo","hami","ishami","chitfund","aadhar","passbook","debitcard","cheque","pnote","greensheet","note","forint","intrate");//map with html
	}
	if($object == "cfcustomers"){
		return array("fname","lname","phone","first","firstid");
	}
	if($object == "cfchiti"){
		return array("date","chitiname","code","no","amount");
	}
	if($object == "cfchiticustomers"){
		return array("chiti","customer","cusno");
	}
	if($object == "cfpaata"){
		return array("date","chiti","no","customer","paata","payamount","repayamount","sripalcomm","sowjicomm");
	}
	if($object == "cftrans"){
		return array("date","chiti","paata","customer","debit","credit","paymentmode","pmid","note");
	}
	if($object == "chiti"){
		return array("customer","code","date","tYpe","advintmonths","iscountable","chitiamount","paymentmode","pmid","pdwtm","interestrate","ccomm","ccommpaymentmode","suriccomm","sowji","suri","fullandevi","reverse","revcash","status","note","irregular");
	}
	if($object == "interestrows"){
		return array("intid","date","credit","debit","note","days","intamount","op");
	}
	if($object == "interest"){
		return array("date","customer","credit","debit","paymentmode","pmid","note","note1");
	}					
	if($object == "collection"){
		return array("chiti","date","amount","notes","sowji","suri","fullandevi","received","reverseid","receivedfrom","rcvddate");
	}
	if($object == "received"){
		return array("customer","amount","rcvddate","asalu","asaluid","chiti","colid","collectedby","paymentmode","pmid","note");
	}
	if($object == "drcr"){
		return array("date","customer","debit","credit","forint","collectedby","paymentmode","pmid","creditexp","crid","chitfundid","showdaybook","note","note1");
	}
	if($object == "asalu"){
		return array("date","customer","amount","chitiamount","chiti","note");		
	}
	if($object == "sowji"){
		return array("date","chiti","amount","note");		
	}
	if($object == "chitfund"){
		return array("customer","date","amount","type","status");		
	}
	if($object == "paymentmodes"){
		return array("modename","opbal");
	}
	if($object == "pmtrans"){
		return array("date","tablename","tableid","paymentmode","credit","debit");
	}
	if($object == "firm"){
		return array("lastbackup");
	}
	if($object == "collectors"){
		return array("name","phone","status","uid");
	}
	if($object == "collectoramount"){
		return array("date","collector","amount","collectiondate","note");
	}
	if($object == "daybook"){
		return array("date","note","note1");
	}	
}
}
?>
