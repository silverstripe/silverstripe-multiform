<?php

/**
 * Estonian (Estonia) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('et_EE', $lang) && is_array($lang['et_EE'])) {
	$lang['et_EE'] = array_merge($lang['en_US'], $lang['et_EE']);
} else {
	$lang['et_EE'] = $lang['en_US'];
}

$lang['et_EE']['MultiForm']['BACK'] = 'Tagasi';
$lang['et_EE']['MultiForm']['NEXT'] = 'Järgmine';
$lang['et_EE']['MultiForm']['SUBMIT'] = 'Saada';
$lang['et_EE']['MultiFormSession']['db_Hash'] = 'Hash';
$lang['et_EE']['MultiFormSession']['db_IsComplete'] = 'IsComplete';
$lang['et_EE']['MultiFormSession']['has_many_FormSteps'] = 'FormSteps';
$lang['et_EE']['MultiFormSession']['plural_name'] = '(none)';
$lang['et_EE']['MultiFormSession']['singular_name'] = '(none)';
$lang['et_EE']['MultiFormStep']['db_Data'] = 'Andmed';
$lang['et_EE']['MultiFormStep']['plural_name'] = '(none)';
$lang['et_EE']['MultiFormStep']['singular_name'] = '(none)';

?>