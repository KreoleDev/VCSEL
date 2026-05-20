<?php
//Developer:    Charles Palmer
//Created:      2020.10.01
//Revision:     2020.10.02
require_once('common/includes/std_lib.inc.php');

/*
*   2020.10.02  CP  Added start date
*/

/*
$rights
    [0]     Full Control
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    //--------------------------------------------------------------------------------------------------------------//
    function show($values,$errors,$hidden){
        global $common;
        
        $page_title='Order';
        require_once('common/includes/header_inner.inc.php');
            echo $common['window']->begin($page_title,false);
                $tabs=array();
                
                $frm=new frm($values,$errors,$hidden);
                /////////////////////////////////////////////////////////////////////////////////
                echo $frm->begin_frm();
                  echo $frm->begin_fieldset('General Information');
                    echo $frm->begin_dl();
                      echo $frm->text('firstUsableId','First Usable ID (internal DB index):',true,16,'','','','',true);
                      echo $frm->text('qtyNeeded','Quantity:',true,24);
                      echo $frm->text('startDate','Start Date:',true,10,'','date','','mm/dd/yyyy');
                      echo $frm->text('dueDate','Due Date:',true,10,'','date','','mm/dd/yyyy');
                    echo $frm->end_dl();
                  echo $frm->end_fieldset();
                    
                
		    
		              echo $frm->begin_fieldset('');
                    echo $frm->begin_dl('submit');
                      echo '<dd><a href="../index.php" title="Cancel" class="button">Cancel</a></dd>';
                      echo $frm->submit('submit','Submit','submit');
                    echo $frm->end_dl();    
                  echo $frm->end_fieldset();
		    
                echo $frm->end_frm();
                /////////////////////////////////////////////////////////////////////////////////
                
            echo $common['window']->end();
        require_once('common/includes/footer_inner.inc.php');
    }
    //--------------------------------------------------------------------------------------------------------------//
    function validate(){
      global $common;
      $errors=array();
      
      if(empty($_POST['firstUsableId'])){
          $errors['firstUsableId']=array('First Usable ID','Please specify the ID');
      }
      
      if(empty($_POST['qtyNeeded'])){
        $errors['qtyNeeded']=array('Quantity','Please specify the quantity');
      }
      
      //Check date
      if(!$common['validate']->date($_POST['dueDate'])){
          $errors['dueDate']=array('Due Date','Please enter a valid date');	
      }

      if(!$common['validate']->date($_POST['startDate'])){
        $errors['startDate']=array('Start Date','Please enter a valid date');	
    }
      
      return $errors;
    }
    //--------------------------------------------------------------------------------------------------------------//
    function insert(){
	    global $common;

	    //Check for errors
	    $errors=validate();
	    
	    if(!empty($errors) || !$common['security']->verify_frm()){
        //Errors found
        show($_POST,$errors,array('mode'=>'insert'));
      }else{      
        //Add entry
        $affected=$common['db']->pec('INSERT INTO 2019_prod_7680_target_dates SET firstUsableId=?, qtyNeeded=?, startDate=?, dueDate=?',
            array($_POST['firstUsableId'],$_POST['qtyNeeded'],$common['format']->unformat_date($_POST['startDate']),$common['format']->unformat_date($_POST['dueDate'])),
            'iiss');
        
        //Create log entry
        $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Target", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
        
        $state=$affected?'success':'fail';
        $msg=$affected?'Schedule target account was successfully created':'Schedule target was NOT successfully created';
        
        //Load main page
        header('location: ../index.php?msg_state=' . $state . '&msg=' . $msg);
        die();
	    }
    }
    //--------------------------------------------------------------------------------------------------------------//
    function update(){
      global $common;
      //Check for errors
      $errors=validate();
      
      if(!empty($errors) || !$common['security']->verify_frm()){
        //Errors found
        show($_POST,$errors,array('targetId'=>$_POST['targetId'],'mode'=>'update'));
      }else{
        //Perform update
        $affected=$common['db']->pec('UPDATE 2019_prod_7680_target_dates SET firstUsableId=?, qtyNeeded=?, startDate=?, dueDate=? WHERE targetId=? LIMIT 1',
          array($_POST['firstUsableId'],$_POST['qtyNeeded'],$common['format']->unformat_date($_POST['startDate']),$common['format']->unformat_date($_POST['dueDate']), $_POST['targetId']),
          'iissi'
        );
        
        //Create log entry
        $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Target", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
        
        $state=$affected?'success':'fail';
        $msg=$affected?'Schedule target was successfully updated':'Schedule target was NOT successfully updated';
        
        //Load main page
        header('location: ../index.php?msg_state=' . $state . '&msg=' . $msg);
        die();
      }
    }
    //--------------------------------------------------------------------------------------------------------------//
    $mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:'show';
    
    switch($mode){
        ////////////////////////////////////////////////////////
        case 'show':
            // Get last entry to suggest next ID
            $info = $common['db']->pec('SELECT firstUsableId, qtyNeeded FROM 2019_prod_7680_target_dates ORDER BY dueDate DESC LIMIT 1',array(),'',array('firstUsableId', 'qtyNeeded'));
            if(isset($info[0])) {
              $info[0]['firstUsableId'] += $info[0]['qtyNeeded'];
            }
            show($info[0],array(),array('mode'=>'insert'));
        break;
        ////////////////////////////////////////////////////////
        case 'insert':
            insert();
        break;
        ////////////////////////////////////////////////////////
        case 'edit':
            //Find form info
            $info=$common['db']->pec('SELECT firstUsableId, qtyNeeded, startDate, dueDate FROM 2019_prod_7680_target_dates WHERE targetId=? LIMIT 1',array($_REQUEST['targetId']),'i',array('firstUsableId', 'qtyNeeded', 'startDate', 'dueDate'));
            $info[0]['dueDate']=$common['format']->format_date($info[0]['dueDate']); //format  date
            $info[0]['startDate']=$common['format']->format_date($info[0]['startDate']); //format  date
            show($info[0],array(),array('targetId'=>$_REQUEST['targetId'],'mode'=>'update'));
        break;
        ////////////////////////////////////////////////////////
        case 'update':
            update();
        break;
        ////////////////////////////////////////////////////////
    }
}
?>