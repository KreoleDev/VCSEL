<?php
//Developer:    Charles Palmer
//Created:      2019.12.05
//Revision:     2019.12.05

/*
*   
*/

/*
[0]     View
[1]     Admin
*/

require_once('common/includes/header.inc.php');

?>
<style>
    .pn-width{
        width:100px;
    }
    .large{
        font-size:22px !important;
        line-height:1.8em;
    }
</style>
<?php

$common['db']=new api_db();
$results = $common['db']->pec('SELECT manual_id, part_number, title, keywords FROM 7008_manuals WHERE active=1',array(),'',array('manual_id', 'part_number', 'title', 'keywords'));

$headings=array('Part Number','Title','');
$heading_classes=array('pn-width','','center');

echo '<div style="margin:10px;">';

echo $common['table']->begin($headings,'full_table',$heading_classes);

foreach($results as $row) {
    $cols=array($row['part_number'] . '<span style="display:none;">' . $row['keywords'] . '</span>',$row['title'],'<a href="pdf/?id=' . $row['manual_id'] . '" >Manual</a>');
    $col_classes=array('large','large','large center');

    echo $common['table']->add_row($cols,$col_classes);
}

echo $common['table']->end();

echo '</div>';

require_once('common/includes/footer.inc.php');
?>
