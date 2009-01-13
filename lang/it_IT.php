<?php

/**
 * Italian (Italy) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('it_IT', $lang) && is_array($lang['it_IT'])) {
	$lang['it_IT'] = array_merge($lang['en_US'], $lang['it_IT']);
} else {
	$lang['it_IT'] = $lang['en_US'];
}

$lang['it_IT']['MultiForm']['BACK'] = 'Indietro';
$lang['it_IT']['MultiForm']['NEXT'] = 'Successivo';
$lang['it_IT']['MultiForm']['SUBMIT'] = 'Invia';
$lang['it_IT']['MultiFormSession']['db_Hash'] = 'Hash';
$lang['it_IT']['MultiFormSession']['db_IsComplete'] = 'IsComplete';
$lang['it_IT']['MultiFormSession']['has_many_FormSteps'] = 'FormSteps';
$lang['it_IT']['MultiFormSession']['plural_name'] = '(nessuno)';
$lang['it_IT']['MultiFormSession']['singular_name'] = '(nessuno)';
$lang['it_IT']['MultiFormStep']['db_Data'] = 'Dati';
$lang['it_IT']['MultiFormStep']['plural_name'] = '(nessuno)';
$lang['it_IT']['MultiFormStep']['singular_name'] = '(nessuno)';

?>