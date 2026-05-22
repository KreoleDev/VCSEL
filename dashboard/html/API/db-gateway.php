<?php
// Central API-owned database gateway. Non-API application code should use api_db.

if(!class_exists('db')){
    require_once(dirname(__DIR__) . '/production/lib/classes/db.class.php');
}

class api_db_gateway{
    private $db;

    public function __construct(){
        $this->db = new db(dirname(__DIR__) . '/production/protected/db.info.php');
    }

    public function prepare($prepared_statement){
        return $this->db->prepare($prepared_statement);
    }

    public function execute($parameters=array(), $datatypes='', $results=array()){
        return $this->db->execute($parameters, $datatypes, $results);
    }

    public function close(){
        return $this->db->close();
    }

    public function affected_rows(){
        return $this->db->affected_rows();
    }

    public function pec($prepared_statement, $parameters=array(), $datatypes='', $results=array()){
        return $this->db->pec($prepared_statement, $parameters, $datatypes, $results);
    }

    public function last_insert_id(){
        return $this->db->last_insert_id();
    }

    public function start_transaction(){
        return $this->db->start_transaction();
    }

    public function end_transaction($success){
        return $this->db->end_transaction($success);
    }

    public function clean_field($field){
        return $this->db->clean_field($field);
    }
}

function api_db_gateway_instance(){
    static $gateway = null;
    if($gateway === null){
        $gateway = new api_db_gateway();
    }
    return $gateway;
}

if(realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__){
    header('Content-Type: application/json');
    echo json_encode(array(
        'success' => true,
        'service' => 'Pertech API DB Gateway'
    ));
}
?>
