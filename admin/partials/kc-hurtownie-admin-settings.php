<?php
/**
 * Szablon strony ustawień wtyczki
 *
 * @link       https://kemuri.codes
 * @since      1.0.0
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/admin/partials
 */

// Zabezpieczenie przed bezpośrednim dostępem
if (!defined('WPINC')) {
    die;
}

$settings = get_option('kc_hurtownie_settings', array());
error_log('Aktualne ustawienia: ' . print_r($settings, true));
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="options.php">
        <?php
        settings_fields('kc_hurtownie_settings');
        do_settings_sections('kc_hurtownie_settings');

        // Pobierz aktualne ustawienia
        $settings = get_option('kc_hurtownie_settings', array());

        // Dodaj nonce do formularza
        wp_nonce_field('kc_hurtownie_nonce', 'kc_hurtownie_nonce');
        ?>

        <h2 class="nav-tab-wrapper">
            <a href="#tab-hurtownia1" class="nav-tab nav-tab-active">Hurtownia 1</a>
            <a href="#tab-axpol" class="nav-tab">AXPOL</a>
            <a href="#tab-par" class="nav-tab">PAR</a>
            <a href="#tab-inspirion" class="nav-tab">Inspirion</a>
            <a href="#tab-macma" class="nav-tab">Macma</a>
            <a href="#tab-settings" class="nav-tab">Ustawienia importu</a>
        </h2>

        <div id="tab-hurtownia1" class="tab-content" style="display: block;">
            <h3>Ustawienia Hurtowni 1</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Włącz integrację</th>
                    <td>
                        <label>
                            <input type="checkbox" name="kc_hurtownie_settings[hurtownia1_enabled]" value="1" <?php checked(isset($settings['hurtownia1_enabled']) && $settings['hurtownia1_enabled'] == '1'); ?>>
                            Zaznacz, aby włączyć integrację z hurtownią
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Nazwa hurtowni</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia1_name]"
                            value="<?php echo isset($settings['hurtownia1_name']) ? esc_attr($settings['hurtownia1_name']) : 'Hurtownia 1'; ?>"
                            class="regular-text">
                        <p class="description">Nazwa hurtowni wyświetlana w panelu administracyjnym</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Adres serwera FTP</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia1_ftp_host]"
                            value="<?php echo isset($settings['hurtownia1_ftp_host']) ? esc_attr($settings['hurtownia1_ftp_host']) : ''; ?>"
                            class="regular-text">
                        <p class="description">Adres serwera FTP, np. ftp.example.com</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Nazwa użytkownika FTP</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia1_ftp_user]"
                            value="<?php echo isset($settings['hurtownia1_ftp_user']) ? esc_attr($settings['hurtownia1_ftp_user']) : ''; ?>"
                            class="regular-text">
                        <p class="description">Nazwa użytkownika do logowania na serwer FTP</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Hasło FTP</th>
                    <td>
                        <input type="password" name="kc_hurtownie_settings[hurtownia1_ftp_pass]"
                            value="<?php echo isset($settings['hurtownia1_ftp_pass']) ? esc_attr($settings['hurtownia1_ftp_pass']) : ''; ?>"
                            class="regular-text">
                        <p class="description">Hasło do logowania na serwer FTP</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Ścieżka do pliku XML</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia1_ftp_path]"
                            value="<?php echo isset($settings['hurtownia1_ftp_path']) ? esc_attr($settings['hurtownia1_ftp_path']) : ''; ?>"
                            class="regular-text">
                        <p class="description">Ścieżka do pliku XML z produktami na serwerze FTP</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Ścieżka do katalogu ze zdjęciami</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia1_images_path]"
                            value="<?php echo isset($settings['hurtownia1_images_path']) ? esc_attr($settings['hurtownia1_images_path']) : ''; ?>"
                            class="regular-text">
                        <p class="description">Ścieżka do katalogu ze zdjęciami produktów na serwerze FTP</p>
                    </td>
                </tr>
            </table>
        </div>

        <div id="tab-axpol" class="tab-content" style="display: none;">
            <h3>Ustawienia AXPOL</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Włącz integrację</th>
                    <td>
                        <label>
                            <input type="checkbox" name="kc_hurtownie_settings[hurtownia2_enabled]" value="1" <?php checked(isset($settings['hurtownia2_enabled']) && $settings['hurtownia2_enabled'] == '1'); ?>>
                            Zaznacz, aby włączyć integrację z hurtownią AXPOL
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Nazwa hurtowni</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia2_name]"
                            value="<?php echo isset($settings['hurtownia2_name']) ? esc_attr($settings['hurtownia2_name']) : 'AXPOL'; ?>"
                            class="regular-text">
                        <p class="description">Nazwa hurtowni wyświetlana w panelu administracyjnym</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Adres serwera FTP</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia2_ftp_host]"
                            value="<?php echo isset($settings['hurtownia2_ftp_host']) ? esc_attr($settings['hurtownia2_ftp_host']) : 'ftp.axpol.com.pl'; ?>"
                            class="regular-text">
                        <p class="description">Adres serwera FTP z danymi produktów, np. ftp.axpol.com.pl</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Nazwa użytkownika FTP (dane)</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia2_ftp_user]"
                            value="<?php echo isset($settings['hurtownia2_ftp_user']) ? esc_attr($settings['hurtownia2_ftp_user']) : 'userPL017'; ?>"
                            class="regular-text">
                        <p class="description">Nazwa użytkownika do logowania na serwer FTP z danymi produktów</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Hasło FTP (dane)</th>
                    <td>
                        <input type="password" name="kc_hurtownie_settings[hurtownia2_ftp_pass]"
                            value="<?php echo isset($settings['hurtownia2_ftp_pass']) ? esc_attr($settings['hurtownia2_ftp_pass']) : 'vSocD2N8'; ?>"
                            class="regular-text">
                        <p class="description">Hasło do logowania na serwer FTP z danymi produktów</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Ścieżka do pliku XML</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia2_ftp_path]"
                            value="<?php echo isset($settings['hurtownia2_ftp_path']) ? esc_attr($settings['hurtownia2_ftp_path']) : '/file/d/axpol_product_data_PL.xml'; ?>"
                            class="regular-text">
                        <p class="description">Ścieżka do pliku XML z produktami na serwerze FTP</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Ścieżka do katalogu ze zdjęciami</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia2_images_path]"
                            value="<?php echo isset($settings['hurtownia2_images_path']) ? esc_attr($settings['hurtownia2_images_path']) : '/file/d/00_VOYAGER_HR/'; ?>"
                            class="regular-text">
                        <p class="description">Ścieżka do katalogu ze zdjęciami produktów na serwerze FTP</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Adres serwera FTP ze zdjęciami</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia2_ftp_images_host]"
                            value="<?php echo isset($settings['hurtownia2_ftp_images_host']) ? esc_attr($settings['hurtownia2_ftp_images_host']) : 'ftp2.axpol.com.pl'; ?>"
                            class="regular-text">
                        <p class="description">Adres serwera FTP ze zdjęciami, np. ftp2.axpol.com.pl</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Ścieżka do zdjęć na serwerze FTP</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia2_ftp_images_path]"
                            value="<?php echo isset($settings['hurtownia2_ftp_images_path']) ? esc_attr($settings['hurtownia2_ftp_images_path']) : '/file/d/00_VOYAGER_HR/'; ?>"
                            class="regular-text">
                        <p class="description">Ścieżka do katalogu ze zdjęciami na serwerze FTP, np.
                            /file/d/00_VOYAGER_HR/</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Nazwa użytkownika FTP (zdjęcia)</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia2_ftp_images_user]"
                            value="<?php echo isset($settings['hurtownia2_ftp_images_user']) ? esc_attr($settings['hurtownia2_ftp_images_user']) : 'userPL017img'; ?>"
                            class="regular-text">
                        <p class="description">Nazwa użytkownika do logowania na serwer FTP ze zdjęciami</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Hasło FTP (zdjęcia)</th>
                    <td>
                        <input type="password" name="kc_hurtownie_settings[hurtownia2_ftp_images_pass]"
                            value="<?php echo isset($settings['hurtownia2_ftp_images_pass']) ? esc_attr($settings['hurtownia2_ftp_images_pass']) : 'vSocD2N8'; ?>"
                            class="regular-text">
                        <p class="description">Hasło do logowania na serwer FTP ze zdjęciami</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Test połączenia FTP</th>
                    <td>
                        <button type="button" id="test-ftp-connection-2" class="button" data-hurtownia="hurtownia2">Testuj
                            połączenie FTP</button>
                        <div id="ftp-test-results-2" style="margin-top: 10px; display: none;"></div>
                    </td>
                </tr>
            </table>
        </div>

        <div id="tab-par" class="tab-content" style="display: none;">
            <h3>Ustawienia PAR</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Włącz integrację</th>
                    <td>
                        <label>
                            <input type="checkbox" name="kc_hurtownie_settings[hurtownia3_enabled]" value="1" <?php checked(isset($settings['hurtownia3_enabled']) && $settings['hurtownia3_enabled'] == '1'); ?>>
                            Zaznacz, aby włączyć integrację z hurtownią PAR
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Nazwa hurtowni</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia3_name]"
                            value="<?php echo isset($settings['hurtownia3_name']) ? esc_attr($settings['hurtownia3_name']) : 'PAR'; ?>"
                            class="regular-text">
                        <p class="description">Nazwa hurtowni wyświetlana w panelu administracyjnym</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">URL API</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia3_api_url]"
                            value="<?php echo isset($settings['hurtownia3_api_url']) ? esc_attr($settings['hurtownia3_api_url']) : 'http://www.par.com.pl/api'; ?>"
                            class="regular-text">
                        <p class="description">Adres API, np. http://www.par.com.pl/api</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Login API</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia3_api_login]"
                            value="<?php echo isset($settings['hurtownia3_api_login']) ? esc_attr($settings['hurtownia3_api_login']) : 'dmurawski@promo-mix.pl'; ?>"
                            class="regular-text">
                        <p class="description">Login do API</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Hasło API</th>
                    <td>
                        <input type="password" name="kc_hurtownie_settings[hurtownia3_api_password]"
                            value="<?php echo isset($settings['hurtownia3_api_password']) ? esc_attr($settings['hurtownia3_api_password']) : '#Reklamy!1'; ?>"
                            class="regular-text">
                        <p class="description">Hasło do API</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Format danych</th>
                    <td>
                        <select name="kc_hurtownie_settings[hurtownia3_api_format]">
                            <option value="xml" <?php selected(isset($settings['hurtownia3_api_format']) && $settings['hurtownia3_api_format'] == 'xml'); ?>>XML</option>
                            <option value="json" <?php selected(isset($settings['hurtownia3_api_format']) && $settings['hurtownia3_api_format'] == 'json'); ?>>JSON</option>
                        </select>
                        <p class="description">Format danych API</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Ścieżka lokalna</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia3_local_path]"
                            value="<?php echo isset($settings['hurtownia3_local_path']) ? esc_attr($settings['hurtownia3_local_path']) : 'par'; ?>"
                            class="regular-text">
                        <p class="description">Nazwa katalogu w uploads, gdzie będą przechowywane pliki (bez ukośników)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Pobierz pełny katalog</th>
                    <td>
                        <button type="button" id="download-par-products" class="button" data-hurtownia="hurtownia3">Pobierz pełny katalog produktów</button>
                        <div id="download-results-3" style="margin-top: 10px; display: none;"></div>
                        <p class="description">Uwaga: Pobieranie pełnego katalogu może potrwać kilka minut.</p>
                    </td>
                </tr>
            </table>
        </div>

        <div id="tab-inspirion" class="tab-content" style="display: none;">
            <h3>Ustawienia Inspirion</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Włącz integrację</th>
                    <td>
                        <label>
                            <input type="checkbox" name="kc_hurtownie_settings[hurtownia4_enabled]" value="1" <?php checked(isset($settings['hurtownia4_enabled']) && $settings['hurtownia4_enabled'] == '1'); ?>>
                            Zaznacz, aby włączyć integrację z hurtownią Inspirion
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Nazwa hurtowni</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia4_name]"
                            value="<?php echo isset($settings['hurtownia4_name']) ? esc_attr($settings['hurtownia4_name']) : 'Inspirion'; ?>"
                            class="regular-text">
                        <p class="description">Nazwa hurtowni wyświetlana w panelu administracyjnym</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Adres serwera FTP</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia4_ftp_host]"
                            value="<?php echo isset($settings['hurtownia4_ftp_host']) ? esc_attr($settings['hurtownia4_ftp_host']) : 'ftp.inspirion.pl'; ?>"
                            class="regular-text">
                        <p class="description">Adres serwera FTP z danymi produktów, np. ftp.inspirion.pl</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Nazwa użytkownika FTP</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia4_ftp_user]"
                            value="<?php echo isset($settings['hurtownia4_ftp_user']) ? esc_attr($settings['hurtownia4_ftp_user']) : 'inp-customer'; ?>"
                            class="regular-text">
                        <p class="description">Nazwa użytkownika do logowania na serwer FTP</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Hasło FTP</th>
                    <td>
                        <input type="password" name="kc_hurtownie_settings[hurtownia4_ftp_pass]"
                            value="<?php echo isset($settings['hurtownia4_ftp_pass']) ? esc_attr($settings['hurtownia4_ftp_pass']) : 'Q2JG9FZLo'; ?>"
                            class="regular-text">
                        <p class="description">Hasło do logowania na serwer FTP</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Ścieżka do pliku z danymi</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia4_ftp_path]"
                            value="<?php echo isset($settings['hurtownia4_ftp_path']) ? esc_attr($settings['hurtownia4_ftp_path']) : '/PT2024/PL_mP2_ADC/'; ?>"
                            class="regular-text">
                        <p class="description">Ścieżka do katalogu z danymi produktów na serwerze FTP</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Ścieżka lokalna</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia4_local_path]"
                            value="<?php echo isset($settings['hurtownia4_local_path']) ? esc_attr($settings['hurtownia4_local_path']) : 'inspirion'; ?>"
                            class="regular-text">
                        <p class="description">Nazwa katalogu w uploads, gdzie będą przechowywane pliki (bez ukośników)
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Test połączenia FTP</th>
                    <td>
                        <button type="button" id="test-ftp-connection-4" class="button"
                            data-hurtownia="hurtownia4">Testuj połączenie FTP</button>
                        <div id="ftp-test-results-4" style="margin-top: 10px; display: none;"></div>
                    </td>
                </tr>
            </table>
        </div>

        <div id="tab-macma" class="tab-content" style="display: none;">
            <h3>Ustawienia Macma</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Włącz integrację</th>
                    <td>
                        <label>
                            <input type="checkbox" name="kc_hurtownie_settings[hurtownia5_enabled]" value="1" <?php checked(isset($settings['hurtownia5_enabled']) && $settings['hurtownia5_enabled'] == '1'); ?>>
                            Zaznacz, aby włączyć integrację z hurtownią Macma
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Nazwa hurtowni</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia5_name]"
                            value="<?php echo isset($settings['hurtownia5_name']) ? esc_attr($settings['hurtownia5_name']) : 'Macma'; ?>"
                            class="regular-text">
                        <p class="description">Nazwa hurtowni wyświetlana w panelu administracyjnym</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Adres API</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia5_api_url]"
                            value="<?php echo isset($settings['hurtownia5_api_url']) ? esc_attr($settings['hurtownia5_api_url']) : 'http://www.macma.pl/data/webapi/pl/xml/'; ?>"
                            class="regular-text">
                        <p class="description">Bazowy adres API Macma</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Ścieżka lokalna</th>
                    <td>
                        <input type="text" name="kc_hurtownie_settings[hurtownia5_local_path]"
                            value="<?php echo isset($settings['hurtownia5_local_path']) ? esc_attr($settings['hurtownia5_local_path']) : 'macma'; ?>"
                            class="regular-text">
                        <p class="description">Nazwa katalogu w uploads, gdzie będą przechowywane pliki (bez ukośników)
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Test połączenia API</th>
                    <td>
                        <button type="button" id="test-api-connection-5" class="button"
                            data-hurtownia="hurtownia5">Testuj połączenie API</button>
                        <div id="api-test-results-5" style="margin-top: 10px; display: none;"></div>
                    </td>
                </tr>
            </table>
        </div>

        <div id="tab-settings" class="tab-content" style="display: none;">
            <h3>Ustawienia importu</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Harmonogram importu</th>
                    <td>
                        <select name="kc_hurtownie_settings[import_schedule]">
                            <option value="manual" <?php selected(isset($settings['import_schedule']) ? $settings['import_schedule'] : 'manual', 'manual'); ?>>Ręczny</option>
                            <option value="hourly" <?php selected(isset($settings['import_schedule']) ? $settings['import_schedule'] : 'manual', 'hourly'); ?>>Co godzinę</option>
                            <option value="twicedaily" <?php selected(isset($settings['import_schedule']) ? $settings['import_schedule'] : 'manual', 'twicedaily'); ?>>Dwa razy dziennie</option>
                            <option value="daily" <?php selected(isset($settings['import_schedule']) ? $settings['import_schedule'] : 'manual', 'daily'); ?>>Codziennie</option>
                            <option value="weekly" <?php selected(isset($settings['import_schedule']) ? $settings['import_schedule'] : 'manual', 'weekly'); ?>>Co tydzień</option>
                        </select>
                        <p class="description">Wybierz, jak często ma być wykonywany automatyczny import produktów</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Importuj kategorie</th>
                    <td>
                        <label>
                            <input type="checkbox" name="kc_hurtownie_settings[import_categories]" value="1" <?php checked(isset($settings['import_categories']) && $settings['import_categories'] == '1'); ?>>
                            Zaznacz, aby importować kategorie produktów
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Importuj zdjęcia</th>
                    <td>
                        <label>
                            <input type="checkbox" name="kc_hurtownie_settings[import_images]" value="1" <?php checked(isset($settings['import_images']) && $settings['import_images'] == '1'); ?>>
                            Zaznacz, aby importować zdjęcia produktów
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Aktualizuj istniejące produkty</th>
                    <td>
                        <label>
                            <input type="checkbox" name="kc_hurtownie_settings[update_existing]" value="1" <?php checked(isset($settings['update_existing']) && $settings['update_existing'] == '1'); ?>>
                            Zaznacz, aby aktualizować istniejące produkty podczas importu
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button('Zapisz ustawienia'); ?>
    </form>

    <script>
        jQuery(document).ready(function ($) {
            // Obsługa zakładek
            $('.nav-tab').on('click', function (e) {
                e.preventDefault();

                // Ukryj wszystkie zakładki
                $('.tab-content').hide();

                // Usuń klasę aktywną ze wszystkich przycisków
                $('.nav-tab').removeClass('nav-tab-active');

                // Pokaż wybraną zakładkę
                $($(this).attr('href')).show();

                // Dodaj klasę aktywną do klikniętego przycisku
                $(this).addClass('nav-tab-active');
            });

            // Obsługa przycisku testowania połączenia FTP
            $('#test-ftp-connection').on('click', function () {
                var hurtownia_id = $(this).data('hurtownia');

                // Pokaż informację o trwającym teście
                $('#ftp-test-results').show().html('<p>Trwa testowanie połączenia FTP...</p>');

                // Wykonaj żądanie AJAX
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'kc_hurtownie_test_ftp',
                        hurtownia_id: hurtownia_id,
                        nonce: $('#kc_hurtownie_nonce').val()
                    },
                    success: function (response) {
                        if (response.success) {
                            var filesHtml = '<ul>';
                            if (response.data.files && response.data.files.length > 0) {
                                for (var i = 0; i < response.data.files.length; i++) {
                                    filesHtml += '<li>' + response.data.files[i] + '</li>';
                                }
                            }
                            filesHtml += '</ul>';

                            $('#ftp-test-results').html('<h4>Wyniki testu:</h4><div id="ftp-results"><p style="color: green;">✓ ' + response.data.message + '</p>' + filesHtml + '</div>');
                        } else {
                            $('#ftp-test-results').html('<h4>Wyniki testu:</h4><div id="ftp-results"><p style="color: red;">✗ ' + response.data + '</p></div>');
                        }
                    },
                    error: function (xhr, status, error) {
                        $('#ftp-test-results').html('<h4>Wyniki testu:</h4><div id="ftp-results"><p style="color: red;">✗ Wystąpił błąd podczas testowania połączenia: ' + error + '</p></div>');
                    }
                });
            });
        });
    </script>
</div>