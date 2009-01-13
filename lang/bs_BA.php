<?php

/**
 * Bosnian (Bosnia and Herzegovina) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('bs_BA', $lang) && is_array($lang['bs_BA'])) {
	$lang['bs_BA'] = array_merge($lang['en_US'], $lang['bs_BA']);
} else {
	$lang['bs_BA'] = $lang['en_US'];
}

$lang['bs_BA']['MultiFormSession']['db_Hash'] = 'Hash';
$lang['bs_BA']['MultiFormSession']['db_IsComplete'] = 'JeZavršen';
$lang['bs_BA']['MultiFormSession']['has_many_FormSteps'] = 'KoraciForme';
$lang['bs_BA']['MultiFormSession']['plural_name'] = '(ništa)';
$lang['bs_BA']['MultiFormSession']['singular_name'] = '(ništa)';
$lang['bs_BA']['MultiFormStep']['db_Data'] = 'Podaci';
$lang['bs_BA']['MultiFormStep']['plural_name'] = '(ništa)';
$lang['bs_BA']['MultiFormStep']['singular_name'] = '(ništa)';

?>