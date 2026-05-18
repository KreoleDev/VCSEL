<?php
//Developer:    Charles Palmer
//Created:      2020.01.14
//Revision:     2020.01.14
require_once('common/includes/std_lib.inc.php');

/*
*   
*/

/*
$rights
    [0]     View
    [1]     Edit
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(1)){
    //--------------------------------------------------------------------------------------------------------------//
    function show($values,$errors,$hidden){
        global $common;
        
        $page_title='7680 Test Results';
        require_once('common/includes/header_inner.inc.php');
            echo $common['window']->begin($page_title . ($hidden['mode']=='update'?': ' . $values['printer_serial_num']:''),false);
				$frm=new frm($values,$errors,$hidden);
				echo $frm->begin_frm();
					echo $frm->begin_fieldset('General');
            echo $frm->begin_dl();      

							echo $frm->textarea('additional_notes','Additional Notes:');
							
						echo $frm->end_dl();
					echo $frm->end_fieldset();
					
					echo $frm->begin_fieldset('','','','submit');
						echo $frm->begin_dl('submit');
							echo '<dd><a class="button" href="../index.php" title="cancel">Cancel</a></dd>';
							echo $frm->submit();
						echo $frm->end_dl();
					echo $frm->end_fieldset();
				
				echo $frm->end_frm();
            echo $common['window']->end();
        require_once('common/includes/footer_inner.inc.php');
    }
    //--------------------------------------------------------------------------------------------------------------//
    function validate(){
      global $common;
      $errors=array();
      
      return $errors;
    }
    //--------------------------------------------------------------------------------------------------------------//
    function update(){
      global $common;
      if($common['security']->check_rights(1)){ //Can edit
          //Check for errors
          $errors=validate();
          
          if(!empty($errors) || !$common['security']->verify_frm()){
            //Errors found
            show($_POST,$errors,array('test_id'=>$_POST['test_id'],'mode'=>'update'));
          }else{
            //Perform update
            $affected=$common['db']->pec('UPDATE 2019_prod_7680_printer_results SET additional_notes=? WHERE test_id=? LIMIT 1',
              array(htmlspecialchars($_POST['additional_notes'],ENT_QUOTES,'UTF-8'), $_POST['test_id']),
              'si'
            );
        
            //Create log entry
            $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Notes", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
            $state=$affected?'success':'fail';
            $msg=$affected?'Note was successfully updated':'Note was NOT successfully updated';
            
            //Load main page
            header('location: ../index.php?msg_state=' . $state . '&msg=' . $msg);
            die();
          }
      }
    }
    //--------------------------------------------------------------------------------------------------------------//
    $mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:'show';
    
    switch($mode){
        ////////////////////////////////////////////////////////
        case 'show':
            show(array(),array(),array('mode'=>'insert'));
        break;
        ////////////////////////////////////////////////////////
        case 'edit':
            //Find form info
            $user_info=$common['db']->pec('SELECT printer_serial_num, additional_notes FROM 2019_prod_7680_printer_results WHERE test_id=? LIMIT 1',array($_REQUEST['test_id']),'i',array('printer_serial_num', 'additional_notes'));
            show($user_info[0],array(),array('test_id'=>$_REQUEST['test_id'],'mode'=>'update'));
        break;
        ////////////////////////////////////////////////////////
        case 'update':
            update();
        break;
        ////////////////////////////////////////////////////////
    }
}
?>