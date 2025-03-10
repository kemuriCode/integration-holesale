<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://kemuri.codes
 * @since      1.0.0
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/admin
 * @author     Marcin Dymek <contact@kemuri.codes>
 */
class Kc_Hurtownie_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Kc_Hurtownie_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Kc_Hurtownie_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/kc-hurtownie-admin.css', array(), $this->version, 'all');

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Kc_Hurtownie_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Kc_Hurtownie_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/kc-hurtownie-admin.js', array('jquery'), $this->version, false);

	}

	/**
	 * Dodaje menu administracyjne dla wtyczki.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu()
	{
		// Dodaj stronę główną
		add_menu_page(
			'KC Hurtownie', // Tytuł strony
			'KC Hurtownie', // Tekst w menu
			'manage_options', // Uprawnienia
			$this->plugin_name, // Slug
			array($this, 'display_plugin_admin_page'), // Callback
			'dashicons-cart', // Ikona
			26 // Pozycja w menu
		);

		// Dodaj podstronę ustawień
		add_submenu_page(
			$this->plugin_name, // Rodzic
			'Ustawienia KC Hurtownie', // Tytuł strony
			'Ustawienia', // Tekst w menu
			'manage_options', // Uprawnienia
			$this->plugin_name . '-settings', // Slug
			array($this, 'display_plugin_admin_settings_page') // Callback
		);

		// Dodaj podstronę importu
		add_submenu_page(
			$this->plugin_name, // Rodzic
			'Import produktów', // Tytuł strony
			'Import produktów', // Tekst w menu
			'manage_options', // Uprawnienia
			$this->plugin_name . '-import', // Slug
			array($this, 'display_plugin_admin_import_page') // Callback
		);
	}

	/**
	 * Wyświetla główną stronę wtyczki
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page()
	{
		require_once plugin_dir_path(__FILE__) . 'partials/kc-hurtownie-admin-display.php';
	}

	/**
	 * Wyświetla stronę ustawień wtyczki
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_settings_page()
	{
		require_once plugin_dir_path(__FILE__) . 'partials/kc-hurtownie-admin-settings.php';
	}

	/**
	 * Wyświetla stronę importu produktów
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_import_page()
	{
		require_once plugin_dir_path(__FILE__) . 'partials/kc-hurtownie-admin-import.php';
	}

	/**
	 * Rejestruje ustawienia wtyczki
	 *
	 * @since    1.0.0
	 */
	public function register_settings()
	{
		register_setting(
			'kc_hurtownie_settings',
			'kc_hurtownie_settings',
			array(
				'sanitize_callback' => array($this, 'sanitize_settings')
			)
		);
	}

	/**
	 * Sanityzuje ustawienia przed zapisem
	 */
	public function sanitize_settings($input)
	{
		$sanitized = array();

		// Dla każdego pola w ustawieniach
		foreach ($input as $key => $value) {
			switch ($key) {
				// Dla adresów FTP - usuń http:// i https://
				case 'hurtownia4_ftp_host':
				case 'hurtownia1_ftp_host':
					$sanitized[$key] = preg_replace('#^https?://#', '', $value);
					break;

				// Dla ścieżek FTP - usuń początkowy slash i dodaj końcowy jeśli potrzeba
				case 'hurtownia4_ftp_path':
				case 'hurtownia1_ftp_path':
					$sanitized[$key] = trim($value, '/');
					if (!empty($sanitized[$key])) {
						$sanitized[$key] = '/' . $sanitized[$key] . '/';
					}
					break;

				// Dla adresów API - upewnij się że jest http:// lub https://
				case 'hurtownia2_api_url':
				case 'hurtownia3_api_url':
					if (!preg_match('#^https?://#', $value)) {
						$sanitized[$key] = 'http://' . $value;
					} else {
						$sanitized[$key] = $value;
					}
					break;

				// Dla pozostałych pól - standardowa sanityzacja
				default:
					$sanitized[$key] = sanitize_text_field($value);
					break;
			}
		}

		return $sanitized;
	}

	/**
	 * Inicjalizuje zadania cron dla importu produktów.
	 *
	 * @since    1.0.0
	 */
	public function setup_cron_jobs()
	{
		$settings = get_option($this->plugin_name . '-settings');

		if (!$settings) {
			return;
		}

		// Usuwamy istniejące zadania
		wp_clear_scheduled_hook('kc_hurtownie_import_products');

		// Ustawiamy nowe zadanie zgodnie z harmonogramem
		if (isset($settings['import_schedule']) && $settings['import_schedule'] !== 'manual') {
			$schedule = $settings['import_schedule'];
			if (!wp_next_scheduled('kc_hurtownie_import_products')) {
				wp_schedule_event(time(), $schedule, 'kc_hurtownie_import_products');
			}
		}
	}

	/**
	 * Pobiera dane produktów z FTP hurtowni.
	 *
	 * @since    1.0.0
	 * @param    string    $hurtownia_id    ID hurtowni.
	 * @return   mixed                      Dane XML lub false w przypadku błędu.
	 */
	public function fetch_products_data($hurtownia_id)
	{
		$settings = get_option($this->plugin_name . '-settings');

		if (!$settings || !isset($settings[$hurtownia_id . '_enabled']) || !$settings[$hurtownia_id . '_enabled']) {
			return false;
		}

		$ftp_host = $settings[$hurtownia_id . '_ftp_host'];
		$ftp_user = $settings[$hurtownia_id . '_ftp_user'];
		$ftp_pass = $settings[$hurtownia_id . '_ftp_pass'];
		$ftp_path = $settings[$hurtownia_id . '_ftp_path'];

		// Tworzymy tymczasowy plik
		$temp_file = wp_tempnam('kc-hurtownie-import');

		// Pobieramy plik przez FTP
		$conn_id = ftp_connect($ftp_host);
		if (!$conn_id) {
			return false;
		}

		$login_result = ftp_login($conn_id, $ftp_user, $ftp_pass);
		if (!$login_result) {
			ftp_close($conn_id);
			return false;
		}

		// Ustawiamy tryb pasywny
		ftp_pasv($conn_id, true);

		// Pobieramy plik
		if (!ftp_get($conn_id, $temp_file, $ftp_path, FTP_BINARY)) {
			ftp_close($conn_id);
			unlink($temp_file);
			return false;
		}

		ftp_close($conn_id);

		// Wczytujemy zawartość pliku
		$xml_content = file_get_contents($temp_file);
		unlink($temp_file);

		// Parsujemy XML
		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($xml_content);

		if (!$xml) {
			return false;
		}

		return $xml;
	}

	/**
	 * Pobiera obrazek produktu z FTP hurtowni.
	 *
	 * @since    1.0.0
	 * @param    string    $hurtownia_id    ID hurtowni.
	 * @param    string    $image_path      Ścieżka do obrazka.
	 * @return   string                     Ścieżka do pobranego obrazka lub pusty string.
	 */
	public function fetch_product_image($hurtownia_id, $image_path)
	{
		$settings = get_option($this->plugin_name . '-settings');

		if (!$settings || !isset($settings[$hurtownia_id . '_enabled']) || !$settings[$hurtownia_id . '_enabled']) {
			return '';
		}

		$ftp_host = $settings[$hurtownia_id . '_ftp_host'];
		$ftp_user = $settings[$hurtownia_id . '_ftp_user'];
		$ftp_pass = $settings[$hurtownia_id . '_ftp_pass'];
		$images_path = $settings[$hurtownia_id . '_images_path'];

		// Tworzymy tymczasowy plik
		$temp_file = wp_tempnam('kc-hurtownie-image');

		// Pobieramy plik przez FTP
		$conn_id = ftp_connect($ftp_host);
		if (!$conn_id) {
			return '';
		}

		$login_result = ftp_login($conn_id, $ftp_user, $ftp_pass);
		if (!$login_result) {
			ftp_close($conn_id);
			return '';
		}

		// Ustawiamy tryb pasywny
		ftp_pasv($conn_id, true);

		// Pełna ścieżka do obrazka
		$full_image_path = rtrim($images_path, '/') . '/' . $image_path;

		// Pobieramy plik
		if (!ftp_get($conn_id, $temp_file, $full_image_path, FTP_BINARY)) {
			ftp_close($conn_id);
			unlink($temp_file);
			return '';
		}

		ftp_close($conn_id);

		return $temp_file;
	}

	/**
	 * Importuje produkty z wybranej hurtowni.
	 *
	 * @since    1.0.0
	 * @param    string    $hurtownia_id    ID hurtowni.
	 * @return   array                      Wyniki importu.
	 */
	public function import_products($hurtownia_id)
	{
		$settings = get_option($this->plugin_name . '-settings');

		if (!$settings || !isset($settings[$hurtownia_id . '_enabled']) || !$settings[$hurtownia_id . '_enabled']) {
			return array(
				'success' => false,
				'message' => 'Hurtownia nie jest włączona.'
			);
		}

		if ($hurtownia_id === 'hurtownia1') {
			// Obsługa importu z hurtowni 1
			// ...
			return array(
				'success' => false,
				'message' => 'Import z hurtowni 1 nie jest jeszcze zaimplementowany.'
			);
		} elseif ($hurtownia_id === 'hurtownia2') {
			// Obsługa importu z hurtowni AXPOL
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kc-hurtownie-axpol-importer.php';
			$importer = new Kc_Hurtownie_Axpol_Importer($settings);
			return $importer->import();
		} elseif ($hurtownia_id === 'hurtownia3') {
			// Obsługa importu z hurtowni PAR
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kc-hurtownie-par-importer.php';
			$importer = new Kc_Hurtownie_Par_Importer($settings);
			return $importer->import();
		} elseif ($hurtownia_id === 'hurtownia4') {
			// Obsługa importu z hurtowni Inspirion
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kc-hurtownie-inspirion-importer.php';
			$importer = new Kc_Hurtownie_Inspirion_Importer($settings);
			return $importer->import();
		} elseif ($hurtownia_id === 'hurtownia5') {
			// Obsługa importu z hurtowni Macma
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kc-hurtownie-macma-importer.php';
			$importer = new Kc_Hurtownie_Macma_Importer($settings);
			return $importer->import();
		} else {
			return array(
				'success' => false,
				'message' => 'Nieznana hurtownia.'
			);
		}
	}

	/**
	 * Tworzy atrybut produktu.
	 *
	 * @since    1.0.0
	 * @param    string    $name     Nazwa atrybutu.
	 * @param    string    $value    Wartość atrybutu.
	 * @return   object              Obiekt atrybutu.
	 */
	private function create_attribute($name, $value)
	{
		$attribute = new WC_Product_Attribute();
		$attribute->set_name($name);
		$attribute->set_options(array($value));
		$attribute->set_visible(true);
		$attribute->set_variation(false);

		return $attribute;
	}

	/**
	 * Pobiera lub tworzy kategorię produktu.
	 *
	 * @since    1.0.0
	 * @param    string    $name        Nazwa kategorii.
	 * @param    int       $parent_id   ID kategorii nadrzędnej.
	 * @return   int                    ID kategorii.
	 */
	private function get_or_create_category($name, $parent_id = 0)
	{
		// Sprawdzamy czy kategoria już istnieje
		$term = get_term_by('name', $name, 'product_cat');

		if ($term) {
			return $term->term_id;
		}

		// Tworzymy nową kategorię
		$term = wp_insert_term(
			$name,
			'product_cat',
			array(
				'parent' => $parent_id
			)
		);

		if (is_wp_error($term)) {
			return 0;
		}

		return $term['term_id'];
	}

	/**
	 * Wgrywa obrazek do biblioteki mediów.
	 *
	 * @since    1.0.0
	 * @param    string    $file_path    Ścieżka do pliku.
	 * @param    string    $title        Tytuł obrazka.
	 * @return   int                     ID obrazka.
	 */
	private function upload_image_to_media_library($file_path, $title)
	{
		// Sprawdzamy czy plik istnieje
		if (!file_exists($file_path)) {
			return 0;
		}

		// Przygotowujemy dane do wgrania
		$file_name = basename($file_path);
		$upload_file = wp_upload_bits($file_name, null, file_get_contents($file_path));

		if ($upload_file['error']) {
			return 0;
		}

		// Przygotowujemy dane załącznika
		$wp_filetype = wp_check_filetype($file_name, null);
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => sanitize_file_name($title),
			'post_content' => '',
			'post_status' => 'inherit'
		);

		// Wgrywamy załącznik
		$attachment_id = wp_insert_attachment($attachment, $upload_file['file']);

		if (!$attachment_id) {
			return 0;
		}

		// Generujemy metadane
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
		wp_update_attachment_metadata($attachment_id, $attachment_data);

		return $attachment_id;
	}

	/**
	 * Obsługuje żądanie AJAX importu produktów.
	 *
	 * @since    1.0.0
	 */
	public function handle_ajax_import()
	{
		// Sprawdź uprawnienia
		if (!current_user_can('manage_options')) {
			wp_send_json_error('Nie masz uprawnień do wykonania tej akcji.');
			return;
		}

		// Sprawdź nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'kc_hurtownie_import')) {
			wp_send_json_error('Nieprawidłowy token bezpieczeństwa.');
			return;
		}

		// Sprawdź, czy wybrano hurtownię
		if (!isset($_POST['hurtownia_id']) || empty($_POST['hurtownia_id'])) {
			wp_send_json_error('Nie wybrano hurtowni.');
			return;
		}

		$hurtownia_id = sanitize_text_field($_POST['hurtownia_id']);

		// Importuj produkty
		$result = $this->import_products($hurtownia_id);

		if ($result['success']) {
			// Zapisz czas ostatniego importu
			$last_import = get_option($this->plugin_name . '-last-import', array());
			$last_import[$hurtownia_id] = time();
			update_option($this->plugin_name . '-last-import', $last_import);

			wp_send_json_success($result['data']);
		} else {
			wp_send_json_error($result['message']);
		}
	}

	/**
	 * Rejestruje hooki dla wtyczki.
	 *
	 * @since    1.0.0
	 */
	public function register_hooks()
	{
		// Dodajemy menu administracyjne
		add_action('admin_menu', array($this, 'add_plugin_admin_menu'));

		// Rejestrujemy ustawienia
		add_action('admin_init', array($this, 'register_settings'));

		// Obsługujemy akcje AJAX
		add_action('wp_ajax_kc_hurtownie_import', array($this, 'handle_ajax_import'));
		add_action('wp_ajax_kc_hurtownie_test_ftp', array($this, 'test_connection'));
		add_action('wp_ajax_kc_hurtownie_test_api', array($this, 'test_api_connection'));

		// Ustawiamy zadania cron
		add_action('admin_init', array($this, 'setup_cron_jobs'));

		// Obsługujemy import przez cron
		add_action('kc_hurtownie_import_products', array($this, 'cron_import_products'));
	}

	/**
	 * Importuje produkty przez zadanie cron.
	 *
	 * @since    1.0.0
	 */
	public function cron_import_products()
	{
		$settings = get_option($this->plugin_name . '-settings');
		$last_import = get_option($this->plugin_name . '-last-import', array());

		if (!$settings) {
			return;
		}

		// Importujemy produkty z włączonych hurtowni
		if (isset($settings['hurtownia1_enabled']) && $settings['hurtownia1_enabled']) {
			$result = $this->import_products('hurtownia1');
			if ($result['success']) {
				$last_import['hurtownia1'] = time();
			}
		}

		if (isset($settings['hurtownia2_enabled']) && $settings['hurtownia2_enabled']) {
			$result = $this->import_products('hurtownia2');
			if ($result['success']) {
				$last_import['hurtownia2'] = time();
			}
		}

		if (isset($settings['hurtownia3_enabled']) && $settings['hurtownia3_enabled']) {
			$result = $this->import_products('hurtownia3');
			if ($result['success']) {
				$last_import['hurtownia3'] = time();
			}
		}

		if (isset($settings['hurtownia4_enabled']) && $settings['hurtownia4_enabled']) {
			$result = $this->import_products('hurtownia4');
			if ($result['success']) {
				$last_import['hurtownia4'] = time();
			}
		}

		if (isset($settings['hurtownia5_enabled']) && $settings['hurtownia5_enabled']) {
			$result = $this->import_products('hurtownia5');
			if ($result['success']) {
				$last_import['hurtownia5'] = time();
			}
		}

		// Zapisz czasy ostatnich importów
		update_option($this->plugin_name . '-last-import', $last_import);
	}

	/**
	 * Inicjalizuje wtyczkę.
	 *
	 * @since    1.0.0
	 */
	public function init()
	{
		$this->register_hooks();
	}

	/**
	 * Testuje połączenie z hurtownią
	 */
	public function test_connection()
	{
		// Sprawdź uprawnienia
		if (!current_user_can('manage_options')) {
			wp_send_json_error('Brak uprawnień');
		}

		// Sprawdź nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'kc_hurtownie_nonce')) {
			wp_send_json_error('Nieprawidłowy token bezpieczeństwa');
		}

		// Pobierz ID hurtowni
		$hurtownia_id = isset($_POST['hurtownia_id']) ? sanitize_text_field($_POST['hurtownia_id']) : '';
		if (empty($hurtownia_id)) {
			wp_send_json_error('Nie podano ID hurtowni');
		}

		// Pobierz ustawienia
		$settings = get_option('kc_hurtownie_settings', array());

		// Przygotuj katalog na pliki
		$upload_dir = wp_upload_dir();

		try {
			switch ($hurtownia_id) {
				case 'hurtownia2': // AXPOL
					if (empty($settings['hurtownia2_ftp_host']) || empty($settings['hurtownia2_ftp_user']) || empty($settings['hurtownia2_ftp_pass'])) {
						wp_send_json_error('Hurtownia AXPOL nie jest skonfigurowana');
					}

					$local_dir = $upload_dir['basedir'] . '/' . (isset($settings['hurtownia2_local_path']) ? $settings['hurtownia2_local_path'] : 'axpol');
					if (!file_exists($local_dir)) {
						wp_mkdir_p($local_dir);
					}

					require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kc-hurtownie-ftp-helper.php';
					$ftp = new Kc_Hurtownie_Ftp_Helper(
						$settings['hurtownia2_ftp_host'],
						$settings['hurtownia2_ftp_user'],
						$settings['hurtownia2_ftp_pass']
					);

					if (!$ftp->connect()) {
						wp_send_json_error('Nie można połączyć się z serwerem FTP AXPOL');
					}

					// Pobierz listę plików
					$files = $ftp->list_files(isset($settings['hurtownia2_ftp_path']) ? $settings['hurtownia2_ftp_path'] : '/');
					$ftp->close();

					wp_send_json_success(array(
						'message' => 'Połączenie z hurtownią AXPOL działa poprawnie',
						'files' => $files ? array_slice($files, 0, 10) : array()
					));
					break;

				case 'hurtownia4': // Inspirion
					if (empty($settings['hurtownia4_ftp_host']) || empty($settings['hurtownia4_ftp_user']) || empty($settings['hurtownia4_ftp_pass'])) {
						wp_send_json_error('Hurtownia Inspirion nie jest skonfigurowana');
					}

					$local_dir = $upload_dir['basedir'] . '/' . (isset($settings['hurtownia4_local_path']) ? $settings['hurtownia4_local_path'] : 'inspirion');
					if (!file_exists($local_dir)) {
						wp_mkdir_p($local_dir);
					}

					require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kc-hurtownie-ftp-helper.php';
					$ftp = new Kc_Hurtownie_Ftp_Helper(
						$settings['hurtownia4_ftp_host'],
						$settings['hurtownia4_ftp_user'],
						$settings['hurtownia4_ftp_pass']
					);

					if (!$ftp->connect()) {
						wp_send_json_error('Nie można połączyć się z serwerem FTP Inspirion');
					}

					// Pobierz listę plików
					$files = $ftp->list_files(isset($settings['hurtownia4_ftp_path']) ? $settings['hurtownia4_ftp_path'] : '/');
					$ftp->close();

					wp_send_json_success(array(
						'message' => 'Połączenie z hurtownią Inspirion działa poprawnie',
						'files' => $files ? array_slice($files, 0, 10) : array()
					));
					break;

				default:
					wp_send_json_error('Nieznane ID hurtowni');
			}
		} catch (Exception $e) {
			wp_send_json_error('Wystąpił błąd podczas testowania połączenia: ' . $e->getMessage());
		}
	}

	/**
	 * Testuje połączenie API
	 *
	 * @since    1.0.0
	 */
	public function test_api_connection()
	{
		// Sprawdź uprawnienia i nonce
		if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'kc_hurtownie_nonce')) {
			wp_send_json_error('Nieprawidłowy token bezpieczeństwa');
			return;
		}

		// Pobierz ID hurtowni
		$hurtownia_id = isset($_POST['hurtownia_id']) ? sanitize_text_field($_POST['hurtownia_id']) : '';

		// Pobierz ustawienia
		$settings = get_option('kc_hurtownie_settings', array());

		// Przygotuj katalog na pliki
		$upload_dir = wp_upload_dir();

		if ($hurtownia_id === 'hurtownia3') { // PAR
			// Sprawdź konfigurację
			if (empty($settings['hurtownia3_api_login']) || empty($settings['hurtownia3_api_password'])) {
				wp_send_json_error('Hurtownia PAR nie jest skonfigurowana');
				return;
			}

			// Użyj mniejszego pliku do testu - stocks zamiast products
			$test_url = 'http://www.par.com.pl/api/stocks';

			// Przygotuj argumenty żądania z autoryzacją
			$args = array(
				'timeout' => 120,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode($settings['hurtownia3_api_login'] . ':' . $settings['hurtownia3_api_password'])
				),
				'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
				'sslverify' => false,
				'httpversion' => '1.1'
			);

			// Wykonaj zapytanie
			$response = wp_remote_get($test_url, $args);

			if (is_wp_error($response)) {
				// Spróbuj jeszcze mniejszy plik - categories
				$test_url = 'http://www.par.com.pl/api/categories';
				$response = wp_remote_get($test_url, $args);

				if (is_wp_error($response)) {
					wp_send_json_error('Błąd połączenia: ' . $response->get_error_message());
					return;
				}
			}

			$code = wp_remote_retrieve_response_code($response);
			if ($code !== 200) {
				wp_send_json_error('Błąd API: ' . $code . ' ' . wp_remote_retrieve_response_message($response));
				return;
			}

			$body = wp_remote_retrieve_body($response);
			if (empty($body)) {
				wp_send_json_error('Pusta odpowiedź z API');
				return;
			}

			// Zapisz plik lokalnie
			$local_dir = $upload_dir['basedir'] . '/' . (isset($settings['hurtownia3_local_path']) ? $settings['hurtownia3_local_path'] : 'par');
			if (!file_exists($local_dir)) {
				wp_mkdir_p($local_dir);
			}

			// Zapisz plik z odpowiednią nazwą
			$filename = strpos($test_url, 'categories') !== false ? 'categories.xml' : 'stocks.xml';
			file_put_contents($local_dir . '/' . $filename, $body);

			wp_send_json_success(array(
				'message' => 'Połączenie z API PAR nawiązane pomyślnie',
				'format' => 'xml',
				'file' => $filename
			));
		} elseif ($hurtownia_id === 'hurtownia5') { // Macma
			// Sprawdź, czy hurtownia jest skonfigurowana
			if (!isset($settings['hurtownia5_api_url']) || empty($settings['hurtownia5_api_url'])) {
				wp_send_json_error('Hurtownia Macma nie jest skonfigurowana');
				return;
			}

			// Przygotuj katalog lokalny
			$local_dir = $upload_dir['basedir'] . '/' . (isset($settings['hurtownia5_local_path']) ? $settings['hurtownia5_local_path'] : 'macma');
			if (!file_exists($local_dir)) {
				wp_mkdir_p($local_dir);
			}

			// Pobierz dane API
			$api_url = $settings['hurtownia5_api_url'];

			// Testuj połączenie z API Macma
			$test_url = rtrim($api_url, '/') . '/stocks.xml';

			// Przygotuj argumenty żądania
			$args = array(
				'timeout' => 120,
				'sslverify' => false,
				'redirection' => 5,
				'httpversion' => '1.1',
				'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
			);

			// Wykonaj żądanie
			$response = wp_remote_get($test_url, $args);

			// Sprawdź, czy wystąpił błąd
			if (is_wp_error($response)) {
				wp_send_json_error('Błąd połączenia z API: ' . $response->get_error_message());
				return;
			}

			// Sprawdź kod odpowiedzi
			$response_code = wp_remote_retrieve_response_code($response);
			if ($response_code !== 200) {
				wp_send_json_error('Błąd API: ' . $response_code . ' ' . wp_remote_retrieve_response_message($response));
				return;
			}

			// Pobierz treść odpowiedzi
			$body = wp_remote_retrieve_body($response);
			if (empty($body)) {
				wp_send_json_error('Pusta odpowiedź z API.');
				return;
			}

			// Sprawdź, czy odpowiedź jest poprawnym XML
			libxml_use_internal_errors(true);
			$xml = simplexml_load_string($body);
			if (!$xml) {
				$errors = libxml_get_errors();
				$error_msg = '';
				foreach ($errors as $error) {
					$error_msg .= $error->message . "\n";
				}
				libxml_clear_errors();
				wp_send_json_error('Błąd parsowania XML: ' . $error_msg);
				return;
			}

			// Zapisz plik lokalnie
			file_put_contents($local_dir . '/stocks.xml', $body);

			// Wszystko OK
			wp_send_json_success(array(
				'message' => 'Połączenie z API Macma nawiązane pomyślnie.',
				'format' => 'xml'
			));
		} else {
			wp_send_json_error('Nieznana hurtownia.');
		}
	}

	/**
	 * Pobiera pełny katalog produktów PAR
	 */
	public function download_par_products()
	{
		// Sprawdź uprawnienia i nonce
		if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'kc_hurtownie_nonce')) {
			wp_send_json_error('Nieprawidłowy token bezpieczeństwa');
			return;
		}

		// Pobierz ustawienia
		$settings = get_option('kc_hurtownie_settings', array());

		// Sprawdź konfigurację
		if (empty($settings['hurtownia3_api_login']) || empty($settings['hurtownia3_api_password'])) {
			wp_send_json_error('Hurtownia PAR nie jest skonfigurowana');
			return;
		}

		// Zwiększ limity wykonania
		set_time_limit(300); // 5 minut
		ini_set('memory_limit', '512M');

		// Przygotuj katalog na pliki
		$upload_dir = wp_upload_dir();
		$local_dir = $upload_dir['basedir'] . '/' . (isset($settings['hurtownia3_local_path']) ? $settings['hurtownia3_local_path'] : 'par');
		if (!file_exists($local_dir)) {
			wp_mkdir_p($local_dir);
		}

		// URL do pełnego katalogu produktów
		$test_url = 'http://www.par.com.pl/api/products';

		// Przygotuj argumenty żądania z autoryzacją
		$args = array(
			'timeout' => 300, // 5 minut
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode($settings['hurtownia3_api_login'] . ':' . $settings['hurtownia3_api_password'])
			),
			'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
			'sslverify' => false,
			'httpversion' => '1.1',
			'stream' => true,
			'filename' => $local_dir . '/products.xml'
		);

		// Wykonaj zapytanie z zapisem bezpośrednio do pliku
		$response = wp_remote_get($test_url, $args);

		if (is_wp_error($response)) {
			wp_send_json_error('Błąd pobierania: ' . $response->get_error_message());
			return;
		}

		$code = wp_remote_retrieve_response_code($response);
		if ($code !== 200) {
			wp_send_json_error('Błąd API: ' . $code . ' ' . wp_remote_retrieve_response_message($response));
			return;
		}

		wp_send_json_success(array(
			'message' => 'Katalog produktów PAR został pomyślnie pobrany',
			'file' => 'products.xml'
		));
	}
}
