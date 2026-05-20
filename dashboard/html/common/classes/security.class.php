<?php
//Developer:	Charles Palmer
//Created:	2014.04.30
//Revision:	2018.09.13

/*
 *	2015.09.22	CP	Added option to generate_page_rights to alert on session expire instead of logging out, useful on ajaxed content
 *  2018.09.13  CP  Changed logout to start session without parameters
 */

class security{
    private $mod_rights=array();
    //=========================================================================================//
    public function login($site,$username,$password){
        
        $status=array();
        $status['success']=false;
        
        //Makes data consistent for db queries
        $site=strtolower($site);
        $username=strtolower($username);
        
        //look up to see if site defined exists and collect info on site
        $db=new db('protected/db.info.php');
        $site_info=$db->pec('SELECT site_path, timezone, auth_type, session_expire_hours, local_password_expire_days, max_login_attempt_count, failed_login_lockout_minutes FROM sites WHERE site_name=? AND enabled LIMIT 1',array($site),'s',array('site_path', 'timezone', 'auth_type', 'session_expire_hours', 'local_password_expire_days','max_login_attempt_count', 'failed_login_lockout_minutes'));
        if(!empty($site_info)){
            $_SESSION['timezone']=$site_info[0]['timezone']; //Used to set timezone on each loaded page
            ini_set ('date.timezone',$site_info[0]['timezone']); //Set timezone for this page
            $_SESSION['site_path']=$site_info[0]['site_path'];
            
            //Connect to site specific database
            if(file_exists('sites/' . $site_info[0]['site_path'] . 'protected/db.info.php')){
                $site_db=new db('sites/' . $site_info[0]['site_path'] . 'protected/db.info.php');
                
                //verify useraccount exists
                $user_info=$site_db->pec('SELECT user_id, first_name, last_name FROM core_users WHERE username=? LIMIT 1',array($username),'s',array('user_id','first_name', 'last_name'));
                
                if(!empty($user_info)){
                    //user account found
                    $_SESSION['user_id']=$user_info[0]['user_id'];
                    $_SESSION['user']=$user_info[0]['first_name'] . ' ' . $user_info[0]['last_name'];
                    $_SESSION['site_name']=$site;
                    
                    //Set session expire time to x hour from now
                    $_SESSION['expire_time']=strtotime('+'.$site_info[0]['session_expire_hours'].' hours');
                    $_SESSION['expire_hours']=$site_info[0]['session_expire_hours'];
                    
                    //Set session local password expire days
                    if($site_info[0]['local_password_expire_days']>0){
                        $_SESSION['local_password_expire_days']=$site_info[0]['local_password_expire_days'];
                    }
                    
                    //Call correct local or ldap authentication mode
                    $status=$site_info[0]['auth_type']=='local'?$this->login_local($site_info[0]['site_path'],$username, $user_info[0]['user_id'],$password,$site_info[0]['local_password_expire_days'],$site_info[0]['max_login_attempt_count'],$site_info[0]['failed_login_lockout_minutes']):$this->login_ldap($site_info[0]['site_path'],$username,$user_info[0]['user_id'],$password);
                }else{
                    //user account not found
                    $status['errors']=array();
                    $status['errors']['username']=array('Username','Invalid Username');
                }
            }
            
        }else{
            //site not defined in site database
            $status['errors']=array();
            $status['errors']['site']=array('Site','Invalid Site Name');
        }
        
        //destroy session info if login fails
        if(!$status['success']){
            $frm_token=isset($_SESSION['frm_token'])?$_SESSION['frm_token']:'';
            $_SESSION=array();
            $_SESSION['frm_token']=$frm_token;
        }
        
        return $status;
    }
    //=========================================================================================//
    private function login_local($site_path, $username, $user_id, $password, $local_password_expire_days, $max_login_attempt_count, $failed_login_lockout_minutes){
        $status=array();
        $status['success']=false;
        
        //create db connection for site
        $site_db=new db('sites/' . $site_path . 'protected/db.info.php');
        
        //check failed login attempts based on $max_login_attempt_count and $failed_login_lockout_minutes
        $failed_attempts=$site_db->pec('SELECT count(*) FROM core_user_auth_actions WHERE ext_user_id=? AND action="login" AND status="fail" AND datetime>SUBTIME(NOW(),CONCAT("0:",?))',array($user_id,$failed_login_lockout_minutes),'ii',array('count'));
        if($failed_attempts[0]['count']>=$max_login_attempt_count){
            //Too many failed login attempts
            $status['errors']=array();
            $status['errors']['password']=array('Password','Too many failed attempts! Please wait ' . $failed_login_lockout_minutes . ' minutes.');
                        
            //Create failed log
            $site_db->pec('INSERT INTO core_user_auth_actions SET ext_user_id=?, datetime=NOW(), action="login", status="fail", reason="Too Many Attempts", remote_address=?',array($user_id,$_SERVER['REMOTE_ADDR']),'is');
        }else{
            //Not too many failed login attempts
            //verify username and pw
            $valid_user=$site_db->pec('SELECT user_id, active, account_expires, expiry_date, password, salt FROM core_users WHERE username=? LIMIT 1',array($username),'s',array('user_id','active','account_expires', 'expiry_date','password','salt'));
            if(!empty($valid_user) && $valid_user[0]['password']!==sha1($password . $valid_user[0]['salt'])){
                $valid_user=array();
            }
            if(!empty($valid_user)){
                //valid password
                //check to see if user account is active
                if($valid_user[0]['active']){
                    //account active
                    //check to see if account is expired
                    if($valid_user[0]['account_expires'] && strtotime($valid_user[0]['expiry_date'])<strtotime(date('Y-m-d'))){
                        //account expired
                        $status['errors']=array();
                        $status['errors']['username']=array('Username','Account Disabled');
                        
                        //Create failed log
                        $site_db->pec('INSERT INTO core_user_auth_actions SET ext_user_id=?, datetime=NOW(), action="login", status="fail", reason="Account Expired", remote_address=?',array($user_id,$_SERVER['REMOTE_ADDR']),'is');
                    }else{
                        //account not expired
                        //Create session tracking and access rights
                        return $this->authenticated_user($site_path,$user_id);
                    }
                }else{
                    //account not active
                    $status['errors']=array();
                    $status['errors']['username']=array('Username','Account Disabled');
                    
                    //Create failed log
                    $site_db->pec('INSERT INTO core_user_auth_actions SET ext_user_id=?, datetime=NOW(), action="login", status="fail", reason="Account Disabled", remote_address=?',array($user_id,$_SERVER['REMOTE_ADDR']),'is');
                }
                
            }else{
                //invalid password
                $status['errors']=array();
                $status['errors']['password']=array('Password','Invalid Password');
                
                //Create failed log
                $site_db->pec('INSERT INTO core_user_auth_actions SET ext_user_id=?, datetime=NOW(), action="login", status="fail", reason="Incorrect Password", remote_address=?',array($user_id,$_SERVER['REMOTE_ADDR']),'is');
            }
        }
        
        return $status;
    }
    //=========================================================================================//
    private function login_ldap($site_path, $username, $user_id, $password){
        //Currently non-functional
        $status=array();
        $status['success']=false;
        $status['errors']=array();
        $status['errors']['password']=array('Password','Invalid Password');
        return $status;
    }
    //=========================================================================================//
    private function authenticated_user($site_path,$user_id){
        $status=array();
        
        //create db connection for site
        $site_db=new db('sites/' . $site_path . 'protected/db.info.php');
        
        $site_db->start_transaction();
            //-------------------------------------------------------------------//
            //Perform db house cleaning
            $success=$site_db->pec('DELETE FROM core_user_auth_actions WHERE datetime < SUBDATE(NOW(),90)'); //remove logs over 90 days old
            if($success){
                $success=$site_db->pec('DELETE FROM core_user_module_actions WHERE datetime < SUBDATE(NOW(),90)'); //remove logs over 90 days old
            }
            if($success){
                $success=$site_db->pec('DELETE FROM core_user_sessions WHERE ext_user_id=? LIMIT 1',array($user_id),'i'); //remove old session info
            }
            //-------------------------------------------------------------------//
            //Store session info into db
            if($success){
                $success=$site_db->pec('INSERT INTO core_user_sessions SET ext_user_id=?, session_id=?, remote_address=?',array($user_id,session_id(),$_SERVER['REMOTE_ADDR']),'iss');
            }
            //-------------------------------------------------------------------//
            //Create success log
            if($success){
                $success=$site_db->pec('INSERT INTO core_user_auth_actions SET ext_user_id=?, datetime=NOW(), action="login", status="success", remote_address=?',array($user_id,$_SERVER['REMOTE_ADDR']),'is');
            }
            //-------------------------------------------------------------------//
            //Store user rights in session
            $modules=array();
            
            //check to see if user is admin (access to all modules)
            $admin_info=$site_db->pec('SELECT count(*) FROM core_user_group_lookup WHERE ext_user_id=? AND ext_group_id=2 LIMIT 1',array($user_id),'i',array('count'));
            if($admin_info[0]['count']==1){
                //User is admin, collect all modules
                $results=$site_db->pec('SELECT module_id, title, path, ext_panel_id, sort_order FROM core_modules ORDER BY ext_panel_id, sort_order',array(),'',array('module_id', 'title', 'path', 'ext_panel_id', 'sort_order'));
                foreach($results as $row){
                    $_SESSION['modules'][$row['ext_panel_id']][$row['sort_order']]['id']=$row['module_id'];
                    $_SESSION['modules'][$row['ext_panel_id']][$row['sort_order']]['title']=$row['title'];
                    $_SESSION['modules'][$row['ext_panel_id']][$row['sort_order']]['path']=$row['path'];
                }
            }else{
                //User is NOT admin
                //Check groups user is a member of
                $results=$site_db->pec('SELECT module_id, title, path, ext_panel_id, sort_order FROM core_modules WHERE module_id IN(SELECT ext_module_id FROM core_group_rights WHERE (ext_group_id=1 OR ext_group_id IN(SELECT ext_group_id FROM core_user_group_lookup WHERE ext_user_id=?))) ORDER BY ext_panel_id, sort_order',array($user_id),'i',array('module_id', 'title', 'path', 'ext_panel_id', 'sort_order'));
                foreach($results as $row){
                    $_SESSION['modules'][$row['ext_panel_id']][$row['sort_order']]['id']=$row['module_id'];
                    $_SESSION['modules'][$row['ext_panel_id']][$row['sort_order']]['title']=$row['title'];
                    $_SESSION['modules'][$row['ext_panel_id']][$row['sort_order']]['path']=$row['path'];
                }
                
                //Check user specific rights
                $results=$site_db->pec('SELECT module_id, title, path, ext_panel_id, sort_order FROM core_modules WHERE module_id IN(SELECT ext_module_id FROM core_user_rights WHERE ext_user_id=?) ORDER BY ext_panel_id, sort_order',array($user_id),'i',array('module_id', 'title', 'path', 'ext_panel_id', 'sort_order'));
                foreach($results as $row){
                    $_SESSION['modules'][$row['ext_panel_id']][$row['sort_order']]['id']=$row['module_id'];
                    $_SESSION['modules'][$row['ext_panel_id']][$row['sort_order']]['title']=$row['title'];
                    $_SESSION['modules'][$row['ext_panel_id']][$row['sort_order']]['path']=$row['path'];
                }
            }
            //-------------------------------------------------------------------//
        $status['success']=$site_db->end_transaction($success);
        
        if(!$status['success']){
            $status['errors']=array();
            $status['errors']['username']=array('System Error','Could not create access rights');
        }
        
        return $status;
    }
    //=========================================================================================//
    public function verify_frm(){
        //used to verify form data is being submitted from form
        $success=false;
        if(isset($_POST['frm_token']) && isset($_SESSION['frm_token'])){
            $success=$_POST['frm_token']==$_SESSION['frm_token']?true:false;
            unset($_SESSION['frm_token']);
        }
        return $success;
    }
    //=========================================================================================//
    public function generate_page_rights($verify_url=true,$mod_id=0,$alert_only=false){
        global $common;
        $rights=array();
        
        if(isset($_SESSION['expire_time']) && $_SESSION['expire_time']>strtotime(date('c'))){
            //Increase expire time
            $_SESSION['expire_time']=strtotime('+'.$_SESSION['expire_hours'].' hours');
            
            //Find mod id
            if($mod_id==0){
                $mod_id=isset($_REQUEST['mod_id'])?$_REQUEST['mod_id']:(isset($_SESSION['mod_id'])?$_SESSION['mod_id']:0);
                $_SESSION['mod_id']=$mod_id;
            }
            
            
            //verify session security info matches db
            $session_info=$common['db']->pec('SELECT count(*) FROM core_user_sessions WHERE ext_user_id=? AND session_id=? AND remote_address=? LIMIT 1',array($_SESSION['user_id'],session_id(),$_SERVER['REMOTE_ADDR']),'iss',array('count'));
            if($session_info[0]['count']==1){
                //user session info matches db
                
                //check to see if user is admin
                $admin_info=$common['db']->pec('SELECT count(*) FROM core_user_group_lookup WHERE ext_user_id=? AND ext_group_id=2 LIMIT 1',array($_SESSION['user_id']),'i',array('count'));
                if($admin_info[0]['count']==1){
                    //User is admin
                    $rights['is_admin']=true;
                }else{
                    //User is NOT admin
                    if($mod_id!=0){
                        //Module ID found
                        //Find rights for any group user is part of
                        $results=$common['db']->pec('SELECT access_level FROM core_group_rights WHERE ext_module_id=? AND (ext_group_id=1 OR ext_group_id IN(SELECT ext_group_id FROM core_user_group_lookup WHERE ext_user_id=?))',array($mod_id,$_SESSION['user_id']),'ii',array('access_level'));
                        foreach($results as $row){
                            $values=str_split(strrev(base_convert($row['access_level'],10,2)));
                            foreach($values as $key=>$value){
                                if((!isset($rights[$key])||$rights[$key]<$value) && $value>0){
                                    $rights[$key]=$value;
                                }
                            }
                        }
                        
                        //Find rights for user specifically
                        $results=$common['db']->pec('SELECT access_level FROM core_user_rights WHERE ext_module_id=? AND ext_user_id=? LIMIT 1',array($mod_id,$_SESSION['user_id']),'ii',array('access_level'));
                        foreach($results as $row){
                            $values=str_split(strrev(base_convert($row['access_level'],10,2)));
                            foreach($values as $key=>$value){
                                if((!isset($rights[$key])||$rights[$key]<$value) && $value>0){
                                    $rights[$key]=$value;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        //Verify url matches mod_id
        if($verify_url){
            if($mod_id!=0){
                //look up url path from db
                $path_info=$common['db']->pec('SELECT path FROM core_modules WHERE module_id=? LIMIT 1',array($mod_id),'i',array('path'));
                if(!empty($path_info[0]['path'])){
                    if(!strpos($_SERVER['PHP_SELF'],$path_info[0]['path'])){ //path not matching
                       $rights=array(); 
                    }
                }else{
                    $rights=array();
                }
            }else{
                $rights=array();
            }
        }
        
        if(empty($rights) && $verify_url){
            if($alert_only){
                echo 'Your session has expired, please <a href="' . CFG_CMS_BASE_URL . 'index.php?logoff=true" title="Log in">log back in</a>!';
            }else{
                //No rights set to section. Redirect to home page
                header('location: ' . CFG_CMS_BASE_URL . 'index.php?logoff=true');
            }
            die();
        }
        
        $this->mod_rights=$rights;
        
        return true;
    }
    //=========================================================================================//
    public function check_rights($right_index){
        return isset($this->mod_rights['is_admin']) || isset($this->mod_rights[$right_index])?true:false;
    }
    //=========================================================================================//
    public function logout(){
        $success=false;
        if(isset($_SESSION['site_path'])){
            $site_db=new db('sites/' . $_SESSION['site_path'] . 'protected/db.info.php');
            //mark log off
            $site_db->pec('INSERT INTO core_user_auth_actions SET ext_user_id=?, datetime=NOW(), action="logout", status="success", remote_address=?',array($_SESSION['user_id'],$_SERVER['REMOTE_ADDR']),'is');
            
            //remove db entry
            $site_db->pec('DELETE FROM core_user_sessions WHERE ext_user_id=? LIMIT 1',array($_SESSION['user_id']),'i');
        
            $success=true;
        }
        
        //Clear session
        $_SESSION=array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        } 
        $session_id=session_regenerate_id();
        session_destroy();
        session_commit();
        //session_start($session_id);
        session_start();
        return $success;
    }
    //=========================================================================================//
}
?>
