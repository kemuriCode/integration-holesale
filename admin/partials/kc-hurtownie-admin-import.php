<?php
/**
 * Szablon strony importu produktów
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
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="kc-hurtownie-import">
        <h2>Import produktów z hurtowni</h2>
        
        <form id="kc-hurtownie-import-form" method="post">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="hurtownia_id">Wybierz hurtownię</label>
                    </th>
                    <td>
                        <select id="hurtownia_id" name="hurtownia_id">
                            <option value="">-- Wybierz hurtownię --</option>
                            <?php if (isset($settings['hurtownia1_enabled']) && $settings['hurtownia1_enabled'] == '1'): ?>
                                <option value="hurtownia1"><?php echo esc_html(isset($settings['hurtownia1_name']) && !empty($settings['hurtownia1_name']) ? $settings['hurtownia1_name'] : 'Hurtownia 1'); ?></option>
                            <?php endif; ?>
                            
                            <?php if (isset($settings['hurtownia2_enabled']) && $settings['hurtownia2_enabled'] == '1'): ?>
                                <option value="hurtownia2"><?php echo esc_html(isset($settings['hurtownia2_name']) && !empty($settings['hurtownia2_name']) ? $settings['hurtownia2_name'] : 'AXPOL'); ?></option>
                            <?php endif; ?>
                            
                            <?php if (isset($settings['hurtownia3_enabled']) && $settings['hurtownia3_enabled'] == '1'): ?>
                                <option value="hurtownia3"><?php echo esc_html(isset($settings['hurtownia3_name']) && !empty($settings['hurtownia3_name']) ? $settings['hurtownia3_name'] : 'PAR'); ?></option>
                            <?php endif; ?>
                            
                            <?php if (isset($settings['hurtownia4_enabled']) && $settings['hurtownia4_enabled'] == '1'): ?>
                                <option value="hurtownia4"><?php echo esc_html(isset($settings['hurtownia4_name']) && !empty($settings['hurtownia4_name']) ? $settings['hurtownia4_name'] : 'Inspirion'); ?></option>
                            <?php endif; ?>
                            
                            <?php if (isset($settings['hurtownia5_enabled']) && $settings['hurtownia5_enabled'] == '1'): ?>
                                <option value="hurtownia5"><?php echo esc_html(isset($settings['hurtownia5_name']) && !empty($settings['hurtownia5_name']) ? $settings['hurtownia5_name'] : 'Macma'); ?></option>
                            <?php endif; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="import_options">Opcje importu</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="import_categories" name="import_categories" value="1" <?php checked(isset($settings['import_categories']) && $settings['import_categories'] == '1'); ?>>
                            Importuj kategorie
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" id="import_images" name="import_images" value="1" <?php checked(isset($settings['import_images']) && $settings['import_images'] == '1'); ?>>
                            Importuj zdjęcia
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" id="update_existing" name="update_existing" value="1" <?php checked(isset($settings['update_existing']) && $settings['update_existing'] == '1'); ?>>
                            Aktualizuj istniejące produkty
                        </label>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" id="kc-hurtownie-import-button" class="button button-primary">Rozpocznij import</button>
            </p>
            
            <?php wp_nonce_field('kc_hurtownie_import', 'kc_hurtownie_import_nonce'); ?>
        </form>
        
        <div id="kc-hurtownie-import-progress" style="display: none;">
            <h3>Postęp importu</h3>
            <div class="progress-bar">
                <div class="progress-bar-inner" style="width: 0%;"></div>
            </div>
            <p class="progress-status">Przygotowanie do importu...</p>
        </div>
        
        <div id="kc-hurtownie-import-results" style="display: none;">
            <h3>Wyniki importu</h3>
            <div class="import-summary">
                <p>Zaimportowano: <span id="imported-count">0</span> produktów</p>
                <p>Zaktualizowano: <span id="updated-count">0</span> produktów</p>
                <p>Pominięto: <span id="skipped-count">0</span> produktów</p>
                <p>Błędy: <span id="error-count">0</span></p>
            </div>
            <div class="import-details"></div>
        </div>
    </div>
</div>