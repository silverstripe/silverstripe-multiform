<?php

/**
 * French (France) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('fr_FR', $lang) && is_array($lang['fr_FR'])) {
	$lang['fr_FR'] = array_merge($lang['en_US'], $lang['fr_FR']);
} else {
	$lang['fr_FR'] = $lang['en_US'];
}

$lang['fr_FR']['MultiForm']['BACK'] = 'Retour';
$lang['fr_FR']['MultiForm']['NEXT'] = 'Suivant';
$lang['fr_FR']['MultiForm']['SUBMIT'] = 'Envoyez';
$lang['fr_FR']['MultiFormSession']['db_Hash'] = 'Hash';
$lang['fr_FR']['MultiFormSession']['db_IsComplete'] = 'IsComplete';
$lang['fr_FR']['MultiFormSession']['has_many_FormSteps'] = 'Formulaire multi-étapes';
$lang['fr_FR']['MultiFormSession']['plural_name'] = '(aucun)';
$lang['fr_FR']['MultiFormSession']['singular_name'] = '(aucun)';
$lang['fr_FR']['MultiFormStep']['db_Data'] = 'Data';
$lang['fr_FR']['MultiFormStep']['plural_name'] = '(aucun)';
$lang['fr_FR']['MultiFormStep']['singular_name'] = '(aucun)';

?>