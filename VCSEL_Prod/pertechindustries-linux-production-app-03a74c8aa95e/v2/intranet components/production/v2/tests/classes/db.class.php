<?php
//Developer:	Charles Palmer
//Created:		2011.02.21
//Revision:		2022.10.05

/*
*   2022.09.09  CP  Added alternate camelCase method names
*   2022.10.05 - Removed create_function call that was not used
*/


class db{
	private $db, $stmt;
	//**********************************************************************************************//
	public function __construct($db_info_file_location){
		//Pull in db info
		require($db_info_file_location);
		
		$this->db=new mysqli($domain,$username,$password,$database);
		
		//Check the connection
		if(mysqli_connect_errno()){
			echo 'Connection failed: ' . mysqli_connect_error();
			return false;
		}
		
		return true;
	}
	//**********************************************************************************************//
	public function prepare($prepared_statement){
		$this->stmt=$this->db->prepare($prepared_statement) or die('Prepared statment incorrect: ' . $prepared_statement . '<br/>' . $this->db->error);
		return $this->stmt;
	}
	//**********************************************************************************************//
	public function execute($parameters=array(),$datatypes='',$results=array()){
		
		if(!empty($parameters)){
			//bind parameters
			$bind_names[]=$datatypes;
			for($i=0; $i<count($parameters); $i++){
				$bind_name='bind' . $i;
				$$bind_name=$parameters[$i];
				$bind_names[]=&$$bind_name;
			}
			call_user_func_array(array($this->stmt,'bind_param'),$bind_names);
		}

		if(!empty($results)){ //requesting results
			//bind results
			$data=array();
			$params=array();
			foreach($results as $col_name){
				$params[]=& $data[$col_name];	
			}
			call_user_func_array(array($this->stmt,'bind_result'),$params) or die('Could not set results');
			$this->stmt->execute() or die('Could not execute statement: ' . $this->db->error);
			$this->stmt->store_result();
			
			$results2=array();
			$counter=0;
			while($this->stmt->fetch()){
				foreach($results as $key){
					$results2[$counter][$key]=$data[$key];
				}
				$counter++;
			}
		}else{ //modifying data
			//$this->stmt->execute() or die('Could not execute statement: ' . $this->db->error);
			$results2=$this->stmt->execute() or die('Could not execute statement: ' . $this->db->error);
		}
		return $results2;
	}
	//**********************************************************************************************//
	function affected_rows(){
		return $this->stmt->affected_rows;
	}
	//**********************************************************************************************//
  function affectedRows(){
		return $this->stmt->affected_rows;
	}
	//**********************************************************************************************//
	function close(){
		$this->stmt->close();	
	}
	//**********************************************************************************************/
	function pec($prepared_statement,$parameters=array(),$datatypes='',$results=array()){ //Prepare, execute, close
		$this->prepare($prepared_statement);
		$results=$this->execute($parameters,$datatypes,$results);
		$this->close();
		return $results;
	}
	//**********************************************************************************************/
	function last_insert_id(){
		return mysqli_insert_id($this->db);	
	}
	//**********************************************************************************************/
  function lastInsertId(){
		return mysqli_insert_id($this->db);	
	}
	//**********************************************************************************************/
	function start_transaction(){
		mysqli_autocommit($this->db,false); //switch off auto commit to allow transactions
	}
	//**********************************************************************************************/
  function startTransaction(){
		mysqli_autocommit($this->db,false); //switch off auto commit to allow transactions
	}
	//**********************************************************************************************/
	function end_transaction($success){
		if($success){
			mysqli_commit($this->db);
			return true;
		}else{
			mysqli_rollback($this->db);
			return false;
		}
			
	}
	//**********************************************************************************************/
  function endTransaction($success){
		if($success){
			mysqli_commit($this->db);
			return true;
		}else{
			mysqli_rollback($this->db);
			return false;
		}
			
	}
	//**********************************************************************************************/
}
?>
