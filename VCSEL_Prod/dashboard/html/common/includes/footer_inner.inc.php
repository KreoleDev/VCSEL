<?php
//Developer:    Charles Palmer
//Created:      2014.05.09
//Revision:     2019.06.18

/*
*   2019.06.18  CP  Added part number to footer
*/

//Request current load time
$page_load=$common['page_load']->results();
?>
    </div>
    <div style="clear:both;"></div>
    <div id="footer_spacer"></div>
    <footer>&copy; <?=date('Y'); ?> - All Rights Reserved Worldwide | P/N: 109119A | (<?=$page_load; ?> seconds)<?=isset($server_file_last_modified)?' Code Last Modified: '.$server_file_last_modified:''; ?></footer>
    <div id="menu_overlay"></div>
</body>
</html>