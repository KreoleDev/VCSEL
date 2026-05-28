<?php
//Developer:    Charles Palmer
//Created:      2019.01.10
//Revision:     2019.07.16

/*
*   2019.01.28  CP  Adding TLA info needed for all test
*   2019.02.07  CP  Moved product specific code to external file
*   2019.02.18  CP  Change runTest function around to prevent multiple instances
*   2019.02.28  CP  Added in pulling in config for 5300
*   2019.06.17  CP  Changed to load 7680 support files
*   2019.07.16  CP  Added burster home sensor (product ID 6 in DB)
*/

require_once(dirname(__DIR__, 2) . '/API/_cors.php');
pertech_api_cors_headers('application/javascript');

require_once(dirname(__FILE__) .'/../lib/includes/includes.php');

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if(isset($_REQUEST['tla_id'])) {

    //Find configuration info for TLA based upon product type
    $info = $common['db']->pec('SELECT ext_product_id FROM 2019_prod_tlas WHERE tla_id=? LIMIT 1',array($_REQUEST['tla_id']),'i',array('ext_product_id'));
    if(isset($info[0])) {
        switch($info[0]['ext_product_id']) {
            case 4:
                //7680 Printer
                require_once(dirname(__FILE__) .'/7680-printer-test-config.inc.php');
            break;
            case 5:
                //7680 Vcsel
                require_once(dirname(__FILE__) .'/7680-vcsel-test-config.inc.php');
            break;
            case 6:
                //Burster Home Sensor
                require_once(dirname(__FILE__) .'/burster-home-sensor-test-config.inc.php');
            break;
            default:

            break;
        }
    }

    $results = $common['db']->pec('SELECT test_id, codeset FROM 2019_prod_tla_test_assoc, 2019_prod_tests WHERE ext_test_id = test_id AND ext_tla_id=? AND active=1',array($_REQUEST['tla_id']),'i',array('test_id', 'codeset'));
    ?>
    
    function stdError(messageText) {
        window.app.failTest();
        window.app.showStateIndicator = false;
        window.app.errorPromptMessage = messageText;
        window.app.showErrorPrompt = true;
        aniShowErrorPrompt().then(() => { }, () => { });
    }

    runTest = function(test_id) {
        switch(test_id) {
            
            <?php
            foreach($results as $row) {
                ?>
                case "<?=$row['test_id']; ?>":
                    <?=$row['codeset']; ?>
                    
                break;
                <?php
            }
            ?>

            default:
            break;
        }
    }
    <?php
}
?>
