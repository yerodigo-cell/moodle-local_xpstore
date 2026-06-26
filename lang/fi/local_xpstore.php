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

$string['action'] = 'Toiminto';
$string['activity'] = 'Aktiviteetti';
$string['activitydeleted'] = 'Aktiviteetti / Aineisto poistettu';
$string['add'] = 'Lisää';
$string['addproduct'] = 'Lisää uusi tuote';
$string['audit'] = 'XP lunastukset';
$string['balance'] = 'SALDO';
$string['cancel'] = 'Peruuta';
$string['canjear'] = 'Lunasta';
$string['catalog'] = 'Valitse palkintosi';
$string['category'] = 'Kaupan kategoria';
$string['category_placeholder'] = 'Esim: VIP...';
$string['category_short'] = 'Kat:';
$string['categoryicons'] = 'Kategoriaikonit';
$string['chooseactivity'] = 'Valitse aktiviteetti';
$string['choosetype'] = 'Valitse tyyppi';
$string['colaction'] = 'Toiminto';
$string['colactivity'] = 'Linkitetty aktiviteetti';
$string['colcategory'] = 'Kaupan kategoria';
$string['colcost'] = 'Hinta';
$string['collabel'] = 'Tunniste';
$string['color_cat_icon'] = 'Kat. ikonien väri';
$string['colorconfig'] = 'Visuaalinen mukautus';
$string['colorsreset'] = 'Värit on palautettu oletuksiin onnistuneesti.';
$string['colorssaved'] = 'Värit päivitetty onnistuneesti.';
$string['coltype'] = 'Tyyppi';
$string['configtitle'] = 'XP Storen asetukset';
$string['configure'] = 'Määritä';
$string['confirmdelete'] = 'Haluatko varmasti poistaa tämän tuotteen?';
$string['confirmdeleteall'] = 'Oletko varma, että haluat poistaa KAIKKI palkinnot? Tätä toimintoa ei voi peruuttaa.';
$string['confirmreset'] = 'Oletko varma, että haluat tyhjentää koko kurssin historian?';
$string['confirmresetcolors'] = 'Oletko varma, että haluat palauttaa kaupan alkuperäiset värit?';
$string['congratulations'] = 'Onnittelut!';
$string['copyalert'] = 'Koodi kopioitu leikepöydälle! Mene nyt mihin tahansa kurssin nimikkeeseen tai sivulle, avaa HTML-editori (</>) ja liitä se.';
$string['copysinglecard'] = 'Kopioi yksittäinen kortti';
$string['cost'] = 'Hinta';
$string['currentcatalog'] = 'Nykyinen luettelo';
$string['date'] = 'Päivämäärä';
$string['defaultcategory'] = 'Pääluettelo';
$string['delete'] = 'Poista';
$string['deleteall'] = 'Poista kaikki';
$string['deletedall'] = 'Kaikki palkinnot on poistettu luettelosta.';
$string['edit'] = 'Muokkaa';
$string['error'] = 'Virhe lunastuksen käsittelyssä';
$string['exito'] = 'Lunastus onnistui!';
$string['full_store'] = 'Koko kauppa';
$string['global_settings_info'] = 'XP Store on asennettu onnistuneesti! Tämä lisäosa ei vaadi yleisiä asetuksia. Määritä kauppa, värit ja tuotteet menemällä minkä tahansa kurssin pääsivulle ja napsauttamalla "XP Storen asetukset" -painiketta.';
$string['gotoactivity'] = 'Siirry aktiviteettiin';
$string['gotogradebook'] = 'Siirry arviointikirjaan';
$string['gradepoints'] = 'Bonus (+)';
$string['hide_menu_tooltip'] = 'Piilota kauppa kurssivalikosta';
$string['history'] = 'Ostohistoria';
$string['history_button'] = 'Historiapainike';
$string['icon_award'] = 'Palkintosaumake';
$string['icon_bolt'] = 'Salama';
$string['icon_book'] = 'Kirja';
$string['icon_camera'] = 'Kamera';
$string['icon_cart'] = 'Ostoskärry';
$string['icon_diamond'] = 'Timantti';
$string['icon_gamepad'] = 'Peliohjain';
$string['icon_gift'] = 'Lahja';
$string['icon_globe'] = 'Maapallo';
$string['icon_graduation'] = 'Valmistuminen';
$string['icon_heart'] = 'Sydän';
$string['icon_magic'] = 'Taikuus';
$string['icon_medal'] = 'Mitali';
$string['icon_money'] = 'Kolikot';
$string['icon_music'] = 'Musiikki';
$string['icon_puzzle'] = 'Palapeli';
$string['icon_rocket'] = 'Raketti';
$string['icon_shield'] = 'Kilpi';
$string['icon_star'] = 'Tähti';
$string['icon_ticket'] = 'Lippu';
$string['icon_trophy'] = 'Pokaali';
$string['iconcolor'] = 'Ikonin väri';
$string['icons_saved'] = 'Ikonit tallennettu.';
$string['insuficiente'] = 'Riittämättömät pisteet';
$string['isactive'] = 'Aktiivinen';
$string['label'] = 'Tunniste';
$string['label_help'] = 'Jos valitset tyypin <b>Erityinen</b>, ryhmä, jolla on tämä tunniste, luodaan automaattisesti (tai käytetään uudelleen, jos se on jo olemassa), ja valittu aktiviteetti rajoitetaan siihen.';
$string['limit'] = 'Raja';
$string['limitreached'] = 'Olet saavuttanut tämän tuotteen enimmäisostorajan.';
$string['limitzero'] = '0 = ∞ (Rajoittamaton)';
$string['linkedactivity'] = 'Linkitetty aktiviteetti';
$string['menuhidden'] = 'Valikko: Piilotettu';
$string['menuvisibility_help'] = 'Ottaa käyttöön tai poistaa käytöstä kaupan suoran pääsyn linkin opiskelijoiden kurssivalikossa. Suosittelemme pitämään sen piilotettuna ja käyttämään sen sijaan kauppawidgettejä, sillä ne tarjoavat mukaansatempaavamman kokemuksen (mukaan lukien ääniefektit ja animaatiot palkintoja ostettaessa).';
$string['menuvisible'] = 'Valikko: Näkyvissä';
$string['nopurchases'] = 'Tällä kurssilla ei ole vielä kirjattu yhtään ostoa tai lunastusta.';
$string['norewardscreated'] = 'Palkintoja ei ole vielä luotu.';
$string['pluginname'] = 'XP Store';
$string['points'] = 'Pisteet';
$string['primarycolor'] = 'Pääväri (Kortit ja ikonit)';
$string['privacy:metadata:local_xpstore_gastos'] = 'Tallentaa tietoja käyttäjien lunastuksista XP Storessa.';
$string['privacy:metadata:local_xpstore_gastos:amount'] = 'Tuotteen hinta XP:nä.';
$string['privacy:metadata:local_xpstore_gastos:itemid'] = 'Sen kurssimoduulin tunnus, johon lunastus on sovellettu.';
$string['privacy:metadata:local_xpstore_gastos:itemtype'] = 'Lunastetun tuotteen tyyppi.';
$string['privacy:metadata:local_xpstore_gastos:timecreated'] = 'Aika, jolloin lunastus tapahtui.';
$string['privacy:metadata:local_xpstore_gastos:userid'] = 'Käyttäjä, joka suoritti lunastuksen.';
$string['productadded'] = 'Tuote lisätty onnistuneesti';
$string['productdeleted'] = 'Tuote poistettu';
$string['productupdated'] = 'Palkinto päivitetty onnistuneesti.';
$string['purchases'] = 'Ostokset';
$string['redemptions'] = 'lunastukset';
$string['redemptions_count'] = 'Lunastukset:';
$string['reportsubtitle'] = 'XP-etujen yksilöllinen seuranta';
$string['reporttitle'] = 'Lunastusraportti';
$string['resetcolors'] = 'Palauta oletukset';
$string['resetcycle'] = 'Nollaa sykli';
$string['resethistory'] = 'Tyhjennä lunastushistoria';
$string['saldo'] = 'Nykyinen saldosi';
$string['savecolors'] = 'Tallenna värit';
$string['saveicons'] = 'Tallenna ikonit';
$string['secondarycolor'] = 'Toissijainen väri (Liukuvärit)';
$string['show_menu_tooltip'] = 'Näytä kauppa kurssivalikossa';
$string['showinmenu'] = 'Näytä kurssivalikossa';
$string['showinmenu_desc'] = 'Jos valittu, opiskelijat näkevät pikakuvakkeen kauppaan navigointivalikossa.';
$string['soldout'] = 'Loppuunmyyty';
$string['specialcontent'] = 'Erityinen';
$string['storeempty_desc'] = 'Palkintoja ei ole vielä määritetty.';
$string['storeempty_title'] = 'Kauppa on tyhjä';
$string['storetitle'] = 'XP Store';
$string['str_tabproducts'] = 'Tuotteet';
$string['str_tabsettings'] = 'Asetukset';
$string['success_unlock_gradebook'] = 'Sait palkinnon <b>{$a->reward}</b> aktiviteetista <b>{$a->activity}</b>!';
$string['success_unlock_reward'] = 'Sait palkinnon <b>{$a->reward}</b> aktiviteetista <b>{$a->activity}</b>!';
$string['tiendaxp'] = 'XP Store';
$string['type'] = 'Tyyppi';
$string['type_a'] = '24h pidennys';
$string['type_f'] = 'Foorumi (Klassinen)';
$string['type_g'] = 'Bonus';
$string['type_help'] = 'Valitse edun tyyppi:<br><br><b>Ylimääräinen yritys:</b> Toimii tenttien kanssa.<br><b>24h pidennys:</b> Toimii vain tehtävien kanssa.<br><b>Foorumi (Klassinen):</b> Sallii julkaisemisen sulkemispäivän jälkeen.<br><b>Bonus:</b> Lisää lisäpisteitä suoraan arviointikirjaan.<br><b>Erityinen:</b> Avaa piilotetun sisällön tai VIP-ryhmät.';
$string['type_q'] = 'Ylimääräinen yritys';
$string['type_s'] = 'Erityinen';
$string['update'] = 'Päivitä';
$string['user'] = 'Käyttäjä';
$string['widget_panel_desc'] = 'Napsauta painiketta kopioidaksesi HTML-koodin. Liitä se sitten minkä tahansa kurssin nimikkeen tai sivun HTML-editoriin.';
$string['widget_panel_title'] = 'Widgetit (upota kauppa tai kategoriat)';
$string['widgeterror'] = 'Palkinto ei ole saatavilla.';
$string['widgetunlocked'] = 'Avattu!';
$string['widgetunlockeddesc'] = 'Voit nyt käyttää palkintoasi.';
$string['xpstore:manage'] = 'Hallitse XP Storea';

$string['searchfilters'] = 'Hakusuodattimet';
$string['searchfilters_help'] = 'Voit suodattaa raportin kahdella kriteerillä:<br><br><b>Aktiviteetti:</b> Hakee Moodle-aktiviteetin nimen tai palkinnolle määritetyn mukautetun tunnisteen (label) perusteella.<br><b>Tyyppi:</b> Hakee palkinnon tyypin perusteella (esim. Erikois, Bonus jne.).';
$string['searchactivity'] = 'Hae (Aktiviteetti)...';
$string['searchtype'] = 'Hae (Tyyppi)...';
