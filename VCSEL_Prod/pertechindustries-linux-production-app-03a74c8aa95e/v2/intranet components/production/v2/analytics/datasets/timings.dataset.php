<?php
// Developer:  Charles Palmer
// Created:    2022.09.29
// Revision:   2022.10.11

/*
*  2022.10.05  CP  Added more text fields of where in the test things are happening.
*  2022.10.10  CP  Added custom error messaging
*  2022.10.11  CP  Added email address pulling from db
*/

class timings {
  // ======================================================================================== //
  private function sendEmail ($subject, $body) {
    global $db;

    //Perform actual email send
    require_once(dirname(__FILE__) . '/../classes/php-mailer/PHPMailer.php');
    require_once(dirname(__FILE__) . '/../classes/php-mailer/Exception.php');
    require_once(dirname(__FILE__) . '/../classes/php-mailer/SMTP.php');
    require_once(dirname(__FILE__) . '/../protected/email.inc.php');
    
    $mail=new PHPMailer\PHPMailer\PHPMailer(true);
    try {
      $mail->IsSMTP();
      $mail->Mailer="smtp"; // telling the class to use SMTP
      //$mail->SMTPDebug  = 1; 
      $mail->SMTPAuth   = TRUE;
      $mail->SMTPSecure = "tls";
      $mail->Port       = 587;
      $mail->Host       = $emailDefaultHost; // SMTP 
      $mail->Username   = $emailDefaultUsername;
      $mail->Password   = $emailDefaultPassword;
  
      $mail->From       = $emailDefaultFrom;
      $mail->FromName   = 'Production Test';
      
      $mail->Subject= "Message Via Production Test - " . $subject;
      
      $mail->AltBody= "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
      
      $mail->MsgHTML($body);

      $results = $db->pec('SELECT name, email FROM prod_v2_production_managers', [], '', ['name', 'email']);
      $sendEmail = false;
      foreach($results as $row) {
        $sendEmail = true;
        $mail->AddAddress($row['email'], $row['name']);
      }
      
      if ($sendEmail) {
        $mail->Send();
      }
      return true;
    } catch (Exception $e) {
      return false;
    }
  }
  // ======================================================================================== //
  public function setTestTiming ($params) {
    global $db, $isTrustedDomain, $ipWhitelist;
    $success = false;
    $message = '';

    if (
      isset($params->timelapsed) && 
      isset($params->testId) && 
      isset($params->testerName) && 
      isset($params->testPassed) && 
      isset($params->serverFriendlyName) && 
      isset($params->macAddress) && 
      isset($params->extAnalyticsProductionId) && 
      isset($params->failMsg) &&
      isset($params->productTitle) &&
      isset($params->selectedTestSetTitle) &&
      isset($params->testTitle) &&
      isset($params->customError)
    ) {
      $results = $db->pec(
        'INSERT INTO prod_v2_analytics_individual_tests SET generatedDateTime=NOW(), extTestId=?, testerName=?, testPassed=?, timelapsed=?, serverFriendlyName=?, macAddress=?, extAnalyticsProductionId=?, failMsg=?, productTitle=?, selectedTestSetTitle=?, testTitle=?, userSelectedFailure=?', 
        [$params->testId, $params->testerName, ($params->testPassed ? 1 : 0), $params->timelapsed, $params->serverFriendlyName, $params->macAddress, $params->extAnalyticsProductionId, $params->failMsg, $params->productTitle, $params->selectedTestSetTitle, $params->testTitle, $params->customError], 
        'issississsss'
      );
      if ($results) {
        $success = true;
      }

      // Perform clean up (delete old records)
      $results = $db->pec('DELETE FROM prod_v2_analytics_individual_tests WHERE generatedDateTime < DATE_SUB(NOW(), INTERVAL 6 MONTH)');

      // Determine if test has failed multiple times in a short period of time by same tester
      if (!$params->testPassed) {
        $results = $db->pec('SELECT COUNT(*) AS total FROM prod_v2_analytics_individual_tests WHERE generatedDateTime > DATE_SUB(NOW(), INTERVAL 10 MINUTE) AND extTestId=? AND testerName=? AND testPassed=0 AND failMsg=?', [$params->testId, $params->testerName, $params->failMsg], 'iss', ['total']);
        if ($results) {
          if ($results[0]['total'] == 4) {
            // Send email to production managers
            $subject = 'Test Failed Multiple Times';
            $body = '<p>' . $params->testerName . ' had the same test fail ' . $results[0]['total'] . ' times in the last 10 minutes with the error of <em><strong>' . $params->failMsg . '</strong></em>. If you are their supervisor, please help them.</p>';
            $this->sendEmail ($subject, $body);

            // Inform tester that they have failed the test multiple times
            $message = 'This test has failed multiple times in a short period of time.  Please contact your supervisor.';
          }
        }
      }
    }

    return (object) [
      'success' => $success,
      'message' => $message
    ];
  }
  // ======================================================================================== //
}
?>
