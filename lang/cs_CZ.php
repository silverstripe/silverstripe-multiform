<?php

/**
 * Czech (Czech Republic) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('cs_CZ', $lang) && is_array($lang['cs_CZ'])) {
	$lang['cs_CZ'] = array_merge($lang['en_US'], $lang['cs_CZ']);
} else {
	$lang['cs_CZ'] = $lang['en_US'];
}

$lang['cs_CZ']['MultiForm']['BACK'] = 'Zpět';
$lang['cs_CZ']['MultiForm']['NEXT'] = 'Další';
$lang['cs_CZ']['MultiForm']['SUBMIT'] = 'Odeslat';
$lang['cs_CZ']['MultiFormSession']['db_Hash'] = 'Hash';
$lang['cs_CZ']['MultiFormSession']['db_IsComplete'] = 'JeKompletni';
$lang['cs_CZ']['MultiFormSession']['has_many_FormSteps'] = 'FormularoveKroky';
$lang['cs_CZ']['MultiFormSession']['plural_name'] = '(žádný)';
$lang['cs_CZ']['MultiFormSession']['singular_name'] = '(žádný)';
$lang['cs_CZ']['MultiFormStep']['db_Data'] = 'Data';
$lang['cs_CZ']['MultiFormStep']['plural_name'] = '(žádný)';
$lang['cs_CZ']['MultiFormStep']['singular_name'] = '(žádný)';

?>