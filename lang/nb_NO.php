<?php

/**
 * Norwegian Bokmal (Norway) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('nb_NO', $lang) && is_array($lang['nb_NO'])) {
	$lang['nb_NO'] = array_merge($lang['en_US'], $lang['nb_NO']);
} else {
	$lang['nb_NO'] = $lang['en_US'];
}

$lang['nb_NO']['MultiForm']['BACK'] = 'Forrige';
$lang['nb_NO']['MultiForm']['NEXT'] = 'Neste';
$lang['nb_NO']['MultiForm']['SUBMIT'] = 'Send';
$lang['nb_NO']['MultiFormSession']['db_Hash'] = 'Hash';
$lang['nb_NO']['MultiFormSession']['db_IsComplete'] = 'ErFullflørt';
$lang['nb_NO']['MultiFormSession']['has_many_FormSteps'] = 'SkjemaSteg';
$lang['nb_NO']['MultiFormSession']['plural_name'] = '(ingen)';
$lang['nb_NO']['MultiFormSession']['singular_name'] = '(ingen)';
$lang['nb_NO']['MultiFormStep']['db_Data'] = 'Data';
$lang['nb_NO']['MultiFormStep']['plural_name'] = '(ingen)';
$lang['nb_NO']['MultiFormStep']['singular_name'] = '(ingen)';

?>