<?php
//Developer:	Charles Palmer
//Created:		2011.02.19
//Revision:		2011.02.19

class load_time{
	public $start_time=0;
	
	//**********************************************************************************************//
	public function __construct(){
		$starttime = microtime();
		$startarray = explode(" ", $starttime);
		$this->start_time=$startarray[1] + $startarray[0];
	}
	//**********************************************************************************************//
	public function results(){
		global $common;
		$endtime = microtime();
		$endarray = explode(" ", $endtime);
		$endtime = $endarray[1] + $endarray[0];
		$totaltime = $endtime - $this->start_time;
		return round($totaltime,5);
	}
	//**********************************************************************************************//
}
?>