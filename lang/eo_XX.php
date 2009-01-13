<?php

/**
 * Esperanto language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('eo_XX', $lang) && is_array($lang['eo_XX'])) {
	$lang['eo_XX'] = array_merge($lang['en_US'], $lang['eo_XX']);
} else {
	$lang['eo_XX'] = $lang['en_US'];
}

$lang['eo_XX']['MultiForm']['BACK'] = 'Retro';
$lang['eo_XX']['MultiForm']['NEXT'] = 'Sekva';
$lang['eo_XX']['MultiForm']['SUBMIT'] = 'Transsendi';
$lang['eo_XX']['MultiFormSession']['db_Hash'] = 'Haketo';
$lang['eo_XX']['MultiFormSession']['db_IsComplete'] = 'IsComplete';
$lang['eo_XX']['MultiFormSession']['has_many_FormSteps'] = 'FormSteps';
$lang['eo_XX']['MultiFormSession']['plural_name'] = '(neniu)';
$lang['eo_XX']['MultiFormSession']['singular_name'] = '(neniu)';
$lang['eo_XX']['MultiFormStep']['db_Data'] = 'Datumoj';
$lang['eo_XX']['MultiFormStep']['plural_name'] = '(neniu)';
$lang['eo_XX']['MultiFormStep']['singular_name'] = '(neniu)';

?>