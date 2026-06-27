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

$string['action'] = 'İşlem';
$string['activity'] = 'Etkinlik';
$string['activitydeleted'] = 'Etkinlik / Kaynak silindi';
$string['add'] = 'Ekle';
$string['addproduct'] = 'Yeni Ürün Ekle';
$string['audit'] = 'XP Kullanımları';
$string['balance'] = 'BAKİYE';
$string['cancel'] = 'İptal';
$string['canjear'] = 'Kullan';
$string['catalog'] = 'Ödüllerini Seç';
$string['category'] = 'Mağaza Kategorisi';
$string['category_placeholder'] = 'Örn: VIP...';
$string['category_short'] = 'Kat:';
$string['categoryicons'] = 'Kategori İkonları';
$string['chooseactivity'] = 'Etkinlik Seç';
$string['choosetype'] = 'Tür Seç';
$string['colaction'] = 'İşlem';
$string['colactivity'] = 'Bağlı Etkinlik';
$string['colcategory'] = 'Mağaza Kategorisi';
$string['colcost'] = 'Maliyet';
$string['collabel'] = 'Etiket';
$string['color_cat_icon'] = 'Kat. İkon Rengi';
$string['colorconfig'] = 'Görsel Özelleştirme';
$string['colorsreset'] = 'Renkler başarıyla varsayılana sıfırlandı.';
$string['colorssaved'] = 'Renkler başarıyla güncellendi.';
$string['coltype'] = 'Tür';
$string['configtitle'] = 'XP Mağazası Yapılandırması';
$string['configure'] = 'Yapılandır';
$string['confirmdelete'] = 'Bu ürünü gerçekten silmek istiyor musunuz?';
$string['confirmdeleteall'] = 'TÜM ödülleri silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.';
$string['confirmreset'] = 'Bu kursun tüm geçmişini temizlemek istediğinizden emin misiniz?';
$string['confirmresetcolors'] = 'Mağazayı orijinal renklerine sıfırlamak istediğinizden emin misiniz?';
$string['congratulations'] = 'Tebrikler!';
$string['copyalert'] = 'Kod panoya kopyalandı! Şimdi kursunuzdaki herhangi bir Etikete veya Sayfaya gidin, HTML düzenleyiciyi (</>) açın ve yapıştırın.';
$string['copysinglecard'] = 'Tek Kartı Kopyala';
$string['cost'] = 'Maliyet';
$string['currentcatalog'] = 'Mevcut Katalog';
$string['date'] = 'Tarih';
$string['defaultcategory'] = 'Ana Katalog';
$string['delete'] = 'Sil';
$string['deleteall'] = 'Tümünü Sil';
$string['deletedall'] = 'Tüm ödüller katalogdan kaldırıldı.';
$string['edit'] = 'Düzenle';
$string['error'] = 'Kullanım işlenirken hata oluştu';
$string['exito'] = 'Kullanım başarılı!';
$string['full_store'] = 'Tüm Mağaza';
$string['global_settings_info'] = 'XP Mağazası başarıyla kuruldu! Bu eklenti küresel yapılandırma gerektirmez. Mağazayı, renkleri ve ürünleri yapılandırmak için herhangi bir kursun ana sayfasına gidin ve "XP Mağazası Yapılandırması" düğmesini tıklayın.';
$string['gotoactivity'] = 'Etkinliğe Git';
$string['gotogradebook'] = 'Not Defterine Git';
$string['gradepoints'] = 'Bonus (+)';
$string['hide_menu_tooltip'] = 'Mağazayı kurs menüsünden gizle';
$string['history'] = 'Satın Alma Geçmişi';
$string['history_button'] = 'Geçmiş Düğmesi';
$string['icon_award'] = 'Ödül Kurdelesi';
$string['icon_bolt'] = 'Yıldırım';
$string['icon_book'] = 'Kitap';
$string['icon_camera'] = 'Kamera';
$string['icon_cart'] = 'Sepet';
$string['icon_diamond'] = 'Elmas';
$string['icon_gamepad'] = 'Oyun Kolu';
$string['icon_gift'] = 'Hediye';
$string['icon_globe'] = 'Küre';
$string['icon_graduation'] = 'Mezuniyet';
$string['icon_heart'] = 'Kalp';
$string['icon_magic'] = 'Sihir';
$string['icon_medal'] = 'Madalya';
$string['icon_money'] = 'Paralar';
$string['icon_music'] = 'Müzik';
$string['icon_puzzle'] = 'Yapboz';
$string['icon_rocket'] = 'Roket';
$string['icon_shield'] = 'Kalkan';
$string['icon_star'] = 'Yıldız';
$string['icon_ticket'] = 'Bilet';
$string['icon_trophy'] = 'Kupa';
$string['iconcolor'] = 'İkon Rengi';
$string['icons_saved'] = 'İkonlar kaydedildi.';
$string['insuficiente'] = 'Yetersiz puan';
$string['isactive'] = 'Aktif';
$string['label'] = 'Etiket';
$string['label_help'] = '<b>Özel</b> türünü seçerseniz, bu etikete sahip bir grup otomatik olarak oluşturulur (veya zaten varsa yeniden kullanılır) ve seçilen etkinlik bu grupla sınırlandırılır.';
$string['limit'] = 'Sınır';
$string['limitreached'] = 'Bu öğe için maksimum satın alma sınırına ulaştınız.';
$string['limitzero'] = '0 = ∞ (Sınırsız)';
$string['linkedactivity'] = 'Bağlı Etkinlik';
$string['menuhidden'] = 'Menü: Gizli';
$string['menuvisibility_help'] = 'Öğrenciler için kurs gezinme menüsündeki mağaza doğrudan erişim bağlantısını etkinleştirir veya devre dışı bırakır. Ödül satın alırken ses efektleri ve animasyonlar içerdiğinden daha sürükleyici bir deneyim sağlaması nedeniyle bunu gizli tutmanızı ve bunun yerine Mağaza Widget\'larını kullanmanızı öneririz.';
$string['menuvisible'] = 'Menü: Görünür';
$string['nopurchases'] = 'Bu kursta henüz satın alma veya kullanım kaydedilmedi.';
$string['norewardscreated'] = 'Henüz ödül oluşturulmadı.';
$string['pluginname'] = 'XP Mağazası';
$string['points'] = 'Puan';
$string['primarycolor'] = 'Ana Renk (Kartlar ve İkonlar)';
$string['privacy:metadata:local_xpstore_gastos'] = 'XP Mağazasındaki kullanıcı kullanımları hakkında bilgi depolar.';
$string['privacy:metadata:local_xpstore_gastos:amount'] = 'Öğenin XP cinsinden maliyeti.';
$string['privacy:metadata:local_xpstore_gastos:itemid'] = 'Kullanımın uygulandığı kurs modülünün kimliği.';
$string['privacy:metadata:local_xpstore_gastos:itemtype'] = 'Kullanılan öğenin türü.';
$string['privacy:metadata:local_xpstore_gastos:timecreated'] = 'Kullanımın gerçekleştiği zaman.';
$string['privacy:metadata:local_xpstore_gastos:userid'] = 'Kullanımı gerçekleştiren kullanıcı.';
$string['productadded'] = 'Ürün başarıyla eklendi';
$string['productdeleted'] = 'Ürün silindi';
$string['productupdated'] = 'Ödül başarıyla güncellendi.';
$string['purchases'] = 'Satın Alınanlar';
$string['redemptions'] = 'kullanımlar';
$string['redemptions_count'] = 'Kullanımlar:';
$string['reportsubtitle'] = 'XP avantajlarının bireysel takibi';
$string['reporttitle'] = 'Kullanım Raporu';
$string['resetcolors'] = 'Varsayılana sıfırla';
$string['resetcycle'] = 'Döngüyü sıfırla';
$string['resethistory'] = 'Kullanım geçmişini temizle';
$string['saldo'] = 'Mevcut bakiyeniz';
$string['savecolors'] = 'Renkleri Kaydet';
$string['saveicons'] = 'İkonları kaydet';
$string['searchactivity'] = 'Ara (Etkinlik)...';
$string['searchfilters'] = 'Arama filtreleri';
$string['searchfilters_help'] = 'Raporu iki kritere göre filtreleyebilirsiniz:<br><br><b>Etkinlik:</b> Moodle etkinlik adıyla veya ödüle atanan özel etiketle arar.<br><b>Tür:</b> Ödül türüne göre arar (örn. Özel, Bonus, vb.).';
$string['searchtype'] = 'Ara (Tür)...';
$string['secondarycolor'] = 'İkincil Renk (Gradients)';
$string['show_menu_tooltip'] = 'Mağazayı kurs menüsünde göster';
$string['showinmenu'] = 'Kurs menüsünde göster';
$string['showinmenu_desc'] = 'İşaretlenirse, öğrenciler gezinme menüsünde mağazaya giden bir kısayol göreceklerdir.';
$string['soldout'] = 'Tükendi';
$string['specialcontent'] = 'Özel';
$string['storeempty_desc'] = 'Henüz ödül yapılandırılmadı.';
$string['storeempty_title'] = 'Mağaza boş';
$string['storetitle'] = 'XP Mağazası';
$string['str_tabproducts'] = 'Ürünler';
$string['str_tabsettings'] = 'Ayarlar';
$string['success_unlock_gradebook'] = '<b>{$a->activity}</b> etkinliğinde <b>{$a->reward}</b> ödülünü aldınız!';
$string['success_unlock_reward'] = '<b>{$a->activity}</b> etkinliğinde <b>{$a->reward}</b> ödülünü aldınız!';
$string['tiendaxp'] = 'XP Mağazası';
$string['totalspent'] = 'Total spent: {$a} XP';
$string['type'] = 'Tür';
$string['type_a'] = '24s Uzatma';
$string['type_f'] = 'Forum (Klasik)';
$string['type_g'] = 'Bonus';
$string['type_help'] = 'Avantaj türünü seçin:<br><br><b>Ekstra Deneme:</b> Sınavlar ile çalışır.<br><b>24s Uzatma:</b> Sadece Ödevlerle çalışır.<br><b>Forum (Klasik):</b> Kapanış tarihinden sonra gönderi yapılmasına izin verir.<br><b>Bonus:</b> Doğrudan not defterine ekstra puan ekler.<br><b>Özel:</b> Gizli içeriğin veya VIP gruplarının kilidini açar.';
$string['type_q'] = 'Ekstra Deneme';
$string['type_s'] = 'Özel';
$string['update'] = 'Güncelle';
$string['user'] = 'Kullanıcı';
$string['widget_panel_desc'] = 'HTML kodunu kopyalamak için bir düğmeye tıklayın. Ardından kursunuzdaki herhangi bir Etiketin veya Sayfanın HTML düzenleyicisine yapıştırın.';
$string['widget_panel_title'] = 'Widget\'lar (mağaza veya kategori ekle)';
$string['widgeterror'] = 'Ödül mevcut değil.';
$string['widgetunlocked'] = 'Kilit Açıldı!';
$string['widgetunlockeddesc'] = 'Artık ödülünüzü kullanabilirsiniz.';
$string['xpstore:manage'] = 'XP Mağazasını Yönet';
$string['mockup_sampleitem'] = 'Sample Item';
$string['mockup_shortdesc'] = 'Short description';
$string['mockup_storebanner'] = 'Store Banner';
$string['mockup_storecard'] = 'Store Card';
$string['mockup_widget'] = 'Individual Widget';
$string['products'] = 'Products';
$string['settings'] = 'Settings';
$string['analytics'] = 'Analytics';
$string['analyticssubtitle'] = 'Store performance and usage metrics';
$string['toprewardsbypurchases'] = 'Top Rewards (by purchases)';
$string['toprewardsbyxp'] = 'Top Rewards (by XP spent)';
$string['totalstudents'] = 'Engaged Students';
$string['totalxp'] = 'Total XP Spent';
$string['totalpurchases'] = 'Total Purchases';
$string['engagementrate'] = 'Engagement Rate';
$string['viewanalytics'] = 'View Analytics';
$string['viewaudit'] = 'View Audit Log';
