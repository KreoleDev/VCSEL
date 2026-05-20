<?php
// Login endpoint for the Electron production app.

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Content-Type: application/json');

require_once(dirname(__FILE__) .'/../lib/includes/includes.php');

function auth_identifier($value) {
    return preg_match('/^[A-Za-z0-9_]+$/', $value) === 1;
}

function env_value($name, $fallback) {
    $value = getenv($name);
    return $value !== false && $value !== '' ? $value : $fallback;
}

function password_matches($password, $storedPassword) {
    if($storedPassword === null) {
        return false;
    }

    if(password_verify($password, $storedPassword)) {
        return true;
    }

    return hash_equals($storedPassword, $password)
        || hash_equals($storedPassword, md5($password))
        || hash_equals($storedPassword, sha1($password));
}

function json_response($authenticated, $displayName = '') {
    echo json_encode(array(
        'authenticated' => $authenticated,
        'display_name' => $displayName
    ));
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$postdata = file_get_contents("php://input");
if (isset($postdata)) {
    $request = json_decode($postdata);
    switch($request->mode){
        //------------------------------------------------------------------------------------
        case 'login':
            $username = isset($request->username) ? trim($request->username) : '';
            $password = isset($request->password) ? $request->password : '';

            if($username === '' || $password === '') {
                json_response(false);
                break;
            }

            $tableName = env_value('PERTECH_AUTH_TABLE', 'users');
            $usernameColumn = env_value('PERTECH_AUTH_USERNAME_COLUMN', 'username');
            $passwordColumn = env_value('PERTECH_AUTH_PASSWORD_COLUMN', 'password');
            $displayNameColumn = env_value('PERTECH_AUTH_DISPLAY_NAME_COLUMN', $usernameColumn);
            $activeColumn = env_value('PERTECH_AUTH_ACTIVE_COLUMN', '');

            if(
                !auth_identifier($tableName) ||
                !auth_identifier($usernameColumn) ||
                !auth_identifier($passwordColumn) ||
                !auth_identifier($displayNameColumn) ||
                ($activeColumn !== '' && !auth_identifier($activeColumn))
            ) {
                json_response(false);
                break;
            }

            $sql = 'SELECT ' . $passwordColumn . ', ' . $displayNameColumn . ' FROM ' . $tableName . ' WHERE ' . $usernameColumn . '=?';
            if($activeColumn !== '') {
                $sql .= ' AND ' . $activeColumn . '=1';
            }
            $sql .= ' LIMIT 1';

            $results = $common['db']->pec($sql, array($username), 's', array($passwordColumn, $displayNameColumn));

            if(isset($results[0]) && password_matches($password, $results[0][$passwordColumn])) {
                json_response(true, $results[0][$displayNameColumn]);
            } else {
                json_response(false);
            }
        break;
        //------------------------------------------------------------------------------------
    }
}
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
?>
