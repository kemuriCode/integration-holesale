<?php

/**
 * Klasa odpowiedzialna za import produktów z hurtowni PAR
 *
 * @link       https://kemuri.codes
 * @since      1.0.1
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/includes
 */

/**
 * Klasa odpowiedzialna za import produktów z hurtowni PAR
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/includes
 * @author     Marcin Dymek <contact@kemuri.codes>
 */
class Kc_Hurtownie_Par_Importer
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
     * Konstruktor
     *
     * @since    1.0.1
     * @param    array    $settings    Ustawienia importera
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * Importuje produkty z hurtowni PAR
     *
     * @since    1.0.1
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

            // Pobierz dane produktów
            $products_data = $this->get_products_data();
            if (!$products_data) {
                return array(
                    'success' => false,
                    'message' => 'Nie udało się pobrać danych produktów z API.'
                );
            }

            // Pobierz dane kategorii
            $categories_data = $this->get_categories_data();
            if (!$categories_data) {
                error_log('Nie udało się pobrać danych kategorii z API. Kontynuuję import bez kategorii.');
            }

            // Pobierz dane stanów magazynowych
            $stocks_data = $this->get_stocks_data();
            if (!$stocks_data) {
                error_log('Nie udało się pobrać danych stanów magazynowych z API. Kontynuuję import bez stanów magazynowych.');
            }

            // Ustaw całkowitą liczbę produktów
            $this->total_count = count($products_data);

            // Importuj produkty
            foreach ($products_data as $product) {
                $this->import_product($product, $categories_data, $stocks_data);
            }

            // Zwróć wyniki importu
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
     * Pobiera dane produktów z API
     *
     * @since    1.0.1
     * @return   array|false    Dane produktów lub false w przypadku błędu
     */
    private function get_products_data()
    {
        // Sprawdź, czy mamy lokalne pliki
        $upload_dir = wp_upload_dir();
        $local_dir = $upload_dir['basedir'] . '/' . $this->settings['hurtownia3_local_path'];

        // Upewnij się, że katalog lokalny istnieje
        if (!file_exists($local_dir)) {
            wp_mkdir_p($local_dir);
        }

        $local_file = $local_dir . '/products.' . $this->settings['hurtownia3_api_format'];

        // Sprawdź, czy plik lokalny istnieje i czy jest aktualny
        if (file_exists($local_file) && filemtime($local_file) > strtotime('-1 day')) {
            // Plik istnieje i jest aktualny, użyj go
            if ($this->settings['hurtownia3_api_format'] === 'xml') {
                $xml = simplexml_load_file($local_file);
                if ($xml) {
                    return $this->xml_to_array($xml);
                }
            } else {
                $json = file_get_contents($local_file);
                if ($json) {
                    return json_decode($json, true);
                }
            }
        }

        // Plik nie istnieje lub jest nieaktualny, pobierz z API
        $api_url = $this->settings['hurtownia3_api_url'] . '.' . $this->settings['hurtownia3_api_format'];

        // Przygotuj argumenty żądania
        $args = array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->settings['hurtownia3_api_login'] . ':' . $this->settings['hurtownia3_api_password'])
            ),
            'sslverify' => false,
            'redirection' => 5,
            'httpversion' => '1.1',
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
        );

        // Wykonaj żądanie
        $response = wp_remote_get($api_url, $args);

        // Sprawdź, czy wystąpił błąd
        if (is_wp_error($response)) {
            error_log('Błąd połączenia z API: ' . $response->get_error_message());
            return false;
        }

        // Sprawdź kod odpowiedzi
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('Błąd API. Kod odpowiedzi: ' . $response_code);
            return false;
        }

        // Pobierz treść odpowiedzi
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            error_log('Pusta odpowiedź z API');
            return false;
        }

        // Zapisz odpowiedź do pliku lokalnego
        file_put_contents($local_file, $body);

        // Parsuj odpowiedź
        if ($this->settings['hurtownia3_api_format'] === 'xml') {
            $xml = simplexml_load_string($body);
            if (!$xml) {
                error_log('Nie można sparsować odpowiedzi XML');
                return false;
            }
            return $this->xml_to_array($xml);
        } else {
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Nie można sparsować odpowiedzi JSON: ' . json_last_error_msg());
                return false;
            }
            return $data;
        }
    }

    /**
     * Pobiera dane kategorii z API
     *
     * @since    1.0.1
     * @return   array|false    Dane kategorii lub false w przypadku błędu
     */
    private function get_categories_data()
    {
        $api_url = 'http://www.par.com.pl/api/categories';
        $api_format = isset($this->settings['hurtownia3_api_format']) ? $this->settings['hurtownia3_api_format'] : 'xml';
        $api_login = isset($this->settings['hurtownia3_api_login']) ? $this->settings['hurtownia3_api_login'] : '';
        $api_password = isset($this->settings['hurtownia3_api_password']) ? $this->settings['hurtownia3_api_password'] : '';

        // Dodaj format do URL
        $api_url .= '.' . $api_format;

        // Wykonaj żądanie HTTP
        $response = wp_remote_get($api_url, array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($api_login . ':' . $api_password)
            )
        ));

        // Sprawdź, czy żądanie się powiodło
        if (is_wp_error($response)) {
            error_log('Błąd podczas pobierania danych kategorii z API PAR: ' . $response->get_error_message());
            return false;
        }

        // Sprawdź kod odpowiedzi
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('Błąd podczas pobierania danych kategorii z API PAR. Kod odpowiedzi: ' . $response_code);
            return false;
        }

        // Pobierz treść odpowiedzi
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            error_log('Pusta odpowiedź z API PAR podczas pobierania danych kategorii.');
            return false;
        }

        // Parsuj dane w zależności od formatu
        if ($api_format === 'xml') {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($body);
            if (!$xml) {
                $errors = libxml_get_errors();
                foreach ($errors as $error) {
                    error_log('Błąd parsowania XML: ' . $error->message);
                }
                libxml_clear_errors();
                return false;
            }

            // Konwertuj XML na tablicę
            $categories = array();
            foreach ($xml->category as $category) {
                $categories[] = $this->xml_to_array($category);
            }
            return $categories;
        } else {
            // Parsuj JSON
            $categories = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Błąd parsowania JSON: ' . json_last_error_msg());
                return false;
            }
            return $categories;
        }
    }

    /**
     * Pobiera dane stanów magazynowych z API
     *
     * @since    1.0.1
     * @return   array|false    Dane stanów magazynowych lub false w przypadku błędu
     */
    private function get_stocks_data()
    {
        $api_url = 'http://www.par.com.pl/api/stocks';
        $api_format = isset($this->settings['hurtownia3_api_format']) ? $this->settings['hurtownia3_api_format'] : 'xml';
        $api_login = isset($this->settings['hurtownia3_api_login']) ? $this->settings['hurtownia3_api_login'] : '';
        $api_password = isset($this->settings['hurtownia3_api_password']) ? $this->settings['hurtownia3_api_password'] : '';

        // Dodaj format do URL
        $api_url .= '.' . $api_format;

        // Wykonaj żądanie HTTP
        $response = wp_remote_get($api_url, array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($api_login . ':' . $api_password)
            )
        ));

        // Sprawdź, czy żądanie się powiodło
        if (is_wp_error($response)) {
            error_log('Błąd podczas pobierania danych stanów magazynowych z API PAR: ' . $response->get_error_message());
            return false;
        }

        // Sprawdź kod odpowiedzi
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('Błąd podczas pobierania danych stanów magazynowych z API PAR. Kod odpowiedzi: ' . $response_code);
            return false;
        }

        // Pobierz treść odpowiedzi
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            error_log('Pusta odpowiedź z API PAR podczas pobierania danych stanów magazynowych.');
            return false;
        }

        // Parsuj dane w zależności od formatu
        if ($api_format === 'xml') {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($body);
            if (!$xml) {
                $errors = libxml_get_errors();
                foreach ($errors as $error) {
                    error_log('Błąd parsowania XML: ' . $error->message);
                }
                libxml_clear_errors();
                return false;
            }

            // Konwertuj XML na tablicę
            $stocks = array();
            foreach ($xml->stock as $stock) {
                $stock_data = $this->xml_to_array($stock);
                $stocks[$stock_data['product_id']] = $stock_data['quantity'];
            }
            return $stocks;
        } else {
            // Parsuj JSON
            $stocks_data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Błąd parsowania JSON: ' . json_last_error_msg());
                return false;
            }

            // Przekształć dane do formatu [product_id => quantity]
            $stocks = array();
            foreach ($stocks_data as $stock) {
                $stocks[$stock['product_id']] = $stock['quantity'];
            }
            return $stocks;
        }
    }

    /**
     * Importuje kategorie produktów
     *
     * @since    1.0.1
     * @param    array    $categories    Dane kategorii
     */
    private function import_categories($categories)
    {
        foreach ($categories as $category) {
            $this->import_category($category);
        }
    }

    /**
     * Importuje pojedynczą kategorię
     *
     * @since    1.0.1
     * @param    array    $category    Dane kategorii
     * @return   int                   ID kategorii w WooCommerce
     */
    private function import_category($category)
    {
        // Sprawdź, czy kategoria już istnieje
        $term = term_exists($category['name'], 'product_cat');

        if ($term) {
            // Kategoria już istnieje, zwróć jej ID
            return is_array($term) ? $term['term_id'] : $term;
        }

        // Utwórz nową kategorię
        $term = wp_insert_term(
            $category['name'],
            'product_cat',
            array(
                'description' => isset($category['description']) ? $category['description'] : '',
                'parent' => isset($category['parent_id']) ? $this->get_category_term_id($category['parent_id']) : 0
            )
        );

        if (is_wp_error($term)) {
            error_log('Błąd podczas tworzenia kategorii: ' . $term->get_error_message());
            return 0;
        }

        // Zapisz ID kategorii z hurtowni jako meta dane
        update_term_meta($term['term_id'], '_hurtownia3_category_id', $category['id']);

        return $term['term_id'];
    }

    /**
     * Pobiera ID kategorii WooCommerce na podstawie ID kategorii z hurtowni
     *
     * @since    1.0.1
     * @param    string    $category_id    ID kategorii z hurtowni
     * @return   int                       ID kategorii w WooCommerce
     */
    private function get_category_term_id($category_id)
    {
        global $wpdb;

        $term_id = $wpdb->get_var($wpdb->prepare("
            SELECT term_id FROM $wpdb->termmeta
            WHERE meta_key = '_hurtownia3_category_id' AND meta_value = %s
            LIMIT 1
        ", $category_id));

        return $term_id ? (int) $term_id : 0;
    }

    /**
     * Importuje pojedynczy produkt
     *
     * @since    1.0.1
     * @param    array    $product_data    Dane produktu
     * @param    array    $categories_data Dane kategorii
     * @param    array    $stocks_data     Dane stanów magazynowych
     */
    private function import_product($product_data, $categories_data, $stocks_data)
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
            // Produkt już istnieje, aktualizuj go
            $this->update_product(
                $product_id,
                $product_data['name'],
                isset($product_data['description']) ? $product_data['description'] : '',
                isset($product_data['price']) ? $product_data['price'] : 0,
                isset($product_data['category_id']) ? $product_data['category_id'] : '',
                isset($product_data['images']) ? $product_data['images'] : array(),
                $stocks_data,
                $product_data['id']
            );

            $this->updated_count++;
        } else {
            // Produkt nie istnieje, utwórz nowy
            $new_product_id = $this->create_product(
                $product_data['name'],
                $product_data['sku'],
                isset($product_data['description']) ? $product_data['description'] : '',
                isset($product_data['price']) ? $product_data['price'] : 0,
                isset($product_data['category_id']) ? $product_data['category_id'] : '',
                isset($product_data['images']) ? $product_data['images'] : array(),
                $stocks_data,
                $product_data['id']
            );

            if ($new_product_id) {
                $this->imported_count++;
            } else {
                $this->error_count++;
            }
        }
    }

    /**
     * Pobiera produkt na podstawie SKU
     *
     * @since    1.0.1
     * @param    string    $sku    SKU produktu
     * @return   int               ID produktu lub 0
     */
    private function get_product_by_sku($sku)
    {
        global $wpdb;

        $product_id = $wpdb->get_var($wpdb->prepare("
            SELECT post_id FROM $wpdb->postmeta
            WHERE meta_key = '_sku' AND meta_value = %s
            LIMIT 1
        ", $sku));

        return $product_id ? (int) $product_id : 0;
    }

    /**
     * Tworzy nowy produkt
     *
     * @since    1.0.1
     * @param    string    $name           Nazwa produktu
     * @param    string    $sku            SKU produktu
     * @param    string    $description    Opis produktu
     * @param    float     $price          Cena produktu
     * @param    string    $category_id    ID kategorii
     * @param    array     $images         Zdjęcia produktu
     * @param    array     $stocks         Dane stanów magazynowych
     * @param    string    $product_id     ID produktu w hurtowni
     * @return   int                       ID utworzonego produktu lub 0
     */
    private function create_product($name, $sku, $description, $price, $category_id, $images, $stocks, $product_id)
    {
        // Utwórz nowy produkt
        $product = new WC_Product_Simple();

        $product->set_name($name);
        $product->set_sku($sku);
        $product->set_description($description);
        $product->set_regular_price($price);
        $product->set_status('publish');

        // Ustaw stan magazynowy
        if (isset($stocks[$product_id])) {
            $product->set_manage_stock(true);
            $product->set_stock_quantity($stocks[$product_id]);
            $product->set_stock_status($stocks[$product_id] > 0 ? 'instock' : 'outofstock');
        }

        // Zapisz produkt
        $new_product_id = $product->save();

        if (!$new_product_id) {
            error_log('Błąd podczas tworzenia produktu: ' . $name);
            return 0;
        }

        // Zapisz ID produktu z hurtowni jako meta dane
        update_post_meta($new_product_id, '_hurtownia3_product_id', $product_id);

        // Przypisz produkt do kategorii
        if ($category_id) {
            $term_id = $this->get_category_term_id($category_id);
            if ($term_id) {
                wp_set_object_terms($new_product_id, $term_id, 'product_cat');
            }
        }

        // Dodaj zdjęcia produktu
        if (!empty($images) && isset($this->settings['import_images']) && $this->settings['import_images']) {
            $this->import_product_images($new_product_id, $images);
        }

        return $new_product_id;
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
     * @param    array     $stocks         Dane stanów magazynowych
     * @param    string    $hurtownia_id   ID produktu w hurtowni
     */
    private function update_product($product_id, $name, $description, $price, $category_id, $images, $stocks, $hurtownia_id)
    {
        // Pobierz produkt
        $product = wc_get_product($product_id);

        if (!$product) {
            error_log('Nie znaleziono produktu o ID: ' . $product_id);
            return;
        }

        // Aktualizuj dane produktu
        $product->set_name($name);
        $product->set_description($description);
        $product->set_regular_price($price);

        // Ustaw stan magazynowy
        if (isset($stocks[$hurtownia_id])) {
            $product->set_manage_stock(true);
            $product->set_stock_quantity($stocks[$hurtownia_id]);
            $product->set_stock_status($stocks[$hurtownia_id] > 0 ? 'instock' : 'outofstock');
        }

        // Zapisz produkt
        $product->save();

        // Zapisz ID produktu z hurtowni jako meta dane
        update_post_meta($product_id, '_hurtownia3_product_id', $hurtownia_id);

        // Przypisz produkt do kategorii
        if ($category_id) {
            $term_id = $this->get_category_term_id($category_id);
            if ($term_id) {
                wp_set_object_terms($product_id, $term_id, 'product_cat');
            }
        }

        // Dodaj zdjęcia produktu
        if (!empty($images) && isset($this->settings['import_images']) && $this->settings['import_images']) {
            $this->import_product_images($product_id, $images);
        }
    }

    /**
     * Importuje zdjęcia produktu
     *
     * @since    1.0.1
     * @param    int      $product_id    ID produktu
     * @param    array    $images        Tablica z URL-ami zdjęć
     */
    private function import_product_images($product_id, $images)
    {
        if (empty($images)) {
            return;
        }

        $image_ids = array();

        foreach ($images as $image_url) {
            // Pobierz zdjęcie
            $image_id = $this->download_image($image_url, $product_id);
            if ($image_id) {
                $image_ids[] = $image_id;
            }
        }

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
     * Pobiera zdjęcie z URL i dodaje je do biblioteki mediów
     *
     * @since    1.0.1
     * @param    string    $url           URL zdjęcia
     * @param    int       $product_id    ID produktu
     * @return   int|false                ID załącznika lub false w przypadku błędu
     */
    private function download_image($url, $product_id)
    {
        // Pobierz zdjęcie
        $temp_file = download_url($url);
        if (is_wp_error($temp_file)) {
            error_log('Błąd podczas pobierania zdjęcia: ' . $temp_file->get_error_message());
            return false;
        }

        // Przygotuj dane pliku
        $file_array = array(
            'name' => basename($url),
            'tmp_name' => $temp_file
        );

        // Sprawdź typ pliku
        $file_type = wp_check_filetype($file_array['name'], null);
        if (!$file_type['type']) {
            @unlink($temp_file);
            error_log('Nieobsługiwany typ pliku: ' . $file_array['name']);
            return false;
        }

        // Dodaj zdjęcie do biblioteki mediów
        $attachment_id = media_handle_sideload($file_array, $product_id);

        // Usuń tymczasowy plik
        @unlink($temp_file);

        if (is_wp_error($attachment_id)) {
            error_log('Błąd podczas dodawania zdjęcia do biblioteki mediów: ' . $attachment_id->get_error_message());
            return false;
        }

        return $attachment_id;
    }

    /**
     * Konwertuje obiekt SimpleXMLElement na tablicę
     *
     * @since    1.0.1
     * @param    SimpleXMLElement    $xml    Obiekt XML
     * @return   array                       Tablica z danymi
     */
    private function xml_to_array($xml)
    {
        $json = json_encode($xml);
        $array = json_decode($json, true);
        return $array;
    }

    /**
     * Pobiera dane zdjęć produktu
     *
     * @since    1.0.1
     * @param    string    $product_sku    SKU produktu
     * @return   array                     Tablica z URL-ami zdjęć
     */
    private function get_product_images($product_sku)
    {
        // Sprawdź, czy mamy lokalne pliki
        $upload_dir = wp_upload_dir();
        $local_dir = $upload_dir['basedir'] . '/' . $this->settings['hurtownia3_local_path'] . '/images';

        // Upewnij się, że katalog lokalny istnieje
        if (!file_exists($local_dir)) {
            wp_mkdir_p($local_dir);
        }

        // Sprawdź, czy zdjęcia już istnieją lokalnie
        $local_images = glob($local_dir . '/' . $product_sku . '_*.jpg');
        if (!empty($local_images)) {
            $images = array();
            foreach ($local_images as $image) {
                $images[] = $upload_dir['baseurl'] . '/' . $this->settings['hurtownia3_local_path'] . '/images/' . basename($image);
            }
            return $images;
        }

        // Zdjęcia nie istnieją lokalnie, pobierz z API
        $api_url = $this->settings['hurtownia3_api_url'] . '/images/' . $product_sku . '.' . $this->settings['hurtownia3_api_format'];

        // Przygotuj argumenty żądania
        $args = array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->settings['hurtownia3_api_login'] . ':' . $this->settings['hurtownia3_api_password'])
            ),
            'sslverify' => false,
            'redirection' => 5,
            'httpversion' => '1.1',
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
        );

        // Wykonaj żądanie
        $response = wp_remote_get($api_url, $args);

        // Sprawdź, czy wystąpił błąd
        if (is_wp_error($response)) {
            error_log('Błąd połączenia z API: ' . $response->get_error_message());
            return array();
        }

        // Sprawdź kod odpowiedzi
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('Błąd API: ' . $response_code . ' ' . wp_remote_retrieve_response_message($response));
            return array();
        }

        // Pobierz treść odpowiedzi
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            error_log('Pusta odpowiedź z API.');
            return array();
        }

        // Parsuj odpowiedź
        $images = array();
        if ($this->settings['hurtownia3_api_format'] === 'xml') {
            $xml = simplexml_load_string($body);
            if (!$xml) {
                error_log('Nie można sparsować odpowiedzi XML.');
                return array();
            }
            $data = $this->xml_to_array($xml);
            if (isset($data['image'])) {
                if (is_array($data['image'])) {
                    foreach ($data['image'] as $image) {
                        $image_url = $image['url'];
                        $image_filename = basename($image_url);
                        $local_file = $local_dir . '/' . $image_filename;

                        // Pobierz zdjęcie
                        $image_data = file_get_contents($image_url);
                        if ($image_data) {
                            file_put_contents($local_file, $image_data);
                            $images[] = $upload_dir['baseurl'] . '/' . $this->settings['hurtownia3_local_path'] . '/images/' . $image_filename;
                        }
                    }
                }
            }
        } else {
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Nie można sparsować odpowiedzi JSON: ' . json_last_error_msg());
                return array();
            }
            if (isset($data['images'])) {
                foreach ($data['images'] as $image) {
                    $image_url = $image['url'];
                    $image_filename = basename($image_url);
                    $local_file = $local_dir . '/' . $image_filename;

                    // Pobierz zdjęcie
                    $image_data = file_get_contents($image_url);
                    if ($image_data) {
                        file_put_contents($local_file, $image_data);
                        $images[] = $upload_dir['baseurl'] . '/' . $this->settings['hurtownia3_local_path'] . '/images/' . $image_filename;
                    }
                }
            }
        }

        return $images;
    }
}