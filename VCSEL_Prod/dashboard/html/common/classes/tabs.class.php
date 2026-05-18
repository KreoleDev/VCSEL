<?php
//Developer:	Charles Palmer
//Revision:		2014.05.21

class tabs{
	
	public $tab_set=0;
	public $tab_id=0;
	//**********************************************************************************************//
	public function create_tabs($tabs=array(),$default=0){
		
		$output='<div id="tab_set_' . $this->tab_set . '" class="custom_tabs">';
        $output.='<ul>';
		
		//Loops through each tab
		foreach($tabs as $key=>$value){
			//format title
			$tabs[$key]['title']=str_replace(' ','&nbsp;',$tabs[$key]['title']);
			
			if(isset($tabs[$key]['mode']) && $tabs[$key]['mode']=='ajax'){
				//Ajax
				$output.='<li><a href="' . $tabs[$key]['content'] . '"><span>' . $tabs[$key]['title'] . '</span></a></li>';
			}else{
				//Non Ajax
				$output.='<li><a href="#tab_' . $this->tab_id . '" title="' . $tabs[$key]['title'] . '"><span>' . $tabs[$key]['title'] . '</span></a></li>';
			}
			$this->tab_id++;
		}

        $output.='</ul>';
		
		//Creates each tab module
		$this->tab_id-=count($tabs); //Subtracts tabs to recycle id values to match
		foreach($tabs as $key=>$value){
			if(!isset($tabs[$key]['mode']) || ($tabs[$key]['mode']!='ajax' && $tabs[$key]['mode']!='include')){
				//Non Ajax
				$output.='<div id="tab_' . $this->tab_id . '">' . $tabs[$key]['content'] . '</div>';
			}elseif(isset($tabs[$key]['mode']) && $tabs[$key]['mode']=='include'){
				//Include file
				ob_start();
				include $tabs[$key]['content'];
       			$contents = ob_get_contents();
        		ob_end_clean();
				
				$output.='<div id="tab_' . $this->tab_id . '">' . $contents . '</div>';
			}
			$this->tab_id++;
		}
		
		$output.='</div><script type="text/javascript" language="javascript">$("#tab_set_' . $this->tab_set . '").tabs({ active: ' . $default . '});</script>';
		
		$this->tab_set++;
		
		return $output;
	}
	//**********************************************************************************************//
}
?>
