<?php

/**
 * Icelandic (Iceland) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('is_IS', $lang) && is_array($lang['is_IS'])) {
	$lang['is_IS'] = array_merge($lang['en_US'], $lang['is_IS']);
} else {
	$lang['is_IS'] = $lang['en_US'];
}

$lang['is_IS']['MultiFormSession']['plural_name'] = '(ekkert)';
$lang['is_IS']['MultiFormSession']['singular_name'] = '(ekkert)';
$lang['is_IS']['MultiFormStep']['db_Data'] = 'Gögn';
$lang['is_IS']['MultiFormStep']['plural_name'] = '(ekkert)';
$lang['is_IS']['MultiFormStep']['singular_name'] = '(ekkert)';

?>