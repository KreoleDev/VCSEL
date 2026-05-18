<?php
//Developer:	Charles Palmer
//Created:	2014.05.19
//Revision:	2014.05.21

class validate{
    //=========================================================================================//
    public function strong_password($password,$min_length=8){
	if(strlen($password)>=$min_length){
	    $criteria_met=0;
			
	    //check for lowercase
	    if(preg_match('/[a-z]/',$password)){
		$criteria_met++;	
	    }
			
	    //check for uppercase
	    if(preg_match('/[A-Z]/',$password)){
		$criteria_met++;	
	    }
			
	    //check for numbers
	    if(preg_match('/[0-9]/',$password)){
		$criteria_met++;	
	    }
			
	    //check for other
	    if(preg_match('/[^a-zA-Z0-9]/',$password)){
		$criteria_met++;	
	    }
			
	    return $criteria_met>=3?true:false;
	}else{
	    return false; //didn't meet minimum length
	}
    }
    //=========================================================================================//
    public function phone($phone){
	$phone = str_replace(array('(',')',' ','.','-'),'',$phone); //remove excess characters before checking
	return preg_match('/^[0-9]{10}[x]*[0-9]*$/',$phone)?true:false;
    }
    //=========================================================================================//
    public function email($email){
	return preg_match('/^([a-zA-Z0-9_\-\.]+)@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-zA-Z]{2,4})$/',$email)?true:false;
    }
    //=========================================================================================//
    public function date($date){
	$date=str_replace('.','-',str_replace('/','-',$date));
	@list($m,$d,$y)=explode('-',$date);
	return checkdate((int) $m,(int) $d,(int) $y);
    }
    //=========================================================================================//
    public function time($time){
	return 	preg_match('/[\s0]*(\d|1[0-2]):(\d{2})\s*([AaPp][Mm])/xms',$time);
    }
    //=========================================================================================//
    public function is_int($int) {
	return is_numeric($int) && $int == (int) $int ?true:false;
    }
    //=========================================================================================//
    public function image_filename($filename) {
        return preg_match('/^[a-z0-9_-]+\.(gif|jpg|png)$/', $filename);
    }
    //=========================================================================================//
    public function filename($filename) {
        return preg_match('/^([a-zA-Z0-9_-]+|\.)+\.[a-z]{3}$/', $filename);
    }
    //=========================================================================================//
    public function url($url) {
       return preg_match('/^(((ht|f)tp(s?))\:\/\/)?([0-9a-zA-Z\-]+\.)+[a-zA-Z]{2,6}(\:[0-9]+)?(\/\S*)?$/', $url);
   }
    //=========================================================================================//
    public function ip_address($ip_address) {
	return preg_match("/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/", $ip_address);	
    }
    //=========================================================================================//
}
?>