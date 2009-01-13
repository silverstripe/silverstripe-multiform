<?php

/**
 * Spanish (Argentina) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('es_AR', $lang) && is_array($lang['es_AR'])) {
	$lang['es_AR'] = array_merge($lang['en_US'], $lang['es_AR']);
} else {
	$lang['es_AR'] = $lang['en_US'];
}

$lang['es_AR']['MultiForm']['BACK'] = 'Volver';
$lang['es_AR']['MultiForm']['NEXT'] = 'Siguiente';
$lang['es_AR']['MultiForm']['SUBMIT'] = 'Enviar';
$lang['es_AR']['MultiFormSession']['db_Hash'] = 'Hash';
$lang['es_AR']['MultiFormSession']['db_IsComplete'] = 'IsComplete';
$lang['es_AR']['MultiFormSession']['has_many_FormSteps'] = 'FormSteps';
$lang['es_AR']['MultiFormSession']['plural_name'] = '(ninguno)';
$lang['es_AR']['MultiFormSession']['singular_name'] = '(ninguno)';
$lang['es_AR']['MultiFormStep']['db_Data'] = 'Datos';
$lang['es_AR']['MultiFormStep']['plural_name'] = '(ninguno)';
$lang['es_AR']['MultiFormStep']['singular_name'] = '(ninguno)';

?>