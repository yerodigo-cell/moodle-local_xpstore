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

$string['action'] = 'Action';
$string['activity'] = 'Activité';
$string['activitydeleted'] = 'Activité / Ressource supprimée';
$string['add'] = 'Ajouter';
$string['addproduct'] = 'Ajouter un nouveau produit';
$string['audit'] = 'Rachats de XP';
$string['balance'] = 'SOLDE';
$string['cancel'] = 'Annuler';
$string['canjear'] = 'Échanger';
$string['catalog'] = 'Choisissez vos récompenses';
$string['category'] = 'Catégorie de la boutique';
$string['category_placeholder'] = 'Ex : VIP...';
$string['category_short'] = 'Cat :';
$string['categoryicons'] = 'Icônes de catégorie';
$string['chooseactivity'] = 'Sélectionner l\'activité';
$string['choosetype'] = 'Sélectionner le type';
$string['colaction'] = 'Action';
$string['colactivity'] = 'Activité liée';
$string['colcategory'] = 'Catégorie de la boutique';
$string['colcost'] = 'Coût';
$string['collabel'] = 'Étiquette';
$string['color_cat_icon'] = 'Couleur des icônes de Cat.';
$string['colorconfig'] = 'Personnalisation visuelle';
$string['colorsreset'] = 'Couleurs réinitialisées par défaut avec succès.';
$string['colorssaved'] = 'Couleurs mises à jour avec succès.';
$string['coltype'] = 'Type';
$string['configtitle'] = 'Configuration de la boutique XP';
$string['configure'] = 'Configurer';
$string['confirmdelete'] = 'Voulez-vous vraiment supprimer ce produit ?';
$string['confirmdeleteall'] = 'Êtes-vous sûr de vouloir supprimer TOUTES les récompenses ? Cette action ne peut pas être annulée.';
$string['confirmreset'] = 'Êtes-vous sûr de vouloir effacer tout l\'historique de ce cours ?';
$string['confirmresetcolors'] = 'Êtes-vous sûr de vouloir réinitialiser la boutique à ses couleurs d\'origine ?';
$string['congratulations'] = 'Félicitations !';
$string['copyalert'] = 'Code copié dans le presse-papiers ! Allez maintenant à n\'importe quelle étiquette ou page de votre cours, ouvrez l\'éditeur HTML (</>) et collez-le.';
$string['copysinglecard'] = 'Copier la carte unique';
$string['cost'] = 'Coût';
$string['currentcatalog'] = 'Catalogue actuel';
$string['date'] = 'Date';
$string['defaultcategory'] = 'Catalogue principal';
$string['delete'] = 'Supprimer';
$string['deleteall'] = 'Tout supprimer';
$string['deletedall'] = 'Toutes les récompenses ont été retirées du catalogue.';
$string['edit'] = 'Modifier';
$string['error'] = 'Erreur lors du traitement de l\'échange';
$string['exito'] = 'Échange réussi !';
$string['full_store'] = 'Boutique complète';
$string['global_settings_info'] = 'La boutique XP est installée avec succès ! Ce plugin ne nécessite pas de configuration globale. Pour configurer la boutique, les couleurs et les produits, veuillez aller sur la page principale de n\'importe quel cours et cliquez sur le bouton "Configuration boutique XP".';
$string['gotoactivity'] = 'Aller à l\'activité';
$string['gotogradebook'] = 'Aller au carnet de notes';
$string['gradepoints'] = 'Bonus (+)';
$string['hide_menu_tooltip'] = 'Masquer la boutique du menu du cours';
$string['history'] = 'Historique d\'achats';
$string['history_button'] = 'Bouton historique';
$string['icon_award'] = 'Ruban de récompense';
$string['icon_bolt'] = 'Éclair';
$string['icon_book'] = 'Livre';
$string['icon_camera'] = 'Appareil photo';
$string['icon_cart'] = 'Chariot';
$string['icon_diamond'] = 'Diamant';
$string['icon_gamepad'] = 'Manette';
$string['icon_gift'] = 'Cadeau';
$string['icon_globe'] = 'Globe';
$string['icon_graduation'] = 'Diplôme';
$string['icon_heart'] = 'Cœur';
$string['icon_magic'] = 'Magie';
$string['icon_medal'] = 'Médaille';
$string['icon_money'] = 'Pièces';
$string['icon_music'] = 'Musique';
$string['icon_puzzle'] = 'Puzzle';
$string['icon_rocket'] = 'Fusée';
$string['icon_shield'] = 'Bouclier';
$string['icon_star'] = 'Étoile';
$string['icon_ticket'] = 'Billet';
$string['icon_trophy'] = 'Trophée';
$string['iconcolor'] = 'Couleur de l\'icône';
$string['icons_saved'] = 'Icônes enregistrées.';
$string['insuficiente'] = 'Points insuffisants';
$string['isactive'] = 'Actif';
$string['label'] = 'Étiquette';
$string['label_help'] = 'Si vous sélectionnez le type <b>Spécial</b>, un groupe avec cette étiquette sera automatiquement créé (ou réutilisé s\'il existe déjà) et l\'activité sélectionnée y sera restreinte.';
$string['limit'] = 'Limite';
$string['limitreached'] = 'Vous avez atteint la limite d\'achat maximale pour cet article.';
$string['limitzero'] = '0 = ∞ (Infini)';
$string['linkedactivity'] = 'Activité liée';
$string['menuhidden'] = 'Menu : Masqué';
$string['menuvisibility_help'] = 'Active ou désactive le lien d\'accès direct à la boutique dans le menu de navigation du cours pour les étudiants. Nous recommandons de le garder masqué et d\'utiliser plutôt les widgets de la boutique, car ils offrent une expérience plus immersive (incluant des effets sonores et des animations lors de l\'achat de récompenses).';
$string['menuvisible'] = 'Menu : Visible';
$string['nopurchases'] = 'Aucun achat ou échange n\'a encore été enregistré dans ce cours.';
$string['norewardscreated'] = 'Aucune récompense n\'a encore été créée.';
$string['pluginname'] = 'Boutique XP';
$string['points'] = 'Points';
$string['primarycolor'] = 'Couleur primaire (Cartes et Icônes)';
$string['privacy:metadata:local_xpstore_gastos'] = 'Stocke des informations sur les échanges des utilisateurs dans la boutique XP.';
$string['privacy:metadata:local_xpstore_gastos:amount'] = 'Le coût de l\'article en XP.';
$string['privacy:metadata:local_xpstore_gastos:itemid'] = 'L\'ID du module de cours où l\'échange a été appliqué.';
$string['privacy:metadata:local_xpstore_gastos:itemtype'] = 'Le type de l\'article échangé.';
$string['privacy:metadata:local_xpstore_gastos:timecreated'] = 'L\'heure à laquelle l\'échange a eu lieu.';
$string['privacy:metadata:local_xpstore_gastos:userid'] = 'L\'utilisateur qui a effectué l\'échange.';
$string['productadded'] = 'Produit ajouté avec succès';
$string['productdeleted'] = 'Produit supprimé';
$string['productupdated'] = 'Récompense mise à jour avec succès.';
$string['purchases'] = 'Achats';
$string['redemptions'] = 'échanges';
$string['redemptions_count'] = 'Échanges :';
$string['reportsubtitle'] = 'Suivi individuel des avantages XP';
$string['reporttitle'] = 'Rapport d\'échange';
$string['resetcolors'] = 'Réinitialiser par défaut';
$string['resetcycle'] = 'Réinitialiser le cycle';
$string['resethistory'] = 'Effacer l\'historique d\'échange';
$string['saldo'] = 'Votre solde actuel';
$string['savecolors'] = 'Enregistrer les couleurs';
$string['saveicons'] = 'Enregistrer les icônes';
$string['searchactivity'] = 'Rechercher (Activité)...';
$string['searchfilters'] = 'Filtres de recherche';
$string['searchfilters_help'] = 'Vous pouvez filtrer le rapport en utilisant deux critères:<br><br><b>Activité:</b> Recherche par le nom de l\'activité Moodle ou par l\'étiquette personnalisée attribuée à la récompense.<br><b>Type:</b> Recherche par le type de récompense (ex. Spécial, Bonus, etc.).';
$string['searchtype'] = 'Rechercher (Type)...';
$string['secondarycolor'] = 'Couleur secondaire (Dégradés)';
$string['show_menu_tooltip'] = 'Afficher la boutique dans le menu du cours';
$string['showinmenu'] = 'Afficher dans le menu du cours';
$string['showinmenu_desc'] = 'Si coché, les étudiants verront un raccourci vers la boutique dans le menu de navigation.';
$string['soldout'] = 'Épuisé';
$string['specialcontent'] = 'Spécial';
$string['storeempty_desc'] = 'Aucune récompense n\'a encore été configurée.';
$string['storeempty_title'] = 'La boutique est vide';
$string['storetitle'] = 'Boutique XP';
$string['str_tabproducts'] = 'Produits';
$string['str_tabsettings'] = 'Paramètres';
$string['success_unlock_gradebook'] = 'Vous avez obtenu la récompense <b>{$a->reward}</b> sur l\'activité <b>{$a->activity}</b> !';
$string['success_unlock_reward'] = 'Vous avez obtenu la récompense <b>{$a->reward}</b> sur l\'activité <b>{$a->activity}</b> !';
$string['tiendaxp'] = 'Boutique XP';
$string['totalspent'] = 'Total spent: {$a} XP';
$string['type'] = 'Type';
$string['type_a'] = 'Extension de 24h';
$string['type_f'] = 'Forum (Ancien)';
$string['type_g'] = 'Bonus';
$string['type_help'] = 'Sélectionnez le type d\'avantage :<br><br><b>Tentative supplémentaire :</b> Fonctionne avec les Quiz.<br><b>Extension de 24h :</b> Fonctionne uniquement avec les Devoirs.<br><b>Forum (Ancien) :</b> Permet de publier après la date limite.<br><b>Bonus :</b> Ajoute des points supplémentaires directement dans le carnet de notes.<br><b>Spécial :</b> Débloque du contenu masqué ou des groupes VIP.';
$string['type_q'] = 'Tentative supp.';
$string['type_s'] = 'Spécial';
$string['update'] = 'Mettre à jour';
$string['user'] = 'Utilisateur';
$string['widget_panel_desc'] = 'Cliquez sur un bouton pour copier le code HTML. Collez-le ensuite dans l\'éditeur HTML de n\'importe quelle étiquette ou page de votre cours.';
$string['widget_panel_title'] = 'Widgets (intégrer la boutique ou catégories)';
$string['widgeterror'] = 'Récompense non disponible.';
$string['widgetunlocked'] = 'Débloqué !';
$string['widgetunlockeddesc'] = 'Vous pouvez maintenant utiliser votre récompense.';
$string['xpstore:manage'] = 'Gérer la boutique XP';
