<?php
//Developer:	Charles Palmer
//Created:	2014.05.20
//Revision:	2017.11.07

/*
 *  2017.11.07  CP  added ability to pass a class to the row
 */

class table{
    //=========================================================================================//
    public function begin($columns=array(),$table_class='',$classes=array()){
        $str='<table class="stripe' . (!empty($table_class)?' ' . $table_class:'') . '">
            <thead>
                <tr>';
                
        foreach($columns as $key=>$value){
            $str.='<th' . (isset($classes[$key]) && !empty($classes[$key])?' class="' . $classes[$key] . '"':'') . '>' . $value . '</th>';
                
        }
        $str.=' </tr>
            </thead>
            <tbody>';
        
        return $str;
    }
    //=========================================================================================//
    public function add_row($columns=array(),$classes=array(),$row_class=''){
       $str='<tr ' . (!empty($row_class)?'class="'.$row_class.'"':'') . '>';
       
       foreach($columns as $key=>$value){
            $str.='<td' . (isset($classes[$key]) && !empty($classes[$key])?' class="' . $classes[$key] . '"':'') . '>' . $value . '</td>';
       }
       
       $str.='</tr>';
       
       return $str;
    }
    //=========================================================================================//
    public function end(){
        $str='</tbody></table>';
        
        return $str;
    }
    //=========================================================================================//
}
?>