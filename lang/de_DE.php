<?php

/**
 * German (Germany) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('de_DE', $lang) && is_array($lang['de_DE'])) {
	$lang['de_DE'] = array_merge($lang['en_US'], $lang['de_DE']);
} else {
	$lang['de_DE'] = $lang['en_US'];
}

$lang['de_DE']['MultiForm']['BACK'] = 'Zurück';
$lang['de_DE']['MultiForm']['NEXT'] = 'Weiter';
$lang['de_DE']['MultiForm']['SUBMIT'] = 'Absenden';
$lang['de_DE']['MultiFormSession']['db_Hash'] = 'Hash';
$lang['de_DE']['MultiFormSession']['db_IsComplete'] = 'Abgeschlossen?';
$lang['de_DE']['MultiFormSession']['has_many_FormSteps'] = 'Formularschritte';
$lang['de_DE']['MultiFormSession']['plural_name'] = 'Multi-Formulare';
$lang['de_DE']['MultiFormSession']['singular_name'] = 'Multi-Formular';
$lang['de_DE']['MultiFormStep']['db_Data'] = 'Daten';
$lang['de_DE']['MultiFormStep']['plural_name'] = 'Multi-Formular-Schritte';
$lang['de_DE']['MultiFormStep']['singular_name'] = 'Multi-Formular-Schritt';

?>