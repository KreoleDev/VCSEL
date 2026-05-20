<?php
//Developer:	Charles Palmer
//Created:		2011.03.14
//Revision:		2011.07.31

class format{
	//**********************************************************************************************//
	public function format_phone($phone){
		return '(' . substr($phone,0,3) . ') ' . substr($phone,3,3) . '-' . substr($phone,6,4) . (strlen($phone)>10?' x' . substr($phone,10,strlen($phone)-10):'');
	}
	//**********************************************************************************************//
	public function unformat_phone($phone){
		return str_replace(array('(',')',' ','.','-','x'),'',$phone);
	}
	//**********************************************************************************************//
	public function format_date($date){
		return date('m/d/Y',strtotime($date));
	}
	//**********************************************************************************************//
	public function unformat_date($date){
		return date('Y-m-d',strtotime($date));
	}
	//**********************************************************************************************//
	public function format_time($time){
		return empty($time)?'':date('g:i A',strtotime($time));
	}
	//**********************************************************************************************//
	public function unformat_time($time){
		return empty($time)?'':date('H:i',strtotime($time));
	}
	//**********************************************************************************************//
}
?>