<?php

/**
 * Klasa odpowiedzialna za import produktów z hurtowni AXPOL
 *
 * @link       https://kemuri.codes
 * @since      1.0.1
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/includes
 */

/**
 * Klasa odpowiedzialna za import produktów z hurtowni AXPOL
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/includes
 * @author     Marcin Dymek <contact@kemuri.codes>
 */
class Kc_Hurtownie_Axpol_Importer
{
    /**
     * Ustawienia importera
     *
     * @since    1.0.1
     * @access   private
     * @var      array    $settings    Ustawienia importera
     */
    private $settings;

    /**
     * Licznik zaimportowanych produktów
     *
     * @since    1.0.1
     * @access   private
     * @var      int    $imported_count    Licznik zaimportowanych produktów
     */
    private $imported_count = 0;

    /**
     * Licznik zaktualizowanych produktów
     *
     * @since    1.0.1
     * @access   private
     * @var      int    $updated_count    Licznik zaktualizowanych produktów
     */
    private $updated_count = 0;

    /**
     * Licznik pominiętych produktów
     *
     * @since    1.0.1
     * @access   private
     * @var      int    $skipped_count    Licznik pominiętych produktów
     */
    private $skipped_count = 0;

    /**
     * Licznik błędów
     *
     * @since    1.0.1
     * @access   private
     * @var      int    $error_count    Licznik błędów
     */
    private $error_count = 0;

    /**
     * Całkowita liczba produktów
     *
     * @since    1.0.1
     * @access   private
     * @var      int    $total_count    Całkowita liczba produktów
     */
    private $total_count = 0;

    /**
     * Inicjalizacja klasy
     *
     * @since    1.0.1
     * @param    array    $settings    Ustawienia importera
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * Importuje produkty z hurtowni AXPOL
     *
     * @since    1.0.1
     * @return   array    Wyniki importu
     */
    public function import()
    {
        // Sprawdź, czy WooCommerce jest aktywny
        if (!class_exists('WooCommerce')) {
            return array(
                'success' => false,
                'message' => 'WooCommerce nie jest aktywny. Aktywuj WooCommerce, aby importować produkty.'
            );
        }

        try {
            // Pobierz dane produktów z FTP
            $xml_data = $this->get_products_data();
            if (!$xml_data) {
                return array(
                    'success' => false,
                    'message' => 'Nie udało się pobrać danych produktów z serwera FTP.'
                );
            }

            // Parsuj dane XML
            $products = $this->parse_xml_data($xml_data);
            if (!$products) {
                return array(
                    'success' => false,
                    'message' => 'Nie udało się przetworzyć danych XML.'
                );
            }

            $this->total_count = count($products);

            // Importuj produkty
            foreach ($products as $product_data) {
                $this->import_product($product_data);
            }

            // Zapisz datę ostatniego importu
            update_option('kc_hurtownie_last_import_hurtownia2', time());

            return array(
                'success' => true,
                'data' => array(
                    'total' => $this->total_count,
                    'imported' => $this->imported_count,
                    'updated' => $this->updated_count,
                    'skipped' => $this->skipped_count,
                    'errors' => $this->error_count
                )
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Wystąpił błąd podczas importu: ' . $e->getMessage()
            );
        }
    }

