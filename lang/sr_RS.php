<?php

/**
 * Serbian (Serbia) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('sr_RS', $lang) && is_array($lang['sr_RS'])) {
	$lang['sr_RS'] = array_merge($lang['en_US'], $lang['sr_RS']);
} else {
	$lang['sr_RS'] = $lang['en_US'];
}

$lang['sr_RS']['MultiFormSession']['db_IsComplete'] = 'ЈеЗавршен';
$lang['sr_RS']['MultiFormSession']['plural_name'] = '(без)';
$lang['sr_RS']['MultiFormSession']['singular_name'] = '(без)';
$lang['sr_RS']['MultiFormStep']['db_Data'] = 'Подаци';
$lang['sr_RS']['MultiFormStep']['plural_name'] = '(без)';
$lang['sr_RS']['MultiFormStep']['singular_name'] = '(без)';

?>