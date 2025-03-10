<?php

/**
 * Klasa odpowiedzialna za import produktów z hurtowni Inspirion
 *
 * @link       https://kemuri.codes
 * @since      1.0.0
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/includes
 */

/**
 * Klasa odpowiedzialna za import produktów z hurtowni Inspirion
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/includes
 * @author     Marcin Dymek <contact@kemuri.codes>
 */
class Kc_Hurtownie_Inspirion_Importer
{
    /**
     * Ustawienia importera
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $settings    Ustawienia importera
     */
    private $settings;

    /**
     * Licznik zaimportowanych produktów
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $imported_count    Licznik zaimportowanych produktów
     */
    private $imported_count = 0;

    /**
     * Licznik zaktualizowanych produktów
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $updated_count    Licznik zaktualizowanych produktów
     */
    private $updated_count = 0;

    /**
     * Licznik pominiętych produktów
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $skipped_count    Licznik pominiętych produktów
     */
    private $skipped_count = 0;

    /**
     * Licznik błędów
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $error_count    Licznik błędów
     */
    private $error_count = 0;

    /**
     * Całkowita liczba produktów
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $total_count    Całkowita liczba produktów
     */
    private $total_count = 0;

    /**
     * Konstruktor
     *
     * @since    1.0.0
     * @param    array    $settings    Ustawienia importera
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * Importuje produkty z hurtowni Inspirion
     *
     * @since    1.0.0
     * @return   array    Wyniki importu
     */
    public function import()
    {
        try {
            // Resetuj liczniki
            $this->imported_count = 0;
            $this->updated_count = 0;
            $this->skipped_count = 0;
            $this->error_count = 0;
            $this->total_count = 0;

            // Pobierz dane produktów z FTP lub lokalnego katalogu
            $products_data = $this->get_products_data();
            if (!$products_data) {
                return array(
                    'success' => false,
                    'message' => 'Nie udało się pobrać danych produktów z hurtowni Inspirion.'
                );
            }

            // Ustaw całkowitą liczbę produktów
            $this->total_count = count($products_data);

            // Importuj produkty
            foreach ($products_data as $product_data) {
                $this->import_product($product_data);
            }

            // Zwróć wyniki importu
            return array(
                'success' => true,
                'data' => array(
                    'imported' => $this->imported_count,
                    'updated' => $this->updated_count,
                    'skipped' => $this->skipped_count,
                    'errors' => $this->error_count,
                    'total' => $this->total_count
                )
            );
        } catch (Exception $e) {
            error_log('Błąd podczas importu produktów z hurtowni Inspirion: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Wystąpił błąd podczas importu: ' . $e->getMessage()
            );
        }
    }

