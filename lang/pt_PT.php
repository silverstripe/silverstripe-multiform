<?php

/**
 * Portuguese (Portugal) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('pt_PT', $lang) && is_array($lang['pt_PT'])) {
	$lang['pt_PT'] = array_merge($lang['en_US'], $lang['pt_PT']);
} else {
	$lang['pt_PT'] = $lang['en_US'];
}

$lang['pt_PT']['MultiFormSession']['db_IsComplete'] = 'Está completa';
$lang['pt_PT']['MultiFormSession']['has_many_FormSteps'] = 'Passos do formulário';
$lang['pt_PT']['MultiFormSession']['plural_name'] = '(nenhum)';
$lang['pt_PT']['MultiFormSession']['singular_name'] = '(nenhum)';
$lang['pt_PT']['MultiFormStep']['db_Data'] = 'Dados';
$lang['pt_PT']['MultiFormStep']['plural_name'] = '(nenhum)';
$lang['pt_PT']['MultiFormStep']['singular_name'] = '(nenhum)';

?>