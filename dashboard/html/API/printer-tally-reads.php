<?php
// API source for Tally Reader app saves and dashboard result reads.

function api_printer_tally_reads_db(){
    global $common;
    return $common['db'];
}

function api_printer_tally_reads_html($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function api_printer_tally_reads_number_html($value){
    if($value === null || $value === ''){
        return '';
    }

    return number_format((float)$value, 0, '.', ',');
}

function api_printer_tally_reads_request_value($request, $keys, $default=''){
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

function api_printer_tally_reads_datetime($value){
    $value = trim((string)$value);
    if($value === ''){
        return '';
    }

    $timestamp = strtotime($value);
    if($timestamp === false){
        return '';
    }

    return date('Y-m-d H:i:s', $timestamp);
}

function api_printer_tally_reads_bigint($value){
    $digits = preg_replace('/\D+/', '', (string)$value);
    if($digits === ''){
        return null;
    }

    $digits = ltrim($digits, '0');
    return $digits === '' ? '0' : $digits;
}

function api_printer_tally_reads_int_or_null($value){
    if($value === null || $value === ''){
        return null;
    }

    if(is_string($value) && preg_match('/^0x[0-9a-f]+$/i', trim($value))){
        return hexdec($value);
    }

    return (int)$value;
}

function api_printer_tally_reads_bool($value){
    if(is_bool($value)){
        return $value ? 1 : 0;
    }

    $value = strtolower(trim((string)$value));
    return in_array($value, array('1', 'true', 'yes', 'success', 'ok'), true) ? 1 : 0;
}

function api_printer_tally_reads_resolve_user_id($request){
    $userId = trim((string)api_printer_tally_reads_request_value(
        $request,
        array('user_id', 'userId', 'logged_in_user_id', 'loggedInUserId'),
        ''
    ));
    if($userId !== ''){
        return $userId;
    }

    return trim((string)api_printer_tally_reads_request_value(
        $request,
        array('logged_in_username', 'loggedInUsername', 'username'),
        ''
    ));
}

function api_printer_tally_reads_resolve_user_name($request){
    return trim((string)api_printer_tally_reads_request_value(
        $request,
        array('user_name', 'userName', 'tester_name', 'testerName', 'logged_in_username', 'loggedInUsername', 'username'),
        ''
    ));
}

function api_printer_tally_reads_session_user_id(){
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
}

function api_printer_tally_reads_user_is_admin($userId){
    if($userId <= 0){
        return false;
    }

    $db = api_printer_tally_reads_db();
    $rows = $db->pec(
        'SELECT count(*) FROM core_user_group_lookup WHERE ext_user_id=? AND ext_group_id=2 LIMIT 1',
        array($userId),
        'i',
        array('count')
    );

    return !empty($rows) && (int)$rows[0]['count'] > 0;
}

function api_printer_tally_reads_user_filter(){
    $userId = api_printer_tally_reads_session_user_id();
    if($userId <= 0){
        return array('clauses' => array('1=0'), 'params' => array(), 'types' => '');
    }

    if(api_printer_tally_reads_user_is_admin($userId)){
        return array('clauses' => array(), 'params' => array(), 'types' => '');
    }

    return array(
        'clauses' => array('ptr.user_id=?'),
        'params' => array((string)$userId),
        'types' => 's'
    );
}

function api_printer_tally_reads_build_where($search=''){
    $filter = api_printer_tally_reads_user_filter();
    $clauses = $filter['clauses'];
    $params = $filter['params'];
    $types = $filter['types'];

    if($search !== ''){
        $clauses[] = '(ptr.read_at LIKE ? OR ptr.user_id LIKE ? OR ptr.user_name LIKE ? OR ptr.printer_name LIKE ? OR ptr.usb_device_serial LIKE ? OR ptr.manufacturer_serial_number LIKE ? OR ptr.read_error LIKE ?)';
        $like = '%' . $search . '%';
        $params = array_merge($params, array($like, $like, $like, $like, $like, $like, $like));
        $types .= 'sssssss';
    }

    return array(
        'where' => !empty($clauses) ? ' WHERE ' . implode(' AND ', $clauses) : '',
        'params' => $params,
        'types' => $types
    );
}

function api_printer_tally_reads_save($request){
    $db = api_printer_tally_reads_db();

    $readAt = api_printer_tally_reads_datetime(api_printer_tally_reads_request_value($request, array('read_at', 'readAt', 'timestamp', 'date_time', 'dateTime'), ''));
    $userId = api_printer_tally_reads_resolve_user_id($request);
    $userName = api_printer_tally_reads_resolve_user_name($request);
    $printerName = trim((string)api_printer_tally_reads_request_value($request, array('printer_name', 'printerName', 'product_name', 'productName'), ''));
    $usbVendorId = api_printer_tally_reads_int_or_null(api_printer_tally_reads_request_value($request, array('usb_vendor_id', 'usbVendorId', 'vendor_id', 'vendorId'), null));
    $usbProductId = api_printer_tally_reads_int_or_null(api_printer_tally_reads_request_value($request, array('usb_product_id', 'usbProductId', 'product_id', 'productId'), null));
    $usbDeviceSerial = trim((string)api_printer_tally_reads_request_value($request, array('usb_device_serial', 'usbDeviceSerial', 'serial_number', 'serialNumber'), ''));
    $manufacturerSerialNumber = trim((string)api_printer_tally_reads_request_value($request, array('manufacturer_serial_number', 'manufacturerSerialNumber', 'manufacturer_serial', 'manufacturerSerial'), ''));
    $readSuccess = api_printer_tally_reads_bool(api_printer_tally_reads_request_value($request, array('read_success', 'readSuccess', 'success'), true));
    $readError = trim((string)api_printer_tally_reads_request_value($request, array('read_error', 'readError', 'error'), ''));
    $rawSerialAscii = (string)api_printer_tally_reads_request_value($request, array('raw_serial_ascii', 'rawSerialAscii'), '');
    $rawTallyAscii = (string)api_printer_tally_reads_request_value($request, array('raw_tally_ascii', 'rawTallyAscii'), '');

    $counts = array(
        api_printer_tally_reads_bigint(api_printer_tally_reads_request_value($request, array('dot_count', 'dotCount'), null)),
        api_printer_tally_reads_bigint(api_printer_tally_reads_request_value($request, array('form_count', 'formCount'), null)),
        api_printer_tally_reads_bigint(api_printer_tally_reads_request_value($request, array('void_count', 'voidCount'), null)),
        api_printer_tally_reads_bigint(api_printer_tally_reads_request_value($request, array('burst_count', 'burstCount'), null)),
        api_printer_tally_reads_bigint(api_printer_tally_reads_request_value($request, array('vault_install_count', 'vaultInstallCount'), null)),
        api_printer_tally_reads_bigint(api_printer_tally_reads_request_value($request, array('total_time_on_hours', 'totalTimeOnHours'), null)),
        api_printer_tally_reads_bigint(api_printer_tally_reads_request_value($request, array('printer_resets', 'printerResets'), null)),
        api_printer_tally_reads_bigint(api_printer_tally_reads_request_value($request, array('firmware_updates_count', 'firmwareUpdatesCount'), null)),
        api_printer_tally_reads_bigint(api_printer_tally_reads_request_value($request, array('external_sheets_loaded', 'externalSheetsLoaded'), null)),
        api_printer_tally_reads_bigint(api_printer_tally_reads_request_value($request, array('ribbon_count', 'ribbonCount'), null)),
        api_printer_tally_reads_bigint(api_printer_tally_reads_request_value($request, array('last_ribbon_change_dot_count', 'lastRibbonChangeDotCount'), null))
    );

    if($readSuccess && $manufacturerSerialNumber === ''){
        return array(
            'success' => false,
            'error' => 'manufacturer_serial_number is required for successful reads'
        );
    }

    $columns = array('read_at=' . ($readAt !== '' ? '?' : 'NOW()'));
    $params = array();
    $types = '';
    if($readAt !== ''){
        $params[] = $readAt;
        $types .= 's';
    }

    $stringFields = array(
        'user_id' => $userId,
        'user_name' => $userName,
        'printer_name' => $printerName,
        'usb_device_serial' => $usbDeviceSerial,
        'manufacturer_serial_number' => $manufacturerSerialNumber
    );
    foreach($stringFields as $field => $value){
        $columns[] = $field . '=?';
        $params[] = $value;
        $types .= 's';
    }

    $intFields = array(
        'usb_vendor_id' => $usbVendorId,
        'usb_product_id' => $usbProductId
    );
    foreach($intFields as $field => $value){
        if($value === null){
            $columns[] = $field . '=NULL';
        }else{
            $columns[] = $field . '=?';
            $params[] = $value;
            $types .= 'i';
        }
    }

    $countFields = array(
        'dot_count',
        'form_count',
        'void_count',
        'burst_count',
        'vault_install_count',
        'total_time_on_hours',
        'printer_resets',
        'firmware_updates_count',
        'external_sheets_loaded',
        'ribbon_count',
        'last_ribbon_change_dot_count'
    );
    foreach($countFields as $index => $field){
        if($counts[$index] === null){
            $columns[] = $field . '=NULL';
        }else{
            $columns[] = $field . '=?';
            $params[] = $counts[$index];
            $types .= 's';
        }
    }

    $columns[] = 'read_success=?';
    $params[] = $readSuccess;
    $types .= 'i';

    $stringFields = array(
        'read_error' => $readError,
        'raw_serial_ascii' => $rawSerialAscii,
        'raw_tally_ascii' => $rawTallyAscii
    );
    foreach($stringFields as $field => $value){
        $columns[] = $field . '=?';
        $params[] = $value;
        $types .= 's';
    }

    $db->pec(
        'INSERT INTO printer_tally_reads SET ' . implode(', ', $columns),
        $params,
        $types
    );

    $id = $db->last_insert_id();

    return array(
        'success' => true,
        'id' => (string)$id
    );
}

function api_printer_tally_reads_result_fields(){
    return array(
        'id',
        'read_at',
        'user_id',
        'user_name',
        'printer_name',
        'usb_vendor_id',
        'usb_product_id',
        'usb_device_serial',
        'manufacturer_serial_number',
        'dot_count',
        'form_count',
        'void_count',
        'burst_count',
        'vault_install_count',
        'total_time_on_hours',
        'printer_resets',
        'firmware_updates_count',
        'external_sheets_loaded',
        'ribbon_count',
        'last_ribbon_change_dot_count',
        'read_success',
        'read_error',
        'raw_serial_ascii',
        'raw_tally_ascii',
        'created_at'
    );
}

function api_printer_tally_reads_get($limit=200){
    $db = api_printer_tally_reads_db();
    $filter = api_printer_tally_reads_build_where('');
    $limit = max(1, min(1000, (int)$limit));

    return $db->pec(
        'SELECT ptr.* FROM printer_tally_reads ptr' . $filter['where'] . ' ORDER BY ptr.read_at DESC LIMIT ' . $limit,
        $filter['params'],
        $filter['types'],
        api_printer_tally_reads_result_fields()
    );
}

function api_printer_tally_reads_page($request){
    $db = api_printer_tally_reads_db();

    $draw = isset($request['draw']) ? (int)$request['draw'] : 0;
    $start = isset($request['start']) ? max(0, (int)$request['start']) : 0;
    $length = isset($request['length']) ? (int)$request['length'] : 20;
    if($length <= 0 || $length > 100){
        $length = 20;
    }

    $columns = array(
        'ptr.read_at',
        'ptr.user_name',
        'ptr.printer_name',
        'ptr.manufacturer_serial_number',
        'ptr.dot_count',
        'ptr.form_count',
        'ptr.void_count',
        'ptr.burst_count',
        'ptr.vault_install_count',
        'ptr.total_time_on_hours',
        'ptr.printer_resets',
        'ptr.firmware_updates_count',
        'ptr.last_ribbon_change_dot_count',
        'ptr.read_success',
        'ptr.created_at'
    );

    $orderColumnIndex = isset($request['order'][0]['column']) ? (int)$request['order'][0]['column'] : 0;
    $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'ptr.read_at';
    $orderDir = isset($request['order'][0]['dir']) && strtolower($request['order'][0]['dir']) === 'asc' ? 'ASC' : 'DESC';

    $search = isset($request['search']['value']) ? trim($request['search']['value']) : '';
    $baseFilter = api_printer_tally_reads_build_where('');
    $searchFilter = api_printer_tally_reads_build_where($search);

    $totalRows = $db->pec(
        'SELECT count(*) FROM printer_tally_reads ptr' . $baseFilter['where'],
        $baseFilter['params'],
        $baseFilter['types'],
        array('count')
    );
    $recordsTotal = isset($totalRows[0]['count']) ? (int)$totalRows[0]['count'] : 0;

    if($search !== ''){
        $filteredRows = $db->pec(
            'SELECT count(*) FROM printer_tally_reads ptr' . $searchFilter['where'],
            $searchFilter['params'],
            $searchFilter['types'],
            array('count')
        );
        $recordsFiltered = isset($filteredRows[0]['count']) ? (int)$filteredRows[0]['count'] : 0;
    }else{
        $recordsFiltered = $recordsTotal;
    }

    $results = $db->pec(
        'SELECT ptr.* FROM printer_tally_reads ptr' . $searchFilter['where'] . ' ORDER BY ' . $orderColumn . ' ' . $orderDir . ' LIMIT ' . $start . ', ' . $length,
        $searchFilter['params'],
        $searchFilter['types'],
        api_printer_tally_reads_result_fields()
    );

    $data = array();
    foreach($results as $row){
        $status = (int)$row['read_success'] === 1 ? 'Success' : 'Failed';
        if((int)$row['read_success'] !== 1 && $row['read_error'] !== ''){
            $status .= ': ' . $row['read_error'];
        }

        $data[] = array(
            api_printer_tally_reads_html($row['read_at']),
            api_printer_tally_reads_html($row['user_name']),
            api_printer_tally_reads_html($row['printer_name']),
            api_printer_tally_reads_html($row['manufacturer_serial_number']),
            api_printer_tally_reads_number_html($row['dot_count']),
            api_printer_tally_reads_number_html($row['form_count']),
            api_printer_tally_reads_number_html($row['void_count']),
            api_printer_tally_reads_number_html($row['burst_count']),
            api_printer_tally_reads_number_html($row['vault_install_count']),
            api_printer_tally_reads_number_html($row['total_time_on_hours']),
            api_printer_tally_reads_number_html($row['printer_resets']),
            api_printer_tally_reads_number_html($row['firmware_updates_count']),
            api_printer_tally_reads_number_html($row['last_ribbon_change_dot_count']),
            api_printer_tally_reads_html($status)
        );
    }

    return array(
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data
    );
}

function api_printer_tally_reads_dispatch($request){
    $mode = isset($request['mode']) ? $request['mode'] : '';

    switch($mode){
        case 'savePrinterTallyRead':
            echo json_encode(api_printer_tally_reads_save($request));
        break;

        case 'getPrinterTallyReadsPage':
            echo json_encode(api_printer_tally_reads_page($request));
        break;

        case 'getPrinterTallyReads':
            echo json_encode(array(
                'success' => true,
                'data' => api_printer_tally_reads_get(isset($request['limit']) ? $request['limit'] : 200)
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

    api_printer_tally_reads_dispatch($request);
}
?>