    /**
     * Pobiera dane produktów z FTP
     *
     * @since    1.0.1
     * @return   array|false    Dane produktów lub false w przypadku błędu
     */
    private function get_products_data()
    {
        // Sprawdź, czy mamy lokalne pliki
        $upload_dir = wp_upload_dir();
        $local_dir = $upload_dir['basedir'] . '/' . $this->settings['hurtownia1_local_path'];
        $local_file = $local_dir . '/products.xml';

        // Sprawdź, czy plik lokalny istnieje i czy jest aktualny
        if (file_exists($local_file) && filemtime($local_file) > strtotime('-1 day')) {
            // Plik istnieje i jest aktualny, użyj go
            $xml = simplexml_load_file($local_file);
            if ($xml) {
                return $this->xml_to_array($xml);
            }
        }

        // Plik nie istnieje lub jest nieaktualny, pobierz z FTP
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kc-hurtownie-ftp-helper.php';
        $ftp = new Kc_Hurtownie_Ftp_Helper(
            $this->settings['hurtownia1_ftp_host'],
            $this->settings['hurtownia1_ftp_user'],
            $this->settings['hurtownia1_ftp_pass']
        );

        // Nawiąż połączenie
        if (!$ftp->connect()) {
            error_log('Nie można nawiązać połączenia z serwerem FTP.');
            return false;
        }

        // Upewnij się, że katalog lokalny istnieje
        if (!file_exists($local_dir)) {
            wp_mkdir_p($local_dir);
        }

        // Pobierz plik
        $remote_file = $this->settings['hurtownia1_ftp_path'] . '/products.xml';
        if (!$ftp->get_file($local_file, $remote_file)) {
            error_log('Nie można pobrać pliku z serwera FTP: ' . $remote_file);
            return false;
        }

        // Zamknij połączenie
        $ftp->close();

        // Wczytaj plik
        $xml = simplexml_load_file($local_file);
        if (!$xml) {
            error_log('Nie można wczytać pliku XML: ' . $local_file);
            return false;
        }

        return $this->xml_to_array($xml);
    }

    /**
     * Pobiera dane stanów magazynowych z FTP
     *
     * @since    1.0.1
     * @return   array|false    Dane stanów magazynowych lub false w przypadku błędu
     */
    private function get_stocks_data()
    {
        // Sprawdź, czy mamy lokalne pliki
        $upload_dir = wp_upload_dir();
        $local_dir = $upload_dir['basedir'] . '/' . $this->settings['hurtownia1_local_path'];
        $local_file = $local_dir . '/stocks.xml';

        // Sprawdź, czy plik lokalny istnieje i czy jest aktualny
        if (file_exists($local_file) && filemtime($local_file) > strtotime('-1 day')) {
            // Plik istnieje i jest aktualny, użyj go
            $xml = simplexml_load_file($local_file);
            if ($xml) {
                return $this->xml_to_array($xml);
            }
        }

        // Plik nie istnieje lub jest nieaktualny, pobierz z FTP
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kc-hurtownie-ftp-helper.php';
        $ftp = new Kc_Hurtownie_Ftp_Helper(
            $this->settings['hurtownia1_ftp_host'],
            $this->settings['hurtownia1_ftp_user'],
            $this->settings['hurtownia1_ftp_pass']
        );

        // Nawiąż połączenie
        if (!$ftp->connect()) {
            error_log('Nie można nawiązać połączenia z serwerem FTP.');
            return false;
        }

        // Upewnij się, że katalog lokalny istnieje
        if (!file_exists($local_dir)) {
            wp_mkdir_p($local_dir);
        }

        // Pobierz plik
        $remote_file = $this->settings['hurtownia1_ftp_path'] . '/stocks.xml';
        if (!$ftp->get_file($local_file, $remote_file)) {
            error_log('Nie można pobrać pliku z serwera FTP: ' . $remote_file);
            return false;
        }

        // Zamknij połączenie
        $ftp->close();

        // Wczytaj plik
        $xml = simplexml_load_file($local_file);
        if (!$xml) {
            error_log('Nie można wczytać pliku XML: ' . $local_file);
            return false;
        }

        return $this->xml_to_array($xml);
    }

