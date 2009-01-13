<?php

/**
 * Dutch (Netherlands) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('nl_NL', $lang) && is_array($lang['nl_NL'])) {
	$lang['nl_NL'] = array_merge($lang['en_US'], $lang['nl_NL']);
} else {
	$lang['nl_NL'] = $lang['en_US'];
}

$lang['nl_NL']['MultiForm']['BACK'] = 'Terug';
$lang['nl_NL']['MultiForm']['NEXT'] = 'Volgende';
$lang['nl_NL']['MultiForm']['SUBMIT'] = 'Versturen';
$lang['nl_NL']['MultiFormSession']['db_Hash'] = 'Hash';
$lang['nl_NL']['MultiFormSession']['db_IsComplete'] = 'IsCompleet';
$lang['nl_NL']['MultiFormSession']['has_many_FormSteps'] = 'FormulierStappen';
$lang['nl_NL']['MultiFormSession']['plural_name'] = '(geen)';
$lang['nl_NL']['MultiFormSession']['singular_name'] = '(geen)';
$lang['nl_NL']['MultiFormStep']['db_Data'] = 'Data';
$lang['nl_NL']['MultiFormStep']['plural_name'] = '(geen)';
$lang['nl_NL']['MultiFormStep']['singular_name'] = '(geen)';

?>