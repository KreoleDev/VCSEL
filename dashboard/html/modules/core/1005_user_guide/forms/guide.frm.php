<?php
//Created:      2015.07.17
//Revision:     2015.07.17
require_once('common/includes/std_lib.inc.php');

/*
[0]     View
[1]     Modify
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(1)){
    //--------------------------------------------------------------------------------------------------------------//
    function show($values,$errors,$hidden){
        global $common;
        
        $page_title='User Guide';
		$additional_head='
            <script type="text/javascript" src="' . CFG_CMS_BASE_URL . 'common/js/tinymce/tinymce.min.js"></script>
			<script type="text/javascript">
				$(document).ready(function(){
					tinymce.init({
                        selector:"textarea",
                        content_css: "/common/css/screen.css",
                        body_id: "user_guide_content",
                        height:450,
                        plugins: [
                                "advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker",
                                "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
                                "save table contextmenu directionality emoticons template paste textcolor"
                          ],
                        menubar : false,

                        toolbar1: "undo redo | styleselect | bold italic | charmap | table | bullist numlist | outdent indent",
                        toolbar2: "link unlink | cut copy paste pastetext"

                    });
				});
			</script>
        ';
		require_once('common/includes/header_inner.inc.php');
            echo $common['window']->begin($page_title . ($hidden['mode']=='update'?': ' . $values['title']:''),false);
				?>
				<div class="info">
					<h3>The following code blocks can be used to automate text:</h3>
					<ul>
						<li>[site_title] - Replace with the title of the site.</li>
						<li>[mod_url:1005] - Creates the full url path to a module, can be placed as the url in a hyperlink.</li>
					</ul>
				</div>
				<?php
				
				$frm=new frm($values,$errors,$hidden);
                echo $frm->begin_frm();
					echo $frm->begin_fieldset('Guide');
						echo $frm->begin_dl();
                            echo $frm->textarea('content','Content:');
                        echo $frm->end_dl();
					echo $frm->end_fieldset();
					
					echo $frm->begin_fieldset('');
						echo $frm->begin_dl('submit');
							echo '<dd><a href="../modify_guide.php" title="Cancel" class="button">Cancel</a></dd>';
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
		
		if(empty($_POST['content'])){
	    	$errors['content']=array('Content','Please include some content');
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
            show($_POST,$errors,array('ext_module_id'=>$_POST['ext_module_id'],'mode'=>'update'));
        }else{
            //Perform update
            $success=true;
            $common['db']->start_transaction();
            
            $affected=$common['db']->pec('UPDATE core_user_guide_sections SET revision_date_time=NOW(), content=? WHERE ext_module_id=? LIMIT 1',array($_POST['content'],$_POST['ext_module_id']),'si');
            if(!$affected){ $success=false; }
            
            //Create log entry
            $common['db']->pec('INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Updated Guide", remote_address=?',array($_SESSION['user_id'],$_SESSION['mod_id'],$_SERVER['REMOTE_ADDR']),'iis');
                
            $state=$common['db']->end_transaction($success)?'success':'fail';
            $msg=$affected?'Guide was successfully updated':'Guide was NOT successfully updated';
                
            //Load main page
            header('location: ../modify_guide.php?msg_state=' . $state . '&msg=' . $msg);
            die();
        }
    }

    $mode=isset($_REQUEST['mode'])?$_REQUEST['mode']:'edit';
    
    switch($mode){
        ////////////////////////////////////////////////////////
		case 'edit':
            $info=$common['db']->pec('SELECT content FROM core_user_guide_sections WHERE ext_module_id=? LIMIT 1',array($_REQUEST['ext_module_id']),'i',array('content'));
            
            if(empty($info)){
                //create initial entry
                $common['db']->pec('INSERT INTO core_user_guide_sections SET ext_module_id=?, revision_date_time=NOW()',array($_REQUEST['ext_module_id']),'i');
            }
            
            if($_REQUEST['ext_module_id']!=1){
                $module_info=$common['db']->pec('SELECT title FROM core_modules WHERE module_id=? LIMIT 1',array($_REQUEST['ext_module_id']),'i',array('title'));
                $info[0]['title']=$module_info[0]['title'];
            }else{
                $info[0]['title']='Introduction';
            }
            
            show($info[0],array(),array('mode'=>'update','ext_module_id'=>$_REQUEST['ext_module_id']));
		break;
		////////////////////////////////////////////////////////
		case 'update':
			update();
		break;
        ////////////////////////////////////////////////////////
	}
}
?>    