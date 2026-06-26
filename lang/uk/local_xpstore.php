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

$string['action'] = 'Дія';
$string['activity'] = 'Діяльність';
$string['activitydeleted'] = 'Діяльність / Ресурс видалено';
$string['add'] = 'Додати';
$string['addproduct'] = 'Додати новий продукт';
$string['audit'] = 'Обміни XP';
$string['balance'] = 'БАЛАНС';
$string['cancel'] = 'Скасувати';
$string['canjear'] = 'Обміняти';
$string['catalog'] = 'Виберіть ваші нагороди';
$string['category'] = 'Категорія магазину';
$string['category_placeholder'] = 'Напр: VIP...';
$string['category_short'] = 'Кат:';
$string['categoryicons'] = 'Значки категорій';
$string['chooseactivity'] = 'Вибрати діяльність';
$string['choosetype'] = 'Вибрати тип';
$string['colaction'] = 'Дія';
$string['colactivity'] = 'Пов\'язана діяльність';
$string['colcategory'] = 'Категорія магазину';
$string['colcost'] = 'Вартість';
$string['collabel'] = 'Позначка';
$string['color_cat_icon'] = 'Колір значка категорії';
$string['colorconfig'] = 'Візуальне налаштування';
$string['colorsreset'] = 'Кольори успішно скинуто до стандартних.';
$string['colorssaved'] = 'Кольори успішно оновлено.';
$string['coltype'] = 'Тип';
$string['configtitle'] = 'Налаштування XP Store';
$string['configure'] = 'Налаштувати';
$string['confirmdelete'] = 'Ви дійсно хочете видалити цей продукт?';
$string['confirmdeleteall'] = 'Ви впевнені, що хочете видалити ВСІ нагороди? Цю дію неможливо скасувати.';
$string['confirmreset'] = 'Ви впевнені, що хочете очистити всю історію цього курсу?';
$string['confirmresetcolors'] = 'Ви впевнені, що хочете скинути кольори магазину до початкових?';
$string['congratulations'] = 'Вітаємо!';
$string['copyalert'] = 'Код скопійовано! Тепер перейдіть до будь-якого напису або сторінки у вашому курсі, відкрийте HTML-редактор (</>) та вставте його.';
$string['copysinglecard'] = 'Скопіювати одну картку';
$string['cost'] = 'Вартість';
$string['currentcatalog'] = 'Поточний каталог';
$string['date'] = 'Дата';
$string['defaultcategory'] = 'Головний каталог';
$string['delete'] = 'Видалити';
$string['deleteall'] = 'Видалити все';
$string['deletedall'] = 'Всі нагороди видалено з каталогу.';
$string['edit'] = 'Редагувати';
$string['error'] = 'Помилка обробки обміну';
$string['exito'] = 'Обмін успішний!';
$string['full_store'] = 'Повний магазин';
$string['global_settings_info'] = 'XP Store успішно встановлено! Цей плагін не потребує глобальних налаштувань. Щоб налаштувати магазин, кольори та продукти, перейдіть на головну сторінку будь-якого курсу та натисніть кнопку "Налаштування XP Store".';
$string['gotoactivity'] = 'Перейти до діяльності';
$string['gotogradebook'] = 'Перейти до журналу оцінок';
$string['gradepoints'] = 'Бонус (+)';
$string['hide_menu_tooltip'] = 'Приховати магазин з меню курсу';
$string['history'] = 'Історія покупок';
$string['history_button'] = 'Кнопка історії';
$string['icon_award'] = 'Нагородна стрічка';
$string['icon_bolt'] = 'Блискавка';
$string['icon_book'] = 'Книга';
$string['icon_camera'] = 'Камера';
$string['icon_cart'] = 'Кошик';
$string['icon_diamond'] = 'Діамант';
$string['icon_gamepad'] = 'Геймпад';
$string['icon_gift'] = 'Подарунок';
$string['icon_globe'] = 'Глобус';
$string['icon_graduation'] = 'Випускний';
$string['icon_heart'] = 'Серце';
$string['icon_magic'] = 'Магія';
$string['icon_medal'] = 'Медаль';
$string['icon_money'] = 'Монети';
$string['icon_music'] = 'Музика';
$string['icon_puzzle'] = 'Пазл';
$string['icon_rocket'] = 'Ракета';
$string['icon_shield'] = 'Щит';
$string['icon_star'] = 'Зірка';
$string['icon_ticket'] = 'Квиток';
$string['icon_trophy'] = 'Кубок';
$string['iconcolor'] = 'Колір значка';
$string['icons_saved'] = 'Значки збережено.';
$string['insuficiente'] = 'Недостатньо балів';
$string['isactive'] = 'Активно';
$string['label'] = 'Позначка';
$string['label_help'] = 'Якщо ви виберете тип <b>Спеціальний</b>, група з цією позначкою буде автоматично створена (або використана повторно, якщо вже існує), і вибрана діяльність буде обмежена нею.';
$string['limit'] = 'Ліміт';
$string['limitreached'] = 'Ви досягли максимального ліміту покупок для цього товару.';
$string['limitzero'] = '0 = ∞ (Безлімітно)';
$string['linkedactivity'] = 'Пов\'язана діяльність';
$string['menuhidden'] = 'Меню: Приховано';
$string['menuvisibility_help'] = 'Увімкніть або вимкніть посилання на швидкий доступ до магазину в навігаційному меню курсу для студентів. Ми рекомендуємо тримати його прихованим і використовувати віджети магазину замість нього, оскільки вони забезпечують більш захоплюючий досвід (включаючи звукові ефекти та анімації під час купівлі нагород).';
$string['menuvisible'] = 'Меню: Видиме';
$string['nopurchases'] = 'У цьому курсі ще не зареєстровано покупок або обмінів.';
$string['norewardscreated'] = 'Ще не створено жодних нагород.';
$string['pluginname'] = 'XP Store';
$string['points'] = 'Бали';
$string['primarycolor'] = 'Основний колір (картки та значки)';
$string['privacy:metadata:local_xpstore_gastos'] = 'Зберігає інформацію про обміни користувачів у XP Store.';
$string['privacy:metadata:local_xpstore_gastos:amount'] = 'Вартість товару в XP.';
$string['privacy:metadata:local_xpstore_gastos:itemid'] = 'ID модуля курсу, де було застосовано обмін.';
$string['privacy:metadata:local_xpstore_gastos:itemtype'] = 'Тип обміняного товару.';
$string['privacy:metadata:local_xpstore_gastos:timecreated'] = 'Час здійснення обміну.';
$string['privacy:metadata:local_xpstore_gastos:userid'] = 'Користувач, який здійснив обмін.';
$string['productadded'] = 'Продукт успішно додано';
$string['productdeleted'] = 'Продукт видалено';
$string['productupdated'] = 'Нагороду успішно оновлено.';
$string['purchases'] = 'Покупки';
$string['redemptions'] = 'обміни';
$string['redemptions_count'] = 'Обміни:';
$string['reportsubtitle'] = 'Індивідуальне відстеження переваг XP';
$string['reporttitle'] = 'Звіт про обміни';
$string['resetcolors'] = 'Скинути за замовчуванням';
$string['resetcycle'] = 'Скинути цикл';
$string['resethistory'] = 'Очистити історію обмінів';
$string['saldo'] = 'Ваш поточний баланс';
$string['savecolors'] = 'Зберегти кольори';
$string['saveicons'] = 'Зберегти значки';
$string['secondarycolor'] = 'Другорядний колір (Градієнти)';
$string['show_menu_tooltip'] = 'Показати магазин у меню курсу';
$string['showinmenu'] = 'Показати в меню курсу';
$string['showinmenu_desc'] = 'Якщо позначено, студенти бачитимуть посилання на магазин у меню навігації.';
$string['soldout'] = 'Розпродано';
$string['specialcontent'] = 'Спеціальний';
$string['storeempty_desc'] = 'Нагороди ще не налаштовано.';
$string['storeempty_title'] = 'Магазин порожній';
$string['storetitle'] = 'XP Store';
$string['str_tabproducts'] = 'Продукти';
$string['str_tabsettings'] = 'Налаштування';
$string['success_unlock_gradebook'] = 'Ви отримали нагороду <b>{$a->reward}</b> за діяльність <b>{$a->activity}</b>!';
$string['success_unlock_reward'] = 'Ви отримали нагороду <b>{$a->reward}</b> за діяльність <b>{$a->activity}</b>!';
$string['tiendaxp'] = 'XP Store';
$string['type'] = 'Тип';
$string['type_a'] = 'Подовження на 24 год';
$string['type_f'] = 'Форум (Класичний)';
$string['type_g'] = 'Бонус';
$string['type_help'] = 'Виберіть тип переваги:<br><br><b>Додаткова спроба:</b> Працює з тестами.<br><b>Подовження на 24 год:</b> Працює лише із завданнями.<br><b>Форум (Класичний):</b> Дозволяє публікувати після дати закриття.<br><b>Бонус:</b> Додає додаткові бали безпосередньо в журнал оцінок.<br><b>Спеціальний:</b> Розблоковує прихований вміст або VIP-групи.';
$string['type_q'] = 'Додаткова спроба';
$string['type_s'] = 'Спеціальний';
$string['update'] = 'Оновити';
$string['user'] = 'Користувач';
$string['widget_panel_desc'] = 'Натисніть кнопку, щоб скопіювати HTML-код. Потім вставте його у HTML-редактор будь-якого напису або сторінки у вашому курсі.';
$string['widget_panel_title'] = 'Віджети (вставити магазин або категорії)';
$string['widgeterror'] = 'Нагорода недоступна.';
$string['widgetunlocked'] = 'Розблоковано!';
$string['widgetunlockeddesc'] = 'Тепер ви можете використовувати свою нагороду.';
$string['xpstore:manage'] = 'Керувати XP Store';

$string['searchfilters'] = 'Фільтри пошуку';
$string['searchfilters_help'] = 'Ви можете відфільтрувати звіт за двома критеріями:<br><br><b>Діяльність:</b> Пошук за назвою діяльності Moodle або спеціальною міткою, призначеною нагороді.<br><b>Тип:</b> Пошук за типом нагороди (наприклад, Спеціальна, Бонус тощо).';
$string['searchactivity'] = 'Пошук (Діяльність)...';
$string['searchtype'] = 'Пошук (Тип)...';
