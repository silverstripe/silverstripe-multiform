<?php

/**
 * Polish (Poland) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('pl_PL', $lang) && is_array($lang['pl_PL'])) {
	$lang['pl_PL'] = array_merge($lang['en_US'], $lang['pl_PL']);
} else {
	$lang['pl_PL'] = $lang['en_US'];
}

$lang['pl_PL']['MultiForm']['BACK'] = 'Wstecz';
$lang['pl_PL']['MultiForm']['NEXT'] = 'Dalej';
$lang['pl_PL']['MultiForm']['SUBMIT'] = 'Wyślij';
$lang['pl_PL']['MultiFormSession']['db_Hash'] = 'Hash';
$lang['pl_PL']['MultiFormSession']['db_IsComplete'] = 'CzyGotowe';
$lang['pl_PL']['MultiFormSession']['has_many_FormSteps'] = 'KrokiFormularza';
$lang['pl_PL']['MultiFormSession']['plural_name'] = '(brak)';
$lang['pl_PL']['MultiFormSession']['singular_name'] = '(brak)';
$lang['pl_PL']['MultiFormStep']['db_Data'] = 'Dane';
$lang['pl_PL']['MultiFormStep']['plural_name'] = '(brak)';
$lang['pl_PL']['MultiFormStep']['singular_name'] = '(brak)';

?>