<?php
require_once('common/includes/std_lib.inc.php');
require_once(CFG_CMS_INCLUDE_PATH . 'API/printer-tally-reads.php');

/*
[0]     View
[1]     Write
*/

$page_title='Tally Reader';
$additional_head='
<script type="text/javascript">
$(document).ready(function(){
    $("#page_progress_dialog").addClass("is_visible").attr("aria-hidden","false");
    $("#tally_reader_table").dataTable({
        "bDestroy": true,
        "processing": true,
        "serverSide": true,
        "scrollX": true,
        "order": [[0, "desc"]],
        "lengthMenu": [[20, 50, 100], [20, 50, 100]],
        "ajax": {
            "url": "' . CFG_CMS_BASE_URL . 'API/printer-tally-reads.php",
            "type": "POST",
            "data": function(data){
                data.mode = "getPrinterTallyReadsPage";
            }
        },
        "columnDefs": [
            { "className": "tally_reader_number", "targets": [4, 5, 6, 7, 8, 9, 10, 11, 12] }
        ],
        "fnInitComplete": function(){
            $("#page_progress_dialog").removeClass("is_visible").attr("aria-hidden","true");
            $("#tally_reader_table_filter input").attr("placeholder", "Search serial number");
        }
    });
});
</script>
<style type="text/css">
    #tally_reader_table{
        width:100%;
    }
    #tally_reader_table th,
    #tally_reader_table td{
        white-space:nowrap;
    }
    #tally_reader_table td.tally_reader_number{
        text-align:right;
        font-family:Consolas, monospace;
    }
</style>';
$common['security']->generate_page_rights();

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';

        echo $common['window']->begin('Printer Tally Reads');

            ?>
            <table id="tally_reader_table" class="stripe">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Printer</th>
                        <th>Manufacturer Serial</th>
                        <th>Dot Count</th>
                        <th>Form Count</th>
                        <th>Void Count</th>
                        <th>Burst Count</th>
                        <th>Vault Install</th>
                        <th>Total Hours</th>
                        <th>Resets</th>
                        <th>Firmware Updates</th>
                        <th>Last Ribbon Dot Count</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <?php
        echo $common['window']->end();

    require_once('common/includes/footer_inner.inc.php');
}
?>
