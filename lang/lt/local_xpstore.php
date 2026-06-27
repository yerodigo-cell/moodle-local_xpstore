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

$string['action'] = 'Veiksmas';
$string['activity'] = 'Veikla';
$string['activitydeleted'] = 'Veikla / Išteklius ištrintas';
$string['add'] = 'Pridėti';
$string['addproduct'] = 'Pridėti naują produktą';
$string['analytics'] = 'Analytics';
$string['analyticssubtitle'] = 'Store performance and usage metrics';
$string['audit'] = 'XP iškeitimai';
$string['balance'] = 'LIKUTIS';
$string['cancel'] = 'Atšaukti';
$string['canjear'] = 'Iškeisti';
$string['catalog'] = 'Pasirinkite savo apdovanojimus';
$string['category'] = 'Parduotuvės kategorija';
$string['category_placeholder'] = 'Pvz: VIP...';
$string['category_short'] = 'Kat:';
$string['categoryicons'] = 'Kategorijų piktogramos';
$string['chooseactivity'] = 'Pasirinkite veiklą';
$string['choosetype'] = 'Pasirinkite tipą';
$string['colaction'] = 'Veiksmas';
$string['colactivity'] = 'Susijusi veikla';
$string['colcategory'] = 'Parduotuvės kategorija';
$string['colcost'] = 'Kaina';
$string['collabel'] = 'Etiketė';
$string['color_cat_icon'] = 'Kategorijų piktogramų spalva';
$string['colorconfig'] = 'Vizualinis pritaikymas';
$string['colorsreset'] = 'Spalvos sėkmingai atstatytos į numatytąsias.';
$string['colorssaved'] = 'Spalvos sėkmingai atnaujintos.';
$string['coltype'] = 'Tipas';
$string['configtitle'] = 'XP Store konfigūracija';
$string['configure'] = 'Konfigūruoti';
$string['confirmdelete'] = 'Ar tikrai norite ištrinti šį produktą?';
$string['confirmdeleteall'] = 'Ar tikrai norite ištrinti VISUS apdovanojimus? Šio veiksmo atšaukti negalima.';
$string['confirmreset'] = 'Ar tikrai norite išvalyti visą šio kurso istoriją?';
$string['confirmresetcolors'] = 'Ar tikrai norite atstatyti parduotuvės pradines spalvas?';
$string['congratulations'] = 'Sveikiname!';
$string['copyalert'] = 'Kodas nukopijuotas į iškarpinę! Dabar eikite į bet kurią kurso etiketę ar puslapį, atidarykite HTML redaktorių (</>) ir įklijuokite jį.';
$string['copysinglecard'] = 'Kopijuoti vieną kortelę';
$string['cost'] = 'Kaina';
$string['currentcatalog'] = 'Dabartinis katalogas';
$string['date'] = 'Data';
$string['defaultcategory'] = 'Pagrindinis katalogas';
$string['delete'] = 'Ištrinti';
$string['deleteall'] = 'Ištrinti viską';
$string['deletedall'] = 'Visi apdovanojimai buvo pašalinti iš katalogo.';
$string['edit'] = 'Redaguoti';
$string['engagementrate'] = 'Engagement Rate';
$string['error'] = 'Klaida apdorojant iškeitimą';
$string['exito'] = 'Iškeitimas sėkmingas!';
$string['full_store'] = 'Visa parduotuvė';
$string['global_settings_info'] = 'XP Store sėkmingai įdiegta! Šiam papildiniui nereikia globalios konfigūracijos. Norėdami sukonfigūruoti parduotuvę, spalvas ir produktus, eikite į bet kurio kurso pagrindinį puslapį ir spustelėkite mygtuką "XP Store konfigūracija".';
$string['gotoactivity'] = 'Eiti į veiklą';
$string['gotogradebook'] = 'Eiti į įvertinimų knygelę';
$string['gradepoints'] = 'Premija (+)';
$string['hide_menu_tooltip'] = 'Slėpti parduotuvę iš kurso meniu';
$string['history'] = 'Pirkimų istorija';
$string['history_button'] = 'Istorijos mygtukas';
$string['icon_award'] = 'Apdovanojimo kaspinas';
$string['icon_bolt'] = 'Žaibas';
$string['icon_book'] = 'Knyga';
$string['icon_camera'] = 'Kamera';
$string['icon_cart'] = 'Krepšelis';
$string['icon_diamond'] = 'Deimantas';
$string['icon_gamepad'] = 'Žaidimų pultas';
$string['icon_gift'] = 'Dovana';
$string['icon_globe'] = 'Gaublys';
$string['icon_graduation'] = 'Graduacija';
$string['icon_heart'] = 'Širdis';
$string['icon_magic'] = 'Magija';
$string['icon_medal'] = 'Medalis';
$string['icon_money'] = 'Monetos';
$string['icon_music'] = 'Muzika';
$string['icon_puzzle'] = 'Dėlionė';
$string['icon_rocket'] = 'Raketa';
$string['icon_shield'] = 'Skydas';
$string['icon_star'] = 'Žvaigždė';
$string['icon_ticket'] = 'Bilietas';
$string['icon_trophy'] = 'Trofėjus';
$string['iconcolor'] = 'Piktogramos spalva';
$string['icons_saved'] = 'Piktogramos išsaugotos.';
$string['insuficiente'] = 'Nepakanka taškų';
$string['isactive'] = 'Aktyvus';
$string['label'] = 'Etiketė';
$string['label_help'] = 'Jei pasirinksite <b>Specialus</b> tipą, grupė su šia etikete bus sukurta automatiškai (arba pakartotinai panaudota, jei ji jau egzistuoja), ir pasirinkta veikla bus apribota ja.';
$string['limit'] = 'Limitas';
$string['limitreached'] = 'Pasiekėte maksimalų šios prekės pirkimo limitą.';
$string['limitzero'] = '0 = ∞ (Neribota)';
$string['linkedactivity'] = 'Susijusi veikla';
$string['menuhidden'] = 'Meniu: Paslėptas';
$string['menuvisibility_help'] = 'Įjungia arba išjungia tiesioginės prieigos nuorodą į parduotuvę studentų kurso navigacijos meniu. Rekomenduojame palikti ją paslėptą ir vietoj to naudoti parduotuvės valdiklius, nes jie suteikia labiau įtraukiančią patirtį (įskaitant garso efektus ir animacijas perkant apdovanojimus).';
$string['menuvisible'] = 'Meniu: Matomas';
$string['mockup_sampleitem'] = 'Sample Item';
$string['mockup_shortdesc'] = 'Short description';
$string['mockup_storebanner'] = 'Store Banner';
$string['mockup_storecard'] = 'Store Card';
$string['mockup_widget'] = 'Individual Widget';
$string['nopurchases'] = 'Šiame kurse dar nebuvo užregistruota jokių pirkimų ar iškeitimų.';
$string['norewardscreated'] = 'Apdovanojimų dar nesukurta.';
$string['pluginname'] = 'XP Store';
$string['points'] = 'Taškai';
$string['primarycolor'] = 'Pagrindinė spalva (Kortelės ir piktogramos)';
$string['privacy:metadata:local_xpstore_gastos'] = 'Saugoma informacija apie vartotojų iškeitimus XP Store.';
$string['privacy:metadata:local_xpstore_gastos:amount'] = 'Prekės kaina XP.';
$string['privacy:metadata:local_xpstore_gastos:itemid'] = 'Kurso modulio, kuriame pritaikytas iškeitimas, ID.';
$string['privacy:metadata:local_xpstore_gastos:itemtype'] = 'Iškeistos prekės tipas.';
$string['privacy:metadata:local_xpstore_gastos:timecreated'] = 'Laikas, kai įvyko iškeitimas.';
$string['privacy:metadata:local_xpstore_gastos:userid'] = 'Vartotojas, atlikęs iškeitimą.';
$string['productadded'] = 'Produktas sėkmingai pridėtas';
$string['productdeleted'] = 'Produktas ištrintas';
$string['products'] = 'Products';
$string['productupdated'] = 'Apdovanojimas sėkmingai atnaujintas.';
$string['purchases'] = 'Pirkimai';
$string['redemptions'] = 'iškeitimai';
$string['redemptions_count'] = 'Iškeitimai:';
$string['reportsubtitle'] = 'Individualus XP privalumų stebėjimas';
$string['reporttitle'] = 'Iškeitimų ataskaita';
$string['resetcolors'] = 'Atstatyti į numatytuosius';
$string['resetcycle'] = 'Atstatyti ciklą';
$string['resethistory'] = 'Išvalyti iškeitimų istoriją';
$string['saldo'] = 'Jūsų dabartinis likutis';
$string['savecolors'] = 'Išsaugoti spalvas';
$string['saveicons'] = 'Išsaugoti piktogramas';
$string['searchactivity'] = 'Ieškoti (Veikla)...';
$string['searchfilters'] = 'Paieškos filtrai';
$string['searchfilters_help'] = 'Ataskaitą galite filtruoti naudodami du kriterijus:<br><br><b>Veikla:</b> Ieško pagal Moodle veiklos pavadinimą arba pritaikytą etiketę, priskirtą atlygiui.<br><b>Tipas:</b> Ieško pagal atlygio tipą (pvz., Specialus, Premija ir kt.).';
$string['searchtype'] = 'Ieškoti (Tipas)...';
$string['secondarycolor'] = 'Antrinė spalva (Gradientai)';
$string['settings'] = 'Settings';
$string['show_menu_tooltip'] = 'Rodyti parduotuvę kurso meniu';
$string['showinmenu'] = 'Rodyti kurso meniu';
$string['showinmenu_desc'] = 'Jei pažymėta, studentai navigacijos meniu matys parduotuvės nuorodą.';
$string['soldout'] = 'Išparduota';
$string['specialcontent'] = 'Specialus';
$string['storeempty_desc'] = 'Apdovanojimų dar nesukonfigūruota.';
$string['storeempty_title'] = 'Parduotuvė tuščia';
$string['storetitle'] = 'XP Store';
$string['str_tabproducts'] = 'Produktai';
$string['str_tabsettings'] = 'Nustatymai';
$string['success_unlock_gradebook'] = 'Gavote apdovanojimą <b>{$a->reward}</b> veikloje <b>{$a->activity}</b>!';
$string['success_unlock_reward'] = 'Gavote apdovanojimą <b>{$a->reward}</b> veikloje <b>{$a->activity}</b>!';
$string['tiendaxp'] = 'XP Store';
$string['toprewardsbypurchases'] = 'Top Rewards (by purchases)';
$string['toprewardsbyxp'] = 'Top Rewards (by XP spent)';
$string['totalpurchases'] = 'Total Purchases';
$string['totalspent'] = 'Total spent: {$a} XP';
$string['totalstudents'] = 'Engaged Students';
$string['totalxp'] = 'Total XP Spent';
$string['type'] = 'Tipas';
$string['type_a'] = '24 val. pratęsimas';
$string['type_f'] = 'Forumas (Klasikinis)';
$string['type_g'] = 'Premija';
$string['type_help'] = 'Pasirinkite privalumo tipą:<br><br><b>Papildomas bandymas:</b> Veikia su testais.<br><b>24 val. pratęsimas:</b> Veikia tik su užduotimis.<br><b>Forumas (Klasikinis):</b> Leidžia skelbti po uždarymo datos.<br><b>Premija:</b> Prideda papildomus taškus tiesiai į įvertinimų knygelę.<br><b>Specialus:</b> Atrakina paslėptą turinį arba VIP grupes.';
$string['type_q'] = 'Papildomas bandymas';
$string['type_s'] = 'Specialus';
$string['update'] = 'Atnaujinti';
$string['user'] = 'Vartotojas';
$string['viewanalytics'] = 'View Analytics';
$string['viewaudit'] = 'View Audit Log';
$string['widget_panel_desc'] = 'Spustelėkite mygtuką, norėdami nukopijuoti HTML kodą. Tada įklijuokite jį į bet kurios kurso etiketės ar puslapio HTML redaktorių.';
$string['widget_panel_title'] = 'Valdikliai (įterpti parduotuvę ar kategorijas)';
$string['widgeterror'] = 'Apdovanojimas nepasiekiamas.';
$string['widgetunlocked'] = 'Atrakinta!';
$string['widgetunlockeddesc'] = 'Dabar galite naudoti savo apdovanojimą.';
$string['xpstore:manage'] = 'Tvarkyti XP Store';
