<?php
//Developer:    Charles Palmer
//Created:      2019.06.18
//Revision:     2020.09.16

/*
*    2020.09.16   CP  Added in getNextVcselSN
*/

require_once(__DIR__ . '/_cors.php');
pertech_api_cors_headers('application/json');

require_once(dirname(__DIR__) . '/production/lib/includes/includes.php');

function api_vcsel_request_value($request, $keys, $default=''){
    foreach($keys as $key){
        if(isset($request->{$key}) && $request->{$key} !== ''){
            return $request->{$key};
        }
    }
    return $default;
}

function api_vcsel_results_has_user_column(){
    global $common;

    $rows = $common['db']->pec(
        'SELECT count(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?',
        array('2019_prod_7680_vcsel_results', 'ext_user_id'),
        'ss',
        array('count')
    );

    return !empty($rows) && (int)$rows[0]['count'] > 0;
}

function api_vcsel_resolve_user_id($request){
    global $common;

    $userId = (int)api_vcsel_request_value($request, array('logged_in_user_id', 'loggedInUserId', 'user_id', 'userId'), 0);
    if($userId > 0){
        $rows = $common['db']->pec(
            'SELECT user_id FROM core_users WHERE user_id=? AND active=1 LIMIT 1',
            array($userId),
            'i',
            array('user_id')
        );
        if(!empty($rows)){
            return (int)$rows[0]['user_id'];
        }
    }

    $username = trim((string)api_vcsel_request_value($request, array('logged_in_username', 'loggedInUsername', 'username'), ''));
    if($username !== ''){
        $rows = $common['db']->pec(
            'SELECT user_id FROM core_users WHERE username=? AND active=1 LIMIT 1',
            array(strtolower($username)),
            's',
            array('user_id')
        );
        if(!empty($rows)){
            return (int)$rows[0]['user_id'];
        }
    }

    return 0;
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$postdata = file_get_contents("php://input");
if (isset($postdata)) {
    $request = json_decode($postdata);
    if(isset($request->mode)) {
        switch($request->mode){
            //------------------------------------------------------------------------------------
            case 'saveVcselResults':
                $data = '';
                $testerName = api_vcsel_request_value($request, array('tester_name', 'testerName', 'tester'), '');
                $loggedInUserId = api_vcsel_resolve_user_id($request);

                if($loggedInUserId > 0 && api_vcsel_results_has_user_column()){
                    $common['db']->pec('INSERT INTO 2019_prod_7680_vcsel_results SET ext_user_id=?, tester_name=?, date_time=NOW(), programmer_serial_num=?, transmitter_val=?, collector_val=?, collector_voltage=?, vcselSerialNumber=?',
                        array($loggedInUserId, $testerName, $request->programmer_serial_num, $request->set_transmitter_val, $request->set_collector_val, $request->collector_voltage, $request->vcselSerialNumber),'issiidi');
                }else{
                    $common['db']->pec('INSERT INTO 2019_prod_7680_vcsel_results SET tester_name=?, date_time=NOW(), programmer_serial_num=?, transmitter_val=?, collector_val=?, collector_voltage=?, vcselSerialNumber=?',
                        array($testerName, $request->programmer_serial_num, $request->set_transmitter_val, $request->set_collector_val, $request->collector_voltage, $request->vcselSerialNumber),'ssiidi');
                }
            
                $data = $common['db']->last_insert_id();

                foreach($request->collected_data as $key=>$value) {
                    $common['db']->pec('INSERT INTO 2019_prod_7680_vcsel_alt_values SET ext_test_id=?, transmitter_val=?, collector_val=?, collector_voltage=?',
                        array($data,$request->collected_data[$key]->transmitter, $request->collected_data[$key]->collector, $request->collected_data[$key]->voltage),'iiid');
                }

                echo json_encode(array(
                    'id' => (string)$data,
                    'user_id' => $loggedInUserId
                ));
            break;
            //------------------------------------------------------------------------------------
            case 'getNextVcselSN':
              $results = $common['db']->pec('SELECT vcselSerialNumber FROM 2019_prod_7680_vcsel_results ORDER BY vcselSerialNumber DESC LIMIT 1',array(),'',array('vcselSerialNumber'));
              echo $results[0]['vcselSerialNumber'] + 1;
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
