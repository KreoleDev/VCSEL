<?php
//Developer:    Charles Palmer
//Created:      2019.09.16
//Revision:     2019.09.16
require_once('common/includes/std_lib.inc.php');

/*
$rights
    [0]     View
    [1]     Manage
*/

$common['security']->generate_page_rights(); //Generate user rights for page
if($common['security']->check_rights(1)){
    //--------------------------------------------------------------------------------------------------------------//
    function show($values,$errors,$hidden){
        global $common;
        
        $page_title='Test Association';
        require_once('common/includes/header_inner.inc.php');
	    //find tla info
	    $tlaInfo=$common['db']->pec('SELECT tla_number FROM 2019_prod_tlas WHERE tla_id=? LIMIT 1',array($hidden['ext_tla_id']),'i',array('tla_number'));
            
            echo $common['window']->begin($page_title . ': ' . $tlaInfo[0]['tla_number'],false);
                $frm=new frm($values,$errors,$hidden);
		        echo $frm->begin_frm();
		            echo $frm->begin_fieldset('General Information');
                        echo $frm->begin_dl();
                            if($hidden['mode']=='insert') {
                                //find all tests not currently associated
                                $results=$common['db']->pec('SELECT test_id, title, description FROM 2019_prod_tests WHERE test_id IN (SELECT ext_test_id FROM 2019_prod_test_product_assoc WHERE ext_product_id=4) AND test_id NOT IN(SELECT ext_test_id FROM 2019_prod_tla_test_assoc WHERE ext_tla_id=?) ORDER BY title',array($hidden['ext_tla_id']),'i',array('test_id', 'title', 'description'));
                                $tests=array();
                                foreach($results as $row){
                                    $tests[$row['test_id']]=$row['title'] . ': ' . $row['description'];
                                }
                                echo $frm->list_menu('ext_test_id','Test',$tests);
                            } else {
                                $testInfo = $common['db']->pec('SELECT title, description FROM 2019_prod_tests WHERE test_id=? LIMIT 1',array($hidden['ext_test_id']),'i',array('title', 'description'));
                                echo '<h3>'.$testInfo[0]['title'].'</h3>';
                            }

                            //Find all associated tests to determine sort order options
                            if($hidden['mode']=='insert') {
                                $results = $common['db']->pec('SELECT test_id, title FROM 2019_prod_tla_test_assoc, 2019_prod_tests WHERE ext_test_id=test_id AND ext_tla_id=? ORDER BY sort_order',array($hidden['ext_tla_id']),'i',array('test_id', 'title'));
                            } else {
                                $results = $common['db']->pec('SELECT test_id, title FROM 2019_prod_tla_test_assoc, 2019_prod_tests WHERE ext_test_id=test_id AND ext_tla_id=? AND ext_test_id<>? ORDER BY sort_order',array($hidden['ext_tla_id'],$hidden['ext_test_id']),'ii',array('test_id', 'title'));
                            }
                            $placeAfterOptions = array();
                            $placeAfterOptions['0'] = '=== TOP LEVEL ===';
                            foreach($results as $row) {
                                $placeAfterOptions[$row['test_id']] = $row['title'];
                            }
                            echo $frm->list_menu('place_after_id','Place After:',$placeAfterOptions);

                            echo $frm->radio_group('active','Active?:',array('0'=>'No','1'=>'Yes'),true,'','1');
			            echo $frm->end_dl();
		            echo $frm->end_fieldset();
			
                    echo $frm->begin_fieldset('');
                        echo $frm->begin_dl('submit');
                            echo '<dd><a href="tlas.frm.php?mode=edit&tla_id='.$hidden['ext_tla_id'].'&selected_tab=1" title="Cancel" class="button">Cancel</a></dd>';
                            echo $frm->submit('submit','Submit','submit');
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
		
		if($_POST['mode']=='insert') {
            //Verify test is selected
            if(empty($_POST['ext_test_id'])){
                $errors['ext_test_id']=array('Test','Please select a test');
            }
        }

        if(empty($_POST['place_after_id'])&& $_POST['place_after_id']!=='0'){
	    	$errors['place_after_id']=array('Place After','Please select a location');
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
            show($_POST,$errors,array('mode'=>'insert','ext_tla_id'=>$_POST['ext_tla_id']));
        }else{
            $success=true;
            $tla_id=$_POST['ext_tla_id'];

            $common['db']->start_transaction();

            //Open space for test in the sort order set
            if($_POST['place_after_id']==0) {
                //Move everything down 1
                $moveFrom = 0;
            } else {
                //Find sort order of selected place after
                $parentInfo = $common['db']->pec('SELECT sort_order FROM 2019_prod_tla_test_assoc WHERE ext_tla_id=? AND ext_test_id=? LIMIT 1',array($_POST['ext_tla_id'], $_POST['place_after_id']),'ii',array('sort_order'));
                $moveFrom = $parentInfo[0]['sort_order'] + 1;
            }

            //Make opening for new element
            $affected = $common['db']->pec('UPDATE 2019_prod_tla_test_assoc SET sort_order=sort_order+1 WHERE ext_tla_id=? AND sort_order>=?',array($_POST['ext_tla_id'],$moveFrom),'ii');
            if(!$affected){ $success=false; }

            //Add entry
            if($success) {
                $affected = $common['db']->pec('INSERT INTO 2019_prod_tla_test_assoc SET ext_tla_id=?, ext_test_id=?, sort_order=?, active=?',array($_POST['ext_tla_id'],$_POST['ext_test_id'],$moveFrom, $_POST['active']),'iiii');
                if(!$affected){ $success=false; }
            }
            
            //Create log entry
            $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added test", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
            $state=$common['db']->end_transaction($success)?'success':'fail';
            $msg=$affected?'Test was successfully associated':'Test was NOT successfully associated';
            
            //Load edit again with second tab selected
            header('location: tlas.frm.php?mode=edit&selected_tab=1&tla_id='.$tla_id.'&msg_state=' . $state . '&msg=' . $msg);
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
            show($_POST,$errors,array('ext_tla_id'=>$_POST['ext_tla_id'],'ext_test_id'=>$_POST['ext_test_id'],'mode'=>'update'));
        }else{
            //Perform update
            $success=true;
            $common['db']->start_transaction();
            
            $tla_id=$_POST['ext_tla_id'];

            //Determine current sort order of item
            $oldSortInfo = $common['db']->pec('SELECT sort_order FROM 2019_prod_tla_test_assoc WHERE ext_tla_id=? AND ext_test_id=? LIMIT 1',array($_POST['ext_tla_id'],$_POST['ext_test_id']),'ii',array('sort_order'));
            
            //Collapse old location
            $affected = $common['db']->pec('UPDATE 2019_prod_tla_test_assoc SET sort_order=sort_order-1 WHERE ext_tla_id=? AND sort_order>?',array($_POST['ext_tla_id'],$oldSortInfo[0]['sort_order']),'ii');
            if(!$affected){ $success=false; }

            //Open space for test in the sort order set
            if($_POST['place_after_id']==0) {
                //Move everything down 1
                $moveFrom = 0;
            } else {
                //Find sort order of selected place after
                $parentInfo = $common['db']->pec('SELECT sort_order FROM 2019_prod_tla_test_assoc WHERE ext_tla_id=? AND ext_test_id=? LIMIT 1',array($_POST['ext_tla_id'], $_POST['place_after_id']),'ii',array('sort_order'));
                $moveFrom = $parentInfo[0]['sort_order'] + 1;
            }

            //Make opening for new element
            $affected = $common['db']->pec('UPDATE 2019_prod_tla_test_assoc SET sort_order=sort_order+1 WHERE ext_tla_id=? AND sort_order>=?',array($_POST['ext_tla_id'],$moveFrom),'ii');
            if(!$affected){ $success=false; }

            //Update core entry
            if($success) {
                $affected = $common['db']->pec('UPDATE 2019_prod_tla_test_assoc SET sort_order=?, active=? WHERE ext_tla_id=? AND ext_test_id=? LIMIT 1',array($moveFrom, $_POST['active'],$_POST['ext_tla_id'],$_POST['ext_test_id']),'iiii');
                if(!$affected){ $success=false; }
            }
            
            //Create log entry
            $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Test", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
            $state=$common['db']->end_transaction($success)?'success':'fail';
            $msg=$affected?'Test was successfully updated':'Test was NOT successfully updated';
            
            //Load main page
            header('location: tlas.frm.php?mode=edit&selected_tab=1&tla_id='.$tla_id.'&msg_state=' . $state . '&msg=' . $msg);
            die();
        }
    }
    //--------------------------------------------------------------------------------------------------------------//
    function delete(){
        global $common;

        $success=true;
        $common['db']->start_transaction();

        //Determine current sort order of item
        $oldSortInfo = $common['db']->pec('SELECT sort_order FROM 2019_prod_tla_test_assoc WHERE ext_tla_id=? AND ext_test_id=? LIMIT 1',array($_REQUEST['tla_id'],$_REQUEST['ext_test_id']),'ii',array('sort_order'));
            
        //Collapse old location
        $affected = $common['db']->pec('UPDATE 2019_prod_tla_test_assoc SET sort_order=sort_order-1 WHERE ext_tla_id=? AND sort_order>?',array($_REQUEST['tla_id'],$oldSortInfo[0]['sort_order']),'ii');
        if(!$affected){ $success=false; }
        
        if($success) {
            $affected=$common['db']->pec('DELETE FROM 2019_prod_tla_test_assoc WHERE ext_tla_id=? AND ext_test_id=? LIMIT 1',array($_REQUEST['tla_id'],$_REQUEST['ext_test_id']),'ii');
            if(!$affected){ $success=false; }
        }
            
        //Create log entry
	    $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Deleted Test From TLA", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
        $state=$common['db']->end_transaction($success)?'success':'fail';
	    $msg=$affected?'Test was successfully removed':'Test was NOT successfully removed';
		
        //Load main page
        header('location: tlas.frm.php?mode=edit&selected_tab=1&tla_id='.$_REQUEST['tla_id'].'&msg_state=' . $state . '&msg=' . $msg);
        die(); 
    }
    //--------------------------------------------------------------------------------------------------------------//
    function showDuplicate($values,$errors,$hidden){
        global $common;
        
        $page_title='Test Association';
        require_once('common/includes/header_inner.inc.php');
	    //find tla info
	    $tlaInfo=$common['db']->pec('SELECT tla_number FROM 2019_prod_tlas WHERE tla_id=? LIMIT 1',array($hidden['ext_tla_id']),'i',array('tla_number'));
            
            echo $common['window']->begin($page_title . ': ' . $tlaInfo[0]['tla_number'],false);
                $frm=new frm($values,$errors,$hidden);
		        echo $frm->begin_frm();
		            echo $frm->begin_fieldset('General Information');
                        echo $frm->begin_dl();

                            //Find all other similar tlas
                            $results = $common['db']->pec('SELECT tla_id, tla_number FROM 2019_prod_tlas WHERE ext_product_id=4 AND tla_id<>? ORDER BY tla_number',array($hidden['ext_tla_id']),'i',array('tla_id', 'tla_number'));

                            $availableTLAs = array();
                            foreach($results as $row) {
                                $availableTLAs[$row['tla_id']] = $row['tla_number'];
                            }
                            echo $frm->list_menu('copy_tla_id','TLA to copy from:',$availableTLAs);

			            echo $frm->end_dl();
		            echo $frm->end_fieldset();
			
                    echo $frm->begin_fieldset('');
                        echo $frm->begin_dl('submit');
                            echo '<dd><a href="tlas.frm.php?mode=edit&tla_id='.$hidden['ext_tla_id'].'&selected_tab=1" title="Cancel" class="button">Cancel</a></dd>';
                            echo $frm->submit('submit','Submit','submit');
                        echo $frm->end_dl();    
                    echo $frm->end_fieldset();
                echo $frm->end_frm();
            echo $common['window']->end();    
        require_once('common/includes/footer_inner.inc.php');
    }
    //--------------------------------------------------------------------------------------------------------------//
    function performDuplication(){
        global $common;

        //Check for errors
        $errors=array();
        if(empty($_POST['copy_tla_id'])){
            $errors['copy_tla_id']=array('TLA to copy from','Please select a TLA');
        }

        if(!empty($errors) || !$common['security']->verify_frm()){
            //Errors found
            showDuplicate($_POST,$errors,array('mode'=>'insert','ext_tla_id'=>$_POST['ext_tla_id']));
        }else{
            $success=true;
            $tla_id=$_POST['ext_tla_id'];

            $common['db']->start_transaction();

            //Make sure there are no tests currently associated
            $affected = $common['db']->pec('DELETE FROM 2019_prod_tla_test_assoc WHERE ext_tla_id=?',array($_POST['ext_tla_id']),'i');
            if(!$affected){ $success=false; }

            if($success) {
                //Get all test info from other TLA
                $results = $common['db']->pec('SELECT ext_test_id, sort_order, active FROM 2019_prod_tla_test_assoc WHERE ext_tla_id=?',array($_POST['copy_tla_id']),'i',array('ext_test_id', 'sort_order', 'active'));
                foreach($results as $row) {
                    $affected = $common['db']->pec('INSERT INTO 2019_prod_tla_test_assoc SET ext_tla_id=?, ext_test_id=?, sort_order=?, active=?',array($_POST['ext_tla_id'],$row['ext_test_id'],$row['sort_order'],$row['active']),'iiii');
                    if(!$affected){ $success=false; }
                }
            }
            
            //Create log entry
            $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Duplicated test set", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
            
            $state=$common['db']->end_transaction($success)?'success':'fail';
            $msg=$affected?'Tests were successfully associated':'Tests were NOT successfully associated';
            
            //Load edit again with second tab selected
            header('location: tlas.frm.php?mode=edit&selected_tab=1&tla_id='.$tla_id.'&msg_state=' . $state . '&msg=' . $msg);
            die();
        }

	}
    //--------------------------------------------------------------------------------------------------------------//
    $mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:'show';
    
    switch($mode){
        ////////////////////////////////////////////////////////
        case 'show':
            show(array(),array(),array('mode'=>'insert','ext_tla_id'=>$_REQUEST['tla_id']));
        break;
        ////////////////////////////////////////////////////////
        case 'insert':
            insert();
        break;
        ////////////////////////////////////////////////////////
        case 'edit':
            //find current settings
            $testInfo = $common['db']->pec('SELECT sort_order, active FROM 2019_prod_tla_test_assoc WHERE ext_tla_id=? AND ext_test_id=? LIMIT 1',array($_REQUEST['tla_id'],$_REQUEST['ext_test_id']),'ii',array('sort_order', 'active'));
            
            if($testInfo[0]['sort_order'] > 0) {
                $parentInfo = $common['db']->pec('SELECT ext_test_id FROM 2019_prod_tla_test_assoc WHERE ext_tla_id=? AND sort_order=? LIMIT 1',array($_REQUEST['tla_id'],$testInfo[0]['sort_order']-1),'ii',array('ext_test_id'));
                $testInfo[0]['place_after_id'] = $parentInfo[0]['ext_test_id'];
            } else {
                $testInfo[0]['place_after_id'] = 0;
            }
            
            show($testInfo[0],array(),array('mode'=>'update','ext_tla_id'=>$_REQUEST['tla_id'],'ext_test_id'=>$_REQUEST['ext_test_id']));
        break;
        ////////////////////////////////////////////////////////
        case 'update':
            update();
        break;
        ////////////////////////////////////////////////////////
        case 'delete':
            delete();
        break;
        ////////////////////////////////////////////////////////
        case 'duplicate':
            showDuplicate(array(),array(),array('mode'=>'performDuplication','ext_tla_id'=>$_REQUEST['tla_id']));
        break;
        ////////////////////////////////////////////////////////
        case 'performDuplication':
            performDuplication();
        break;
        ////////////////////////////////////////////////////////
    }
}