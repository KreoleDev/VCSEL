<?php
//Developer:    Charles Palmer
//Created:      2019.01.09
//Revision:     2019.01.09

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

require_once(dirname(__DIR__) . '/production/lib/includes/includes.php');

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$postdata = file_get_contents("php://input");
if (isset($postdata)) {
    $request = json_decode($postdata);
    switch($request->mode){
        //------------------------------------------------------------------------------------
        case 'getCurrentVersion':
            $version = '0.00.000';

            $results = $common['db']->pec('SELECT version_number FROM 2019_prod_versions WHERE 1 ORDER BY release_datetime DESC LIMIT 1',array(),'',array('version_number'));
            if(isset($results[0])) {
                $version = $results[0]['version_number'];
            }

            echo '{
                "data": "' . $version . '"
            }';
        break;
        //------------------------------------------------------------------------------------
    }
}
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
?>