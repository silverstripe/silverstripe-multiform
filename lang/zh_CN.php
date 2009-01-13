<?php

/**
 * Chinese (China) language pack
 * @package modules: multiform
 * @subpackage i18n
 */

i18n::include_locale_file('modules: multiform', 'en_US');

global $lang;

if(array_key_exists('zh_CN', $lang) && is_array($lang['zh_CN'])) {
	$lang['zh_CN'] = array_merge($lang['en_US'], $lang['zh_CN']);
} else {
	$lang['zh_CN'] = $lang['en_US'];
}

$lang['zh_CN']['MultiForm']['BACK'] = '区域';
$lang['zh_CN']['MultiForm']['NEXT'] = '下一个';
$lang['zh_CN']['MultiForm']['SUBMIT'] = '提交';
$lang['zh_CN']['MultiFormSession']['db_Hash'] = 'Hash 字符串';
$lang['zh_CN']['MultiFormSession']['db_IsComplete'] = '是否已完成';
$lang['zh_CN']['MultiFormSession']['has_many_FormSteps'] = '表单步骤';
$lang['zh_CN']['MultiFormSession']['plural_name'] = '多名称';
$lang['zh_CN']['MultiFormSession']['singular_name'] = '单名称';
$lang['zh_CN']['MultiFormStep']['db_Data'] = '数据';
$lang['zh_CN']['MultiFormStep']['plural_name'] = '多名称';
$lang['zh_CN']['MultiFormStep']['singular_name'] = '单名称';

?>