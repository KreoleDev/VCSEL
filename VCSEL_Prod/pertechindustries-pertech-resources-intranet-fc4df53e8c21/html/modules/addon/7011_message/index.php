<?php
//Developer:    Charles Palmer
//Created:      2020.10.02
//Revision:     2020.10.02

/*
*    
*/

/*
[0]     Full Control
*/

require_once('common/includes/std_lib.inc.php');

$page_title='Digital Sign Message';
$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(0)){
    require_once('common/includes/header_inner.inc.php');
        echo '<h2>'.$page_title.'</h2>';

        echo $common['window']->begin('Message');           
            $headings=array('Message','');
            $heading_classes=array('','center');

            echo $common['table']->begin($headings,'sortable',$heading_classes);

            //Get all related 7680 orders
            $results = $common['db']->pec('SELECT message FROM 7011_messages WHERE messageId=1 LIMIT 1',
                array(),'',array('message'));

            $cols=array(
              $results[0]['message']);
            $col_classes=array('');

            $cols[]='<a href="forms/message.frm.php?mode=edit&amp;messageId=1" title="Edit">Edit</a>';
            $col_classes[]='no_sort center';

            echo $common['table']->add_row($cols,$col_classes);

            echo $common['table']->end();

        echo $common['window']->end();
    require_once('common/includes/footer_inner.inc.php');
}
?>