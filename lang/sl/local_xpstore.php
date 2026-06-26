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

$string['action'] = 'Dejanje';
$string['activity'] = 'Dejavnost';
$string['activitydeleted'] = 'Dejavnost / Vir izbrisan';
$string['add'] = 'Dodaj';
$string['addproduct'] = 'Dodaj nov izdelek';
$string['audit'] = 'XP Unovčitve';
$string['balance'] = 'STANJE';
$string['cancel'] = 'Prekliči';
$string['canjear'] = 'Unovči';
$string['catalog'] = 'Izberi svoje nagrade';
$string['category'] = 'Kategorija trgovine';
$string['category_placeholder'] = 'Npr: VIP...';
$string['category_short'] = 'Kat:';
$string['categoryicons'] = 'Ikone kategorij';
$string['chooseactivity'] = 'Izberi dejavnost';
$string['choosetype'] = 'Izberi vrsto';
$string['colaction'] = 'Dejanje';
$string['colactivity'] = 'Povezana dejavnost';
$string['colcategory'] = 'Kategorija trgovine';
$string['colcost'] = 'Strošek';
$string['collabel'] = 'Oznaka';
$string['color_cat_icon'] = 'Barva ikon kat.';
$string['colorconfig'] = 'Vizualna prilagoditev';
$string['colorsreset'] = 'Barve so bile uspešno ponastavljene na privzete.';
$string['colorssaved'] = 'Barve so bile uspešno posodobljene.';
$string['coltype'] = 'Vrsta';
$string['configtitle'] = 'Nastavitve XP Store';
$string['configure'] = 'Konfiguriraj';
$string['confirmdelete'] = 'Ali res želite izbrisati ta izdelek?';
$string['confirmdeleteall'] = 'Ali ste prepričani, da želite izbrisati VSE nagrade? Tega dejanja ni mogoče razveljaviti.';
$string['confirmreset'] = 'Ali ste prepričani, da želite počistiti celotno zgodovino za ta tečaj?';
$string['confirmresetcolors'] = 'Ali ste prepričani, da želite trgovino ponastaviti na njene izvirne barve?';
$string['congratulations'] = 'Čestitamo!';
$string['copyalert'] = 'Koda kopirana v odložišče! Sedaj pojdite na katerokoli oznako ali stran v vašem tečaju, odprite urejevalnik HTML (</>) in jo prilepite.';
$string['copysinglecard'] = 'Kopiraj posamezno kartico';
$string['cost'] = 'Strošek';
$string['currentcatalog'] = 'Trenutni katalog';
$string['date'] = 'Datum';
$string['defaultcategory'] = 'Glavni katalog';
$string['delete'] = 'Izbriši';
$string['deleteall'] = 'Izbriši vse';
$string['deletedall'] = 'Vse nagrade so bile odstranjene iz kataloga.';
$string['edit'] = 'Uredi';
$string['error'] = 'Napaka pri obdelavi unovčitve';
$string['exito'] = 'Unovčitev uspešna!';
$string['full_store'] = 'Celotna trgovina';
$string['global_settings_info'] = 'XP Store je bil uspešno nameščen! Ta vtičnik ne zahteva globalne konfiguracije. Za nastavitev trgovine, barv in izdelkov obiščite glavno stran katerega koli tečaja in kliknite gumb "Nastavitve XP Store".';
$string['gotoactivity'] = 'Pojdi na dejavnost';
$string['gotogradebook'] = 'Pojdi v redovalnico';
$string['gradepoints'] = 'Bonus (+)';
$string['hide_menu_tooltip'] = 'Skrij trgovino v meniju tečaja';
$string['history'] = 'Zgodovina nakupov';
$string['history_button'] = 'Gumb Zgodovina';
$string['icon_award'] = 'Trak za nagrado';
$string['icon_bolt'] = 'Strela';
$string['icon_book'] = 'Knjiga';
$string['icon_camera'] = 'Kamera';
$string['icon_cart'] = 'Košarica';
$string['icon_diamond'] = 'Diamant';
$string['icon_gamepad'] = 'Igralni plošček';
$string['icon_gift'] = 'Darilo';
$string['icon_globe'] = 'Globus';
$string['icon_graduation'] = 'Diploma';
$string['icon_heart'] = 'Srce';
$string['icon_magic'] = 'Čarovnija';
$string['icon_medal'] = 'Medalja';
$string['icon_money'] = 'Kovanci';
$string['icon_music'] = 'Glasba';
$string['icon_puzzle'] = 'Sestavljanka';
$string['icon_rocket'] = 'Raketa';
$string['icon_shield'] = 'Ščit';
$string['icon_star'] = 'Zvezda';
$string['icon_ticket'] = 'Vstopnica';
$string['icon_trophy'] = 'Pokal';
$string['iconcolor'] = 'Barva ikone';
$string['icons_saved'] = 'Ikone so bile shranjene.';
$string['insuficiente'] = 'Nezadostno število točk';
$string['isactive'] = 'Aktivno';
$string['label'] = 'Oznaka';
$string['label_help'] = 'Če izberete vrsto <b>Posebno</b>, bo skupina s to oznako samodejno ustvarjena (ali ponovno uporabljena, če že obstaja) in izbrana dejavnost bo omejena nanjo.';
$string['limit'] = 'Omejitev';
$string['limitreached'] = 'Dosegli ste najvišjo omejitev nakupov za ta izdelek.';
$string['limitzero'] = '0 = ∞ (Neskončno)';
$string['linkedactivity'] = 'Povezana dejavnost';
$string['menuhidden'] = 'Meni: Skrit';
$string['menuvisibility_help'] = 'Omogoči ali onemogoči neposredno povezavo za dostop do trgovine v navigacijskem meniju tečaja za študente. Priporočamo, da jo skrijete in namesto tega uporabite pripomočke trgovine, saj zagotavljajo bolj poglobljeno izkušnjo (vključno z zvočnimi učinki in animacijami pri nakupu nagrad).';
$string['menuvisible'] = 'Meni: Viden';
$string['nopurchases'] = 'V tem tečaju še niso zabeleženi nakupi ali unovčitve.';
$string['norewardscreated'] = 'Nagrade še niso bile ustvarjene.';
$string['pluginname'] = 'XP Store';
$string['points'] = 'Točke';
$string['primarycolor'] = 'Glavna barva (kartice in ikone)';
$string['privacy:metadata:local_xpstore_gastos'] = 'Shrani podatke o unovčitvah uporabnikov v XP Store.';
$string['privacy:metadata:local_xpstore_gastos:amount'] = 'Cena predmeta v XP.';
$string['privacy:metadata:local_xpstore_gastos:itemid'] = 'ID modula tečaja, kjer je bila unovčitev uporabljena.';
$string['privacy:metadata:local_xpstore_gastos:itemtype'] = 'Vrsta unovčenega predmeta.';
$string['privacy:metadata:local_xpstore_gastos:timecreated'] = 'Čas, ko se je unovčitev zgodila.';
$string['privacy:metadata:local_xpstore_gastos:userid'] = 'Uporabnik, ki je opravil unovčitev.';
$string['productadded'] = 'Izdelek je bil uspešno dodan';
$string['productdeleted'] = 'Izdelek izbrisan';
$string['productupdated'] = 'Nagrada je bila uspešno posodobljena.';
$string['purchases'] = 'Nakupi';
$string['redemptions'] = 'unovčitve';
$string['redemptions_count'] = 'Unovčitve:';
$string['reportsubtitle'] = 'Posamično spremljanje prednosti XP';
$string['reporttitle'] = 'Poročilo o unovčitvah';
$string['resetcolors'] = 'Ponastavi na privzeto';
$string['resetcycle'] = 'Ponastavi cikel';
$string['resethistory'] = 'Počisti zgodovino unovčitev';
$string['saldo'] = 'Vaše trenutno stanje';
$string['savecolors'] = 'Shrani barve';
$string['saveicons'] = 'Shrani ikone';
$string['searchactivity'] = 'Išči (Dejavnost)...';
$string['searchfilters'] = 'Filtri iskanja';
$string['searchfilters_help'] = 'Poročilo lahko filtrirate po dveh kriterijih:<br><br><b>Dejavnost:</b> Iskanje po imenu dejavnosti Moodle ali po meri določeni oznaki nagrade.<br><b>Vrsta:</b> Iskanje po vrsti nagrade (npr. Posebna, Bonus itd.).';
$string['searchtype'] = 'Išči (Vrsta)...';
$string['secondarycolor'] = 'Sekundarna barva (prelivi)';
$string['show_menu_tooltip'] = 'Pokaži trgovino v meniju tečaja';
$string['showinmenu'] = 'Pokaži v meniju tečaja';
$string['showinmenu_desc'] = 'Če je označeno, bodo študenti videli bližnjico do trgovine v navigacijskem meniju.';
$string['soldout'] = 'Razprodano';
$string['specialcontent'] = 'Posebno';
$string['storeempty_desc'] = 'Nobena nagrada še ni bila konfigurirana.';
$string['storeempty_title'] = 'Trgovina je prazna';
$string['storetitle'] = 'XP Store';
$string['str_tabproducts'] = 'Izdelki';
$string['str_tabsettings'] = 'Nastavitve';
$string['success_unlock_gradebook'] = 'Prejeli ste nagrado <b>{$a->reward}</b> za dejavnost <b>{$a->activity}</b>!';
$string['success_unlock_reward'] = 'Prejeli ste nagrado <b>{$a->reward}</b> za dejavnost <b>{$a->activity}</b>!';
$string['tiendaxp'] = 'XP Store';
$string['type'] = 'Vrsta';
$string['type_a'] = '24-urno podaljšanje';
$string['type_f'] = 'Forum (Klasično)';
$string['type_g'] = 'Bonus';
$string['type_help'] = 'Izberite vrsto ugodnosti:<br><br><b>Dodatni poskus:</b> Deluje s kvizi.<br><b>24-urno podaljšanje:</b> Deluje samo z nalogami.<br><b>Forum (Klasično):</b> Omogoča objavljanje po končnem datumu.<br><b>Bonus:</b> Dodatne točke doda neposredno v redovalnico.<br><b>Posebno:</b> Oklene skrito vsebino ali VIP skupine.';
$string['type_q'] = 'Dodatni poskus';
$string['type_s'] = 'Posebno';
$string['update'] = 'Posodobi';
$string['user'] = 'Uporabnik';
$string['widget_panel_desc'] = 'Kliknite gumb, da kopirate kodo HTML. Nato jo prilepite v urejevalnik HTML katere koli oznake ali strani v vašem tečaju.';
$string['widget_panel_title'] = 'Pripomočki (vdelava trgovine ali kategorij)';
$string['widgeterror'] = 'Nagrada ni na voljo.';
$string['widgetunlocked'] = 'Odklenjeno!';
$string['widgetunlockeddesc'] = 'Sedaj lahko uporabite svojo nagrado.';
$string['xpstore:manage'] = 'Upravljaj XP Store';
