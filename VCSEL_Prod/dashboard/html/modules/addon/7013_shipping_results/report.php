<?php
//Developer:    Charles Palmer
//Created:      2020.10.08
//Revision:     2020.10.08

/*
*   
*/

require_once(dirname(__FILE__) . '/../../../common/includes/std_lib.inc.php');

/*
[0]     Full Control
*/

$page_title='Packing Slip';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){

  $additional_head='
    <style>
      .packing{
        border-top:1px solid rgba(128,128,128,0.5);
        border-left:1px solid rgba(128,128,128,0.5);
        width:100%;
      }
        .packing th{
          background-color:rgba(180,23,23,0.3);
          border-bottom:1px solid rgba(128,128,128,0.5);
          border-right:1px solid rgba(128,128,128,0.5);
          font-weight:bold;
        }
        .packing td{
          border-bottom:1px solid rgba(128,128,128,0.5);
          border-right:1px solid rgba(128,128,128,0.5);
          padding:0 4px;
        }
        .packing td.rightAlign{
          text-align: right;
        }

      #main_menu{
        display:none;
      }
      #main_content{
        left:10px;
      }
      .spaced{
        display:inline-block;
        width:50px;
        text-align:right;
        padding-right:5px;
      }
      .color1{
        background-color:#fff;
      }
      .color2{
        background-color:#ddd;
      }

      tr:hover{
        background:#000;
        color:#fff;
      }
    </style>
  ';

  require_once(dirname(__FILE__) . '/../../../common/includes/header_inner.inc.php');
    $reportInfo = $common['db']->pec('SELECT dueDate FROM 2019_prod_7680_target_dates WHERE targetId=? LIMIT 1',array($_REQUEST['targetId']),'i',array('dueDate'));

    echo $common['window']->begin($page_title . ': ' . date('F j, Y', strtotime($reportInfo[0]['dueDate'])));
      echo '<p><a class="button" href="index.php" title="Back">&lt; Back</a>';

      ?>
      <table class="packing">
        <?php
        // Get all pallets associated with this order
        $results = $common['db']->pec('SELECT palletId, friendlyLabel FROM 2019_prod_pallets WHERE extTargetId=? ORDER BY friendlyLabel',array($_REQUEST['targetId']),'i',array('palletId', 'friendlyLabel'));
        foreach($results as $row) {
          ?>
          <tr>
            <th colspan="2">Pallet <?=$row['friendlyLabel']; ?></th>
          </tr>
          <tr>
            <th>Printer Serial Number</th>
            <th>Vault Serial Number</th>
          </tr>
          <?php
          $subResults = $common['db']->pec('SELECT extPrinterSerialNum, extVaultSerialNum FROM 2019_prod_pallet_items WHERE extPalletId=? ORDER BY extPrinterSerialNum',array($row['palletId']),'i',array('extPrinterSerialNum', 'extVaultSerialNum'));
          $curColor=1;
          foreach($subResults as $subRow) {
            ?>
            <tr class="color<?=$curColor; ?>">
              <td><?=$subRow['extPrinterSerialNum']; ?></td>
              <td><?=$subRow['extVaultSerialNum']; ?></td>
            </tr>
            <?php
            if($curColor == 1) {
              $curColor = 2;
            } else {
              $curColor = 1;
            }
          }
        }
        ?>

      </table>
      <?php
      
    echo $common['window']->end();
  require_once(dirname(__FILE__) . '/../../../common/includes/footer_inner.inc.php');
}
?>