<?php

/**
 * Fired during plugin activation
 *
 * @link       https://kemuri.codes
 * @since      1.0.1
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.1
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/includes
 * @author     Marcin Dymek <contact@kemuri.codes>
 */
class Kc_Hurtownie_Activator
{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.1
	 */
	public static function activate()
	{
		// Utwórz katalogi
		self::create_directories();

		// Dodaj domyślne ustawienia, jeśli nie istnieją
		$settings = get_option('kc_hurtownie_settings', array());

		$default_settings = array(
			// AXPOL - używa FTP
			'hurtownia1_enabled' => '0',
			'hurtownia1_ftp_host' => 'ftp.axpol.com.pl', // bez http://
			'hurtownia1_ftp_user' => '',
			'hurtownia1_ftp_pass' => '',
			'hurtownia1_ftp_path' => '/',
			'hurtownia1_ftp_images_host' => 'ftp.axpol.com.pl',
			'hurtownia1_ftp_images_user' => '',
			'hurtownia1_ftp_images_pass' => '',
			'hurtownia1_ftp_images_path' => '/images',

			// Macma - używa API
			'hurtownia2_enabled' => '0',
			'hurtownia2_api_url' => 'http://api.macma.pl', // używa http:// bo to API
			'hurtownia2_api_login' => '',
			'hurtownia2_api_password' => '',
			'hurtownia2_api_format' => 'xml',

			// PAR - używa API
			'hurtownia3_enabled' => '0',
			'hurtownia3_api_url' => 'http://www.par.com.pl/api',
			'hurtownia3_api_login' => '',
			'hurtownia3_api_password' => '',
			'hurtownia3_api_format' => 'xml',

			// Inspirion - używa FTP
			'hurtownia4_enabled' => '0',
			'hurtownia4_ftp_host' => 'ftp.inspirion.pl', // bez http://
			'hurtownia4_ftp_user' => '',
			'hurtownia4_ftp_pass' => '',
			'hurtownia4_ftp_path' => '/',

			// Malfini - używa REST API
			'hurtownia6_enabled' => '0',
			'hurtownia6_api_url' => 'https://api.malfini.com', // używa https:// bo to API
			'hurtownia6_username' => '',
			'hurtownia6_password' => '',
		);

		// Dodaj brakujące ustawienia
		foreach ($default_settings as $key => $value) {
			if (!isset($settings[$key])) {
				$settings[$key] = $value;
			}
		}

		update_option('kc_hurtownie_settings', $settings);
	}

	/**
	 * Tworzy katalogi dla plików hurtowni
	 *
	 * @since    1.0.1
	 */
	private static function create_directories()
	{
		$upload_dir = wp_upload_dir();
		$base_dir = $upload_dir['basedir'];

		// Pobierz ustawienia
		$settings = get_option('kc_hurtownie_settings', array());

		// Domyślne ścieżki
		$default_paths = array(
			'hurtownia1_local_path' => 'kc-hurtownie/axpol',
			'hurtownia2_local_path' => 'kc-hurtownie/macma',
			'hurtownia3_local_path' => 'kc-hurtownie/par',
			'hurtownia4_local_path' => 'kc-hurtownie/inspirion',
			'hurtownia6_local_path' => 'kc-hurtownie/malfini'
		);

		// Utwórz katalog główny
		wp_mkdir_p($base_dir . '/kc-hurtownie');

		// Utwórz katalogi dla każdej hurtowni
		foreach ($default_paths as $key => $path) {
			$dir_path = isset($settings[$key]) ? $settings[$key] : $path;
			$full_path = $base_dir . '/' . $dir_path;

			// Utwórz katalog główny hurtowni
			wp_mkdir_p($full_path);

			// Utwórz katalog na zdjęcia
			wp_mkdir_p($full_path . '/images');

			// Zapisz ścieżkę w ustawieniach, jeśli nie istnieje
			if (!isset($settings[$key])) {
				$settings[$key] = $path;
			}
		}

		// Zapisz ustawienia
		update_option('kc_hurtownie_settings', $settings);
	}

}
