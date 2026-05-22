<?php
//Developer:    Charles Palmer
//Created:      2019.11.21
//Revision:     2019.12.05

/*
*   2019.11.25  CP  Added in serial number key
*   2019.12.05  CP  Added check on serial numbers if shipped
*/

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

require_once(dirname(__DIR__) . '/production/lib/includes/includes.php');

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$postdata = file_get_contents("php://input");
if (isset($postdata)) {
    $request = json_decode($postdata);
    if(isset($request->mode)) {
        switch($request->mode){
            //------------------------------------------------------------------------------------
            case 'testFormsKey':
                $data = Array();
                $data[0] = 0;
                $data[1] = 0;

                $data[0] = ((($request->key0 << 2) & 0xAC) | $request->key1) + 3;
                $data[1] = (($request->key0^$request->key1) << 1) - 2;

                echo '{
                    "key": [' . $data[0] . ', ' . $data[1] . ']
                }';
            break;
            //------------------------------------------------------------------------------------
            case 'fctKey':
                $data = Array();
                $data[0] = 0;
                $data[1] = 0;

                $data[0] = ((($request->key0 << 3) & 0xAB) | $request->key1) + 2;
                $data[1] = (($request->key0^$request->key1) << 3) - 1;

                echo '{
                    "key": [' . $data[0] . ', ' . $data[1] . ']
                }';
            break;
            //------------------------------------------------------------------------------------
            case 'loadFormKey':
                $data = Array();
                $data[0] = 0;
                $data[1] = 0;

                $data[0] = ((($request->key0 << 2) & 0xFB) | $request->key1) + 3;
                $data[1] = (($request->key0^$request->key1) << 1) - 1;

                echo '{
                    "key": [' . $data[0] . ', ' . $data[1] . ']
                }';
            break;
            //------------------------------------------------------------------------------------
            case 'feederKey':
                $data = Array();
                $data[0] = 0;
                $data[1] = 0;

                $data[0] = (((~($request->key0 << 1)) & $request->key1) + 1) & 0xFF;
                $data[1] = (~((($request->key1 << 3) | $request->key0) + 1)) & 0xFF;

                echo '{
                    "key": [' . $data[0] . ', ' . $data[1] . ']
                }';
            break;
            //------------------------------------------------------------------------------------
            case 'serialKey':
                $checksum = 0;

                $checksum ^= $request->key0;
                $checksum ^= $request->key1;
                $checksum ^= $request->key2;
                $checksum ^= $request->key3;
                $checksum ^= $request->key4;
                $checksum ^= $request->key5;
                $checksum ^= $request->key6;
                $checksum ^= $request->key7;
                $checksum ^= $request->key8;
                $checksum ^= $request->key9;
                $checksum ^= $request->key10;
                $checksum ^= $request->key11;
                $checksum ^= $request->key12;
                $checksum ^= $request->key13;

                $checksum |= 0xED;

                echo '{
                    "key": ' . $checksum . '
                }';
            break;
            //------------------------------------------------------------------------------------
            case 'checkSerial':
                $results = $common['db']->pec('SELECT printer_serial_num FROM 2019_prod_7680_printer_results WHERE printer_serial_num=? AND passed_shipping=1 LIMIT 1',
                    array($request->barcode),
                    's',
                    array('printer_serial_num')
                );
                if(!empty($results) && isset($results[0]['printer_serial_num'])) {
                    echo '{
                        "passed": 0
                    }';
                } else {
                    echo '{
                        "passed": 1
                    }';
                }
            break;
            //------------------------------------------------------------------------------------
            case 'saveSerial':
                $success = $common['db']->pec('INSERT INTO 2019_prod_7680_printer_results SET tester_name=?, date_time=NOW(), printer_serial_num=?', 
                array($request->tester, $request->barcode),
                'ss');

                $test_id = $common['db']->last_insert_id();

                echo '{
                    "test_id": ' . $test_id . '
                }';
            break;
            //------------------------------------------------------------------------------------
            case 'checkSerialVault':
                $results = $common['db']->pec('SELECT vault_serial_num FROM 2019_prod_7680_printer_results WHERE vault_serial_num=? AND passed_shipping=1 LIMIT 1',
                    array($request->barcode),
                    's',
                    array('vault_serial_num')
                );
                if(!empty($results) && isset($results[0]['vault_serial_num'])) {
                    echo '{
                        "passed": 0
                    }';
                } else {
                    echo '{
                        "passed": 1
                    }';
                }
            break;
            //------------------------------------------------------------------------------------
            case 'saveSerialVault':
                $success = $common['db']->pec('UPDATE 2019_prod_7680_printer_results SET vault_serial_num=? WHERE test_id=? LIMIT 1', 
                array($request->barcode, $request->test_id),
                'si');
            break;
            //------------------------------------------------------------------------------------
            case 'saveTalliesAndConfig':
                $success = $common['db']->pec('UPDATE 2019_prod_7680_printer_results SET 
                    fct=?,
                    ac_coupled_barcode_enabled=?,
                    burster_sensor_cfg=?,
                    print_line_offset=?,
                    burst_search_max=?,
                    burst_mark_to_perf=?,
                    wide_vault_enabled=?,
                    design_level=?,
                    flash_write_protect=?,
                    tal_dot_count=?,
                    tal_frm_count=?,
                    tal_void_count=?,
                    tal_burst_count=?,
                    tal_vault_count=?,
                    tal_time_on=?,
                    tal_resets_count=?,
                    tal_firmware_updates_count=?,
                    tal_ext_sheets_count=?,
                    tal_ribbons_count=?,
                    tal_ribbon_dot_count=?,
                    additional_notes=?  
                    WHERE test_id=? LIMIT 1', 
                array(
                    $request->fct,
                    $request->ac_coupled_barcode_enabled,
                    $request->burster_sensor_cfg,
                    $request->print_line_offset,
                    $request->burst_search_max,
                    $request->burst_mark_to_perf,
                    $request->wide_vault_enabled,
                    $request->design_level,
                    $request->flash_write_protect,
                    $request->tal_dot_count,
                    $request->tal_frm_count,
                    $request->tal_void_count,
                    $request->tal_burst_count,
                    $request->tal_vault_count,
                    $request->tal_time_on,
                    $request->tal_resets_count,
                    $request->tal_firmware_updates_count,
                    $request->tal_ext_sheets_count,
                    $request->tal_ribbons_count,
                    $request->tal_ribbon_dot_count,
                    $request->additional_notes,  
                    $request->test_id
                ),
                'iiiiiiiiissssssssssssi');
            break;
            //------------------------------------------------------------------------------------
        }
    } else {
        //Handle other data passing
        if(isset($_POST['mode'])){
            switch($_POST['mode']){
                //===============================================
                case 'saveImg':
                    //Create directory for uploads if not already there
                    if(!is_dir(UPLOAD_URL .'7680printer/')){
                        mkdir(UPLOAD_URL .'7680printer/',0777,true);
                    }
                    move_uploaded_file($_FILES["file"]["tmp_name"], UPLOAD_URL . '7680printer/' . $_POST['id'] . '.jpg');
                break;
                //===============================================
                case 'saveImgVault':
                    //Create directory for uploads if not already there
                    if(!is_dir(UPLOAD_URL .'7680printer/')){
                        mkdir(UPLOAD_URL .'7680printer/',0777,true);
                    }
                    move_uploaded_file($_FILES["file"]["tmp_name"], UPLOAD_URL . '7680printer/' . $_POST['id'] . 'vault.jpg');
                break;
                //===============================================
            }
        }
    }
}
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
?>