    /**
     * Pobiera dane zdjęć z FTP
     *
     * @since    1.0.1
     * @param    string    $product_sku    SKU produktu
     * @return   array                     Tablica z URL-ami zdjęć
     */
    private function get_product_images($product_sku)
    {
        // Sprawdź, czy mamy lokalne pliki
        $upload_dir = wp_upload_dir();
        $local_dir = $upload_dir['basedir'] . '/' . $this->settings['hurtownia1_local_path'] . '/images';

        // Upewnij się, że katalog lokalny istnieje
        if (!file_exists($local_dir)) {
            wp_mkdir_p($local_dir);
        }

        // Sprawdź, czy zdjęcia już istnieją lokalnie
        $local_images = glob($local_dir . '/' . $product_sku . '_*.jpg');
        if (!empty($local_images)) {
            $images = array();
            foreach ($local_images as $image) {
                $images[] = $upload_dir['baseurl'] . '/' . $this->settings['hurtownia1_local_path'] . '/images/' . basename($image);
            }
            return $images;
        }

        // Zdjęcia nie istnieją lokalnie, pobierz z FTP
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kc-hurtownie-ftp-helper.php';
        $ftp = new Kc_Hurtownie_Ftp_Helper(
            $this->settings['hurtownia1_ftp_images_host'],
            $this->settings['hurtownia1_ftp_images_user'],
            $this->settings['hurtownia1_ftp_images_pass']
        );

        // Nawiąż połączenie
        if (!$ftp->connect()) {
            error_log('Nie można nawiązać połączenia z serwerem FTP zdjęć.');
            return array();
        }

        // Pobierz listę plików
        $remote_dir = $this->settings['hurtownia1_ftp_images_path'];
        $files = $ftp->list_files($remote_dir);
        if (!$files) {
            $ftp->close();
            return array();
        }

        // Filtruj pliki pasujące do SKU
        $product_images = array();
        foreach ($files as $file) {
            if (strpos($file, $product_sku . '_') === 0 && strpos($file, '.jpg') !== false) {
                $remote_file = $remote_dir . '/' . $file;
                $local_file = $local_dir . '/' . $file;

                // Pobierz plik
                if ($ftp->get_file($local_file, $remote_file)) {
                    $product_images[] = $upload_dir['baseurl'] . '/' . $this->settings['hurtownia1_local_path'] . '/images/' . $file;
                }
            }
        }

        // Zamknij połączenie
        $ftp->close();

        return $product_images;
    }

    /**
     * Parsuje dane XML
     *
     * @since    1.0.1
     * @param    string    $xml_data    Dane XML
     * @return   array|false    Tablica produktów lub false w przypadku błędu
     */
    private function parse_xml_data($xml_data)
    {
        // Załaduj dane XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_data);
        if (!$xml) {
            $this->error_count++;
            return false;
        }

        $products = array();

        // Przetwórz dane produktów
        foreach ($xml->Row as $row) {
            $product = array(
                'sku' => (string) $row->CodeERP,
                'name' => (string) $row->TitlePL,
                'description' => (string) $row->DescriptionPL,
                'regular_price' => (string) $row->NetPricePLN,
                'catalog_price' => (string) $row->CatalogPricePLN,
                'categories' => array(
                    'main' => (string) $row->MainCategoryPL,
                    'sub' => (string) $row->SubCategoryPL
                ),
                'attributes' => array(
                    'material' => (string) $row->MaterialPL,
                    'dimensions' => (string) $row->Dimensions,
                    'color' => (string) $row->ColorPL,
                    'weight' => (string) $row->ItemWeightG,
                    'country_of_origin' => (string) $row->CountryOfOrigin,
                    'custom_code' => (string) $row->CustomCode,
                    'ean' => (string) $row->EAN
                ),
                'images' => array()
            );

            // Dodaj zdjęcia produktu
            for ($i = 1; $i <= 20; $i++) {
                $foto_field = 'Foto' . str_pad($i, 2, '0', STR_PAD_LEFT);
                if (!empty($row->$foto_field)) {
                    $product['images'][] = (string) $row->$foto_field;
                }
            }

            $products[] = $product;
        }

        return $products;
    }

