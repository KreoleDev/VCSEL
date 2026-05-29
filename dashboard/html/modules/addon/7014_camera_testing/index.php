<?php
require_once('common/includes/std_lib.inc.php');
require_once(CFG_CMS_INCLUDE_PATH . 'API/camera-testing.php');

/*
[0]     View
*/

$page_title='Camera Testing';
$additional_head='
<script type="text/javascript">
$(document).ready(function(){
    $("#camera_testing_table").dataTable({
        "bDestroy": true,
        "processing": true,
        "serverSide": true,
        "order": [[0, "desc"]],
        "lengthMenu": [[20, 50, 100], [20, 50, 100]],
        "ajax": {
            "url": "' . CFG_CMS_BASE_URL . 'API/camera-testing.php",
            "type": "POST",
            "data": function(data){
                data.mode = "getCameraTestingPage";
            }
        }
    });
});
</script>';
$common['security']->generate_page_rights();

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';

        echo $common['window']->begin('Camera Testing Results');

            ?>
            <table id="camera_testing_table" class="stripe">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Tester</th>
                        <th>Logged User</th>
                        <th>Lot</th>
                        <th>Serial Number</th>
                        <th>Brightness</th>
                        <th>Area</th>
                        <th>Acquired</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <?php
        echo $common['window']->end();

    require_once('common/includes/footer_inner.inc.php');
}
?>
