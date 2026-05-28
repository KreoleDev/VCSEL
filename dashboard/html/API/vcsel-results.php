<?php
// API source for dashboard VCSEL result reads and notes.

function api_vcsel_results_db(){
    global $common;
    return $common['db'];
}

function api_vcsel_results_get(){
    $db = api_vcsel_results_db();

    $noteRows = $db->pec(
        'SELECT extVcselSerialNumber, dateTime, note FROM 2019_prod_7680_vcsel_results_notes ORDER BY dateTime',
        array(),
        '',
        array('extVcselSerialNumber', 'dateTime', 'note')
    );

    $notes = array();
    foreach($noteRows as $row){
        $serial = $row['extVcselSerialNumber'];
        if(!isset($notes[$serial])){
            $notes[$serial] = array();
        }
        $notes[$serial][] = array(
            'dateTime' => $row['dateTime'],
            'note' => $row['note']
        );
    }

    $resultRows = $db->pec(
        'SELECT test_id, tester_name, date_time, programmer_serial_num, transmitter_val, collector_val, collector_voltage, vcselSerialNumber FROM 2019_prod_7680_vcsel_results',
        array(),
        '',
        array('test_id', 'tester_name', 'date_time', 'programmer_serial_num', 'transmitter_val', 'collector_val', 'collector_voltage', 'vcselSerialNumber')
    );

    foreach($resultRows as $key => $row){
        $serial = $row['vcselSerialNumber'];
        $resultRows[$key]['notes'] = isset($notes[$serial]) ? $notes[$serial] : array();
    }

    return $resultRows;
}

