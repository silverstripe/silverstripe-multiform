<?php

/**
 * Danish (Denmark) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('da_DK', $lang) && is_array($lang['da_DK'])) {
	$lang['da_DK'] = array_merge($lang['en_US'], $lang['da_DK']);
} else {
	$lang['da_DK'] = $lang['en_US'];
}

$lang['da_DK']['MultiFormSession']['db_Hash'] = 'Havelåge';
$lang['da_DK']['MultiFormSession']['db_IsComplete'] = 'IsComplete';
$lang['da_DK']['MultiFormSession']['has_many_FormSteps'] = 'FormSteps';
$lang['da_DK']['MultiFormSession']['plural_name'] = '(ingen)';
$lang['da_DK']['MultiFormSession']['singular_name'] = '(ingen)';
$lang['da_DK']['MultiFormStep']['db_Data'] = 'Data';
$lang['da_DK']['MultiFormStep']['plural_name'] = '(ingen)';
$lang['da_DK']['MultiFormStep']['singular_name'] = '(none)';

?>