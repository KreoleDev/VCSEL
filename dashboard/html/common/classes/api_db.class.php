<?php
// API-backed database client for legacy dashboard code.

class api_db{
    private $gateway;

    public function __construct($db_info_file_location=''){
        require_once(dirname(__DIR__, 2) . '/API/db-gateway.php');
        $this->gateway = api_db_gateway_instance();
    }

    public function prepare($prepared_statement){
        return $this->gateway->prepare($prepared_statement);
    }

    public function execute($parameters=array(), $datatypes='', $results=array()){
        return $this->gateway->execute($parameters, $datatypes, $results);
    }

    public function close(){
        return $this->gateway->close();
    }

    public function affected_rows(){
        return $this->gateway->affected_rows();
    }

    public function pec($prepared_statement, $parameters=array(), $datatypes='', $results=array()){
        return $this->gateway->pec($prepared_statement, $parameters, $datatypes, $results);
    }

    public function last_insert_id(){
        return $this->gateway->last_insert_id();
    }

    public function start_transaction(){
        return $this->gateway->start_transaction();
    }

    public function end_transaction($success){
        return $this->gateway->end_transaction($success);
    }

    public function clean_field($field){
        return $this->gateway->clean_field($field);
    }
}
?>
