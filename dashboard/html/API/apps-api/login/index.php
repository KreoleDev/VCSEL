<?php
require_once(dirname(__DIR__) . '/_bootstrap.php');
require_once(dirname(__DIR__, 2) . '/db-gateway.php');

$request = apps_api_json_request();
$usernameValue = isset($request['username']) ? strtolower(trim($request['username'])) : '';
$passwordValue = isset($request['password']) ? (string)$request['password'] : '';

$table = apps_api_identifier(getenv('PERTECH_AUTH_TABLE'), 'core_users');
$usernameColumn = apps_api_identifier(getenv('PERTECH_AUTH_USERNAME_COLUMN'), 'username');
$passwordColumn = apps_api_identifier(getenv('PERTECH_AUTH_PASSWORD_COLUMN'), 'password');
$displayNameColumn = apps_api_identifier(getenv('PERTECH_AUTH_DISPLAY_NAME_COLUMN'), '');
$activeColumn = apps_api_identifier(getenv('PERTECH_AUTH_ACTIVE_COLUMN'), 'active');

$fields = array($passwordColumn, $activeColumn);
if($displayNameColumn !== ''){
    $fields[] = $displayNameColumn;
}else{
    $fields[] = 'first_name';
    $fields[] = 'last_name';
}
$fields[] = 'salt';

$db = api_db_gateway_instance();
$rows = $db->pec(
    'SELECT ' . implode(', ', $fields) . ' FROM ' . $table . ' WHERE ' . $usernameColumn . '=? LIMIT 1',
    array($usernameValue),
    's',
    $fields
);

$authenticated = false;
$displayName = '';
if(!empty($rows)){
    $row = $rows[0];
    $active = !isset($row[$activeColumn]) || (int)$row[$activeColumn] === 1;
    $storedPassword = isset($row[$passwordColumn]) ? $row[$passwordColumn] : '';
    $salt = isset($row['salt']) ? $row['salt'] : '';
    $passwordMatches = hash_equals($storedPassword, sha1($passwordValue . $salt)) || hash_equals($storedPassword, $passwordValue);

    if($active && $passwordMatches){
        $authenticated = true;
        if($displayNameColumn !== ''){
            $displayName = isset($row[$displayNameColumn]) ? $row[$displayNameColumn] : $usernameValue;
        }else{
            $displayName = trim((isset($row['first_name']) ? $row['first_name'] : '') . ' ' . (isset($row['last_name']) ? $row['last_name'] : ''));
            if($displayName === ''){
                $displayName = $usernameValue;
            }
        }
    }
}

apps_api_emit_json(array(
    'authenticated' => $authenticated,
    'display_name' => $authenticated ? $displayName : ''
));
?>
