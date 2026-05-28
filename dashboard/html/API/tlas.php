<?php
//Developer:    Charles Palmer
//Created:      2019.01.09
//Revision:     2019.11.19

/*
*   2019.01.14  CP  Added getTLAInfo
*   2019.03.05  CP  Adding in tla info for 5300
*   2019.04.02  CP  Added in mech type for 5300
*   2019.05.28  CP  Adding in more config reporting for 5300
*   2019.11.19  CP  Added in firmware and wide vault reporting for 7680
*/

require_once(__DIR__ . '/_cors.php');
pertech_api_cors_headers('application/json');

require_once(dirname(__DIR__) . '/production/lib/includes/includes.php');

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$postdata = file_get_contents("php://input");
if (isset($postdata)) {
    $request = json_decode($postdata);
    switch($request->mode){
        //------------------------------------------------------------------------------------
        case 'getActiveTLAs':
            $data = '';

            $results = $common['db']->pec('SELECT tla_id, tla_number FROM 2019_prod_tlas WHERE active = 1 AND ext_product_id=? ORDER BY tla_number',array($request->product_id),'i',array('tla_id', 'tla_number'));
            foreach($results as $row) {
                $data .= (empty($data)?'':',') . '
                    { "tla_id": "' . $row['tla_id'] . '", "tla_number": "' . $row['tla_number'] . '" }
                ';
            } 

            echo '{
                "data":[' . $data . ']
            }';
        break;
        //------------------------------------------------------------------------------------
        case 'getTLAInfo':
            $data = '';

            //Determine which table has the info for this tla
            $results = $common['db']->pec('SELECT ext_product_id FROM 2019_prod_tlas WHERE tla_id=? LIMIT 1', array($request->tla_id),'i',array('ext_product_id'));
            if(isset($results[0])) {
                switch ($results[0]['ext_product_id']) {
                    case 4: //7680 printer
                        //Get TLA Info
                        $tlaInfo = $common['db']->pec('
                        SELECT 
                            tla_number, 
                            version, 
                            filename, 
                            md5,
                            wide_vault
                        FROM 
                            2019_prod_tlas, 
                            2019_prod_tla_info_product_4, 
                            2019_prod_firmwares
                        WHERE 
                            ext_tla_id=tla_id AND 
                            tla_id=? AND 
                            ext_firmware_id=firmware_id 
                        ORDER BY rev_timestamp DESC LIMIT 1',
                        array($request->tla_id),'i',array(
                        'tla_number', 
                        'version', 
                        'filename', 
                        'md5',
                        'wide_vault'
                        ));

                        if(isset($tlaInfo[0])) {
                            $data = '{
                                "id": "firmware",
                                "title": "Firmware",
                                "value": "' . $tlaInfo[0]['version'] . '"
                            },{
                                "id": "wide_vault",
                                "title": "Wide Vault",
                                "value": "' . ($tlaInfo[0]['wide_vault']?'Yes':'No') . '"
                            }';
                        }
                        
                    break;
                    case 5: //7680 Vcsel

                    break;
                    case 3:
                        //Get CIS types
                        $results = $common['db']->pec('SELECT cis_id, title FROM 2019_prod_cis_types WHERE 1',array(),'',array('cis_id', 'title'));
                        $cisTypes = array();
                        foreach($results as $row) {
                            $cisTypes[$row['cis_id']] = $row['title'];
                        }

                        $results = $common['db']->pec('SELECT ext_top_cis_id, ext_bottom_cis_id, has_micr, has_stamp, has_watermark, ext_firmware_id, usb_descriptor, tolerance_label, reject_label, version FROM 2019_prod_tla_info_product_3, 2019_prod_firmwares, 2019_prod_skew_reject_modes, 2019_prod_skew_tolerance_modes WHERE ext_tla_id=? AND ext_firmware_id=firmware_id AND ext_reject_id=reject_id AND ext_tolerance_id=tolerance_id ORDER BY rev_timestamp DESC LIMIT 1',array($request->tla_id),'i',array('ext_top_cis_id', 'ext_bottom_cis_id', 'has_micr', 'has_stamp', 'has_watermark', 'ext_firmware_id', 'usb_descriptor', 'tolerance_label', 'reject_label','version'));
                        if(isset($results[0])) {
                            $data = '{
                                "id": "ext_top_cis_id",
                                "title": "Top CIS",
                                "value": "'.$cisTypes[$results[0]['ext_top_cis_id']] . '" 
                            }, {
                                "id": "ext_bottom_cis_id",
                                "title": "Bottom CIS",
                                "value": "'.$cisTypes[$results[0]['ext_bottom_cis_id']] . '"
                            }, {
                                "id": "has_micr",
                                "title": "Has MICR",
                                "value": "' . ($results[0]['has_micr']?'True':'False') . '"
                            }, {
                                "id": "has_stamp",
                                "title": "Has Stamp",
                                "value": "' . ($results[0]['has_stamp']?'True':'False') . '"
                            }, {
                                "id": "has_watermark",
                                "title": "Has Watermark",
                                "value": "' . ($results[0]['has_watermark']?'True':'False') . '"
                            }, {
                                "id": "firmware",
                                "title": "Firmware",
                                "value": "' . $results[0]['version'] . '"
                            }, {
                                "id": "usb_descriptor",
                                "title": "USB Descriptor",
                                "value": "' . ($results[0]['usb_descriptor']==0?'Zero':'Serial') . '"
                            }, {
                                "id": "skew_tolerance",
                                "title": "Skew Tolerance",
                                "value": "' . $results[0]['tolerance_label'] . '"
                            }, {
                                "id": "skew_reject",
                                "title": "Skew Reject",
                                "value": "' . $results[0]['reject_label'] . '"
                            }';
                        }
                    break;
                    default:

                    break;
                }
            }

            echo '{
                "data":[' . $data . ']
            }';
        break;
        //------------------------------------------------------------------------------------
    }
}
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
?>
