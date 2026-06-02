<?php
//Developer:    Charles Palmer
//Created:      2019.06.11
//Revision:     2020.10.05
require_once('common/includes/std_lib.inc.php');
require_once(CFG_CMS_INCLUDE_PATH . 'API/vcsel-results.php');

/*
*   2019.06.18  CP  Added table from database in
*   2020.09.16  CP  Added VCSEL s/n
*   2020.10.05  CP  Added notes
*/

/*
[0]     View
*/

$page_title='Vcsel Results';
$additional_head='
<script type="text/javascript">
$(document).ready(function(){
    $("#page_progress_dialog").addClass("is_visible").attr("aria-hidden","false");
    $("#vcsel_results_table").dataTable({
        "bDestroy": true,
        "processing": true,
        "serverSide": true,
        "order": [[0, "desc"]],
        "lengthMenu": [[20, 50, 100], [20, 50, 100]],
        "ajax": {
            "url": "' . CFG_CMS_BASE_URL . 'API/vcsel-results.php",
            "type": "POST",
            "data": function(data){
                data.mode = "getVcselResultsPage";
            }
        },
        "columnDefs": [
            { "orderable": false, "targets": [7, 8] }
        ],
        "fnInitComplete": function(){
            $("#page_progress_dialog").removeClass("is_visible").attr("aria-hidden","true");
        }
    });
});
</script>';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';
        
        echo $common['window']->begin('Calibration Results');

            ?>
            <table id="vcsel_results_table" class="stripe">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Tester</th>
                        <th>VCSEL S/N</th>
                        <th>Programmer S/N</th>
                        <th>Transmitter</th>
                        <th>Collector</th>
                        <th>Voltage</th>
                        <th>Notes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <?php
        echo $common['window']->end();
        
    require_once('common/includes/footer_inner.inc.php');
}
?>
