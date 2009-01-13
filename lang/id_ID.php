<?php

/**
 * Indonesian (Indonesia) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('id_ID', $lang) && is_array($lang['id_ID'])) {
	$lang['id_ID'] = array_merge($lang['en_US'], $lang['id_ID']);
} else {
	$lang['id_ID'] = $lang['en_US'];
}

$lang['id_ID']['MultiForm']['BACK'] = 'Kembali';
$lang['id_ID']['MultiForm']['NEXT'] = 'Berikutnya';
$lang['id_ID']['MultiForm']['SUBMIT'] = 'Kirim';
$lang['id_ID']['MultiFormSession']['db_Hash'] = 'Tanda Pagar';
$lang['id_ID']['MultiFormSession']['db_IsComplete'] = 'TelahSelesai';
$lang['id_ID']['MultiFormSession']['plural_name'] = '(tidak ada)';
$lang['id_ID']['MultiFormSession']['singular_name'] = '(tidak ada)';
$lang['id_ID']['MultiFormStep']['db_Data'] = 'Data';
$lang['id_ID']['MultiFormStep']['plural_name'] = '(tidak ada)';
$lang['id_ID']['MultiFormStep']['singular_name'] = '(tidak ada)';

?>