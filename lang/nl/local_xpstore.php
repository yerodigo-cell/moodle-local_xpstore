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

$string['action'] = 'Actie';
$string['activity'] = 'Activiteit';
$string['activitydeleted'] = 'Activiteit / Bron verwijderd';
$string['add'] = 'Toevoegen';
$string['addproduct'] = 'Nieuw Product Toevoegen';
$string['analytics'] = 'Analyses';
$string['analyticssubtitle'] = 'Winkelprestaties en gebruiksstatistieken';
$string['audit'] = 'XP Inwisselingen';
$string['balance'] = 'SALDO';
$string['cancel'] = 'Annuleren';
$string['canjear'] = 'Inwisselen';
$string['catalog'] = 'Kies je Beloningen';
$string['category'] = 'Winkel Categorie';
$string['category_placeholder'] = 'Bijv: VIP...';
$string['category_short'] = 'Cat:';
$string['categoryicons'] = 'Categorie-iconen';
$string['chooseactivity'] = 'Selecteer Activiteit';
$string['chooserequirement'] = 'Kies vereiste';
$string['choosetype'] = 'Selecteer Type';
$string['colaction'] = 'Actie';
$string['colactivity'] = 'Gekoppelde Activiteit';
$string['colcategory'] = 'Winkel Categorie';
$string['colcost'] = 'Kosten';
$string['collabel'] = 'Label';
$string['color_cat_icon'] = 'Kleur Cat. Iconen';
$string['colorconfig'] = 'Visuele Aanpassing';
$string['colorsreset'] = 'Kleuren zijn succesvol teruggezet naar de standaardwaarden.';
$string['colorssaved'] = 'Kleuren zijn succesvol bijgewerkt.';
$string['coltype'] = 'Type';
$string['configtitle'] = 'XP Store Configuratie';
$string['configure'] = 'Configureren';
$string['confirmdelete'] = 'Weet je zeker dat je dit product wilt verwijderen?';
$string['confirmdeleteall'] = 'Weet je zeker dat je ALLE beloningen wilt verwijderen? Deze actie kan niet ongedaan worden gemaakt.';
$string['confirmreset'] = 'Weet je zeker dat je de hele geschiedenis voor deze cursus wilt wissen?';
$string['confirmresetcolors'] = 'Weet je zeker dat je de winkel wilt terugzetten naar de oorspronkelijke kleuren?';
$string['congratulations'] = 'Gefeliciteerd!';
$string['copyalert'] = 'Code gekopieerd naar het klembord! Ga nu naar een willekeurig Label of Pagina in je cursus, open de HTML-editor (</>) en plak het.';
$string['copysinglecard'] = 'Kopieer Enkele Kaart';
$string['cost'] = 'Kosten';
$string['currentcatalog'] = 'Huidige Catalogus';
$string['date'] = 'Datum';
$string['defaultcategory'] = 'Hoofdcatalogus';
$string['delete'] = 'Verwijderen';
$string['deleteall'] = 'Alles Verwijderen';
$string['deletedall'] = 'Alle beloningen zijn uit de catalogus verwijderd.';
$string['edit'] = 'Bewerken';
$string['engagementrate'] = 'Betrokkenheidspercentage';
$string['error'] = 'Fout bij verwerken van de inwisseling';
$string['exito'] = 'Inwisselen succesvol!';
$string['full_store'] = 'Volledige Winkel';
$string['global_settings_info'] = 'XP Store is succesvol geïnstalleerd! Deze plugin vereist geen globale configuratie. Om de winkel, kleuren en producten te configureren, ga je naar de hoofdpagina van een cursus en klik je op de knop "XP Store Configuratie".';
$string['gotoactivity'] = 'Ga naar Activiteit';
$string['gotogradebook'] = 'Ga naar Cijferlijst';
$string['gradepoints'] = 'Bonus (+)';
$string['hide_menu_tooltip'] = 'Verberg winkel in cursusmenu';
$string['history'] = 'Aankoopgeschiedenis';
$string['history_button'] = 'Geschiedenis Knop';
$string['icon_award'] = 'Award Lint';
$string['icon_bolt'] = 'Bliksem';
$string['icon_book'] = 'Boek';
$string['icon_camera'] = 'Camera';
$string['icon_cart'] = 'Winkelwagen';
$string['icon_diamond'] = 'Diamant';
$string['icon_gamepad'] = 'Gamepad';
$string['icon_gift'] = 'Cadeau';
$string['icon_globe'] = 'Wereldbol';
$string['icon_graduation'] = 'Afstuderen';
$string['icon_heart'] = 'Hart';
$string['icon_magic'] = 'Magie';
$string['icon_medal'] = 'Medaille';
$string['icon_money'] = 'Munten';
$string['icon_music'] = 'Muziek';
$string['icon_puzzle'] = 'Puzzel';
$string['icon_rocket'] = 'Raket';
$string['icon_shield'] = 'Schild';
$string['icon_star'] = 'Ster';
$string['icon_ticket'] = 'Ticket';
$string['icon_trophy'] = 'Trofee';
$string['iconcolor'] = 'Icoonkleur';
$string['icons_saved'] = 'Iconen opgeslagen.';
$string['insuficiente'] = 'Onvoldoende punten';
$string['isactive'] = 'Actief';
$string['label'] = 'Label';
$string['label_help'] = 'Als je het type <b>Speciaal</b> selecteert, wordt er automatisch een groep met dit label aangemaakt (of hergebruikt indien deze al bestaat) en wordt de geselecteerde activiteit tot deze groep beperkt.';
$string['limit'] = 'Limiet';
$string['limitreached'] = 'Je hebt de maximale aankooplimiet voor dit item bereikt.';
$string['limitzero'] = '0 = ∞ (Oneindig)';
$string['linkedactivity'] = 'Gekoppelde Activiteit';
$string['manualgradeitem'] = '[GRADE ITEM] ';
$string['menuhidden'] = 'Menu: Verborgen';
$string['menuvisibility_help'] = 'Schakelt de directe toegangslink naar de winkel in het cursusnavigatiemenu voor studenten in of uit. We raden aan om deze verborgen te houden en in plaats daarvan Winkel Widgets te gebruiken, omdat deze een meer meeslepende ervaring bieden (inclusief geluidseffecten en animaties bij het kopen van beloningen).';
$string['menuvisible'] = 'Menu: Zichtbaar';
$string['mockup_sampleitem'] = 'Voorbeelditem';
$string['mockup_shortdesc'] = 'Korte beschrijving';
$string['mockup_storebanner'] = 'Winkelbanner';
$string['mockup_storecard'] = 'Winkelkaart';
$string['mockup_widget'] = 'Individuele Widget';
$string['nopurchases'] = 'Er zijn nog geen aankopen of inwisselingen geregistreerd in deze cursus.';
$string['norewardscreated'] = 'Er zijn nog geen beloningen aangemaakt.';
$string['pluginname'] = 'XP Store';
$string['points'] = 'Punten';
$string['predefinedpalettes'] = 'Vooraf gedefinieerde paletten';
$string['preview'] = 'Voorbeeld';
$string['primarycolor'] = 'Primaire Kleur';
$string['productadded'] = 'Product succesvol toegevoegd';
$string['productdeleted'] = 'Product verwijderd';
$string['products'] = 'Producten';
$string['productupdated'] = 'Beloning succesvol bijgewerkt.';
$string['purchases'] = 'Aankopen';
$string['redemptions'] = 'inwisselingen';
$string['redemptions_count'] = 'Inwisselingen:';
$string['remainingbalance'] = 'Saldo: {$a} XP';
$string['reportsubtitle'] = 'Individuele opvolging van XP voordelen';
$string['reporttitle'] = 'Inwisselingsrapport';
$string['requirement'] = 'Vereiste';
$string['requirement_help'] = 'Selecteer een activiteit die de student moet voltooien voordat hij deze beloning kan inwisselen. De beloning blijft vergrendeld totdat de activiteit aan de voltooiingsvoorwaarden voldoet.';
$string['requires'] = 'moet voltooid zijn';
$string['requires_short'] = 'Vereist';
$string['resetcolors'] = 'Terugzetten naar standaard';
$string['resetcycle'] = 'Cyclus resetten';
$string['resethistory'] = 'Inwisselgeschiedenis wissen';
$string['saldo'] = 'Je huidige saldo';
$string['savecolors'] = 'Kleuren Opslaan';
$string['saveicons'] = 'Iconen opslaan';
$string['searchactivity'] = 'Zoeken (Activiteit)...';
$string['searchfilters'] = 'Zoekfilters';
$string['searchfilters_help'] = 'U kunt het rapport filteren met behulp van drie criteria:<br><br><b>Deelnemer:</b> Zoekt op de voor- of achternaam van de student.<br><b>Activiteit:</b> Zoekt op de naam van de Moodle-activiteit of het aangepaste label dat aan de beloning is toegewezen.<br><b>Type:</b> Zoekt op het beloningstype (bijv. Speciaal, Bonus, enz.).';
$string['searchtype'] = 'Zoeken (Type)...';
$string['searchuser'] = 'Zoeken (Deelnemer)...';
$string['secondarycolor'] = 'Secundaire Kleur';
$string['settings'] = 'Instellingen';
$string['show_menu_tooltip'] = 'Toon winkel in cursusmenu';
$string['showinmenu'] = 'Toon in cursusmenu';
$string['showinmenu_desc'] = 'Indien aangevinkt, zien studenten een snelkoppeling naar de winkel in het navigatiemenu.';
$string['soldout'] = 'Uitverkocht';
$string['specialcontent'] = 'Speciaal';
$string['storeempty_desc'] = 'Er zijn nog geen beloningen geconfigureerd.';
$string['storeempty_title'] = 'Winkel is leeg';
$string['storetitle'] = 'XP Store';
$string['str_tabproducts'] = 'Producten';
$string['str_tabsettings'] = 'Instellingen';
$string['style'] = 'Stijl';
$string['success_unlock_gradebook'] = 'Je hebt de beloning <b>{$a->reward}</b> behaald bij de activiteit <b>{$a->activity}</b>!';
$string['success_unlock_reward'] = 'Je hebt de beloning <b>{$a->reward}</b> behaald bij de activiteit <b>{$a->activity}</b>!';
$string['tiendaxp'] = 'XP Store';
$string['topactivities'] = 'Activiteiten met de meeste aankopen';
$string['toprewardsbypurchases'] = 'Meest gekochte beloningen';
$string['toprewardsbyxp'] = 'Beloningen met de meeste uitgegeven XP';
$string['totalpurchases'] = 'Totaal aankopen';
$string['totalspent'] = 'Totaal uitgegeven: {$a} XP';
$string['totalstudents'] = 'Betrokken studenten';
$string['totalxp'] = 'Totaal XP uitgegeven';
$string['type'] = 'Type';
$string['type_a'] = '24u Verlenging';
$string['type_f'] = 'Forum (Klassiek)';
$string['type_g'] = 'Bonus';
$string['type_help'] = 'Selecteer het type voordeel:<br><br><b>Extra Poging:</b> Werkt met Quizzen.<br><b>24u Verlenging:</b> Werkt alleen met Opdrachten.<br><b>Forum (Klassiek):</b> Maakt plaatsen na de sluitingsdatum mogelijk.<br><b>Bonus:</b> Voegt direct extra punten toe aan de cijferlijst.<br><b>Speciaal:</b> Ontgrendelt verborgen inhoud of VIP-groepen.';
$string['type_q'] = 'Extra Poging';
$string['type_s'] = 'Speciaal';
$string['update'] = 'Bijwerken';
$string['user'] = 'Gebruiker';
$string['viewanalytics'] = 'Bekijk Analytics';
$string['viewaudit'] = 'Bekijk Auditlogboek';
$string['widget_panel_desc'] = 'Klik op een knop om de HTML-code te kopiëren. Plak deze vervolgens in de HTML-editor van een Label of Pagina in je cursus.';
$string['widget_panel_title'] = 'Widgets (winkel of categorieën insluiten)';
$string['widgeterror'] = 'Beloning niet beschikbaar.';
$string['widgetunlocked'] = 'Ontgrendeld!';
$string['widgetunlockeddesc'] = 'Je kunt nu je beloning gebruiken.';
