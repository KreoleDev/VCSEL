<?php
// Developer:  Charles Palmer
// Created:    2022.09.22
// Revision:   2022.09.22

class testers {
  // ======================================================================================== //
  public function getTesters ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $testers = [];

    $results = $db->pec('SELECT employeeId, firstName, lastName FROM 3026_employees ORDER BY firstName, lastName',array(),'',array('employeeId', 'firstName', 'lastName'));
    if (count($results)) {
      $success = true;
      foreach($results as $row) {
        array_push($testers, (object) [
          'employeeId' => $row['employeeId'],
          'firstName' => $row['firstName'],
          'lastName' => $row['lastName']
        ]);
      }
    }

    return (object) [
      'success' => $success,
      'testers' => $testers
    ];
  }
  // ======================================================================================== //
}
?>