<?php
//Developer:    Charles Palmer
//Created:      2020.10.05
//Revision:     2020.10.05
require_once('common/includes/std_lib.inc.php');
require_once(CFG_CMS_INCLUDE_PATH . 'API/vcsel-results.php');

/*
*   
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
        
        $page_title='Note';
        require_once('common/includes/header_inner.inc.php');
            echo $common['window']->begin($page_title,false);
                $tabs=array();
                
                $frm=new frm($values,$errors,$hidden);
                /////////////////////////////////////////////////////////////////////////////////
                echo $frm->begin_frm();
                  echo $frm->begin_fieldset('General Information');
                    echo $frm->begin_dl();
                      echo $frm->textarea('note','Note:',true);
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
      
      if(empty($_POST['note'])){
          $errors['note']=array('Note','Please include a note');
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
        show($_POST,$errors,array('mode'=>'insert', 'vcselSerialNumber' => $_POST['vcselSerialNumber']));
      }else{
        $affected = api_vcsel_results_add_note(
            $_SESSION['user_id'],
            $_SESSION['mod_id'],
            $_SERVER['REMOTE_ADDR'],
            $_POST['vcselSerialNumber'],
            $_POST['note']
        );
        
        $state=$affected?'success':'fail';
        $msg=$affected?'Note was successfully created':'Note was NOT successfully created';
        
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
        show(array(),array(),array('mode'=>'insert', 'vcselSerialNumber' => $_REQUEST['vcselSerialNumber']));
      break;
      ////////////////////////////////////////////////////////
      case 'insert':
        insert();
      break;
      ////////////////////////////////////////////////////////
    }
}
?>