    /**
     * Importuje pojedynczy produkt
     *
     * @since    1.0.1
     * @param    array    $product_data    Dane produktu
     */
    private function import_product($product_data)
    {
        // Sprawdź, czy produkt już istnieje
        $existing_product_id = wc_get_product_id_by_sku($product_data['sku']);

        if ($existing_product_id) {
            // Aktualizuj istniejący produkt
            if (isset($this->settings['update_existing']) && $this->settings['update_existing']) {
                $this->update_product($existing_product_id, $product_data);
            } else {
                $this->skipped_count++;
            }
        } else {
            // Utwórz nowy produkt
            $this->create_product($product_data);
        }
    }

    /**
     * Tworzy nowy produkt
     *
     * @since    1.0.1
     * @param    array    $product_data    Dane produktu
     */
    private function create_product($product_data)
    {
        try {
            // Utwórz nowy produkt
            $product = new WC_Product_Simple();
            $product->set_name($product_data['name']);
            $product->set_description($product_data['description']);
            $product->set_sku($product_data['sku']);
            $product->set_regular_price($product_data['regular_price']);
            $product->set_status('publish');
            $product->set_catalog_visibility('visible');
            $product->set_sold_individually(false);
            $product->set_backorders('no');
            $product->set_reviews_allowed(true);
            $product->set_manage_stock(true);
            $product->set_stock_quantity(10); // Domyślna ilość
            $product->set_stock_status('instock');

            // Dodaj atrybuty
            $attributes = array();
            foreach ($product_data['attributes'] as $name => $value) {
                if (!empty($value)) {
                    $attribute = new WC_Product_Attribute();
                    $attribute->set_name(ucfirst($name));
                    $attribute->set_options(array($value));
                    $attribute->set_visible(true);
                    $attributes[] = $attribute;
                }
            }
            $product->set_attributes($attributes);

            // Zapisz produkt
            $product_id = $product->save();

            // Dodaj kategorie
            if (isset($this->settings['import_categories']) && $this->settings['import_categories']) {
                $this->add_product_categories($product_id, $product_data['categories']);
            }

            // Dodaj zdjęcia
            if (isset($this->settings['import_images']) && $this->settings['import_images']) {
                $this->import_product_images($product_id, $product_data['images']);
            }

            $this->imported_count++;
        } catch (Exception $e) {
            $this->error_count++;
        }
    }

    /**
     * Aktualizuje istniejący produkt
     *
     * @since    1.0.1
     * @param    int      $product_id      ID produktu
     * @param    array    $product_data    Dane produktu
     */
    private function update_product($product_id, $product_data)
    {
        try {
            // Pobierz istniejący produkt
            $product = wc_get_product($product_id);
            if (!$product) {
                $this->error_count++;
                return;
            }

            // Aktualizuj dane produktu
            $product->set_name($product_data['name']);
            $product->set_description($product_data['description']);
            $product->set_regular_price($product_data['regular_price']);

            // Aktualizuj atrybuty
            $attributes = array();
            foreach ($product_data['attributes'] as $name => $value) {
                if (!empty($value)) {
                    $attribute = new WC_Product_Attribute();
                    $attribute->set_name(ucfirst($name));
                    $attribute->set_options(array($value));
                    $attribute->set_visible(true);
                    $attributes[] = $attribute;
                }
            }
            $product->set_attributes($attributes);

            // Zapisz produkt
            $product->save();

            // Aktualizuj kategorie
            if (isset($this->settings['import_categories']) && $this->settings['import_categories']) {
                $this->add_product_categories($product_id, $product_data['categories']);
            }

            // Aktualizuj zdjęcia
            if (isset($this->settings['import_images']) && $this->settings['import_images']) {
                $this->import_product_images($product_id, $product_data['images']);
            }

            $this->updated_count++;
        } catch (Exception $e) {
            $this->error_count++;
        }
    }

