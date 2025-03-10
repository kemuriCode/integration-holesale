<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
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

// Pobierz ustawienia
$settings = get_option('kc_hurtownie_settings', array());
$last_import = get_option('kc_hurtownie_last_import', array());
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="kc-hurtownie-dashboard">
        <div class="kc-hurtownie-card">
            <h2>Integracja z hurtowniami</h2>
            <p>Witaj w panelu administracyjnym wtyczki do integracji z hurtowniami.</p>
            <p>Za pomocą tej wtyczki możesz importować produkty z różnych hurtowni do swojego sklepu WooCommerce.</p>

            <h3>Dostępne funkcje:</h3>
            <ul>
                <li>Automatyczny import produktów z hurtowni</li>
                <li>Pobieranie zdjęć produktów</li>
                <li>Aktualizacja istniejących produktów</li>
                <li>Importowanie kategorii produktów</li>
                <li>Harmonogram automatycznych importów</li>
            </ul>

            <p>Aby rozpocząć, przejdź do zakładki <a
                    href="<?php echo admin_url('admin.php?page=kc-hurtownie-settings'); ?>">Ustawienia</a>
                i skonfiguruj połączenie z hurtowniami.</p>
            <p>Następnie możesz przejść do zakładki <a
                    href="<?php echo admin_url('admin.php?page=kc-hurtownie-import'); ?>">Import
                    produktów</a>, aby rozpocząć import.</p>
        </div>

        <div class="kc-hurtownie-card">
            <h2>Status hurtowni</h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Hurtownia</th>
                        <th>Status</th>
                        <th>Ostatni import</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($settings['hurtownia1_enabled']) && $settings['hurtownia1_enabled'] == '1'): ?>
                        <tr>
                            <td><?php echo esc_html(isset($settings['hurtownia1_name']) && !empty($settings['hurtownia1_name']) ? $settings['hurtownia1_name'] : 'Hurtownia 1'); ?>
                            </td>
                            <td><span class="kc-hurtownie-status kc-hurtownie-status-active">Aktywna</span></td>
                            <td><?php echo esc_html(isset($last_import['hurtownia1']) ? date('d.m.Y H:i:s', $last_import['hurtownia1']) : 'Brak'); ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=kc-hurtownie-import&hurtownia=hurtownia1')); ?>"
                                    class="button">Importuj produkty</a>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php if (isset($settings['hurtownia2_enabled']) && $settings['hurtownia2_enabled'] == '1'): ?>
                        <tr>
                            <td><?php echo esc_html(isset($settings['hurtownia2_name']) && !empty($settings['hurtownia2_name']) ? $settings['hurtownia2_name'] : 'AXPOL'); ?>
                            </td>
                            <td><span class="kc-hurtownie-status kc-hurtownie-status-active">Aktywna</span></td>
                            <td><?php echo esc_html(isset($last_import['hurtownia2']) ? date('d.m.Y H:i:s', $last_import['hurtownia2']) : 'Brak'); ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=kc-hurtownie-import&hurtownia=hurtownia2')); ?>"
                                    class="button">Importuj produkty</a>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php if (isset($settings['hurtownia3_enabled']) && $settings['hurtownia3_enabled'] == '1'): ?>
                        <tr>
                            <td><?php echo esc_html(isset($settings['hurtownia3_name']) && !empty($settings['hurtownia3_name']) ? $settings['hurtownia3_name'] : 'PAR'); ?>
                            </td>
                            <td><span class="kc-hurtownie-status kc-hurtownie-status-active">Aktywna</span></td>
                            <td><?php echo esc_html(isset($last_import['hurtownia3']) ? date('d.m.Y H:i:s', $last_import['hurtownia3']) : 'Brak'); ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=kc-hurtownie-import&hurtownia=hurtownia3')); ?>"
                                    class="button">Importuj produkty</a>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php if (isset($settings['hurtownia4_enabled']) && $settings['hurtownia4_enabled'] == '1'): ?>
                        <tr>
                            <td><?php echo esc_html(isset($settings['hurtownia4_name']) && !empty($settings['hurtownia4_name']) ? $settings['hurtownia4_name'] : 'Inspirion'); ?>
                            </td>
                            <td><span class="kc-hurtownie-status kc-hurtownie-status-active">Aktywna</span></td>
                            <td><?php echo esc_html(isset($last_import['hurtownia4']) ? date('d.m.Y H:i:s', $last_import['hurtownia4']) : 'Brak'); ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=kc-hurtownie-import&hurtownia=hurtownia4')); ?>"
                                    class="button">Importuj produkty</a>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php if (isset($settings['hurtownia5_enabled']) && $settings['hurtownia5_enabled'] == '1'): ?>
                        <tr>
                            <td><?php echo esc_html(isset($settings['hurtownia5_name']) && !empty($settings['hurtownia5_name']) ? $settings['hurtownia5_name'] : 'Macma'); ?>
                            </td>
                            <td><span class="kc-hurtownie-status kc-hurtownie-status-active">Aktywna</span></td>
                            <td><?php echo esc_html(isset($last_import['hurtownia5']) ? date('d.m.Y H:i:s', $last_import['hurtownia5']) : 'Brak'); ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=kc-hurtownie-import&hurtownia=hurtownia5')); ?>"
                                    class="button">Importuj produkty</a>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php if (
                        !isset($settings['hurtownia1_enabled']) || $settings['hurtownia1_enabled'] != '1' &&
                        !isset($settings['hurtownia2_enabled']) || $settings['hurtownia2_enabled'] != '1' &&
                        !isset($settings['hurtownia3_enabled']) || $settings['hurtownia3_enabled'] != '1' &&
                        !isset($settings['hurtownia4_enabled']) || $settings['hurtownia4_enabled'] != '1' &&
                        !isset($settings['hurtownia5_enabled']) || $settings['hurtownia5_enabled'] != '1'
                    ): ?>
                        <tr>
                            <td colspan="4">Brak aktywnych hurtowni. Przejdź do <a
                                    href="<?php echo admin_url('admin.php?page=kc-hurtownie-settings'); ?>">ustawień</a>,
                                aby skonfigurować integrację.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>