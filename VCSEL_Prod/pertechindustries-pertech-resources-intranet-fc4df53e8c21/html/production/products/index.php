<?php
//Developer:    Charles Palmer
//Created:      2019.01.08
//Revision:     2019.01.09

/*
*   2019.01.09  CP  Moved defines to includes.php
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
        case 'getActiveProducts':
            $data = '';

            $results = $common['db']->pec('SELECT product_id, title, img_filename FROM 2019_prod_products WHERE active = 1 ORDER BY title',array(),'',array('product_id', 'title', 'img_filename'));
            foreach($results as $row) {
                $data .= (empty($data)?'':',') . '
                    { "product_id": "' . $row['product_id'] . '", "title": "' . $row['title'] . '", "img_filename": "' . BASE_URL . 'lib/images/' . $row['img_filename'] . '" }
                ';
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