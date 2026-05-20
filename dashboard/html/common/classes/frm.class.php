<?php
//Developer:	Charles Palmer
//Created:	2013.11.07
//Revision:	2015.11.16

/*
 *	2015.11.16	CP	Variable reference wrong on file upload for tab_index
 */

class frm{
	private $values=array();
	private $errors=array();
	private $hidden=array();
	private $frm_token='';
	private $tab_index=1;
	
	//=========================================================================================//
	public function __construct($values=array(),$errors=array(),$hidden=array(),$tab_index=1){
		$this->values=$values;
		$this->errors=$errors;
		$this->hidden=$hidden;
		$this->tab_index=$tab_index;
		$this->frm_token=hash('md5',uniqid('auth',true));
		$_SESSION['frm_token']=$this->frm_token;
	}
	//=========================================================================================//
	public function begin_frm($id='',$class='',$enctype='application/x-www-form-urlencoded',$accept='',$action='',$method='post',$accept_charset='ISO-8859-1',$autocomplete=true,$error_report_id='',$error_report_class='error_report'){
		/*
		<form action="$action" method="$method" id="$id" class="$class" accept-charset="$accept_charset" autocomplete="$autocomplete" enctype="$enctype">
		*/
		return $this->error_report($error_report_id,$error_report_class) . '<form action="' . (empty($action)?$_SERVER['PHP_SELF']:$action) . '" method="' . $method . '" ' . (!empty($id)?' id="' . $id . '"':'') . (!empty($class)?' class="' . $class . '"':'') . ' accept-charset="' . $accept_charset . '" autocomplete="' . ($autocomplete?'on':'off') . '" enctype="' . $enctype . '">' . $this->hidden_fields();
	}
	//=========================================================================================//
	public function end_frm(){
		/*
		</form>
		*/
		return '</form>';
	}
	//=========================================================================================//
	public function error_report($id='',$class='error_report'){
		/*
		<dl id="$id" class="$class">
			<dt>$error_title:</dt>
			<dd>$error_msg</dd>
			" "
		</dl>
		*/
		$str='';
		if(!empty($this->errors)){
			$str.='<dl ' . (empty($id)?'':' id="' . $id . '"') . (empty($class)?'':' class="' . $class . '"') . '>';
				foreach($this->errors as $name=>$value){
					$str.='<dt>' . $this->errors[$name][0] . ':</dt><dd>' . $this->errors[$name][1] . '</dd>';	
				}
			$str.='</dl>';
		}
		return $str;
	}
	//=========================================================================================//
	public function hidden_fields(){
		/*
		<input type="hidden" name="$key" id="$key" value="$value" />
		" "
		*/
		$str='<input type="hidden" name="frm_token" id="frm_token" value="' . $this->frm_token . '" />';
		if(!empty($this->hidden)){
			foreach($this->hidden as $key=>$value){
				$str.='<input type="hidden" name="' . $key . '" id="' . $key . '" value="' . $value . '" />';	
			}
		}
		return $str;
	}
	//=========================================================================================//
	public function begin_fieldset($legend='',$required_text='Required Fields are in Red',$id='',$class='',$name=''){
		/*
		<fieldset name="$name" id="$id" class="$class">
			<legend>$legend<span> ($required_text)</span></legend>
		*/
		return '<fieldset' . (!empty($name)?' name="' . $name . '"':'') . (!empty($id)?' id="' . $id . '"':'') . (!empty($class)?' class="' . $class . '"':'') . '>' . (!empty($legend)?'<legend>' . $legend . (!empty($required_text)?' <span>(' . $required_text . ')</span>':'') . '</legend>':'');
	}
	//=========================================================================================//
	public function end_fieldset(){
		/*
		</fieldset>
		*/
		return '</fieldset>';
	}
	//=========================================================================================//
	public function begin_dl($class='',$id=''){
		/*
		<dl class="$class" id="$id">
		*/
		return '<dl' . (!empty($class)?' class="' . $class . '"':'') . (!empty($id)?' id="' . $id . '"':'') . '>';
	}
	//=========================================================================================//
	public function end_dl(){
		/*
		</dl>
		*/
		return '</dl>';
	}
	//=========================================================================================//
	public function text($id,$label='',$required=true,$size=64,$description='',$class='',$value='',$placeholder='',$autofocus=false,$pattern='',$form_id='',$autocomplete=true,$formnovalidate=false){
		/*
		<dt class="$class"><label for="$id" form="$form_id">$label</label></dt>
		<dd class="$class"><input type="text" name="$id" id="$id" size="$size" maxlength="$size" tabindex="" placeholder="$placeholder" autocomplete="on" autofocus="autofocus" formnovalidate="formnovalidate" pattern="$pattern" required value="$value" form="$form_id" /><span>$description</span></dd>
		*/
		$str='';
		$class=(!empty($class)?$class:'') . ($required?' required':'') . (isset($this->errors[$id])?' field_error':'');
		$str.=!empty($label)?'<dt' . (!empty($class)?' class="' . $class . '"':'') . '><label for="' . $id . '"' . (!empty($form_id)?' form="$form_id"':'') . '>' . $label . '</label></dt>':'';
		$str.='<dd' . (!empty($class)?' class="' . $class . '"':'') . '><input type="text" name="' . $id . '" id="' . $id . '" size="' . $size . '" maxlength="' . $size . '" tabindex="' . $this->tab_index . '"' . (!empty($placeholder)?' placeholder="' . $placeholder . '"':'') . ' autocomplete="' . ($autocomplete?'on':'off') . '"' . ($autofocus?' autofocus="autofocus"':'') . ($formnovalidate?' formnovalidate="formnovalidate"':'') . (!empty($pattern)?' pattern="' . $pattern . '"':'') . ($required?' required':'') . ' value="' . (isset($this->values[$id])?$this->values[$id]:$value) . '"' . (!empty($form_id)?' form="$form_id"':'') . ' />' . (!empty($description)?'<span>' . $description . '</span>':'') . '</dd>';
		$this->tab_index++;
		return $str;
	}
	//=========================================================================================//
	public function password($id,$label='',$required=true,$size=64,$class='',$placeholder='',$autofocus=false,$form_id=''){
		/*
		<dt class="$class"><label for="$id" form="$form_id">$label</label></dt>
		<dd class="$class"><input type="password" name="$id" id="$id" size="$size" maxlength="$size" tabindex="" placeholder="$placeholder" autofocus="autofocus" required form_id="$form_id" /></dd>
		*/
		$str='';
		$class=(!empty($class)?$class:'') . ($required?' required':'') . (!empty($this->errors)?' field_error':'');
		$str.=!empty($label)?'<dt' . (!empty($class)?' class="' . $class . '"':'') . '><label for="' . $id . '"' . (!empty($form_id)?' form="$form_id"':'') . '>' . $label . '</label></dt>':'';
		$str.='<dd' . (!empty($class)?' class="' . $class . '"':'') . '><input type="password" name="' . $id . '" id="' . $id . '" size="' . $size . '" maxlength="' . $size . '" tabindex="' . $this->tab_index . '"' . (!empty($placeholder)?' placeholder="' . $placeholder . '"':'') . ($autofocus?' autofocus="autofocus"':'') . ($required?' required':'') . (!empty($form_id)?' form="$form_id"':'') . ' /></dd>';
		$this->tab_index++;
		return $str;
	}
	//=========================================================================================//
	public function list_menu($id,$label='',$values=array(),$required=true,$description='',$class='',$value='',$autofocus=false,$form_id=''){
		//=<dt class=" required"><label for="$id">$label</label></dt>
		//=<dd class=" required"><select name="$id" id="$id" tabindex=""><option value="$values" selected="selected">$values</option></select> <span>$description</span></dd>
		$class.=$required?' required':'';
		$class.=!empty($this->errors[$id])?' field_error':'';
		$value=isset($this->values[$id])?$this->values[$id]:$value;
		$str='<dt' . (!empty($class)?' class="' . $class . '"':'') . '><label for="' . $id . '">' . $label . '</label></dt>'
		. '<dd' . (!empty($class)?' class="' . $class . '"':'') . '><select name="' . $id . '" id="' . $id . '" tabindex="' . $this->tab_index . '"' . ($autofocus?' autofocus="autofocus"':'') . ($required?' required':'') . (!empty($form_id)?' form="$form_id"':'') . '>'
		. '<option value="">-- Select One --</option>';
		foreach($values as $key=>$value2){
			$str.='<option value="' . $key . '" ' . ((string)$value==(string)$key?'selected="selected"':'') . '>' . $value2 . '</option>';	
		}
		$str.='</select>' . (!empty($description)?' <span>' . $description . '</span>':'') . '</dd>';
		$this->tab_index++;
		return $str;
	}	
	//=========================================================================================//
	public function textarea($id,$label='',$required=true,$width='360px',$height='90px',$description='',$class='',$value='',$placeholder='', $autofocus=false, $form_id=''){
		//<dt class="$class"><label for="$id">$label</label></dt>
		//<dd class="$class"><textarea tabindex="" id="$id" name="$id" style="width:$width;height:$height;" placeholder="$placeholder" autofocus="autofocus" required form_id="$form_id">$value</textarea><span>$description</span></dd>
		$class.=$required?' required':'';
		$class.=!empty($this->errors[$id])?' field_error':'';
		$value=isset($this->values[$id])?$this->values[$id]:$value;
		$str='<dt' . (!empty($class)?' class="' . $class . '"':'') . '><label for="' . $id . '"' . (!empty($form_id)?' form="$form_id"':'') . '>' . $label . '</label></dt>'
		. '<dd' . (!empty($class)?' class="' . $class . '"':'') . '><textarea' . (!empty($class)?' class="' . $class . '"':'') . ' tabindex="' . $this->tab_index . '" id="' . $id . '" name="' . $id . '" style="width:' . $width . '; height:' . $height . ';"' . (!empty($placeholder)?' placeholder="' . $placeholder . '"':'') . ($autofocus?' autofocus="autofocus"':'') . ($required && strpos($class,'tinymce')<0?' required':'') . (!empty($form_id)?' form="$form_id"':'') . '>' . $value . '</textarea>' . (!empty($description)?'<span>' . $description . '</span>':'') . '</dd>';
		$this->tab_index++;
		return $str;
	}
	//=========================================================================================//
	public function radio_group($id,$label='',$values=array(),$required=true,$class='',$value=''){
		//<dt class=" required"><label for="$id">$label</label></dt>
		//<dd class=" required"><input type="radio" tabindex="" id="$id$counter" name="$id" value="$values" checked="checked" /><label for="$id$counter">$values</label></dd>
		$class.=$required?' required':'';
		$class.=!empty($this->errors[$id])?' field_error':'';
		$value=isset($this->values[$id])?$this->values[$id]:$value;
		$str=!empty($label)?'<dt' . (!empty($class)?' class="' . $class . '"':'') . '><label for="' . $id . '">' . $label . '</label></dt>':'';
		$counter=0;
		foreach($values as $value2=>$label2){
			$str.='<dd' . (!empty($class)?' class="' . $class . '"':'') . '><input type="radio" tabindex="' . $this->tab_index . '" id="' . $id . $counter . '" name="' . $id . '" value="' . $value2 . '"' . ($value==$value2?' checked="checked"':'') . '/><label for="' . $id . $counter . '">' . $label2 . '</label></dd>';
			$counter++;
			$this->tab_index++;
		}
		return $str;
	}
	//=========================================================================================//
	public function checkbox_group($id,$label,$values=array(),$required=true,$class='',$selected=array()){
		//<dt class=" required"><label for="$id">$label</label></dt>
		//<dd class=" required"><input type="checkbox" tabindex="" id="$id$counter" name="$id[]" checked="checked" value="$values" /><label for="$id$counter">$values</label></dd>
		$class.=$required?' required':'';
		$class.=!empty($this->errors[$id])?' field_error':'';
		$str=!empty($label)?'<dt' . (!empty($class)?' class="' . $class . '"':'') . '><label for="' . $id . '">' . $label . '</label></dt>':'';
		$selected=isset($this->values[$id])?$this->values[$id]:$selected;
		$i=0;
		foreach($values as $key=>$value){
			$str.='<dd' . (!empty($class)?' class="' . $class . '"':'') . '><input type="checkbox" tabindex="' . $this->tab_index . '" id="' . $id . $i . '" name="' . $id . '[' . $key . ']"' . (in_array($key,$selected)?' checked="checked"':'') . ' value="' . $key . '" /><label for="' . $id . $i . '">' . $value . '</label></dd>';
			$i++;
			$this->tab_index++;
		}
		
		return $str;
	}
	//=========================================================================================//
	public function upload($id,$label='',$required=true,$description='',$class='',$autofocus=false,$form_id=''){
		//<dt class=" required"><label for="$id">$label</label></dt>
		//<dd class=" required"><input tabindex="" type="file" id="$id" name="$id" class="$class" /><span>$description</span></dd>
		$class.=' upload';
		$class.=$required?' required':'';
		$class.=!empty($this->errors[$id]) || (!empty($this->errors) && isset($_FILES[$id]['name']) && !empty($_FILES[$id]['name']))?' field_error':'';
		$str='<dt' . (!empty($class)?' class="' . $class . '"':'') . '><label for="' . $id . '"' . (!empty($form_id)?' form="$form_id"':'') . '>' . $label . '</label></dt>'
		. '<dd' . (!empty($class)?' class="' . $class . '"':'') . '><input tabindex="' . $this->tab_index . '" type="file" id="' . $id . '" name="' . $id . '" class="' . $class . '"' . ($required?' required':'') . (!empty($form_id)?' form="$form_id"':'') . ($autofocus?' autofocus="autofocus"':'') . ' />'
		. (!empty($description)?' <span>' . $description . '</span>':'')
		. '</dd>';
		$this->tab_index++;
		return $str;
	}
	//=========================================================================================//
	public function submit($id='submit',$label='Submit',$class='',$form_id=''){
		/*
		<dd class="$class"><button type="submit" name="$id" id="$id" form="$form_id">$label</button></dd>
		*/
		$str='<dd' . (!empty($class)?' class="' . $class . '"':'') . '><button type="submit" name="' . $id . '" id="'. $id . '"' . (!empty($form_id)?' form="' . $form_id . '"':'') . ' tabindex="' . $this->tab_index . '">' . $label . '</button></dd>';
		$this->tab_index++;
		return $str;
	}
	//=========================================================================================//
	
}
?>