    /**
     * Dodaje kategorie do produktu
     *
     * @since    1.0.1
     * @param    int      $product_id    ID produktu
     * @param    array    $categories    Kategorie produktu
     */
    private function add_product_categories($product_id, $categories)
    {
        // Dodaj główną kategorię
        if (!empty($categories['main'])) {
            $main_term = term_exists($categories['main'], 'product_cat');
            if (!$main_term) {
                $main_term = wp_insert_term($categories['main'], 'product_cat');
            }

            if (!is_wp_error($main_term)) {
                $main_term_id = $main_term['term_id'];

                // Dodaj podkategorię
                if (!empty($categories['sub'])) {
                    $sub_term = term_exists($categories['sub'], 'product_cat', $main_term_id);
                    if (!$sub_term) {
                        $sub_term = wp_insert_term($categories['sub'], 'product_cat', array('parent' => $main_term_id));
                    }

                    if (!is_wp_error($sub_term)) {
                        wp_set_object_terms($product_id, $sub_term['term_id'], 'product_cat', true);
                    } else {
                        wp_set_object_terms($product_id, $main_term_id, 'product_cat', true);
                    }
                } else {
                    wp_set_object_terms($product_id, $main_term_id, 'product_cat', true);
                }
            }
        }
    }

    /**
     * Pobiera zdjęcia produktu z serwera FTP i dodaje je do produktu
     *
     * @since    1.0.1
     * @param    int      $product_id    ID produktu
     * @param    array    $images        Tablica z nazwami plików zdjęć
     */
    private function import_product_images($product_id, $images)
    {
        if (empty($images)) {
            return;
        }

        // Dane dostępowe do FTP ze zdjęciami
        $ftp_host = isset($this->settings['hurtownia2_ftp_images_host']) ? $this->settings['hurtownia2_ftp_images_host'] : 'ftp2.axpol.com.pl';
        $ftp_user = isset($this->settings['hurtownia2_ftp_images_user']) ? $this->settings['hurtownia2_ftp_images_user'] : 'userPL017img';
        $ftp_pass = isset($this->settings['hurtownia2_ftp_images_pass']) ? $this->settings['hurtownia2_ftp_images_pass'] : 'vSocD2N8';
        $ftp_images_path = isset($this->settings['hurtownia2_ftp_images_path']) ? $this->settings['hurtownia2_ftp_images_path'] : '/file/d/00_VOYAGER_HR/';

        // Sprawdź, czy funkcje FTP są dostępne
        if (!function_exists('ftp_connect')) {
            error_log('Funkcje FTP nie są dostępne na serwerze. Zainstaluj rozszerzenie FTP dla PHP.');
            return;
        }

        // Połączenie z serwerem FTP
        $conn_id = @ftp_connect($ftp_host);
        if (!$conn_id) {
            error_log("Nie można połączyć się z serwerem FTP: $ftp_host");
            return;
        }

        // Logowanie do serwera FTP
        $login_result = @ftp_login($conn_id, $ftp_user, $ftp_pass);
        if (!$login_result) {
            error_log("Nie można zalogować się do serwera FTP. Sprawdź nazwę użytkownika i hasło.");
            ftp_close($conn_id);
            return;
        }

        // Włącz tryb pasywny
        ftp_pasv($conn_id, true);

        // Sprawdź, czy katalog istnieje
        $current_dir = @ftp_pwd($conn_id);
        if ($current_dir === false) {
            error_log("Nie można pobrać bieżącego katalogu na serwerze FTP.");
            ftp_close($conn_id);
            return;
        }

        // Spróbuj zmienić katalog, aby sprawdzić, czy ścieżka jest poprawna
        $change_dir = @ftp_chdir($conn_id, $ftp_images_path);
        if (!$change_dir) {
            error_log("Nie można zmienić katalogu na serwerze FTP: $ftp_images_path");
            ftp_close($conn_id);
            return;
        }

        // Wróć do katalogu głównego
        @ftp_chdir($conn_id, $current_dir);

        $image_ids = array();

        // Pobierz i dodaj zdjęcia
        foreach ($images as $index => $image_name) {
            if (empty($image_name)) {
                continue;
            }

            // Ścieżka do zdjęcia na serwerze FTP
            $remote_file = $ftp_images_path . $image_name;

            // Tymczasowy plik do zapisania zdjęcia
            $temp_file = wp_tempnam($image_name);

            // Pobierz zdjęcie
            $get_result = @ftp_get($conn_id, $temp_file, $remote_file, FTP_BINARY);
            if (!$get_result) {
                error_log("Nie można pobrać pliku z serwera FTP: $remote_file");
                continue;
            }

            // Dodaj zdjęcie do biblioteki mediów
            $attachment_id = $this->add_image_to_media_library($temp_file, $image_name, $product_id);
            if ($attachment_id) {
                $image_ids[] = $attachment_id;
            } else {
                error_log("Nie można dodać zdjęcia do biblioteki mediów: $image_name");
            }

            // Usuń tymczasowy plik
            @unlink($temp_file);
        }

        // Zamknij połączenie FTP
        ftp_close($conn_id);

        // Ustaw zdjęcia produktu
        if (!empty($image_ids)) {
            // Ustaw zdjęcie główne
            set_post_thumbnail($product_id, $image_ids[0]);

            // Ustaw galerię zdjęć
            if (count($image_ids) > 1) {
                update_post_meta($product_id, '_product_image_gallery', implode(',', array_slice($image_ids, 1)));
            }
        }
    }

