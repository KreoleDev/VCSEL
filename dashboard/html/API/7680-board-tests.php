<?php
//Developer:    Charles Palmer
//Created:      2020.09.17
//Revision:     2020.09.17

/*
*    
*/

require_once(__DIR__ . '/_cors.php');
pertech_api_cors_headers('application/json');

require_once(dirname(__DIR__) . '/production/lib/includes/includes.php');

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$postdata = file_get_contents("php://input");
if (isset($postdata)) {
    $request = json_decode($postdata);
    if(isset($request->mode)) {
        switch($request->mode){
            //------------------------------------------------------------------------------------
            case 'saveBoardTestResults':
                $data = '';

                $common['db']->pec('INSERT INTO 2019_prod_7680_board_test_results SET testerName=?, dateTime=NOW(), serialNumber=?',
                    array($request->testerName, $request->serialNumber),'ss');
            
                $data = $common['db']->last_insert_id();

                echo '{
                    "id":"' . $data . '"
                }';
            break;
            //------------------------------------------------------------------------------------
        }
    } else {
        //Handle other data passing
        if(isset($_POST['mode'])){
            switch($_POST['mode']){
                //===============================================

                //===============================================
            }
        }
    }
}
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
?>
