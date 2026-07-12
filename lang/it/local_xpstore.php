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

$string['action'] = 'Azione';
$string['activity'] = 'Attività';
$string['activitydeleted'] = 'Attività / Risorsa eliminata';
$string['add'] = 'Aggiungi';
$string['addproduct'] = 'Aggiungi Nuovo Prodotto';
$string['analytics'] = 'Analisi';
$string['analyticssubtitle'] = 'Prestazioni del negozio e metriche di utilizzo';
$string['audit'] = 'Riscatti XP';
$string['balance'] = 'SALDO';
$string['cancel'] = 'Annulla';
$string['canjear'] = 'Riscatta';
$string['catalog'] = 'Scegli le tue Ricompense';
$string['category'] = 'Categoria Negozio';
$string['category_placeholder'] = 'Es: VIP...';
$string['category_short'] = 'Cat:';
$string['categoryicons'] = 'Icone di categoria';
$string['chooseactivity'] = 'Seleziona Attività';
$string['chooserequirement'] = 'Scegli il requisito';
$string['choosetype'] = 'Seleziona Tipo';
$string['colaction'] = 'Azione';
$string['colactivity'] = 'Attività Collegata';
$string['colcategory'] = 'Categoria Negozio';
$string['colcost'] = 'Costo';
$string['collabel'] = 'Etichetta';
$string['color_cat_icon'] = 'Colore Icone Cat.';
$string['colorconfig'] = 'Personalizzazione Visiva';
$string['colorsreset'] = 'Colori ripristinati correttamente ai valori predefiniti.';
$string['colorssaved'] = 'Colori aggiornati correttamente.';
$string['coltype'] = 'Tipo';
$string['configtitle'] = 'Configurazione Negozio XP';
$string['configure'] = 'Configura';
$string['confirmdelete'] = 'Vuoi davvero eliminare questo prodotto?';
$string['confirmdeleteall'] = 'Sei sicuro di voler eliminare TUTTE le ricompense? Questa azione non può essere annullata.';
$string['confirmreset'] = 'Sei sicuro di voler cancellare tutta la cronologia per questo corso?';
$string['confirmresetcolors'] = 'Sei sicuro di voler ripristinare il negozio ai suoi colori originali?';
$string['congratulations'] = 'Congratulazioni!';
$string['copyalert'] = 'Codice copiato negli appunti! Ora vai a qualsiasi Etichetta o Pagina nel tuo corso, apri l\'editor HTML (</>) e incollalo.';
$string['copysinglecard'] = 'Copia Singola Scheda';
$string['cost'] = 'Costo';
$string['currentcatalog'] = 'Catalogo Attuale';
$string['date'] = 'Data';
$string['defaultcategory'] = 'Catalogo Principale';
$string['delete'] = 'Elimina';
$string['deleteall'] = 'Elimina Tutto';
$string['deletedall'] = 'Tutte le ricompense sono state rimosse dal catalogo.';
$string['edit'] = 'Modifica';
$string['engagementrate'] = 'Tasso di coinvolgimento';
$string['error'] = 'Errore durante l\'elaborazione del riscatto';
$string['exito'] = 'Riscatto completato!';
$string['full_store'] = 'Negozio Completo';
$string['global_settings_info'] = 'Il Negozio XP è stato installato con successo! Questo plugin non richiede una configurazione globale. Per configurare il negozio, i colori e i prodotti, vai alla pagina principale di qualsiasi corso e fai clic sul pulsante "Configurazione Negozio XP".';
$string['gotoactivity'] = 'Vai all\'Attività';
$string['gotogradebook'] = 'Vai al Registro Valutatore';
$string['gradepoints'] = 'Bonus (+)';
$string['hide_menu_tooltip'] = 'Nascondi il negozio dal menu del corso';
$string['history'] = 'Cronologia Acquisti';
$string['history_button'] = 'Pulsante Cronologia';
$string['icon_award'] = 'Nastro di Premiazione';
$string['icon_bolt'] = 'Fulmine';
$string['icon_book'] = 'Libro';
$string['icon_camera'] = 'Fotocamera';
$string['icon_cart'] = 'Carrello';
$string['icon_diamond'] = 'Diamante';
$string['icon_gamepad'] = 'Gamepad';
$string['icon_gift'] = 'Regalo';
$string['icon_globe'] = 'Globo';
$string['icon_graduation'] = 'Laurea';
$string['icon_heart'] = 'Cuore';
$string['icon_magic'] = 'Magia';
$string['icon_medal'] = 'Medaglia';
$string['icon_money'] = 'Monete';
$string['icon_music'] = 'Musica';
$string['icon_puzzle'] = 'Puzzle';
$string['icon_rocket'] = 'Razzo';
$string['icon_shield'] = 'Scudo';
$string['icon_star'] = 'Stella';
$string['icon_ticket'] = 'Biglietto';
$string['icon_trophy'] = 'Trofeo';
$string['iconcolor'] = 'Colore Icona';
$string['icons_saved'] = 'Icone salvate.';
$string['insuficiente'] = 'Punti insufficienti';
$string['isactive'] = 'Attivo';
$string['label'] = 'Etichetta';
$string['label_help'] = 'Se selezioni il tipo <b>Speciale</b>, verrà automaticamente creato un gruppo con questa etichetta (o riutilizzato se esiste già) e l\'attività selezionata sarà limitata ad esso.';
$string['limit'] = 'Limite';
$string['limitreached'] = 'Hai raggiunto il limite massimo di acquisti per questo articolo.';
$string['limitzero'] = '0 = ∞ (Infinito)';
$string['linkedactivity'] = 'Attività Collegata';
$string['menuhidden'] = 'Menu: Nascosto';
$string['menuvisibility_help'] = 'Abilita o disabilita il link di accesso diretto al negozio nel menu di navigazione del corso per gli studenti. Ti consigliamo di tenerlo nascosto e utilizzare invece i widget del negozio, in quanto offrono un\'esperienza più coinvolgente (inclusi effetti sonori e animazioni durante l\'acquisto delle ricompense).';
$string['menuvisible'] = 'Menu: Visibile';
$string['mockup_sampleitem'] = 'Articolo di prova';
$string['mockup_shortdesc'] = 'Breve descrizione';
$string['mockup_storebanner'] = 'Banner del negozio';
$string['mockup_storecard'] = 'Scheda del negozio';
$string['mockup_widget'] = 'Widget individuale';
$string['nopurchases'] = 'Nessun acquisto o riscatto è stato ancora registrato in questo corso.';
$string['norewardscreated'] = 'Nessuna ricompensa è stata ancora creata.';
$string['pluginname'] = 'Negozio XP';
$string['points'] = 'Punti';
$string['predefinedpalettes'] = 'Tavolozze predefinite';
$string['preview'] = 'Anteprima';
$string['primarycolor'] = 'Colore Primario';
$string['productadded'] = 'Prodotto aggiunto con successo';
$string['productdeleted'] = 'Prodotto eliminato';
$string['products'] = 'Prodotti';
$string['productupdated'] = 'Ricompensa aggiornata con successo.';
$string['purchases'] = 'Acquisti';
$string['redemptions'] = 'riscatti';
$string['redemptions_count'] = 'Riscatti:';
$string['remainingbalance'] = 'Saldo: {$a} XP';
$string['reportsubtitle'] = 'Monitoraggio individuale dei vantaggi XP';
$string['reporttitle'] = 'Rapporto dei Riscatti';
$string['requirement'] = 'Requisito';
$string['requirement_help'] = 'Seleziona un\'attività che lo studente deve completare prima di poter riscattare questa ricompensa. La ricompensa sarà bloccata fino al soddisfacimento delle condizioni di completamento dell\'attività.';
$string['requires'] = 'deve essere completata';
$string['requires_short'] = 'Richiede';
$string['resetcolors'] = 'Ripristina predefiniti';
$string['resetcycle'] = 'Ripristina Ciclo';
$string['resethistory'] = 'Cancella cronologia riscatti';
$string['saldo'] = 'Il tuo saldo attuale';
$string['savecolors'] = 'Salva Colori';
$string['saveicons'] = 'Salva icone';
$string['searchactivity'] = 'Cerca (Attività)...';
$string['searchfilters'] = 'Filtri di ricerca';
$string['searchfilters_help'] = 'Puoi filtrare il report utilizzando tre criteri:<br><br><b>Partecipante:</b> Cerca per nome o cognome dello studente.<br><b>Attività:</b> Cerca per il nome dell\'attività Moodle o per l\'etichetta personalizzata assegnata alla ricompensa.<br><b>Tipo:</b> Cerca per il tipo di ricompensa (es. Speciale, Bonus, ecc.).';
$string['searchtype'] = 'Cerca (Tipo)...';
$string['searchuser'] = 'Cerca (Partecipante)...';
$string['secondarycolor'] = 'Colore Secondario';
$string['settings'] = 'Impostazioni';
$string['show_menu_tooltip'] = 'Mostra il negozio nel menu del corso';
$string['showinmenu'] = 'Mostra nel menu del corso';
$string['showinmenu_desc'] = 'Se selezionato, gli studenti vedranno un collegamento rapido al negozio nel menu di navigazione.';
$string['soldout'] = 'Esaurito';
$string['specialcontent'] = 'Speciale';
$string['storeempty_desc'] = 'Nessuna ricompensa è stata ancora configurata.';
$string['storeempty_title'] = 'Il negozio è vuoto';
$string['storetitle'] = 'Negozio XP';
$string['str_tabproducts'] = 'Prodotti';
$string['str_tabsettings'] = 'Impostazioni';
$string['style'] = 'Stile';
$string['success_unlock_gradebook'] = 'Hai ottenuto la ricompensa <b>{$a->reward}</b> sull\'attività <b>{$a->activity}</b>!';
$string['success_unlock_reward'] = 'Hai ottenuto la ricompensa <b>{$a->reward}</b> sull\'attività <b>{$a->activity}</b>!';
$string['tiendaxp'] = 'Negozio XP';
$string['topactivities'] = 'Attività con più acquisti';
$string['toprewardsbypurchases'] = 'Premi più acquistati';
$string['toprewardsbyxp'] = 'Premi con più XP spesi';
$string['totalpurchases'] = 'Acquisti totali';
$string['totalspent'] = 'Totale speso: {$a} XP';
$string['totalstudents'] = 'Studenti coinvolti';
$string['totalxp'] = 'Totale XP spesi';
$string['type'] = 'Tipo';
$string['type_a'] = 'Estensione di 24h';
$string['type_f'] = 'Forum (Classico)';
$string['type_g'] = 'Bonus';
$string['type_help'] = 'Seleziona il tipo di vantaggio:<br><br><b>Tentativo Extra:</b> Funziona con i Quiz.<br><b>Estensione di 24h:</b> Funziona solo con i Compiti.<br><b>Forum (Classico):</b> Consente di pubblicare dopo la data di chiusura.<br><b>Bonus:</b> Aggiunge punti extra direttamente al registro valutatore.<br><b>Speciale:</b> Sblocca contenuti nascosti o gruppi VIP.';
$string['type_q'] = 'Tentativo Extra';
$string['type_s'] = 'Speciale';
$string['update'] = 'Aggiorna';
$string['user'] = 'Utente';
$string['viewanalytics'] = 'Visualizza analitiche';
$string['viewaudit'] = 'Visualizza registro di controllo';
$string['widget_panel_desc'] = 'Fai clic su un pulsante per copiare il codice HTML. Incollalo quindi nell\'editor HTML di qualsiasi Etichetta o Pagina nel tuo corso.';
$string['widget_panel_title'] = 'Widget (incorpora il negozio o le categorie)';
$string['widgeterror'] = 'Ricompensa non disponibile.';
$string['widgetunlocked'] = 'Sbloccato!';
$string['widgetunlockeddesc'] = 'Ora puoi utilizzare la tua ricompensa.';
