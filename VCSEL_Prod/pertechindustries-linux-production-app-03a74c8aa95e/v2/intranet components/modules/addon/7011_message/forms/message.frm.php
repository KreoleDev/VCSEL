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
        
        $page_title='Digital Sign Message';
        require_once('common/includes/header_inner.inc.php');
            echo $common['window']->begin($page_title,false);
                $tabs=array();
                
                $frm=new frm($values,$errors,$hidden);
                /////////////////////////////////////////////////////////////////////////////////
                echo $frm->begin_frm();
                  echo $frm->begin_fieldset('General Information');
                    echo $frm->begin_dl();
                      echo $frm->textarea('message','Message:',true);
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
      
      if(empty($_POST['message'])){
          $errors['message']=array('Message','Please include a message');
      }
      
      return $errors;
    }
    //--------------------------------------------------------------------------------------------------------------//
    function update(){
      global $common;
      //Check for errors
      $errors=validate();
      
      if(!empty($errors) || !$common['security']->verify_frm()){
        //Errors found
        show($_POST,$errors,array('messageId'=>$_POST['messageId'],'mode'=>'update'));
      }else{
        //Perform update
        $affected=$common['db']->pec('UPDATE 7011_messages SET message=? WHERE messageId=? LIMIT 1',
          array($_POST['message'], $_POST['messageId']),
          'si'
        );
        
        //Create log entry
        $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Message", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
        
        $state=$affected?'success':'fail';
        $msg=$affected?'Message was successfully updated':'Message was NOT successfully updated';
        
        //Load main page
        header('location: ../index.php?msg_state=' . $state . '&msg=' . $msg);
        die();
      }
    }
    //--------------------------------------------------------------------------------------------------------------//
    $mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:'show';
    
    switch($mode){
        ////////////////////////////////////////////////////////
        case 'edit':
            //Find form info
            $info=$common['db']->pec('SELECT message FROM 7011_messages WHERE messageId=? LIMIT 1',array($_REQUEST['messageId']),'i',array('message'));
            show($info[0],array(),array('messageId'=>$_REQUEST['messageId'],'mode'=>'update'));
        break;
        ////////////////////////////////////////////////////////
        case 'update':
            update();
        break;
        ////////////////////////////////////////////////////////
    }
}
?>