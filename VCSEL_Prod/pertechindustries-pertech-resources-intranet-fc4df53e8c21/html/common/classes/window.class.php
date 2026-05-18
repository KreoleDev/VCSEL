<?php
//Developer:	Charles Palmer
//Created:	2014.05.12
//Revision:	2015.08.06

class window {
    //**********************************************************************************************//
    public function begin($title='',$collapsible=true,$collapsed=false, $class=''){
        return '<div class="window' . ($collapsible?' collapsible':'') . (!empty($class)?' '.$class:'') . '"><div class="win_title' . ($collapsed?' collapse_rounded':'') .'">' . $title . ($collapsible?'<span class="'. ($collapsed?'collapsed':'expanded') .'"></span>':'') .  '</div><div class="win_content' . ($collapsed?' collapsed':'') . '">';
    }
    //**********************************************************************************************//
    public function end(){
        return '<div style="clear:both;"></div></div></div>';
    }
    //**********************************************************************************************//
}

?>