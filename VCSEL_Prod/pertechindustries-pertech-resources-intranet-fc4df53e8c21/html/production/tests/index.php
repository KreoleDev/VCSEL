<?php
//Developer:    Charles Palmer
//Created:      2019.01.10
//Revision:     2019.01.11

/*
*   2019.01.11  CP  added is_current
*/

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-Requested-With');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

require_once(dirname(__FILE__) .'/../lib/includes/includes.php');

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
$postdata = file_get_contents("php://input");
if (isset($postdata)) {
    $request = json_decode($postdata);
    switch($request->mode){
        //------------------------------------------------------------------------------------
        case 'getTests':
            $data = '';

            $is_current = 1;

            $results = $common['db']->pec('SELECT test_id, title, description, instructions FROM 2019_prod_tests, 2019_prod_tla_test_assoc WHERE test_id = ext_test_id AND active = 1 AND ext_tla_id=? ORDER BY sort_order',array($request->tla_id),'i',array('test_id', 'title', 'description','instructions'));
            foreach($results as $row) {
                $data .= (empty($data)?'':',') . '
                    { "test_id": "' . $row['test_id'] . '", "title": "' . $row['title'] . '", "description": "' . $row['description'] . '", "instructions": "' . $row['instructions'] . '", "is_current": "' . $is_current . '", "passed_test": "false" }
                ';
                $is_current = 0;
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