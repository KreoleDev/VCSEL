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

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$postdata = file_get_contents("php://input");
if (isset($postdata)) {
    $request = json_decode($postdata);
    if(isset($request->mode)) {
        switch($request->mode){
            //------------------------------------------------------------------------------------
            case 'saveVcselResults':
                $data = '';

                $common['db']->pec('INSERT INTO 2019_prod_7680_vcsel_results SET tester_name=?, date_time=NOW(), programmer_serial_num=?, transmitter_val=?, collector_val=?, collector_voltage=?, vcselSerialNumber=?',
                    array($request->tester_name, $request->programmer_serial_num, $request->set_transmitter_val, $request->set_collector_val, $request->collector_voltage, $request->vcselSerialNumber),'ssiidi');
            
                $data = $common['db']->last_insert_id();

                foreach($request->collected_data as $key=>$value) {
                    $common['db']->pec('INSERT INTO 2019_prod_7680_vcsel_alt_values SET ext_test_id=?, transmitter_val=?, collector_val=?, collector_voltage=?',
                        array($data,$request->collected_data[$key]->transmitter, $request->collected_data[$key]->collector, $request->collected_data[$key]->voltage),'iiid');
                }

                echo '{
                    "id":"' . $data . '"
                }';
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
