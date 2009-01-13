<?php

/**
 * Turkish (Turkey) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('tr_TR', $lang) && is_array($lang['tr_TR'])) {
	$lang['tr_TR'] = array_merge($lang['en_US'], $lang['tr_TR']);
} else {
	$lang['tr_TR'] = $lang['en_US'];
}

$lang['tr_TR']['MultiForm']['BACK'] = 'Geri';
$lang['tr_TR']['MultiForm']['NEXT'] = 'İleri';
$lang['tr_TR']['MultiForm']['SUBMIT'] = 'Gönder';
$lang['tr_TR']['MultiFormSession']['db_Hash'] = 'Hash';
$lang['tr_TR']['MultiFormSession']['db_IsComplete'] = 'IsComplete';
$lang['tr_TR']['MultiFormSession']['has_many_FormSteps'] = 'FormSteps';
$lang['tr_TR']['MultiFormSession']['plural_name'] = '(hiçbiri)';
$lang['tr_TR']['MultiFormSession']['singular_name'] = '(hiçbiri)';
$lang['tr_TR']['MultiFormStep']['db_Data'] = 'Veri';
$lang['tr_TR']['MultiFormStep']['plural_name'] = '(hiçbiri)';
$lang['tr_TR']['MultiFormStep']['singular_name'] = '(hiçbiri)';

?>