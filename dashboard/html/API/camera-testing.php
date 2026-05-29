<?php
// API source for camera testing app saves and dashboard result reads.

function api_camera_testing_db(){
    global $common;
    return $common['db'];
}

function api_camera_testing_html($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function api_camera_testing_request_value($request, $keys, $default=''){
    foreach($keys as $key){
        if(is_array($request) && isset($request[$key]) && $request[$key] !== ''){
            return $request[$key];
        }
        if(is_object($request) && isset($request->{$key}) && $request->{$key} !== ''){
            return $request->{$key};
        }
    }
    return $default;
}

function api_camera_testing_has_column($column){
    static $columns = array();
    if(isset($columns[$column])){
        return $columns[$column];
    }

    $db = api_camera_testing_db();
    $rows = $db->pec(
        'SELECT count(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?',
        array('camera_testing', $column),
        'ss',
        array('count')
    );
    $columns[$column] = !empty($rows) && (int)$rows[0]['count'] > 0;

    return $columns[$column];
}

function api_camera_testing_resolve_user_id($request){
    $db = api_camera_testing_db();

    $userId = (int)api_camera_testing_request_value($request, array('logged_in_user_id', 'loggedInUserId', 'user_id', 'userId'), 0);
    if($userId > 0){
        $rows = $db->pec(
            'SELECT user_id FROM core_users WHERE user_id=? AND active=1 LIMIT 1',
            array($userId),
            'i',
            array('user_id')
        );
        if(!empty($rows)){
            return (int)$rows[0]['user_id'];
        }
    }

    $username = trim((string)api_camera_testing_request_value($request, array('logged_in_username', 'loggedInUsername', 'username'), ''));
    if($username !== ''){
        $rows = $db->pec(
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

function api_camera_testing_session_user_id(){
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
}

function api_camera_testing_user_is_admin($userId){
    if($userId <= 0){
        return false;
    }

    $db = api_camera_testing_db();
    $rows = $db->pec(
        'SELECT count(*) FROM core_user_group_lookup WHERE ext_user_id=? AND ext_group_id=2 LIMIT 1',
        array($userId),
        'i',
        array('count')
    );

    return !empty($rows) && (int)$rows[0]['count'] > 0;
}

function api_camera_testing_user_filter(){
    $userId = api_camera_testing_session_user_id();
    if($userId <= 0){
        return array('clauses' => array('1=0'), 'params' => array(), 'types' => '');
    }

    if(api_camera_testing_user_is_admin($userId)){
        return array('clauses' => array(), 'params' => array(), 'types' => '');
    }

    if(!api_camera_testing_has_column('ext_user_id')){
        return array('clauses' => array('1=0'), 'params' => array(), 'types' => '');
    }

    return array(
        'clauses' => array('ct.ext_user_id=?'),
        'params' => array($userId),
        'types' => 'i'
    );
}

function api_camera_testing_build_where($search=''){
    $filter = api_camera_testing_user_filter();
    $clauses = $filter['clauses'];
    $params = $filter['params'];
    $types = $filter['types'];

    if($search !== ''){
        $clauses[] = '(ct.timestamp LIKE ? OR ct.tester_name LIKE ? OR ct.lot_number LIKE ? OR ct.serial_number LIKE ? OR ct.brightness LIKE ? OR ct.area LIKE ? OR ct.acquired LIKE ? OR u.username LIKE ?)';
        $like = '%' . $search . '%';
        $params = array_merge($params, array($like, $like, $like, $like, $like, $like, $like, $like));
        $types .= 'ssssssss';
    }

    return array(
        'where' => !empty($clauses) ? ' WHERE ' . implode(' AND ', $clauses) : '',
        'params' => $params,
        'types' => $types
    );
}

function api_camera_testing_save($request){
    $db = api_camera_testing_db();

    $timestamp = trim((string)api_camera_testing_request_value($request, array('timestamp', 'date_time', 'dateTime'), ''));
    $brightness = (float)api_camera_testing_request_value($request, array('brightness'), 0);
    $area = (float)api_camera_testing_request_value($request, array('area'), 0);
    $lotNumber = trim((string)api_camera_testing_request_value($request, array('lot_number', 'lotNumber'), 'LOT0000'));
    $serialNumber = trim((string)api_camera_testing_request_value($request, array('serial_number', 'serialNumber'), ''));
    $acquired = trim((string)api_camera_testing_request_value($request, array('acquired'), 'Auto'));
    $testerName = trim((string)api_camera_testing_request_value($request, array('tester_name', 'testerName', 'tester'), ''));
    $loggedInUserId = api_camera_testing_resolve_user_id($request);

    if($serialNumber === ''){
        return array(
            'success' => false,
            'error' => 'serial_number is required'
        );
    }

    $timestampSql = $timestamp !== '' ? '?' : 'NOW()';
    $columns = array('timestamp=' . $timestampSql, 'brightness=?', 'area=?', 'lot_number=?', 'serial_number=?', 'acquired=?');
    $params = array();
    $types = '';

    if($timestamp !== ''){
        $params[] = $timestamp;
        $types .= 's';
    }
    $params = array_merge($params, array($brightness, $area, $lotNumber, $serialNumber, $acquired));
    $types .= 'ddsss';

    if(api_camera_testing_has_column('tester_name')){
        $columns[] = 'tester_name=?';
        $params[] = $testerName;
        $types .= 's';
    }

    if($loggedInUserId > 0 && api_camera_testing_has_column('ext_user_id')){
        $columns[] = 'ext_user_id=?';
        $params[] = $loggedInUserId;
        $types .= 'i';
    }

    $db->pec('INSERT INTO camera_testing SET ' . implode(', ', $columns), $params, $types);
    $id = $db->last_insert_id();

    return array(
        'success' => true,
        'id' => (string)$id,
        'user_id' => $loggedInUserId
    );
}

function api_camera_testing_get(){
    $db = api_camera_testing_db();
    $filter = api_camera_testing_build_where('');
    $rows = $db->pec(
        'SELECT ct.id, ct.timestamp, ct.brightness, ct.area, ct.lot_number, ct.serial_number, ct.acquired, ct.created_at, ct.tester_name, ct.ext_user_id, u.username FROM camera_testing ct LEFT JOIN core_users u ON u.user_id=ct.ext_user_id' . $filter['where'] . ' ORDER BY ct.timestamp DESC',
        $filter['params'],
        $filter['types'],
        array('id', 'timestamp', 'brightness', 'area', 'lot_number', 'serial_number', 'acquired', 'created_at', 'tester_name', 'ext_user_id', 'username')
    );

    return $rows;
}

function api_camera_testing_page($request){
    $db = api_camera_testing_db();

    $draw = isset($request['draw']) ? (int)$request['draw'] : 0;
    $start = isset($request['start']) ? max(0, (int)$request['start']) : 0;
    $length = isset($request['length']) ? (int)$request['length'] : 20;
    if($length <= 0 || $length > 100){
        $length = 20;
    }

    $columns = array(
        'ct.timestamp',
        'ct.tester_name',
        'u.username',
        'ct.lot_number',
        'ct.serial_number',
        'ct.brightness',
        'ct.area',
        'ct.acquired',
        'ct.created_at'
    );

    $orderColumnIndex = isset($request['order'][0]['column']) ? (int)$request['order'][0]['column'] : 0;
    $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'ct.timestamp';
    $orderDir = isset($request['order'][0]['dir']) && strtolower($request['order'][0]['dir']) === 'asc' ? 'ASC' : 'DESC';

    $search = isset($request['search']['value']) ? trim($request['search']['value']) : '';
    $baseFilter = api_camera_testing_build_where('');
    $searchFilter = api_camera_testing_build_where($search);

    $totalRows = $db->pec(
        'SELECT count(*) FROM camera_testing ct LEFT JOIN core_users u ON u.user_id=ct.ext_user_id' . $baseFilter['where'],
        $baseFilter['params'],
        $baseFilter['types'],
        array('count')
    );
    $recordsTotal = isset($totalRows[0]['count']) ? (int)$totalRows[0]['count'] : 0;

    if($search !== ''){
        $filteredRows = $db->pec(
            'SELECT count(*) FROM camera_testing ct LEFT JOIN core_users u ON u.user_id=ct.ext_user_id' . $searchFilter['where'],
            $searchFilter['params'],
            $searchFilter['types'],
            array('count')
        );
        $recordsFiltered = isset($filteredRows[0]['count']) ? (int)$filteredRows[0]['count'] : 0;
    }else{
        $recordsFiltered = $recordsTotal;
    }

    $results = $db->pec(
        'SELECT ct.id, ct.timestamp, ct.brightness, ct.area, ct.lot_number, ct.serial_number, ct.acquired, ct.created_at, ct.tester_name, ct.ext_user_id, u.username FROM camera_testing ct LEFT JOIN core_users u ON u.user_id=ct.ext_user_id' . $searchFilter['where'] . ' ORDER BY ' . $orderColumn . ' ' . $orderDir . ' LIMIT ' . $start . ', ' . $length,
        $searchFilter['params'],
        $searchFilter['types'],
        array('id', 'timestamp', 'brightness', 'area', 'lot_number', 'serial_number', 'acquired', 'created_at', 'tester_name', 'ext_user_id', 'username')
    );

    $data = array();
    foreach($results as $row){
        $data[] = array(
            api_camera_testing_html($row['timestamp']),
            api_camera_testing_html($row['tester_name']),
            api_camera_testing_html($row['username']),
            api_camera_testing_html($row['lot_number']),
            api_camera_testing_html($row['serial_number']),
            api_camera_testing_html($row['brightness']),
            api_camera_testing_html($row['area']),
            api_camera_testing_html($row['acquired']),
            api_camera_testing_html($row['created_at'])
        );
    }

    return array(
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    );
}

function api_camera_testing_dispatch($request){
    $mode = isset($request['mode']) ? $request['mode'] : '';

    switch($mode){
        case 'saveCameraTesting':
            echo json_encode(api_camera_testing_save($request));
        break;

        case 'getCameraTestingPage':
            echo json_encode(api_camera_testing_page($request));
        break;

        case 'getCameraTesting':
            echo json_encode(array(
                'success' => true,
                'data' => api_camera_testing_get()
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

if(realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__){
    require_once(__DIR__ . '/_cors.php');
    pertech_api_cors_headers('application/json');

    require_once(dirname(__DIR__) . '/production/lib/includes/includes.php');

    $postdata = file_get_contents('php://input');
    $decodedRequest = !empty($postdata) ? json_decode($postdata, true) : array();
    $request = !empty($decodedRequest) ? $decodedRequest : $_REQUEST;

    api_camera_testing_dispatch($request);
}
?>
