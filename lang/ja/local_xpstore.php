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

$string['action'] = 'アクション';
$string['activity'] = '活動';
$string['activitydeleted'] = '活動 / リソースが削除されました';
$string['add'] = '追加';
$string['addproduct'] = '新製品の追加';
$string['analytics'] = '分析';
$string['analyticssubtitle'] = 'ストアのパフォーマンスと使用状況';
$string['audit'] = 'XP引き換え';
$string['balance'] = '残高';
$string['cancel'] = 'キャンセル';
$string['canjear'] = '引き換える';
$string['catalog'] = '報酬を選択してください';
$string['category'] = 'ストアカテゴリ';
$string['category_placeholder'] = '例：VIP...';
$string['category_short'] = 'カテゴリ:';
$string['categoryicons'] = 'カテゴリアイコン';
$string['chooseactivity'] = '活動を選択';
$string['choosetype'] = 'タイプを選択';
$string['colaction'] = 'アクション';
$string['colactivity'] = 'リンクされた活動';
$string['colcategory'] = 'ストアカテゴリ';
$string['colcost'] = 'コスト';
$string['collabel'] = 'ラベル';
$string['color_cat_icon'] = 'カテゴリアイコンの色';
$string['colorconfig'] = '視覚のカスタマイズ';
$string['colorsreset'] = '色はデフォルトに正常にリセットされました。';
$string['colorssaved'] = '色が正常に更新されました。';
$string['coltype'] = 'タイプ';
$string['configtitle'] = 'XPストア設定';
$string['configure'] = '設定';
$string['confirmdelete'] = '本当にこの製品を削除しますか？';
$string['confirmdeleteall'] = '本当にすべての報酬を削除しますか？この操作は元に戻せません。';
$string['confirmreset'] = '本当にこのコースのすべての履歴をクリアしますか？';
$string['confirmresetcolors'] = '本当にストアを元の色にリセットしますか？';
$string['congratulations'] = 'おめでとうございます！';
$string['copyalert'] = 'コードをクリップボードにコピーしました！コースの任意のラベルまたはページに移動し、HTMLエディタ (</>) を開いて貼り付けてください。';
$string['copysinglecard'] = 'シングルカードをコピー';
$string['cost'] = 'コスト';
$string['currentcatalog'] = '現在のカタログ';
$string['date'] = '日付';
$string['defaultcategory'] = 'メインカタログ';
$string['delete'] = '削除';
$string['deleteall'] = 'すべて削除';
$string['deletedall'] = 'すべての報酬がカタログから削除されました。';
$string['edit'] = '編集';
$string['engagementrate'] = 'エンゲージメント率';
$string['error'] = '引き換え処理エラー';
$string['exito'] = '引き換え成功！';
$string['full_store'] = 'フルストア';
$string['global_settings_info'] = 'XP Storeが正常にインストールされました！このプラグインはグローバル設定を必要としません。ストア、色、および製品を構成するには、任意のコースのメインページに移動し、「XP Store設定」ボタンをクリックしてください。';
$string['gotoactivity'] = '活動に移動';
$string['gotogradebook'] = '評定表に移動';
$string['gradepoints'] = 'ボーナス (+)';
$string['hide_menu_tooltip'] = 'コースメニューからストアを隠す';
$string['history'] = '購入履歴';
$string['history_button'] = '履歴ボタン';
$string['icon_award'] = '受賞リボン';
$string['icon_bolt'] = '稲妻';
$string['icon_book'] = '本';
$string['icon_camera'] = 'カメラ';
$string['icon_cart'] = 'カート';
$string['icon_diamond'] = 'ダイヤモンド';
$string['icon_gamepad'] = 'ゲームパッド';
$string['icon_gift'] = 'ギフト';
$string['icon_globe'] = '地球儀';
$string['icon_graduation'] = '卒業';
$string['icon_heart'] = 'ハート';
$string['icon_magic'] = '魔法';
$string['icon_medal'] = 'メダル';
$string['icon_money'] = 'コイン';
$string['icon_music'] = '音楽';
$string['icon_puzzle'] = 'パズル';
$string['icon_rocket'] = 'ロケット';
$string['icon_shield'] = '盾';
$string['icon_star'] = '星';
$string['icon_ticket'] = 'チケット';
$string['icon_trophy'] = 'トロフィー';
$string['iconcolor'] = 'アイコンの色';
$string['icons_saved'] = 'アイコンが保存されました。';
$string['insuficiente'] = 'ポイントが不足しています';
$string['isactive'] = 'アクティブ';
$string['label'] = 'ラベル';
$string['label_help'] = '<b>特別</b>タイプを選択した場合、このラベルを持つグループが自動的に作成され（またはすでに存在する場合は再利用され）、選択した活動はそのグループに制限されます。';
$string['limit'] = '制限';
$string['limitreached'] = 'このアイテムの最大購入制限に達しました。';
$string['limitzero'] = '0 = ∞ (無限)';
$string['linkedactivity'] = 'リンクされた活動';
$string['menuhidden'] = 'メニュー：非表示';
$string['menuvisibility_help'] = '学生向けのコースナビゲーションメニューのストアへの直接アクセスリンクを有効または無効にします。報酬を購入する際のサウンドエフェクトやアニメーションなど、より没入感のある体験を提供するため、非表示にしてストアウィジェットを使用することをお勧めします。';
$string['menuvisible'] = 'メニュー：表示';
$string['mockup_sampleitem'] = 'サンプルアイテム';
$string['mockup_shortdesc'] = '短い説明';
$string['mockup_storebanner'] = 'ストアバナー';
$string['mockup_storecard'] = 'ストアカード';
$string['mockup_widget'] = '個別ウィジェット';
$string['nopurchases'] = 'このコースではまだ購入や引き換えは記録されていません。';
$string['norewardscreated'] = 'まだ報酬は作成されていません。';
$string['pluginname'] = 'XP Store';
$string['points'] = 'ポイント';
$string['predefinedpalettes'] = '定義済みパレット';
$string['preview'] = 'プレビュー';
$string['primarycolor'] = 'メインカラー';
$string['productadded'] = '製品が正常に追加されました';
$string['productdeleted'] = '製品が削除されました';
$string['products'] = '製品';
$string['productupdated'] = '報酬が正常に更新されました。';
$string['purchases'] = '購入';
$string['redemptions'] = '引き換え';
$string['redemptions_count'] = '引き換え：';
$string['remainingbalance'] = '残高: {$a} XP';
$string['reportsubtitle'] = 'XPの特典の個別追跡';
$string['reporttitle'] = '引き換えレポート';
$string['resetcolors'] = 'デフォルトにリセット';
$string['resetcycle'] = 'サイクルをリセット';
$string['resethistory'] = '引き換え履歴をクリア';
$string['saldo'] = '現在の残高';
$string['savecolors'] = '色を保存';
$string['saveicons'] = 'アイコンを保存';
$string['searchactivity'] = '検索（活動）...';
$string['searchfilters'] = '検索フィルター';
$string['searchfilters_help'] = 'レポートは2つの条件を使用してフィルタリングできます:<br><br><b>活動:</b> Moodleの活動名または報酬に割り当てられたカスタムラベルで検索します。<br><b>タイプ:</b> 報酬タイプで検索します（例：特別、ボーナスなど）。';
$string['searchtype'] = '検索（タイプ）...';
$string['secondarycolor'] = 'サブカラー';
$string['settings'] = '設定';
$string['show_menu_tooltip'] = 'コースメニューにストアを表示';
$string['showinmenu'] = 'コースメニューに表示';
$string['showinmenu_desc'] = 'チェックを入れると、学生のナビゲーションメニューにストアへのショートカットが表示されます。';
$string['soldout'] = '売り切れ';
$string['specialcontent'] = '特別';
$string['storeempty_desc'] = '報酬はまだ設定されていません。';
$string['storeempty_title'] = 'ストアは空です';
$string['storetitle'] = 'XP Store';
$string['str_tabproducts'] = '製品';
$string['str_tabsettings'] = '設定';
$string['style'] = 'スタイル';
$string['success_unlock_gradebook'] = '活動 <b>{$a->activity}</b> で報酬 <b>{$a->reward}</b> を獲得しました！';
$string['success_unlock_reward'] = '活動 <b>{$a->activity}</b> で報酬 <b>{$a->reward}</b> を獲得しました！';
$string['tiendaxp'] = 'XP Store';
$string['topactivities'] = '購入が最も多いアクティビティ';
$string['toprewardsbypurchases'] = '最も購入された報酬';
$string['toprewardsbyxp'] = '最もXPが消費された報酬';
$string['totalpurchases'] = '合計購入数';
$string['totalspent'] = '合計消費: {$a} XP';
$string['totalstudents'] = '参加学生数';
$string['totalxp'] = '消費XP合計';
$string['type'] = 'タイプ';
$string['type_a'] = '24時間延長';
$string['type_f'] = 'フォーラム (レガシー)';
$string['type_g'] = 'ボーナス';
$string['type_help'] = '特典のタイプを選択してください:<br><br><b>追加の受験:</b> 小テストで機能します。<br><b>24時間延長:</b> 課題でのみ機能します。<br><b>フォーラム (レガシー):</b> 終了日時後の投稿を許可します。<br><b>ボーナス:</b> 評定表に直接追加のポイントを追加します。<br><b>特別:</b> 非表示のコンテンツやVIPグループのロックを解除します。';
$string['type_q'] = '追加の受験';
$string['type_s'] = '特別';
$string['update'] = '更新';
$string['user'] = 'ユーザー';
$string['viewanalytics'] = 'アナリティクスを表示';
$string['viewaudit'] = '監査ログを表示';
$string['widget_panel_desc'] = 'ボタンをクリックしてHTMLコードをコピーします。次に、コース内の任意のラベルまたはページのHTMLエディタに貼り付けます。';
$string['widget_panel_title'] = 'ウィジェット (ストアまたはカテゴリを埋め込む)';
$string['widgeterror'] = '報酬は利用できません。';
$string['widgetunlocked'] = 'ロック解除！';
$string['widgetunlockeddesc'] = '報酬を使用できるようになりました。';

$string['requirement'] = '要件';
$string['chooserequirement'] = '要件を選択';
$string['requires'] = 'を完了する必要があります';

$string['requirement_help'] = 'この報酬を交換する前に学生が完了しなければならない活動を選択します。活動が完了条件を満たすまで、報酬はロックされます。';

$string['requires_short'] = '必要';
