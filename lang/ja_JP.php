<?php

/**
 * Japanese (Japan) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('ja_JP', $lang) && is_array($lang['ja_JP'])) {
	$lang['ja_JP'] = array_merge($lang['en_US'], $lang['ja_JP']);
} else {
	$lang['ja_JP'] = $lang['en_US'];
}

$lang['ja_JP']['MultiForm']['BACK'] = '戻る';
$lang['ja_JP']['MultiForm']['NEXT'] = '次へ';
$lang['ja_JP']['MultiForm']['SUBMIT'] = '送信';
$lang['ja_JP']['MultiFormSession']['db_Hash'] = 'ハッシュ';
$lang['ja_JP']['MultiFormStep']['db_Data'] = 'データ';

?>