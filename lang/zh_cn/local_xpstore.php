<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * XP Store (local_xpstore)
 *
 * @package     local_xpstore
 * @copyright   2026 Yeison Díaz
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['action'] = '操作';
$string['activity'] = '活动';
$string['activitydeleted'] = '活动 / 资源已删除';
$string['add'] = '添加';
$string['addproduct'] = '添加新产品';
$string['analytics'] = '分析';
$string['analyticssubtitle'] = '商店表现与使用指标';
$string['audit'] = 'XP 兑换';
$string['balance'] = '余额';
$string['cancel'] = '取消';
$string['canjear'] = '兑换';
$string['catalog'] = '选择您的奖励';
$string['category'] = '商店类别';
$string['category_placeholder'] = '例：VIP...';
$string['category_short'] = '分类:';
$string['categoryicons'] = '类别图标';
$string['chooseactivity'] = '选择活动';
$string['choosetype'] = '选择类型';
$string['colaction'] = '操作';
$string['colactivity'] = '关联活动';
$string['colcategory'] = '商店类别';
$string['colcost'] = '花费';
$string['collabel'] = '标签';
$string['color_cat_icon'] = '分类图标颜色';
$string['colorconfig'] = '视觉定制';
$string['colorsreset'] = '颜色已成功重置为默认值。';
$string['colorssaved'] = '颜色已成功更新。';
$string['coltype'] = '类型';
$string['configtitle'] = 'XP 商店配置';
$string['configure'] = '配置';
$string['confirmdelete'] = '您确定要删除此产品吗？';
$string['confirmdeleteall'] = '您确定要删除所有奖励吗？此操作无法撤销。';
$string['confirmreset'] = '您确定要清除本课程的所有历史记录吗？';
$string['confirmresetcolors'] = '您确定要将商店颜色重置为原始状态吗？';
$string['congratulations'] = '恭喜！';
$string['copyalert'] = '代码已复制到剪贴板！现在转到您课程中的任何标签或页面，打开 HTML 编辑器 (</>) 并粘贴。';
$string['copysinglecard'] = '复制单张卡片';
$string['cost'] = '花费';
$string['currentcatalog'] = '当前目录';
$string['date'] = '日期';
$string['defaultcategory'] = '主目录';
$string['delete'] = '删除';
$string['deleteall'] = '删除所有';
$string['deletedall'] = '所有奖励已从目录中移除。';
$string['edit'] = '编辑';
$string['engagementrate'] = '参与度';
$string['error'] = '处理兑换时出错';
$string['exito'] = '兑换成功！';
$string['full_store'] = '完整商店';
$string['global_settings_info'] = 'XP 商店安装成功！此插件不需要全局配置。要配置商店、颜色和产品，请转到任何课程的主页并点击“XP 商店配置”按钮。';
$string['gotoactivity'] = '前往活动';
$string['gotogradebook'] = '前往成绩单';
$string['gradepoints'] = '奖励 (+)';
$string['hide_menu_tooltip'] = '在课程菜单中隐藏商店';
$string['history'] = '购买历史';
$string['history_button'] = '历史记录按钮';
$string['icon_award'] = '奖带';
$string['icon_bolt'] = '闪电';
$string['icon_book'] = '书籍';
$string['icon_camera'] = '相机';
$string['icon_cart'] = '购物车';
$string['icon_diamond'] = '钻石';
$string['icon_gamepad'] = '手柄';
$string['icon_gift'] = '礼物';
$string['icon_globe'] = '地球';
$string['icon_graduation'] = '毕业';
$string['icon_heart'] = '心';
$string['icon_magic'] = '魔法';
$string['icon_medal'] = '奖牌';
$string['icon_money'] = '金币';
$string['icon_music'] = '音乐';
$string['icon_puzzle'] = '拼图';
$string['icon_rocket'] = '火箭';
$string['icon_shield'] = '盾牌';
$string['icon_star'] = '星星';
$string['icon_ticket'] = '门票';
$string['icon_trophy'] = '奖杯';
$string['iconcolor'] = '图标颜色';
$string['icons_saved'] = '图标已保存。';
$string['insuficiente'] = '积分不足';
$string['isactive'] = '激活';
$string['label'] = '标签';
$string['label_help'] = '如果您选择<b>特殊</b>类型，将自动创建（如果已存在则重用）具有此标签的群组，并且所选活动将仅限于该群组。';
$string['limit'] = '限制';
$string['limitreached'] = '您已达到此项目的最大购买限制。';
$string['limitzero'] = '0 = ∞ (无限制)';
$string['linkedactivity'] = '关联活动';
$string['menuhidden'] = '菜单：已隐藏';
$string['menuvisibility_help'] = '为学生启用或禁用课程导航菜单中商店的直接访问链接。我们建议将其隐藏并使用商店小工具代替，因为它们提供更加沉浸式的体验（包括购买奖励时的声音效果和动画）。';
$string['menuvisible'] = '菜单：可见';
$string['mockup_sampleitem'] = '商品示例';
$string['mockup_shortdesc'] = '简短说明';
$string['mockup_storebanner'] = '商店横幅';
$string['mockup_storecard'] = '商店卡片';
$string['mockup_widget'] = '独立小部件';
$string['nopurchases'] = '此课程尚未记录任何购买或兑换。';
$string['norewardscreated'] = '尚未创建任何奖励。';
$string['pluginname'] = 'XP 商店';
$string['points'] = '积分';
$string['predefinedpalettes'] = '预设调色板';
$string['preview'] = '预览';
$string['primarycolor'] = '主色调';
$string['productadded'] = '产品添加成功';
$string['productdeleted'] = '产品已删除';
$string['products'] = '产品';
$string['productupdated'] = '奖励已成功更新。';
$string['purchases'] = '购买';
$string['redemptions'] = '兑换';
$string['redemptions_count'] = '兑换次数：';
$string['reportsubtitle'] = 'XP 奖励的个人追踪';
$string['reporttitle'] = '兑换报告';
$string['resetcolors'] = '重置为默认值';
$string['resetcycle'] = '重置周期';
$string['resethistory'] = '清除兑换历史';
$string['saldo'] = '您当前的余额';
$string['savecolors'] = '保存颜色';
$string['saveicons'] = '保存图标';
$string['searchactivity'] = '搜索（活动）...';
$string['searchfilters'] = '搜索过滤器';
$string['searchfilters_help'] = '您可以使用两个条件过滤报告：<br><br><b>活动：</b>按Moodle活动名称或分配给奖励的自定义标签进行搜索。<br><b>类型：</b>按奖励类型搜索（例如特别、奖励等）。';
$string['searchtype'] = '搜索（类型）...';
$string['secondarycolor'] = '副色调';
$string['settings'] = '设置';
$string['show_menu_tooltip'] = '在课程菜单中显示商店';
$string['showinmenu'] = '在课程菜单中显示';
$string['showinmenu_desc'] = '如果选中，学生将在导航菜单中看到商店的快捷链接。';
$string['soldout'] = '已售罄';
$string['specialcontent'] = '特殊';
$string['storeempty_desc'] = '尚未配置任何奖励。';
$string['storeempty_title'] = '商店是空的';
$string['storetitle'] = 'XP 商店';
$string['str_tabproducts'] = '产品';
$string['str_tabsettings'] = '设置';
$string['style'] = '样式';
$string['success_unlock_gradebook'] = '您在活动 <b>{$a->activity}</b> 中获得了奖励 <b>{$a->reward}</b>！';
$string['success_unlock_reward'] = '您在活动 <b>{$a->activity}</b> 中获得了奖励 <b>{$a->reward}</b>！';
$string['tiendaxp'] = 'XP 商店';
$string['topactivities'] = '热门活动（按购买量）';
$string['toprewardsbypurchases'] = '热门奖励（按购买量）';
$string['toprewardsbyxp'] = '热门奖励（按消耗 XP）';
$string['totalpurchases'] = '总购买量';
$string['totalspent'] = '总花费: {$a} XP';
$string['totalstudents'] = '参与的学生';
$string['totalxp'] = '总消耗 XP';
$string['type'] = '类型';
$string['type_a'] = '24小时延期';
$string['type_f'] = '论坛（经典）';
$string['type_g'] = '奖励分';
$string['type_help'] = '选择奖励类型：<br><br><b>额外尝试：</b>适用于测验。<br><b>24小时延期：</b>仅适用于作业。<br><b>论坛（经典）：</b>允许在截止日期后发帖。<br><b>奖励分：</b>直接向成绩单添加额外分数。<br><b>特殊：</b>解锁隐藏内容或 VIP 群组。';
$string['type_q'] = '额外尝试';
$string['type_s'] = '特殊';
$string['update'] = '更新';
$string['user'] = '用户';
$string['viewanalytics'] = '查看数据分析';
$string['viewaudit'] = '查看审计日志';
$string['widget_panel_desc'] = '点击按钮以复制 HTML 代码。然后将其粘贴到您课程中的任何标签或页面的 HTML 编辑器中。';
$string['widget_panel_title'] = '小工具（嵌入商店或类别）';
$string['widgeterror'] = '奖励不可用。';
$string['widgetunlocked'] = '已解锁！';
$string['widgetunlockeddesc'] = '您现在可以使用您的奖励了。';
