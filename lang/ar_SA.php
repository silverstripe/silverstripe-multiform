<?php

/**
 * Arabic (Saudi Arabia) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('ar_SA', $lang) && is_array($lang['ar_SA'])) {
	$lang['ar_SA'] = array_merge($lang['en_US'], $lang['ar_SA']);
} else {
	$lang['ar_SA'] = $lang['en_US'];
}

$lang['ar_SA']['MultiForm']['BACK'] = 'السابق';
$lang['ar_SA']['MultiForm']['NEXT'] = 'التالي';
$lang['ar_SA']['MultiForm']['SUBMIT'] = 'إرسال';
$lang['ar_SA']['MultiFormSession']['db_Hash'] = 'Hash';
$lang['ar_SA']['MultiFormSession']['db_IsComplete'] = 'مكتمل؟';
$lang['ar_SA']['MultiFormSession']['has_many_FormSteps'] = 'خطوات النموذج';
$lang['ar_SA']['MultiFormSession']['plural_name'] = '(لايوجد)';
$lang['ar_SA']['MultiFormSession']['singular_name'] = '(لايوجد)';
$lang['ar_SA']['MultiFormStep']['db_Data'] = 'بيانات';
$lang['ar_SA']['MultiFormStep']['plural_name'] = '(لايوجد)';
$lang['ar_SA']['MultiFormStep']['singular_name'] = '(لايوجد)';

?>