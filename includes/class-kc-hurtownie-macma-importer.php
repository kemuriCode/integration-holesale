<?php

/**
 * Klasa odpowiedzialna za import produktów z hurtowni Macma
 *
 * @link       https://kemuri.codes
 * @since      1.0.1
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/includes
 */

/**
 * Klasa odpowiedzialna za import produktów z hurtowni Macma
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/includes
 * @author     Marcin Dymek <contact@kemuri.codes>
 */
class Kc_Hurtownie_Macma_Importer
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
     * Obiekt do obsługi atrybutów
     *
     * @since    1.0.1
     * @access   private
     * @var      Kc_Hurtownie_Attributes    $attributes    Obiekt do obsługi atrybutów
     */
    private $attributes;

    /**
     * Konstruktor
     *
     * @since    1.0.1
     * @param    array    $settings    Ustawienia importera
     */
    public function __construct($settings)
    {
        $this->settings = $settings;

        // Inicjalizuj obiekt atrybutów
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kc-hurtownie-attributes.php';
        $this->attributes = new Kc_Hurtownie_Attributes();
    }

    /**
     * Importuje produkty z hurtowni
     *
     * @since    1.0.1
     * @return   array    Wyniki importu
     */
    public function import()
    {
        try {
            // Inicjalizuj atrybuty
            $this->attributes->init_attributes();

            // Pobierz dane produktów
            $products_data = $this->get_products_data();
            if (!$products_data) {
                return array(
                    'success' => false,
                    'message' => 'Nie można pobrać danych produktów.'
                );
            }

            // Pobierz dane stanów magazynowych
            $stocks_data = $this->get_stocks_data();
            if (!$stocks_data) {
                error_log('Nie można pobrać danych stanów magazynowych.');
                $stocks_data = array();
            }

            // Pobierz dane kategorii
            $categories_data = $this->get_categories_data();
            if (!$categories_data) {
                error_log('Nie można pobrać danych kategorii.');
                $categories_data = array();
            }

            // Pobierz dane cen
            $prices_data = $this->get_prices_data();
            if (!$prices_data) {
                error_log('Nie można pobrać danych cen.');
                $prices_data = array();
            }

            // Importuj kategorie
            if (isset($this->settings['import_categories']) && $this->settings['import_categories']) {
                $this->import_categories($categories_data);
            }

            // Ustaw całkowitą liczbę produktów
            $this->total_count = count($products_data);

            // Importuj produkty
            $limit = isset($this->settings['import_limit']) && $this->settings['import_limit'] > 0 ? $this->settings['import_limit'] : 0;
            $count = 0;

            foreach ($products_data as $product_data) {
                // Sprawdź limit importu
                if ($limit > 0 && $count >= $limit) {
                    break;
                }

                // Importuj produkt
                $this->import_product($product_data, $categories_data, $stocks_data, $prices_data);
                $count++;
            }

            return array(
                'success' => true,
                'message' => 'Import zakończony pomyślnie.',
                'stats' => array(
                    'imported' => $this->imported_count,
                    'updated' => $this->updated_count,
                    'skipped' => $this->skipped_count,
                    'errors' => $this->error_count,
                    'total' => $this->total_count
                )
            );
        } catch (Exception $e) {
            error_log('Błąd podczas importu produktów z hurtowni Macma: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Wystąpił błąd podczas importu: ' . $e->getMessage()
            );
        }
    }

    /**
     * Pobiera dane produktów z API lub lokalnego katalogu
     *
     * @since    1.0.1
     * @return   array|false    Dane produktów lub false w przypadku błędu
     */
    private function get_products_data()
    {
        return $this->get_xml_data('offer.xml');
    }

    /**
     * Pobiera dane stanów magazynowych z API lub lokalnego katalogu
     *
     * @since    1.0.1
     * @return   array|false    Dane stanów magazynowych lub false w przypadku błędu
     */
    private function get_stocks_data()
    {
        return $this->get_xml_data('stocks.xml');
    }

    /**
     * Pobiera dane kategorii z API lub lokalnego katalogu
     *
     * @since    1.0.1
     * @return   array|false    Dane kategorii lub false w przypadku błędu
     */
    private function get_categories_data()
    {
        return $this->get_xml_data('categories.xml');
    }

    /**
     * Pobiera dane cen z API lub lokalnego katalogu
     *
     * @since    1.0.1
     * @return   array|false    Dane cen lub false w przypadku błędu
     */
    private function get_prices_data()
    {
        return $this->get_xml_data('prices.xml');
    }

    /**
     * Pobiera dane XML z API lub lokalnego katalogu
     *
     * @since    1.0.1
     * @param    string    $filename    Nazwa pliku
     * @return   array|false            Dane XML lub false w przypadku błędu
     */
    private function get_xml_data($filename)
    {
        // Sprawdź, czy plik istnieje lokalnie
        $upload_dir = wp_upload_dir();
        $local_dir = $upload_dir['basedir'] . '/' . $this->settings['hurtownia5_local_path'];
        $local_file = $local_dir . '/' . $filename;

        if (file_exists($local_file)) {
            // Pobierz dane z lokalnego pliku
            $xml_data = file_get_contents($local_file);
        } else {
            // Pobierz dane z API
            $api_url = isset($this->settings['hurtownia5_api_url']) ? $this->settings['hurtownia5_api_url'] : 'http://www.macma.pl/data/webapi/pl/xml/';
            $api_url = rtrim($api_url, '/') . '/' . $filename;

            // Przygotuj argumenty żądania
            $args = array(
                'timeout' => 120,
                'sslverify' => false,
                'httpversion' => '1.1',
                'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
            );

            // Wykonaj żądanie
            $response = wp_remote_get($api_url, $args);

            // Sprawdź, czy wystąpił błąd
            if (is_wp_error($response)) {
                error_log('Błąd pobierania danych z API Macma: ' . $response->get_error_message());
                return false;
            }

            // Pobierz treść odpowiedzi
            $xml_data = wp_remote_retrieve_body($response);

            // Zapisz dane do pliku lokalnego
            if (!empty($xml_data)) {
                // Utwórz katalog, jeśli nie istnieje
                if (!file_exists($local_dir)) {
                    wp_mkdir_p($local_dir);
                }

                // Zapisz plik
                file_put_contents($local_file, $xml_data);
            }
        }

        // Parsuj XML
        if (!empty($xml_data)) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xml_data);

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
            return $this->xml_to_array($xml);
        }

        return false;
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
        return json_decode($json, true);
    }

    /**
     * Importuje kategorie produktów
     *
     * @since    1.0.1
     * @param    array    $categories_data    Dane kategorii
     */
    private function import_categories($categories_data)
    {
        if (empty($categories_data)) {
            return;
        }

        foreach ($categories_data as $category_data) {
            if (!isset($category_data['id']) || !isset($category_data['name'])) {
                continue;
            }

            $this->import_category($category_data['id'], $category_data['name'], isset($category_data['parent_id']) ? $category_data['parent_id'] : 0);
        }
    }

    /**
     * Importuje kategorię produktu
     *
     * @since    1.0.1
     * @param    string    $category_id    ID kategorii
     * @param    string    $name           Nazwa kategorii
     * @param    string    $parent_id      ID kategorii nadrzędnej
     * @return   int                       ID kategorii w WooCommerce
     */
    private function import_category($category_id, $name, $parent_id = 0)
    {
        // Sprawdź, czy kategoria już istnieje
        $term_id = $this->get_category_by_id($category_id);
        if ($term_id) {
            return $term_id;
        }

        // Sprawdź, czy istnieje kategoria nadrzędna
        $parent_term_id = 0;
        if ($parent_id) {
            $parent_term_id = $this->get_category_by_id($parent_id);

            if (!$parent_term_id) {
                // Pobierz dane kategorii, jeśli nie zostały jeszcze pobrane
                if (!isset($categories_data)) {
                    $categories_data = $this->get_categories_data();
                }

                // Importuj kategorię nadrzędną, jeśli nie istnieje
                if (is_array($categories_data)) {
                    foreach ($categories_data as $parent_category) {
                        if (isset($parent_category['id']) && $parent_category['id'] === $parent_id) {
                            $parent_term_id = $this->import_category($parent_id, $parent_category['name'], isset($parent_category['parent_id']) ? $parent_category['parent_id'] : 0);
                            break;
                        }
                    }
                }
            }
        }

        // Utwórz kategorię
        $term = wp_insert_term($name, 'product_cat', array(
            'parent' => $parent_term_id
        ));

        if (is_wp_error($term)) {
            error_log('Błąd podczas tworzenia kategorii: ' . $term->get_error_message());
            return 0;
        }

        // Zapisz ID kategorii z hurtowni
        update_term_meta($term['term_id'], '_hurtownia5_category_id', $category_id);

        return $term['term_id'];
    }

    /**
     * Importuje pojedynczy produkt
     *
     * @since    1.0.1
     * @param    array    $product_data     Dane produktu
     * @param    array    $categories_data  Dane kategorii
     * @param    array    $stocks_data      Dane stanów magazynowych
     * @param    array    $prices_data      Dane cen
     */
    private function import_product($product_data, $categories_data, $stocks_data, $prices_data)
    {
        // Sprawdź, czy produkt ma wymagane dane
        if (!isset($product_data['id']) || !isset($product_data['name']) || !isset($product_data['code'])) {
            $this->error_count++;
            error_log('Brak wymaganych danych produktu: ' . print_r($product_data, true));
            return;
        }

        // Sprawdź, czy produkt już istnieje
        $product_id = $this->get_product_by_sku($product_data['code']);

        // Przygotuj dane produktu
        $product_name = $product_data['name'];
        $product_description = isset($product_data['description']) ? $product_data['description'] : '';
        $product_sku = $product_data['code'];

        // Znajdź cenę produktu
        $product_price = 0;
        if (!empty($prices_data)) {
            foreach ($prices_data as $price_data) {
                if (isset($price_data['code']) && $price_data['code'] === $product_sku) {
                    $product_price = isset($price_data['price']) ? $price_data['price'] : 0;
                    break;
                }
            }
        }

        // Znajdź stan magazynowy produktu
        $product_stock = 0;
        if (!empty($stocks_data)) {
            foreach ($stocks_data as $stock_data) {
                if (isset($stock_data['code']) && $stock_data['code'] === $product_sku) {
                    $product_stock = isset($stock_data['quantity']) ? $stock_data['quantity'] : 0;
                    break;
                }
            }
        }

        // Znajdź kategorię produktu
        $product_category = '';
        if (!empty($categories_data) && isset($product_data['category_id'])) {
            foreach ($categories_data as $category_data) {
                if (isset($category_data['id']) && $category_data['id'] === $product_data['category_id']) {
                    $product_category = $category_data['name'];
                    break;
                }
            }
        }

        // Przygotuj zdjęcia produktu
        $product_images = array();
        if (isset($product_data['images']) && is_array($product_data['images'])) {
            foreach ($product_data['images'] as $image) {
                if (isset($image['url'])) {
                    $product_images[] = $image['url'];
                }
            }
        }

        // Mapuj atrybuty produktu
        $product_attributes = $this->attributes->map_attributes('hurtownia5', $product_data);

        if ($product_id) {
            // Produkt już istnieje, aktualizuj go
            if (isset($this->settings['update_existing']) && $this->settings['update_existing']) {
                $this->update_product($product_id, $product_name, $product_description, $product_price, $product_data['category_id'], $product_images, $product_stock, $product_data['id'], $product_attributes);
                $this->updated_count++;
            } else {
                $this->skipped_count++;
            }
        } else {
            // Utwórz nowy produkt
            $this->create_product($product_name, $product_description, $product_price, $product_sku, $product_data['category_id'], $product_images, $product_stock, $product_data['id'], $product_attributes);
            $this->imported_count++;
        }
    }

    /**
     * Tworzy nowy produkt
     *
     * @since    1.0.1
     * @param    string    $name           Nazwa produktu
     * @param    string    $description    Opis produktu
     * @param    float     $price          Cena produktu
     * @param    string    $sku            SKU produktu
     * @param    string    $category_id    ID kategorii
     * @param    array     $images         Zdjęcia produktu
     * @param    int       $stock          Stan magazynowy
     * @param    string    $product_id     ID produktu w hurtowni
     * @param    array     $attributes     Atrybuty produktu
     * @return   int                       ID utworzonego produktu
     */
    private function create_product($name, $description, $price, $sku, $category_id, $images, $stock, $product_id, $attributes = array())
    {
        // Utwórz nowy produkt
        $product = new WC_Product_Simple();

        // Ustaw dane produktu
        $product->set_name($name);
        $product->set_description($description);
        $product->set_short_description('');
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_price($price);
        $product->set_regular_price($price);
        $product->set_sku($sku);
        $product->set_manage_stock(true);
        $product->set_stock_quantity($stock);
        $product->set_stock_status($stock > 0 ? 'instock' : 'outofstock');
        $product->set_backorders('no');
        $product->set_reviews_allowed(true);
        $product->set_sold_individually(false);

        // Zapisz produkt
        $product_id_wc = $product->save();

        // Zapisz ID produktu z hurtowni
        update_post_meta($product_id_wc, '_hurtownia5_product_id', $product_id);

        // Przypisz kategorię
        if ($category_id) {
            $category_id_wc = $this->get_category_by_id($category_id);
            if ($category_id_wc) {
                wp_set_object_terms($product_id_wc, $category_id_wc, 'product_cat');
            }
        }

        // Dodaj zdjęcia
        if (!empty($images) && isset($this->settings['import_images']) && $this->settings['import_images']) {
            $this->add_product_images($product_id_wc, $images);
        }

        // Dodaj atrybuty
        if (!empty($attributes)) {
            $this->attributes->add_attributes_to_product($product_id_wc, $attributes);
        }

        return $product_id_wc;
    }

    /**
     * Aktualizuje istniejący produkt
     *
     * @since    1.0.1
     * @param    int       $product_id     ID produktu
     * @param    string    $name           Nazwa produktu
     * @param    string    $description    Opis produktu
     * @param    float     $price          Cena produktu
     * @param    string    $category_id    ID kategorii
     * @param    array     $images         Zdjęcia produktu
     * @param    int       $stock          Stan magazynowy
     * @param    string    $hurtownia_id   ID produktu w hurtowni
     * @param    array     $attributes     Atrybuty produktu
     */
    private function update_product($product_id, $name, $description, $price, $category_id, $images, $stock, $hurtownia_id, $attributes = array())
    {
        // Pobierz produkt
        $product = wc_get_product($product_id);
        if (!$product) {
            $this->error_count++;
            error_log('Nie można pobrać produktu o ID: ' . $product_id);
            return;
        }

        // Aktualizuj dane produktu
        $product->set_name($name);
        $product->set_description($description);
        $product->set_price($price);
        $product->set_regular_price($price);
        $product->set_manage_stock(true);
        $product->set_stock_quantity($stock);
        $product->set_stock_status($stock > 0 ? 'instock' : 'outofstock');

        // Zapisz produkt
        $product->save();

        // Zapisz ID produktu z hurtowni
        update_post_meta($product_id, '_hurtownia5_product_id', $hurtownia_id);

        // Przypisz kategorię
        if ($category_id) {
            $category_id_wc = $this->get_category_by_id($category_id);
            if ($category_id_wc) {
                wp_set_object_terms($product_id, $category_id_wc, 'product_cat');
            }
        }

        // Dodaj zdjęcia
        if (!empty($images) && isset($this->settings['import_images']) && $this->settings['import_images']) {
            $this->add_product_images($product_id, $images);
        }

        // Dodaj atrybuty
        if (!empty($attributes)) {
            $this->attributes->add_attributes_to_product($product_id, $attributes);
        }
    }

    /**
     * Pobiera ID produktu na podstawie SKU
     *
     * @since    1.0.1
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
     * Pobiera ID kategorii na podstawie ID kategorii z hurtowni
     *
     * @since    1.0.1
     * @param    string    $category_id    ID kategorii z hurtowni
     * @return   int|false                 ID kategorii lub false jeśli nie znaleziono
     */
    private function get_category_by_id($category_id)
    {
        global $wpdb;

        $term_id = $wpdb->get_var($wpdb->prepare("
            SELECT term_id FROM $wpdb->termmeta
            WHERE meta_key = '_hurtownia5_category_id' AND meta_value = %s
            LIMIT 1
        ", $category_id));

        return $term_id ? (int) $term_id : false;
    }

    /**
     * Dodaje zdjęcia do produktu
     *
     * @since    1.0.1
     * @param    int      $product_id    ID produktu
     * @param    array    $images        Tablica z URL-ami zdjęć
     */
    private function add_product_images($product_id, $images)
    {
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }

        $attachment_ids = array();

        foreach ($images as $image_url) {
            $attachment_id = $this->import_image($image_url, $product_id);
            if ($attachment_id) {
                $attachment_ids[] = $attachment_id;
            }
        }

        if (!empty($attachment_ids)) {
            // Ustaw pierwsze zdjęcie jako główne
            $product->set_image_id($attachment_ids[0]);

            // Ustaw pozostałe zdjęcia jako galeria
            if (count($attachment_ids) > 1) {
                $gallery_ids = array_slice($attachment_ids, 1);
                $product->set_gallery_image_ids($gallery_ids);
            }

            $product->save();
        }
    }

    /**
     * Importuje pojedyncze zdjęcie
     *
     * @since    1.0.1
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
     * @since    1.0.1
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
     * Pobiera pliki produktów z API
     *
     * @since    1.0.1
     * @return   bool    Czy pobieranie się powiodło
     */
    private function download_products_files()
    {
        // Przygotuj katalog lokalny
        $upload_dir = wp_upload_dir();
        $local_dir = $upload_dir['basedir'] . '/' . $this->settings['hurtownia5_local_path'];
        if (!file_exists($local_dir)) {
            wp_mkdir_p($local_dir);
        }

        // Pobierz pliki z API
        $files_to_download = array(
            'offer.xml',
            'prices.xml',
            'stocks.xml'
        );

        $api_url = rtrim($this->settings['hurtownia5_api_url'], '/');
        $success = true;

        foreach ($files_to_download as $file) {
            $file_url = $api_url . '/' . $file;
            $local_file = $local_dir . '/' . $file;

            // Przygotuj argumenty żądania
            $args = array(
                'timeout' => 60,
                'sslverify' => false
            );

            // Wykonaj żądanie
            $response = wp_remote_get($file_url, $args);

            // Sprawdź, czy wystąpił błąd
            if (is_wp_error($response)) {
                error_log('Błąd pobierania pliku z API Macma: ' . $response->get_error_message());
                $success = false;
                continue;
            }

            // Sprawdź kod odpowiedzi
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                error_log('Błąd API Macma: ' . $response_code . ' ' . wp_remote_retrieve_response_message($response));
                $success = false;
                continue;
            }

            // Pobierz treść odpowiedzi
            $body = wp_remote_retrieve_body($response);
            if (empty($body)) {
                error_log('Pusta odpowiedź z API Macma dla pliku: ' . $file);
                $success = false;
                continue;
            }

            // Zapisz plik lokalnie
            file_put_contents($local_file, $body);
        }

        return $success;
    }
}