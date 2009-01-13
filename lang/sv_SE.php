<?php

/**
 * Swedish (Sweden) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('sv_SE', $lang) && is_array($lang['sv_SE'])) {
	$lang['sv_SE'] = array_merge($lang['en_US'], $lang['sv_SE']);
} else {
	$lang['sv_SE'] = $lang['en_US'];
}

$lang['sv_SE']['MultiForm']['BACK'] = 'Tillbaka';
$lang['sv_SE']['MultiForm']['NEXT'] = 'Nästa';
$lang['sv_SE']['MultiForm']['SUBMIT'] = 'Skicka';
$lang['sv_SE']['MultiFormSession']['db_Hash'] = 'Hash';
$lang['sv_SE']['MultiFormSession']['plural_name'] = '(ingen)';
$lang['sv_SE']['MultiFormStep']['db_Data'] = 'Data';
$lang['sv_SE']['MultiFormStep']['plural_name'] = '(inga)';
$lang['sv_SE']['MultiFormStep']['singular_name'] = '(ingen)';

?>