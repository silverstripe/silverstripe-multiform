<?php

/**
 * Malay (Malaysia) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('ms_MY', $lang) && is_array($lang['ms_MY'])) {
	$lang['ms_MY'] = array_merge($lang['en_US'], $lang['ms_MY']);
} else {
	$lang['ms_MY'] = $lang['en_US'];
}

$lang['ms_MY']['MultiFormSession']['db_Hash'] = 'Cincangan';
$lang['ms_MY']['MultiFormSession']['db_IsComplete'] = 'TelahLengkap';
$lang['ms_MY']['MultiFormSession']['has_many_FormSteps'] = 'JejakLangkahBorang';
$lang['ms_MY']['MultiFormSession']['plural_name'] = '(tiada)';
$lang['ms_MY']['MultiFormSession']['singular_name'] = '(tiada)';
$lang['ms_MY']['MultiFormStep']['db_Data'] = 'Data';
$lang['ms_MY']['MultiFormStep']['plural_name'] = '(tiada)';
$lang['ms_MY']['MultiFormStep']['singular_name'] = '(tiada)';

?>