    /**
     * Dodaje zdjęcie do biblioteki mediów
     *
     * @since    1.0.1
     * @param    string    $file_path     Ścieżka do pliku
     * @param    string    $file_name     Nazwa pliku
     * @param    int       $product_id    ID produktu
     * @return   int|false                ID załącznika lub false w przypadku błędu
     */
    private function add_image_to_media_library($file_path, $file_name, $product_id)
    {
        // Sprawdź, czy plik istnieje
        if (!file_exists($file_path)) {
            return false;
        }

        // Pobierz typ pliku
        $file_type = wp_check_filetype(basename($file_name), null);
        if (!$file_type['type']) {
            return false;
        }

        // Przygotuj dane załącznika
        $attachment = array(
            'post_mime_type' => $file_type['type'],
            'post_title' => sanitize_file_name(pathinfo($file_name, PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        // Wstaw załącznik do bazy danych
        $attachment_id = wp_insert_attachment($attachment, $file_path, $product_id);
        if (!$attachment_id) {
            return false;
        }

        // Wygeneruj metadane dla załącznika
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        return $attachment_id;
    }

    /**
     * Konwertuje obiekt SimpleXML na tablicę
     *
     * @since    1.0.1
     * @param    SimpleXMLElement    $xml    Obiekt SimpleXML
     * @return   array                       Tablica z danymi
     */
    private function xml_to_array($xml)
    {
        $json = json_encode($xml);
        $array = json_decode($json, true);
        return $array;
    }

    /**
     * Pobiera plik produktów z serwera FTP
     *
     * @since    1.0.1
     * @return   bool    Czy pobieranie się powiodło
     */
    private function download_products_file()
    {
        // Przygotuj katalog lokalny
        $upload_dir = wp_upload_dir();
        $local_dir = $upload_dir['basedir'] . '/' . $this->settings['hurtownia2_local_path'];
        if (!file_exists($local_dir)) {
            wp_mkdir_p($local_dir);
        }

        // Przygotuj ścieżki plików
        $local_file = $local_dir . '/products.xml';

        // Połącz z serwerem FTP
        require_once plugin_dir_path(__FILE__) . 'class-kc-hurtownie-ftp-helper.php';
        $ftp = new Kc_Hurtownie_Ftp_Helper(
            $this->settings['hurtownia2_ftp_host'],
            $this->settings['hurtownia2_ftp_user'],
            $this->settings['hurtownia2_ftp_pass']
        );

        if (!$ftp->connect()) {
            error_log('Nie można połączyć się z serwerem FTP: ' . $this->settings['hurtownia2_ftp_host']);
            return false;
        }

        // Pobierz plik
        $remote_file = $this->settings['hurtownia2_ftp_path'] . '/products.xml';
        if (!$ftp->get_file($local_file, $remote_file)) {
            error_log('Nie można pobrać pliku z serwera FTP: ' . $remote_file);
            return false;
        }

        $ftp->close();
        return true;
    }

    /**
     * Pobiera plik stanów magazynowych z serwera FTP
     *
     * @since    1.0.1
     * @return   bool    Czy pobieranie się powiodło
     */
    private function download_stocks_file()
    {
        // Przygotuj katalog lokalny
        $upload_dir = wp_upload_dir();
        $local_dir = $upload_dir['basedir'] . '/' . $this->settings['hurtownia2_local_path'];
        if (!file_exists($local_dir)) {
            wp_mkdir_p($local_dir);
        }

        // Przygotuj ścieżki plików
        $local_file = $local_dir . '/stocks.xml';

        // Połącz z serwerem FTP
        require_once plugin_dir_path(__FILE__) . 'class-kc-hurtownie-ftp-helper.php';
        $ftp = new Kc_Hurtownie_Ftp_Helper(
            $this->settings['hurtownia2_ftp_host'],
            $this->settings['hurtownia2_ftp_user'],
            $this->settings['hurtownia2_ftp_pass']
        );

        if (!$ftp->connect()) {
            error_log('Nie można połączyć się z serwerem FTP: ' . $this->settings['hurtownia2_ftp_host']);
            return false;
        }

        // Pobierz plik
        $remote_file = $this->settings['hurtownia2_ftp_path'] . '/stocks.xml';
        if (!$ftp->get_file($local_file, $remote_file)) {
            error_log('Nie można pobrać pliku z serwera FTP: ' . $remote_file);
            return false;
        }

        $ftp->close();
        return true;
    }

    /**
     * Pobiera zdjęcia produktu
     *
     * @since    1.0.1
     * @param    array    $product_data    Dane produktu
     * @return   array                     Lista ścieżek do zdjęć
     */
    private function download_product_images($product_data)
    {
        // Sprawdź, czy importowanie zdjęć jest włączone
        if (!isset($this->settings['import_images']) || $this->settings['import_images'] != '1') {
            return array();
        }

        // Przygotuj katalog lokalny
        $upload_dir = wp_upload_dir();
        $local_dir = $upload_dir['basedir'] . '/' . $this->settings['hurtownia2_local_path'] . '/images';
        if (!file_exists($local_dir)) {
            wp_mkdir_p($local_dir);
        }

        // Połącz z serwerem FTP
        require_once plugin_dir_path(__FILE__) . 'class-kc-hurtownie-ftp-helper.php';
        $ftp = new Kc_Hurtownie_Ftp_Helper(
            $this->settings['hurtownia2_ftp_host'],
            $this->settings['hurtownia2_ftp_user'],
            $this->settings['hurtownia2_ftp_pass']
        );

        if (!$ftp->connect()) {
            error_log('Nie można połączyć się z serwerem FTP: ' . $this->settings['hurtownia2_ftp_host']);
            return array();
        }

        $product_images = array();

        // Pobierz zdjęcia produktu
        if (isset($product_data['images']) && is_array($product_data['images'])) {
            foreach ($product_data['images'] as $image) {
                $file = basename($image);
                $remote_file = $this->settings['hurtownia2_images_path'] . '/' . $file;
                $local_file = $local_dir . '/' . $file;

                // Pobierz plik
                if ($ftp->get_file($local_file, $remote_file)) {
                    $product_images[] = $upload_dir['baseurl'] . '/' . $this->settings['hurtownia2_local_path'] . '/images/' . $file;
                }
            }
        }

        $ftp->close();
        return $product_images;
    }
}