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

$string['action'] = 'Действие';
$string['activity'] = 'Элемент курса';
$string['activitydeleted'] = 'Элемент курса / Ресурс удален';
$string['add'] = 'Добавить';
$string['addproduct'] = 'Добавить новый продукт';
$string['analytics'] = 'Аналитика';
$string['analyticssubtitle'] = 'Показатели производительности и использования магазина';
$string['audit'] = 'Использование XP';
$string['balance'] = 'БАЛАНС';
$string['cancel'] = 'Отмена';
$string['canjear'] = 'Обменять';
$string['catalog'] = 'Выберите ваши награды';
$string['category'] = 'Категория магазина';
$string['category_placeholder'] = 'Например: VIP...';
$string['category_short'] = 'Кат:';
$string['categoryicons'] = 'Иконки категорий';
$string['chooseactivity'] = 'Выберите элемент курса';
$string['choosetype'] = 'Выберите тип';
$string['colaction'] = 'Действие';
$string['colactivity'] = 'Связанный элемент';
$string['colcategory'] = 'Категория магазина';
$string['colcost'] = 'Стоимость';
$string['collabel'] = 'Метка';
$string['color_cat_icon'] = 'Цвет иконок кат.';
$string['colorconfig'] = 'Визуальная настройка';
$string['colorsreset'] = 'Цвета успешно сброшены по умолчанию.';
$string['colorssaved'] = 'Цвета успешно обновлены.';
$string['coltype'] = 'Тип';
$string['configtitle'] = 'Настройка XP Store';
$string['configure'] = 'Настроить';
$string['confirmdelete'] = 'Вы действительно хотите удалить этот продукт?';
$string['confirmdeleteall'] = 'Вы уверены, что хотите удалить ВСЕ награды? Это действие нельзя отменить.';
$string['confirmreset'] = 'Вы уверены, что хотите очистить всю историю для этого курса?';
$string['confirmresetcolors'] = 'Вы уверены, что хотите вернуть магазину исходные цвета?';
$string['congratulations'] = 'Поздравляем!';
$string['copyalert'] = 'Код скопирован в буфер обмена! Теперь перейдите к любой пояснению или странице в вашем курсе, откройте HTML-редактор (</>) и вставьте его.';
$string['copysinglecard'] = 'Копировать одну карточку';
$string['cost'] = 'Стоимость';
$string['currentcatalog'] = 'Текущий каталог';
$string['date'] = 'Дата';
$string['defaultcategory'] = 'Главный каталог';
$string['delete'] = 'Удалить';
$string['deleteall'] = 'Удалить все';
$string['deletedall'] = 'Все награды удалены из каталога.';
$string['edit'] = 'Редактировать';
$string['engagementrate'] = 'Уровень вовлеченности';
$string['error'] = 'Ошибка при обработке обмена';
$string['exito'] = 'Обмен прошел успешно!';
$string['full_store'] = 'Весь магазин';
$string['global_settings_info'] = 'XP Store успешно установлен! Этот плагин не требует глобальных настроек. Чтобы настроить магазин, цвета и продукты, перейдите на главную страницу любого курса и нажмите кнопку "Настройка XP Store".';
$string['gotoactivity'] = 'Перейти к элементу';
$string['gotogradebook'] = 'Перейти в журнал оценок';
$string['gradepoints'] = 'Бонус (+)';
$string['hide_menu_tooltip'] = 'Скрыть магазин из меню курса';
$string['history'] = 'История покупок';
$string['history_button'] = 'Кнопка истории';
$string['icon_award'] = 'Наградная лента';
$string['icon_bolt'] = 'Молния';
$string['icon_book'] = 'Книга';
$string['icon_camera'] = 'Камера';
$string['icon_cart'] = 'Корзина';
$string['icon_diamond'] = 'Бриллиант';
$string['icon_gamepad'] = 'Геймпад';
$string['icon_gift'] = 'Подарок';
$string['icon_globe'] = 'Глобус';
$string['icon_graduation'] = 'Выпускной';
$string['icon_heart'] = 'Сердце';
$string['icon_magic'] = 'Магия';
$string['icon_medal'] = 'Медаль';
$string['icon_money'] = 'Монеты';
$string['icon_music'] = 'Музыка';
$string['icon_puzzle'] = 'Пазл';
$string['icon_rocket'] = 'Ракета';
$string['icon_shield'] = 'Щит';
$string['icon_star'] = 'Звезда';
$string['icon_ticket'] = 'Билет';
$string['icon_trophy'] = 'Трофей';
$string['iconcolor'] = 'Цвет иконки';
$string['icons_saved'] = 'Иконки сохранены.';
$string['insuficiente'] = 'Недостаточно баллов';
$string['isactive'] = 'Активен';
$string['label'] = 'Метка';
$string['label_help'] = 'Если вы выберете тип <b>Специальный</b>, группа с этой меткой будет автоматически создана (или повторно использована, если она уже существует), и доступ к выбранному элементу курса будет ограничен этой группой.';
$string['limit'] = 'Лимит';
$string['limitreached'] = 'Вы достигли максимального лимита покупок для этого товара.';
$string['limitzero'] = '0 = ∞ (Бесконечно)';
$string['linkedactivity'] = 'Связанный элемент';
$string['menuhidden'] = 'Меню: Скрыто';
$string['menuvisibility_help'] = 'Включает или отключает прямую ссылку на магазин в навигационном меню курса для студентов. Мы рекомендуем скрыть ее и использовать виджеты магазина, так как они обеспечивают более захватывающий опыт (включая звуковые эффекты и анимацию при покупке наград).';
$string['menuvisible'] = 'Меню: Видимо';
$string['mockup_sampleitem'] = 'Образец товара';
$string['mockup_shortdesc'] = 'Краткое описание';
$string['mockup_storebanner'] = 'Баннер магазина';
$string['mockup_storecard'] = 'Карточка магазина';
$string['mockup_widget'] = 'Отдельный виджет';
$string['nopurchases'] = 'В этом курсе пока не зарегистрировано покупок или обменов.';
$string['norewardscreated'] = 'Награды еще не созданы.';
$string['pluginname'] = 'XP Store';
$string['points'] = 'Баллы';
$string['predefinedpalettes'] = 'Предустановленные палитры';
$string['preview'] = 'Предварительный просмотр';
$string['primarycolor'] = 'Основной цвет';
$string['productadded'] = 'Продукт успешно добавлен';
$string['productdeleted'] = 'Продукт удален';
$string['products'] = 'Продукты';
$string['productupdated'] = 'Награда успешно обновлена.';
$string['purchases'] = 'Покупки';
$string['redemptions'] = 'обмены';
$string['redemptions_count'] = 'Обмены:';
$string['remainingbalance'] = 'Баланс: {$a} XP';
$string['reportsubtitle'] = 'Индивидуальное отслеживание преимуществ XP';
$string['reporttitle'] = 'Отчет об обменах';
$string['resetcolors'] = 'Сброс по умолчанию';
$string['resetcycle'] = 'Сбросить цикл';
$string['resethistory'] = 'Очистить историю обменов';
$string['saldo'] = 'Ваш текущий баланс';
$string['savecolors'] = 'Сохранить цвета';
$string['saveicons'] = 'Сохранить иконки';
$string['searchactivity'] = 'Поиск (Элемент)...';
$string['searchfilters'] = 'Фильтры поиска';
$string['searchfilters_help'] = 'Вы можете отфильтровать отчет, используя два критерия:<br><br><b>Элемент курса:</b> Поиск по названию элемента курса Moodle или пользовательской метке, назначенной вознаграждению.<br><b>Тип:</b> Поиск по типу вознаграждения (например, Специальное, Бонус и т.д.).';
$string['searchtype'] = 'Поиск (Тип)...';
$string['secondarycolor'] = 'Дополнительный цвет';
$string['settings'] = 'Настройки';
$string['show_menu_tooltip'] = 'Показать магазин в меню курса';
$string['showinmenu'] = 'Показать в меню курса';
$string['showinmenu_desc'] = 'Если отмечено, студенты увидят ярлык магазина в навигационном меню.';
$string['soldout'] = 'Распродано';
$string['specialcontent'] = 'Специальный';
$string['storeempty_desc'] = 'Награды еще не настроены.';
$string['storeempty_title'] = 'Магазин пуст';
$string['storetitle'] = 'XP Store';
$string['str_tabproducts'] = 'Продукты';
$string['str_tabsettings'] = 'Настройки';
$string['style'] = 'Стиль';
$string['success_unlock_gradebook'] = 'Вы получили награду <b>{$a->reward}</b> для элемента курса <b>{$a->activity}</b>!';
$string['success_unlock_reward'] = 'Вы получили награду <b>{$a->reward}</b> для элемента курса <b>{$a->activity}</b>!';
$string['tiendaxp'] = 'XP Store';
$string['topactivities'] = 'Активности с наибольшим числом покупок';
$string['toprewardsbypurchases'] = 'Самые покупаемые награды';
$string['toprewardsbyxp'] = 'Награды с наибольшими затратами XP';
$string['totalpurchases'] = 'Всего покупок';
$string['totalspent'] = 'Всего потрачено: {$a} XP';
$string['totalstudents'] = 'Вовлеченные студенты';
$string['totalxp'] = 'Всего потрачено XP';
$string['type'] = 'Тип';
$string['type_a'] = 'Продление на 24 часа';
$string['type_f'] = 'Форум (Классический)';
$string['type_g'] = 'Бонус';
$string['type_help'] = 'Выберите тип преимущества:<br><br><b>Дополнительная попытка:</b> Работает с тестами.<br><b>Продление на 24 часа:</b> Работает только с заданиями.<br><b>Форум (Классический):</b> Позволяет публиковать сообщения после закрытия.<br><b>Бонус:</b> Добавляет дополнительные баллы прямо в журнал оценок.<br><b>Специальный:</b> Разблокирует скрытый контент или VIP-группы.';
$string['type_q'] = 'Доп. попытка';
$string['type_s'] = 'Специальный';
$string['update'] = 'Обновить';
$string['user'] = 'Пользователь';
$string['viewanalytics'] = 'Посмотреть аналитику';
$string['viewaudit'] = 'Посмотреть журнал аудита';
$string['widget_panel_desc'] = 'Нажмите кнопку, чтобы скопировать HTML-код. Затем вставьте его в HTML-редактор любой пояснения или страницы в вашем курсе.';
$string['widget_panel_title'] = 'Виджеты (встроить магазин или категории)';
$string['widgeterror'] = 'Награда недоступна.';
$string['widgetunlocked'] = 'Разблокировано!';
$string['widgetunlockeddesc'] = 'Теперь вы можете использовать вашу награду.';

$string['requirement'] = 'Требование';
$string['chooserequirement'] = 'Выберите требование';
$string['requires'] = 'должно быть выполнено';

$string['requirement_help'] = 'Выберите элемент курса, который студент должен выполнить, прежде чем он сможет получить эту награду. Награда будет заблокирована до тех пор, пока элемент не будет выполнен.';

$string['requires_short'] = 'Требуется';
