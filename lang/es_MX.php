<?php

/**
 * Spanish (Mexico) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('es_MX', $lang) && is_array($lang['es_MX'])) {
	$lang['es_MX'] = array_merge($lang['en_US'], $lang['es_MX']);
} else {
	$lang['es_MX'] = $lang['en_US'];
}

$lang['es_MX']['MultiForm']['BACK'] = 'Atrás';
$lang['es_MX']['MultiForm']['NEXT'] = 'Siguiente';
$lang['es_MX']['MultiForm']['SUBMIT'] = 'Envíar';
$lang['es_MX']['MultiFormSession']['db_Hash'] = 'Desclose';
$lang['es_MX']['MultiFormSession']['db_IsComplete'] = 'Concluido';
$lang['es_MX']['MultiFormSession']['has_many_FormSteps'] = 'Formularios';
$lang['es_MX']['MultiFormSession']['plural_name'] = '(ningunos)';
$lang['es_MX']['MultiFormSession']['singular_name'] = '(ningún)';
$lang['es_MX']['MultiFormStep']['db_Data'] = 'Datos';
$lang['es_MX']['MultiFormStep']['plural_name'] = '(ningunos)';
$lang['es_MX']['MultiFormStep']['singular_name'] = '(ningún)';

?>