    /**
     * Pobiera dane produktów z FTP
     *
     * @since    1.0.0
     * @return   array|false    Dane produktów lub false w przypadku błędu
     */
    private function get_products_data()
    {
        // Sprawdź, czy mamy lokalne pliki
        $upload_dir = wp_upload_dir();
        $local_dir = $upload_dir['basedir'] . '/' . $this->settings['hurtownia4_local_path'];
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
            $this->settings['hurtownia4_ftp_host'],
            $this->settings['hurtownia4_ftp_user'],
            $this->settings['hurtownia4_ftp_pass']
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
        $remote_file = $this->settings['hurtownia4_ftp_path'] . '/products.xml';
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
     * Parsuje plik XML
     *
     * @since    1.0.0
     * @param    string    $file    Ścieżka do pliku XML
     * @return   array|false        Dane z pliku XML lub false w przypadku błędu
     */
    private function parse_xml_file($file)
    {
        if (!file_exists($file)) {
            error_log('Plik nie istnieje: ' . $file);
            return false;
        }

        // Wczytaj plik XML
        $xml_content = file_get_contents($file);
        if (!$xml_content) {
            error_log('Nie można odczytać zawartości pliku: ' . $file);
            return false;
        }

        // Parsuj XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_content);
        if (!$xml) {
            $errors = libxml_get_errors();
            $error_msg = '';
            foreach ($errors as $error) {
                $error_msg .= $error->message . "\n";
            }
            libxml_clear_errors();
            error_log('Błąd parsowania XML: ' . $error_msg);
            return false;
        }

        // Konwertuj XML na tablicę
        $data = json_decode(json_encode($xml), true);

        return $data;
    }

    /**
     * Importuje pojedynczy produkt
     *
     * @since    1.0.0
     * @param    array    $product_data    Dane produktu
     */
    private function import_product($product_data)
    {
        // Sprawdź, czy produkt ma wymagane dane
        if (!isset($product_data['id']) || !isset($product_data['name']) || !isset($product_data['sku'])) {
            $this->error_count++;
            error_log('Brak wymaganych danych produktu: ' . print_r($product_data, true));
            return;
        }

        // Sprawdź, czy produkt już istnieje
        $product_id = $this->get_product_by_sku($product_data['sku']);

        if ($product_id) {
            // Jeśli aktualizacja istniejących produktów jest wyłączona, pomiń
            if (!isset($this->settings['update_existing']) || !$this->settings['update_existing']) {
                $this->skipped_count++;
                return;
            }

            // Aktualizuj produkt
            $this->update_product($product_id, $product_data);
            $this->updated_count++;
        } else {
            // Utwórz nowy produkt
            $this->create_product($product_data);
            $this->imported_count++;
        }
    }

    /**
     * Pobiera ID produktu na podstawie SKU
     *
     * @since    1.0.0
     * @param    string    $sku    SKU produktu
     * @return   int|false         ID produktu lub false jeśli nie znaleziono
     */
    private function get_product_by_sku($sku)
    {
        global $wpdb;

        $product_id = $wpdb->get_var($wpdb->prepare("
            SELECT post_id FROM $wpdb->postmeta
            WHERE meta_key = '_sku' AND meta_value = %s
            LIMIT 1
        ", $sku));

        return $product_id ? (int) $product_id : false;
    }

    /**
     * Tworzy nowy produkt
     *
     * @since    1.0.0
     * @param    array    $product_data    Dane produktu
     * @return   int|false                 ID utworzonego produktu lub false w przypadku błędu
     */
    private function create_product($product_data)
    {
        // Utwórz nowy produkt
        $product = new WC_Product_Simple();

        // Ustaw podstawowe dane produktu
        $product->set_name($product_data['name']);
        $product->set_sku($product_data['sku']);

        if (isset($product_data['description'])) {
            $product->set_description($product_data['description']);
        }

        if (isset($product_data['short_description'])) {
            $product->set_short_description($product_data['short_description']);
        }

        if (isset($product_data['price'])) {
            $product->set_regular_price($product_data['price']);
        }

        if (isset($product_data['stock_quantity'])) {
            $product->set_stock_quantity($product_data['stock_quantity']);
            $product->set_manage_stock(true);
            $product->set_stock_status($product_data['stock_quantity'] > 0 ? 'instock' : 'outofstock');
        }

        // Zapisz produkt
        $product_id = $product->save();

        // Dodaj kategorie
        if (isset($product_data['categories']) && is_array($product_data['categories'])) {
            $this->assign_categories($product_id, $product_data['categories']);
        }

        // Dodaj zdjęcia
        if (isset($product_data['images']) && is_array($product_data['images']) && isset($this->settings['import_images']) && $this->settings['import_images']) {
            $this->import_product_images($product_id, $product_data['images']);
        }

        // Zapisz ID produktu z hurtowni jako meta dane
        update_post_meta($product_id, '_hurtownia4_product_id', $product_data['id']);

        return $product_id;
    }

    /**
     * Aktualizuje istniejący produkt
     *
     * @since    1.0.0
     * @param    int      $product_id      ID produktu
     * @param    array    $product_data    Dane produktu
     * @return   int|false                 ID zaktualizowanego produktu lub false w przypadku błędu
     */
    private function update_product($product_id, $product_data)
    {
        // Pobierz produkt
        $product = wc_get_product($product_id);
        if (!$product) {
            return false;
        }

        // Aktualizuj dane produktu
        $product->set_name($product_data['name']);

        if (isset($product_data['description'])) {
            $product->set_description($product_data['description']);
        }

        if (isset($product_data['short_description'])) {
            $product->set_short_description($product_data['short_description']);
        }

        if (isset($product_data['price'])) {
            $product->set_regular_price($product_data['price']);
        }

        if (isset($product_data['stock_quantity'])) {
            $product->set_stock_quantity($product_data['stock_quantity']);
            $product->set_manage_stock(true);
            $product->set_stock_status($product_data['stock_quantity'] > 0 ? 'instock' : 'outofstock');
        }

        // Zapisz produkt
        $product_id = $product->save();

        // Aktualizuj kategorie
        if (isset($product_data['categories']) && is_array($product_data['categories'])) {
            $this->assign_categories($product_id, $product_data['categories']);
        }

        // Aktualizuj zdjęcia
        if (isset($product_data['images']) && is_array($product_data['images']) && isset($this->settings['import_images']) && $this->settings['import_images']) {
            $this->import_product_images($product_id, $product_data['images']);
        }

        return $product_id;
    }

    /**
     * Przypisuje kategorie do produktu
     *
     * @since    1.0.0
     * @param    int      $product_id    ID produktu
     * @param    array    $categories    Kategorie produktu
     */
    private function assign_categories($product_id, $categories)
    {
        $category_ids = array();

        foreach ($categories as $category) {
            $term = term_exists($category, 'product_cat');

            if (!$term) {
                // Utwórz nową kategorię
                $term = wp_insert_term($category, 'product_cat');
            }

            if (!is_wp_error($term)) {
                $category_ids[] = $term['term_id'];
            }
        }

        if (!empty($category_ids)) {
            wp_set_object_terms($product_id, $category_ids, 'product_cat');
        }
    }

    /**
     * Importuje zdjęcia produktu
     *
     * @since    1.0.0
     * @param    int      $product_id    ID produktu
     * @param    array    $images        Zdjęcia produktu
     */
    private function import_product_images($product_id, $images)
    {
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }

        $image_ids = array();

        foreach ($images as $image_url) {
            $attachment_id = $this->import_image($image_url, $product_id);
            if ($attachment_id) {
                $image_ids[] = $attachment_id;
            }
        }

        if (!empty($image_ids)) {
            // Ustaw pierwsze zdjęcie jako główne
            $product->set_image_id($image_ids[0]);

            // Ustaw pozostałe zdjęcia jako galeria
            if (count($image_ids) > 1) {
                $gallery_ids = array_slice($image_ids, 1);
                $product->set_gallery_image_ids($gallery_ids);
            }

            $product->save();
        }
    }

    /**
     * Importuje pojedyncze zdjęcie
     *
     * @since    1.0.0
     * @param    string    $image_url     URL zdjęcia
     * @param    int       $product_id    ID produktu
     * @return   int|false                ID załącznika lub false w przypadku błędu
     */
    private function import_image($image_url, $product_id)
    {
        // Pobierz nazwę pliku z URL
        $filename = basename($image_url);

        // Sprawdź, czy zdjęcie już istnieje
        $attachment_id = $this->get_attachment_by_filename($filename);
        if ($attachment_id) {
            return $attachment_id;
        }

        // Pobierz zdjęcie
        $image_data = file_get_contents($image_url);
        if (!$image_data) {
            error_log('Nie można pobrać zdjęcia: ' . $image_url);
            return false;
        }

        // Przygotuj katalog uploads
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['path'] . '/' . $filename;

        // Zapisz zdjęcie
        file_put_contents($upload_path, $image_data);

        // Przygotuj dane załącznika
        $wp_filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        // Dodaj załącznik
        $attachment_id = wp_insert_attachment($attachment, $upload_path, $product_id);
        if (!$attachment_id) {
            error_log('Nie można dodać załącznika: ' . $filename);
            return false;
        }

        // Wygeneruj metadane załącznika
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        return $attachment_id;
    }

    /**
     * Pobiera ID załącznika na podstawie nazwy pliku
     *
     * @since    1.0.0
     * @param    string    $filename    Nazwa pliku
     * @return   int|false              ID załącznika lub false jeśli nie znaleziono
     */
    private function get_attachment_by_filename($filename)
    {
        global $wpdb;

        $attachment_id = $wpdb->get_var($wpdb->prepare("
            SELECT post_id FROM $wpdb->postmeta
            WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s
            LIMIT 1
        ", '%' . $wpdb->esc_like($filename)));

        return $attachment_id ? (int) $attachment_id : false;
    }

    /**
     * Konwertuje obiekt SimpleXML na tablicę
     *
     * @since    1.0.0
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
     * Pobiera i rozpakuje pliki z FTP Inspirion
     */
    private function download_and_extract_files()
    {
        try {
            // Przygotuj katalog lokalny
            $upload_dir = wp_upload_dir();
            $local_dir = $upload_dir['basedir'] . '/' . $this->settings['hurtownia4_local_path'];
            $temp_dir = $local_dir . '/temp';

            // Utwórz katalogi jeśli nie istnieją
            if (!file_exists($local_dir)) {
                wp_mkdir_p($local_dir);
            }
            if (!file_exists($temp_dir)) {
                wp_mkdir_p($temp_dir);
            }

            // Połącz z FTP
            $ftp = ftp_connect($this->settings['hurtownia4_ftp_host']);
            if (!$ftp) {
                throw new Exception('Nie można połączyć się z serwerem FTP');
            }

            // Zaloguj się
            if (!@ftp_login($ftp, $this->settings['hurtownia4_ftp_user'], $this->settings['hurtownia4_ftp_pass'])) {
                throw new Exception('Błąd logowania do FTP');
            }

            // Włącz tryb pasywny
            ftp_pasv($ftp, true);

            // Pobierz listę plików ZIP
            $files = ftp_nlist($ftp, $this->settings['hurtownia4_ftp_path']);
            if (!$files) {
                throw new Exception('Nie można pobrać listy plików');
            }

            // Znajdź i pobierz najnowszy plik ZIP
            $zip_files = array_filter($files, function ($file) {
                return preg_match('/\.zip$/i', $file);
            });

            if (empty($zip_files)) {
                throw new Exception('Nie znaleziono plików ZIP');
            }

            // Pobierz najnowszy plik ZIP
            $latest_zip = end($zip_files);
            $local_zip = $temp_dir . '/' . basename($latest_zip);

            // Pobierz plik ZIP
            if (!ftp_get($ftp, $local_zip, $latest_zip, FTP_BINARY)) {
                throw new Exception('Nie można pobrać pliku ZIP');
            }

            // Zamknij połączenie FTP
            ftp_close($ftp);

            // Rozpakuj ZIP
            $zip = new ZipArchive;
            if ($zip->open($local_zip) === TRUE) {
                $zip->extractTo($local_dir);
                $zip->close();

                // Usuń tymczasowy plik ZIP
                unlink($local_zip);

                return true;
            } else {
                throw new Exception('Nie można rozpakować pliku ZIP');
            }

        } catch (Exception $e) {
            error_log('Błąd pobierania plików Inspirion: ' . $e->getMessage());
            if (isset($ftp) && $ftp) {
                ftp_close($ftp);
            }
            return false;
        }
    }

    /**
     * Testuje połączenie z FTP
     */
    public function test_connection()
    {
        try {
            // Połącz z FTP
            $ftp = ftp_connect($this->settings['hurtownia4_ftp_host']);
            if (!$ftp) {
                throw new Exception('Nie można połączyć się z serwerem FTP');
            }

            // Zaloguj się
            if (!@ftp_login($ftp, $this->settings['hurtownia4_ftp_user'], $this->settings['hurtownia4_ftp_pass'])) {
                throw new Exception('Błąd logowania do FTP');
            }

            // Włącz tryb pasywny
            ftp_pasv($ftp, true);

            // Sprawdź czy możemy pobrać listę plików
            $files = ftp_nlist($ftp, $this->settings['hurtownia4_ftp_path']);
            if (!$files) {
                throw new Exception('Nie można pobrać listy plików');
            }

            // Znajdź pliki ZIP
            $zip_files = array_filter($files, function ($file) {
                return preg_match('/\.zip$/i', $file);
            });

            if (empty($zip_files)) {
                throw new Exception('Nie znaleziono plików ZIP');
            }

            // Zamknij połączenie
            ftp_close($ftp);

            return array(
                'success' => true,
                'message' => 'Połączenie z FTP nawiązane pomyślnie',
                'files' => array_values($zip_files)
            );

        } catch (Exception $e) {
            if (isset($ftp) && $ftp) {
                ftp_close($ftp);
            }
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
}