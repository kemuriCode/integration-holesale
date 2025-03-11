<?php

/**
 * Klasa odpowiedzialna za import produktów z hurtowni Malfini
 *
 * @link       https://kemuri.codes
 * @since      1.0.1
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/includes
 */

/**
 * Klasa odpowiedzialna za import produktów z hurtowni Malfini
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/includes
 * @author     Marcin Dymek <contact@kemuri.codes>
 */
class Kc_Hurtownie_Malfini_Importer
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
     * Token dostępu do API
     *
     * @since    1.0.1
     * @access   private
     * @var      string    $access_token    Token dostępu do API
     */
    private $access_token = '';

    /**
     * Token odświeżania do API
     *
     * @since    1.0.1
     * @access   private
     * @var      string    $refresh_token    Token odświeżania do API
     */
    private $refresh_token = '';

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

            // Autoryzacja w API
            if (!$this->authenticate()) {
                error_log('Malfini importer: Nie można uwierzytelnić w API Malfini');
                return array(
                    'success' => false,
                    'message' => 'Nie można uwierzytelnić w API Malfini. Sprawdź ustawienia API.'
                );
            }

            // Pobierz dane produktów
            error_log('Malfini importer: Pobieranie danych produktów...');
            $products_data = $this->get_products_data();
            if (!$products_data) {
                error_log('Malfini importer: Nie można pobrać danych produktów z API Malfini');
                return array(
                    'success' => false,
                    'message' => 'Nie można pobrać danych produktów z API Malfini.'
                );
            }
            error_log('Malfini importer: Pobrano dane produktów: ' . count($products_data) . ' produktów');

            // Pobierz dane stanów magazynowych
            error_log('Malfini importer: Pobieranie danych stanów magazynowych...');
            $stocks_data = $this->get_stocks_data();
            if (!$stocks_data) {
                error_log('Malfini importer: Nie można pobrać danych stanów magazynowych z API Malfini');
                $stocks_data = array();
            }
            error_log('Malfini importer: Pobrano dane stanów magazynowych: ' . count($stocks_data) . ' rekordów');

            // Pobierz dane kategorii
            error_log('Malfini importer: Pobieranie danych kategorii...');
            $categories_data = $this->get_categories_data();
            if (!$categories_data) {
                error_log('Malfini importer: Nie można pobrać danych kategorii z API Malfini');
                $categories_data = array();
            }
            error_log('Malfini importer: Pobrano dane kategorii: ' . count($categories_data) . ' kategorii');

            // Importuj kategorie
            if (isset($this->settings['import_categories']) && $this->settings['import_categories']) {
                $this->import_categories($categories_data);
            }

            // Ustaw całkowitą liczbę produktów
            $this->total_count = count($products_data);
            error_log('Malfini importer: Całkowita liczba produktów: ' . $this->total_count);

            // Importuj produkty
            $limit = isset($this->settings['import_limit']) && $this->settings['import_limit'] > 0 ? $this->settings['import_limit'] : 0;
            $count = 0;

            foreach ($products_data as $product_data) {
                // Sprawdź limit importu
                if ($limit > 0 && $count >= $limit) {
                    error_log('Malfini importer: Osiągnięto limit importu: ' . $limit);
                    break;
                }

                // Importuj produkt
                $this->import_product($product_data, $categories_data, $stocks_data);
                $count++;

                // Loguj postęp co 10 produktów
                if ($count % 10 === 0) {
                    error_log('Malfini importer: Zaimportowano ' . $count . ' z ' . $this->total_count . ' produktów');
                }
            }

            error_log('Malfini importer: Import zakończony. Zaimportowano: ' . $this->imported_count . ', Zaktualizowano: ' . $this->updated_count . ', Pominięto: ' . $this->skipped_count . ', Błędy: ' . $this->error_count);

            // Upewnij się, że zwracamy poprawną strukturę danych
            return array(
                'success' => true,
                'message' => 'Import zakończony pomyślnie.',
                'stats' => array(
                    'total' => $this->total_count,
                    'imported' => $this->imported_count,
                    'updated' => $this->updated_count,
                    'skipped' => $this->skipped_count,
                    'errors' => $this->error_count
                )
            );
        } catch (Exception $e) {
            error_log('Malfini importer: Błąd podczas importu produktów: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return array(
                'success' => false,
                'message' => 'Wystąpił błąd podczas importu: ' . $e->getMessage()
            );
        }
    }

    /**
     * Uwierzytelnia w API Malfini
     *
     * @since    1.0.1
     * @return   boolean    Czy uwierzytelnienie się powiodło
     */
    private function authenticate()
    {
        error_log('Malfini importer: Rozpoczynam uwierzytelnianie');

        // Sprawdź, czy mamy zapisany token
        $token_data = get_transient('kc_hurtownie_malfini_token');
        if ($token_data) {
            error_log('Malfini importer: Znaleziono zapisany token');
            $token_data = json_decode($token_data, true);
            $this->access_token = $token_data['access_token'];
            $this->refresh_token = $token_data['refresh_token'];

            // Sprawdź, czy token nie wygasł
            if (time() < $token_data['expires_at']) {
                error_log('Malfini importer: Token jest ważny, wygasa za ' . ($token_data['expires_at'] - time()) . ' sekund');
                return true;
            }

            error_log('Malfini importer: Token wygasł, próba odświeżenia');
            // Spróbuj odświeżyć token
            if ($this->refresh_token()) {
                error_log('Malfini importer: Token odświeżony pomyślnie');
                return true;
            }
            error_log('Malfini importer: Nie udało się odświeżyć tokenu, próba nowego uwierzytelnienia');
        }

        // Pobierz dane uwierzytelniające
        $api_url = isset($this->settings['hurtownia6_api_url']) ? rtrim($this->settings['hurtownia6_api_url'], '/') : 'https://api.malfini.com';
        $username = isset($this->settings['hurtownia6_username']) ? $this->settings['hurtownia6_username'] : '';
        $password = isset($this->settings['hurtownia6_password']) ? $this->settings['hurtownia6_password'] : '';

        error_log('Malfini importer: Dane uwierzytelniające - API URL: ' . $api_url . ', Username: ' . $username);

        if (empty($username) || empty($password)) {
            error_log('Malfini importer: Brak nazwy użytkownika lub hasła dla Malfini.');
            return false;
        }

        // Przygotuj dane do uwierzytelnienia
        $auth_url = $api_url . '/api/v4/api-auth/login';
        $auth_data = array(
            'username' => $username,
            'password' => $password
        );

        error_log('Malfini importer: Wysyłam żądanie uwierzytelnienia do: ' . $auth_url);

        // Wykonaj żądanie
        $response = wp_remote_post($auth_url, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($auth_data),
            'timeout' => 30,
            'sslverify' => false
        ));

        // Sprawdź, czy wystąpił błąd
        if (is_wp_error($response)) {
            error_log('Malfini importer: Błąd uwierzytelniania w API Malfini: ' . $response->get_error_message());
            return false;
        }

        // Pobierz kod odpowiedzi
        $response_code = wp_remote_retrieve_response_code($response);
        error_log('Malfini importer: Kod odpowiedzi uwierzytelniania: ' . $response_code);

        // Pobierz treść odpowiedzi
        $body = wp_remote_retrieve_body($response);
        error_log('Malfini importer: Treść odpowiedzi uwierzytelniania: ' . $body);

        $data = json_decode($body, true);

        // Sprawdź, czy otrzymaliśmy token
        if (!isset($data['access_token'])) {
            error_log('Malfini importer: Nie otrzymano tokenu z API Malfini: ' . print_r($data, true));
            return false;
        }

        error_log('Malfini importer: Otrzymano token dostępu');

        // Zapisz tokeny
        $this->access_token = $data['access_token'];
        $this->refresh_token = $data['refresh_token'];

        // Zapisz dane tokenu w transient
        $token_data = array(
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_at' => time() + $data['expires_in']
        );
        set_transient('kc_hurtownie_malfini_token', json_encode($token_data), 86400); // Ważny przez 24 godziny

        error_log('Malfini importer: Token zapisany, wygasa za ' . $data['expires_in'] . ' sekund');
        return true;
    }

    /**
     * Odświeża token dostępu
     *
     * @since    1.0.1
     * @return   boolean    Czy odświeżenie tokenu się powiodło
     */
    private function refresh_token()
    {
        if (empty($this->refresh_token)) {
            return false;
        }

        $api_url = isset($this->settings['hurtownia6_api_url']) ? rtrim($this->settings['hurtownia6_api_url'], '/') : 'https://api.malfini.com';
        $refresh_url = $api_url . '/api/v4/api-auth/refresh';

        // Przygotuj dane do odświeżenia tokenu
        $refresh_data = array(
            'refreshToken' => $this->refresh_token
        );

        // Wykonaj żądanie
        $response = wp_remote_post($refresh_url, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($refresh_data),
            'timeout' => 30,
            'sslverify' => false
        ));

        // Sprawdź, czy wystąpił błąd
        if (is_wp_error($response)) {
            error_log('Błąd odświeżania tokenu w API Malfini: ' . $response->get_error_message());
            return false;
        }

        // Pobierz treść odpowiedzi
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Sprawdź, czy otrzymaliśmy nowy token
        if (!isset($data['access_token'])) {
            error_log('Nie otrzymano nowego tokenu z API Malfini: ' . print_r($data, true));
            return false;
        }

        // Zapisz nowe tokeny
        $this->access_token = $data['access_token'];
        $this->refresh_token = $data['refresh_token'];

        // Zapisz dane tokenu w transient
        $token_data = array(
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_at' => time() + $data['expires_in']
        );
        set_transient('kc_hurtownie_malfini_token', json_encode($token_data), 86400); // Ważny przez 24 godziny

        return true;
    }

    /**
     * Pobiera przykładowe dane testowe
     *
     * @since    1.0.1
     * @return   array    Przykładowe dane testowe
     */
    private function get_test_data()
    {
        // Przykładowe dane produktów
        $products = array(
            array(
                'id' => 1,
                'code' => 'MALFINI-001',
                'name' => 'Koszulka testowa 1',
                'description' => 'Opis koszulki testowej 1'
            ),
            array(
                'id' => 2,
                'code' => 'MALFINI-002',
                'name' => 'Koszulka testowa 2',
                'description' => 'Opis koszulki testowej 2'
            ),
            array(
                'id' => 3,
                'code' => 'MALFINI-003',
                'name' => 'Koszulka testowa 3',
                'description' => 'Opis koszulki testowej 3'
            )
        );

        return $products;
    }

    /**
     * Pobiera przykładowe dane stanów magazynowych
     *
     * @since    1.0.1
     * @return   array    Przykładowe dane stanów magazynowych
     */
    private function get_test_stocks_data()
    {
        // Przykładowe dane stanów magazynowych
        $stocks = array(
            array(
                'product_id' => 1,
                'quantity' => 100
            ),
            array(
                'product_id' => 2,
                'quantity' => 50
            ),
            array(
                'product_id' => 3,
                'quantity' => 75
            )
        );

        return $stocks;
    }

    /**
     * Pobiera przykładowe dane kategorii
     *
     * @since    1.0.1
     * @return   array    Przykładowe dane kategorii
     */
    private function get_test_categories_data()
    {
        // Przykładowe dane kategorii
        $categories = array(
            array(
                'id' => 1,
                'name' => 'Koszulki',
                'parent_id' => 0
            ),
            array(
                'id' => 2,
                'name' => 'Bluzy',
                'parent_id' => 0
            ),
            array(
                'id' => 3,
                'name' => 'Koszulki polo',
                'parent_id' => 1
            )
        );

        return $categories;
    }

    /**
     * Pobiera dane produktów z API
     *
     * @since    1.0.1
     * @return   array|false    Dane produktów lub false w przypadku błędu
     */
    private function get_products_data()
    {
        // W trybie testowym zwróć przykładowe dane
        if (defined('KC_HURTOWNIE_TEST_MODE') && KC_HURTOWNIE_TEST_MODE) {
            error_log('Malfini importer: Używanie danych testowych');
            return $this->get_test_data();
        }

        return $this->get_api_data('/api/v4/product');
    }

    /**
     * Pobiera dane stanów magazynowych z API
     *
     * @since    1.0.1
     * @return   array|false    Dane stanów magazynowych lub false w przypadku błędu
     */
    private function get_stocks_data()
    {
        // W trybie testowym zwróć przykładowe dane
        if (defined('KC_HURTOWNIE_TEST_MODE') && KC_HURTOWNIE_TEST_MODE) {
            error_log('Malfini importer: Używanie testowych danych stanów magazynowych');
            return $this->get_test_stocks_data();
        }

        // Endpoint dla stanów magazynowych zgodnie z dokumentacją
        return $this->get_api_data('/api/v4/stock');
    }

    /**
     * Pobiera dane kategorii z API
     *
     * @since    1.0.1
     * @return   array|false    Dane kategorii lub false w przypadku błędu
     */
    private function get_categories_data()
    {
        // W trybie testowym zwróć przykładowe dane
        if (defined('KC_HURTOWNIE_TEST_MODE') && KC_HURTOWNIE_TEST_MODE) {
            error_log('Malfini importer: Używanie testowych danych kategorii');
            return $this->get_test_categories_data();
        }

        // Endpoint dla kategorii - może nie być dostępny w API, ale spróbujmy
        return $this->get_api_data('/api/v4/category');
    }

    /**
     * Pobiera dane z API Malfini
     *
     * @since    1.0.1
     * @param    string    $endpoint    Endpoint API
     * @return   array|false            Dane z API lub false w przypadku błędu
     */
    private function get_api_data($endpoint)
    {
        error_log('Malfini importer: Pobieranie danych z API dla endpointu: ' . $endpoint);

        // Sprawdź, czy mamy token
        if (empty($this->access_token)) {
            error_log('Malfini importer: Brak tokenu dostępu, próba uwierzytelnienia');
            if (!$this->authenticate()) {
                error_log('Malfini importer: Uwierzytelnienie nie powiodło się');
                return false;
            }
            error_log('Malfini importer: Uwierzytelnienie powiodło się, token: ' . substr($this->access_token, 0, 10) . '...');
        }

        // Przygotuj URL
        $api_url = isset($this->settings['hurtownia6_api_url']) ? rtrim($this->settings['hurtownia6_api_url'], '/') : 'https://api.malfini.com';

        // Dodaj parametr language=pl, jeśli nie jest już obecny
        if (strpos($endpoint, 'language=') === false) {
            $endpoint .= (strpos($endpoint, '?') === false ? '?' : '&') . 'language=pl';
        }

        $url = $api_url . $endpoint;
        error_log('Malfini importer: URL żądania: ' . $url);

        // Sprawdź, czy mamy dane w cache
        $cache_key = 'kc_hurtownie_malfini_' . md5($endpoint);
        $cached_data = get_transient($cache_key);

        if ($cached_data) {
            error_log('Malfini importer: Znaleziono dane w cache dla endpointu: ' . $endpoint);
            return $cached_data;
        }

        error_log('Malfini importer: Wykonywanie żądania GET do: ' . $url);
        // Wykonaj żądanie
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Accept' => 'application/json'
            ),
            'timeout' => 60,
            'sslverify' => false
        ));

        // Sprawdź, czy wystąpił błąd
        if (is_wp_error($response)) {
            error_log('Malfini importer: Błąd pobierania danych z API: ' . $response->get_error_message());
            return false;
        }

        // Sprawdź kod odpowiedzi
        $response_code = wp_remote_retrieve_response_code($response);
        error_log('Malfini importer: Kod odpowiedzi: ' . $response_code);

        // Jeśli token wygasł (401), spróbuj odświeżyć token i ponowić żądanie
        if ($response_code === 401) {
            error_log('Malfini importer: Token wygasł, próba odświeżenia');
            if ($this->refresh_token()) {
                error_log('Malfini importer: Token odświeżony, ponowienie żądania');
                // Ponów żądanie z nowym tokenem
                $response = wp_remote_get($url, array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $this->access_token,
                        'Accept' => 'application/json'
                    ),
                    'timeout' => 60,
                    'sslverify' => false
                ));

                if (is_wp_error($response)) {
                    error_log('Malfini importer: Błąd pobierania danych z API po odświeżeniu tokenu: ' . $response->get_error_message());
                    return false;
                }

                $response_code = wp_remote_retrieve_response_code($response);
                error_log('Malfini importer: Kod odpowiedzi po odświeżeniu tokenu: ' . $response_code);
            } else {
                error_log('Malfini importer: Nie udało się odświeżyć tokenu');
            }
        }

        // Jeśli nadal mamy błąd, zwróć false
        if ($response_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            error_log('Malfini importer: Błąd API: ' . $response_code . ' ' . wp_remote_retrieve_response_message($response));
            error_log('Malfini importer: Treść odpowiedzi: ' . $body);
            error_log('Malfini importer: Nagłówki odpowiedzi: ' . print_r(wp_remote_retrieve_headers($response), true));

            // Zapisz odpowiedź do pliku dla debugowania
            $upload_dir = wp_upload_dir();
            $debug_dir = $upload_dir['basedir'] . '/kc-hurtownie/malfini/debug';
            if (!file_exists($debug_dir)) {
                wp_mkdir_p($debug_dir);
            }
            $debug_file = $debug_dir . '/error_' . time() . '.txt';
            file_put_contents($debug_file, "URL: " . $url . "\nKod: " . $response_code . "\nOdpowiedź: " . $body . "\nNagłówki: " . print_r(wp_remote_retrieve_headers($response), true));

            return false;
        }

        // Pobierz treść odpowiedzi
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Sprawdź, czy otrzymaliśmy dane
        if (!$data) {
            error_log('Malfini importer: Nie otrzymano danych z API: ' . $body);
            return false;
        }

        error_log('Malfini importer: Otrzymano dane z API dla endpointu: ' . $endpoint . ', liczba elementów: ' . (is_array($data) ? count($data) : 'nie jest tablicą'));

        // Zapisz dane w cache
        set_transient($cache_key, $data, 3600); // Ważne przez 1 godzinę

        // Zapisz dane do pliku dla debugowania
        $upload_dir = wp_upload_dir();
        $debug_dir = $upload_dir['basedir'] . '/kc-hurtownie/malfini/debug';
        if (!file_exists($debug_dir)) {
            wp_mkdir_p($debug_dir);
        }
        $debug_file = $debug_dir . '/response_' . md5($endpoint) . '.json';
        file_put_contents($debug_file, $body);

        return $data;
    }

    /**
     * Importuje kategorie produktów
     *
     * @since    1.0.1
     * @param    array    $categories_data    Dane kategorii
     */
    private function import_categories($categories_data)
    {
        // Implementacja importu kategorii
        // ...
    }

    /**
     * Importuje pojedynczy produkt
     *
     * @since    1.0.1
     * @param    array    $product_data      Dane produktu
     * @param    array    $categories_data   Dane kategorii
     * @param    array    $stocks_data       Dane stanów magazynowych
     * @return   void
     */
    private function import_product($product_data, $categories_data, $stocks_data)
    {
        try {
            // Sprawdź, czy produkt ma wszystkie wymagane dane
            if (!isset($product_data['id']) || !isset($product_data['name'])) {
                $this->error_count++;
                error_log('Malfini importer: Brak wymaganych danych produktu: ' . print_r($product_data, true));
                return;
            }

            // Pobierz identyfikator produktu
            $product_id = $product_data['id'];
            $product_name = isset($product_data['name']) ? $product_data['name'] : '';
            $product_sku = isset($product_data['code']) ? $product_data['code'] : '';

            // Sprawdź, czy produkt już istnieje
            $existing_product_id = $this->get_product_by_sku($product_sku);

            if ($existing_product_id) {
                // Aktualizuj istniejący produkt
                $this->update_product($existing_product_id, $product_data, $categories_data, $stocks_data);
                $this->updated_count++;
            } else {
                // Utwórz nowy produkt
                $this->create_product($product_data, $categories_data, $stocks_data);
                $this->imported_count++;
            }
        } catch (Exception $e) {
            $this->error_count++;
            error_log('Malfini importer: Błąd podczas importu produktu: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    /**
     * Tworzy nowy produkt
     *
     * @since    1.0.1
     * @param    array    $product_data      Dane produktu
     * @param    array    $categories_data   Dane kategorii
     * @param    array    $stocks_data       Dane stanów magazynowych
     * @return   int                         ID utworzonego produktu
     */
    private function create_product($product_data, $categories_data, $stocks_data)
    {
        // Podstawowa implementacja - w przyszłości można rozbudować
        error_log('Malfini importer: Tworzenie nowego produktu: ' . $product_data['name']);

        // Symulacja utworzenia produktu
        return 0;
    }

    /**
     * Aktualizuje istniejący produkt
     *
     * @since    1.0.1
     * @param    int      $product_id        ID produktu
     * @param    array    $product_data      Dane produktu
     * @param    array    $categories_data   Dane kategorii
     * @param    array    $stocks_data       Dane stanów magazynowych
     * @return   void
     */
    private function update_product($product_id, $product_data, $categories_data, $stocks_data)
    {
        // Podstawowa implementacja - w przyszłości można rozbudować
        error_log('Malfini importer: Aktualizacja produktu: ' . $product_data['name']);
    }

    /**
     * Pobiera ID produktu na podstawie SKU
     *
     * @since    1.0.1
     * @param    string    $sku    SKU produktu
     * @return   int               ID produktu lub 0 jeśli nie znaleziono
     */
    private function get_product_by_sku($sku)
    {
        global $wpdb;

        $product_id = $wpdb->get_var($wpdb->prepare("
            SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_sku' AND meta_value = %s LIMIT 1
        ", $sku));

        return $product_id ? $product_id : 0;
    }

    /**
     * Pobiera dane zdjęć produktu
     *
     * @since    1.0.1
     * @param    string    $product_id    ID produktu
     * @return   array                    Dane zdjęć
     */
    private function get_product_images($product_id)
    {
        return $this->get_api_data('/api/v4/product/' . $product_id . '/image');
    }

    /**
     * Pobiera dane wariantów produktu
     *
     * @since    1.0.1
     * @param    string    $product_id    ID produktu
     * @return   array                    Dane wariantów
     */
    private function get_product_variants($product_id)
    {
        return $this->get_api_data('/api/v4/product/' . $product_id . '/variant');
    }
}