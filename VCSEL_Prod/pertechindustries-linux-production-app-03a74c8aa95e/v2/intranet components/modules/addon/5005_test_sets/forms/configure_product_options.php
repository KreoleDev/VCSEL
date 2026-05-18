<?php
//Developer:    Charles Palmer
//Created:      2022.10.04
//Revision:     2022.10.04
require_once('common/includes/std_lib.inc.php');

/*
$rights
    [0]     View
    [1]     Manage
*/

/*
*   
*/

$common['security']->generate_page_rights(); //Generate user rights for page

if($common['security']->check_rights(1)){
  if($_REQUEST['extProductId'] > 0 && $_REQUEST['configureProduct'] == "true") {
    // Set defaut values
    $values = [];
    $values['extFirmwareId'] = 0;

    $results = $common['db']->pec('SELECT variableName FROM prod_v2_product_config_parameters WHERE extProductId=? ORDER BY sortOrder', [$_REQUEST['extProductId']], 'i', ['variableName']);
    foreach($results as $row) {
      $values[$row['variableName']] = 0;
    }

    // Get current config (overwrite defaults)
    if(isset($_REQUEST['testSetId'])) {
      $testSetInfo = $common['db']->pec('SELECT extProductId, configureProduct, productConfig, extFirmwareId FROM prod_v2_test_sets WHERE testSetId=? LIMIT 1', [$_REQUEST['testSetId']], 'i', ['extProductId', 'configureProduct', 'productConfig', 'extFirmwareId']);
      if($testSetInfo[0]['extProductId'] == $_REQUEST['extProductId'] && $testSetInfo[0]['configureProduct'] == ($_REQUEST['configureProduct'] == 'true' ? 1 : 0)) {
        $values['extFirmwareId'] = $testSetInfo[0]['extFirmwareId'];
      }

      if(!empty($testSetInfo[0]['productConfig'])) {
        $prodCfg = json_decode($testSetInfo[0]['productConfig']);
        foreach($prodCfg as $key=>$value) {
          $values[$key] = $value;
        }
      }
    }

    // Get Firmware list
    $results = $common['db']->pec('SELECT firmwareId, version FROM prod_v2_firmwares WHERE extProductId=? ORDER BY version DESC',[$_REQUEST['extProductId']], 'i', ['firmwareId', 'version']);
    $firmwareOptions = [];
    foreach($results as $row) {
      $firmwareOptions[$row['firmwareId']] = $row['version'];
    }

    // Get other lists
    $results = $common['db']->pec('SELECT extParameterId, title, value, isInt, valueString FROM prod_v2_product_config_parameter_options WHERE extParameterId IN(SELECT parameterId FROM prod_v2_product_config_parameters WHERE extProductId=?) ORDER BY extParameterId, sortOrder', [$_REQUEST['extProductId']], 'i', ['extParameterId', 'title', 'value', 'isInt', 'valueString']);
    $listOptions = [];
    foreach($results as $row) {
      if(!isset($listOptions[$row['extParameterId']])) {
        $listOptions[$row['extParameterId']] = [];
      }
      $listOptions[$row['extParameterId']][($row['isInt'] ? $row['value'] : $row['valueString'])] = $row['title'];
    }
    ?>
    <fieldset>
			<legend>Configure Product</legend>
      <dl>
        <dt class="required"><label for="extFirmwareId">Firmware:</label></dt>
		    <dd class="required">
          <select name="extFirmwareId" id="extFirmwareId">
            <option value="">-- Select One --</option>
            <?php
            foreach($firmwareOptions as $key=>$value) {
              echo '<option value="' . $key . '" ' . ($key==$values['extFirmwareId'] ? 'selected="selected"' : '') . '>' . $value . '</option>';
            }
            ?>
          </select>
        </dd>

        <?php
        $results = $common['db']->pec('SELECT parameterId, label, variableName, isBooleanType FROM prod_v2_product_config_parameters WHERE extProductId=? ORDER BY sortOrder', [$_REQUEST['extProductId']], 'i', ['parameterId', 'label', 'variableName', 'isBooleanType']);
        foreach($results as $row) {
          if($row['isBooleanType']) {
            // Boolean
            ?>
            <dt class="required"><label for="<?=$row['variableName']; ?>"><?=$row['label']; ?>:</label></dt>
            <dd class="required"><input type="radio" tabindex="" id="<?=$row['variableName']; ?>0" name="<?=$row['variableName']; ?>" value="0" <?=isset($values[$row['variableName']]) && $values[$row['variableName']] == 0 ? 'checked="checked"' : ''; ?> /><label for="<?=$row['variableName']; ?>0">No</label></dd>
            <dd class="required"><input type="radio" tabindex="" id="<?=$row['variableName']; ?>1" name="<?=$row['variableName']; ?>" value="1" <?=isset($values[$row['variableName']]) && $values[$row['variableName']] == 1 ? 'checked="checked"' : ''; ?> /><label for="<?=$row['variableName']; ?>1">Yes</label></dd>
            <?php
          } else {
            // Select List
            ?>
            <dt class="required"><label for="<?=$row['variableName']; ?>"><?=$row['label']; ?>:</label></dt>
            <dd class="required">
              <select name="<?=$row['variableName']; ?>" id="<?=$row['variableName']; ?>">
                <option value="">-- Select One --</option>
                <?php
                if(isset($listOptions[$row['parameterId']])) {
                  foreach($listOptions[$row['parameterId']] as $key=>$value) {
                    echo '<option value="' . $key . '" ' . ($key==$values[$row['variableName']] ? 'selected="selected"' : '') . '>' . $value . '</option>';
                  }
                }
                ?>
              </select>
            </dd>
            <?php
          }
        }
        ?>
      </dl>
    </fieldset>
    <?php
  }
}
?>