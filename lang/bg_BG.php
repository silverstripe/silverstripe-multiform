<?php

/**
 * Bulgarian (Bulgaria) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('bg_BG', $lang) && is_array($lang['bg_BG'])) {
	$lang['bg_BG'] = array_merge($lang['en_US'], $lang['bg_BG']);
} else {
	$lang['bg_BG'] = $lang['en_US'];
}

$lang['bg_BG']['MultiForm']['BACK'] = 'Назад';
$lang['bg_BG']['MultiForm']['NEXT'] = 'Следващо';
$lang['bg_BG']['MultiForm']['SUBMIT'] = 'Прати';
$lang['bg_BG']['MultiFormSession']['plural_name'] = '(никакви)';
$lang['bg_BG']['MultiFormSession']['singular_name'] = '(никакво)';
$lang['bg_BG']['MultiFormStep']['plural_name'] = '(никакви)';
$lang['bg_BG']['MultiFormStep']['singular_name'] = '(никакво)';

?>