function api_vcsel_results_html($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function api_vcsel_results_page($request){
    $db = api_vcsel_results_db();

    $draw = isset($request['draw']) ? (int)$request['draw'] : 0;
    $start = isset($request['start']) ? max(0, (int)$request['start']) : 0;
    $length = isset($request['length']) ? (int)$request['length'] : 20;
    if($length <= 0 || $length > 100){
        $length = 20;
    }

    $columns = array(
        'date_time',
        'tester_name',
        'vcselSerialNumber',
        'programmer_serial_num',
        'transmitter_val',
        'collector_val',
        'collector_voltage'
    );

    $orderColumnIndex = isset($request['order'][0]['column']) ? (int)$request['order'][0]['column'] : 0;
    $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'date_time';
    $orderDir = isset($request['order'][0]['dir']) && strtolower($request['order'][0]['dir']) === 'asc' ? 'ASC' : 'DESC';

    $search = isset($request['search']['value']) ? trim($request['search']['value']) : '';
    $where = '';
    $params = array();
    $types = '';
    if($search !== ''){
        $where = ' WHERE tester_name LIKE ? OR date_time LIKE ? OR programmer_serial_num LIKE ? OR vcselSerialNumber LIKE ? OR transmitter_val LIKE ? OR collector_val LIKE ? OR collector_voltage LIKE ?';
        $like = '%' . $search . '%';
        $params = array($like, $like, $like, $like, $like, $like, $like);
        $types = 'sssssss';
    }

    $totalRows = $db->pec(
        'SELECT count(*) FROM 2019_prod_7680_vcsel_results',
        array(),
        '',
        array('count')
    );
    $recordsTotal = isset($totalRows[0]['count']) ? (int)$totalRows[0]['count'] : 0;

    if($where !== ''){
        $filteredRows = $db->pec(
            'SELECT count(*) FROM 2019_prod_7680_vcsel_results' . $where,
            $params,
            $types,
            array('count')
        );
        $recordsFiltered = isset($filteredRows[0]['count']) ? (int)$filteredRows[0]['count'] : 0;
    }else{
        $recordsFiltered = $recordsTotal;
    }

    $results = $db->pec(
        'SELECT test_id, tester_name, date_time, programmer_serial_num, transmitter_val, collector_val, collector_voltage, vcselSerialNumber FROM 2019_prod_7680_vcsel_results' . $where . ' ORDER BY ' . $orderColumn . ' ' . $orderDir . ' LIMIT ' . $start . ', ' . $length,
        $params,
        $types,
        array('test_id', 'tester_name', 'date_time', 'programmer_serial_num', 'transmitter_val', 'collector_val', 'collector_voltage', 'vcselSerialNumber')
    );

    $serials = array();
    foreach($results as $row){
        if((int)$row['vcselSerialNumber'] !== 0){
            $serials[(int)$row['vcselSerialNumber']] = (int)$row['vcselSerialNumber'];
        }
    }

    $notes = array();
    if(!empty($serials)){
        $placeholders = implode(',', array_fill(0, count($serials), '?'));
        $noteRows = $db->pec(
            'SELECT extVcselSerialNumber, dateTime, note FROM 2019_prod_7680_vcsel_results_notes WHERE extVcselSerialNumber IN(' . $placeholders . ') ORDER BY dateTime',
            array_values($serials),
            str_repeat('i', count($serials)),
            array('extVcselSerialNumber', 'dateTime', 'note')
        );
        foreach($noteRows as $row){
            $serial = $row['extVcselSerialNumber'];
            if(!isset($notes[$serial])){
                $notes[$serial] = array();
            }
            $notes[$serial][] = '<strong>' . api_vcsel_results_html($row['dateTime']) . ':</strong> ' . api_vcsel_results_html($row['note']);
        }
    }

    $data = array();
    foreach($results as $row){
        $serial = $row['vcselSerialNumber'];
        $data[] = array(
            api_vcsel_results_html($row['date_time']),
            api_vcsel_results_html($row['tester_name']),
            api_vcsel_results_html($serial),
            api_vcsel_results_html($row['programmer_serial_num']),
            api_vcsel_results_html($row['transmitter_val']),
            api_vcsel_results_html($row['collector_val']),
            api_vcsel_results_html($row['collector_voltage']),
            isset($notes[$serial]) ? implode('<br/>', $notes[$serial]) : '',
            (int)$serial !== 0 ? '<a href="forms/notes.frm.php?vcselSerialNumber=' . urlencode($serial) . '">Add Note</a>' : ''
        );
    }

    return array(
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    );
}

function api_vcsel_results_add_note($userId, $moduleId, $remoteAddress, $vcselSerialNumber, $note){
    $db = api_vcsel_results_db();

    $affected = $db->pec(
        'INSERT INTO 2019_prod_7680_vcsel_results_notes SET dateTime=NOW(), note=?, extUserId=?, extVcselSerialNumber=?',
        array($note, $userId, $vcselSerialNumber),
        'sii'
    );

    if($affected){
        $db->pec(
            'INSERT INTO core_user_module_actions SET ext_user_id=?, ext_module_id=?, datetime=NOW(), action="Added Note", remote_address=?',
            array($userId, $moduleId, $remoteAddress),
            'iis'
        );
    }

    return $affected;
}

if(realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__){
    require_once(__DIR__ . '/_cors.php');
    pertech_api_cors_headers('application/json');

    require_once(dirname(__DIR__) . '/production/lib/includes/includes.php');

    $postdata = file_get_contents('php://input');
    $decodedRequest = !empty($postdata) ? json_decode($postdata, true) : array();
    $request = !empty($decodedRequest) ? $decodedRequest : $_REQUEST;
    $mode = isset($request['mode']) ? $request['mode'] : '';

    switch($mode){
        case 'getVcselResultsPage':
            echo json_encode(api_vcsel_results_page($request));
        break;

        case 'getVcselResults':
            echo json_encode(array(
                'success' => true,
                'data' => api_vcsel_results_get()
            ));
        break;

        case 'addVcselNote':
            $success = api_vcsel_results_add_note(
                isset($request['userId']) ? $request['userId'] : 0,
                isset($request['moduleId']) ? $request['moduleId'] : 0,
                isset($request['remoteAddress']) ? $request['remoteAddress'] : '',
                isset($request['vcselSerialNumber']) ? $request['vcselSerialNumber'] : 0,
                isset($request['note']) ? $request['note'] : ''
            );

            echo json_encode(array(
                'success' => (bool)$success
            ));
        break;

        default:
            http_response_code(400);
            echo json_encode(array(
                'success' => false,
                'error' => 'Unknown mode'
            ));
        break;
    }
}
?>
