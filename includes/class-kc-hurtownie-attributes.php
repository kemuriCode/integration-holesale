<?php

/**
 * Klasa do obsługi atrybutów produktów
 *
 * @link       https://kemuri.codes
 * @since      1.0.1
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/includes
 */

/**
 * Klasa do obsługi atrybutów produktów
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/includes
 * @author     Marcin Dymek <contact@kemuri.codes>
 */
class Kc_Hurtownie_Attributes
{
    /**
     * Lista standardowych atrybutów
     *
     * @since    1.0.1
     * @access   private
     * @var      array    $standard_attributes    Lista standardowych atrybutów
     */
    private $standard_attributes = array(
        'kolor' => 'Kolor',
        'material' => 'Materiał',
        'rozmiar' => 'Rozmiar',
        'waga' => 'Waga',
        'pojemnosc' => 'Pojemność',
        'wymiary' => 'Wymiary',
        'czas_nadruku' => 'Czas nadruku',
        'technika_nadruku' => 'Technika nadruku',
        'minimalne_zamowienie' => 'Minimalne zamówienie',
        'kraj_pochodzenia' => 'Kraj pochodzenia',
        'certyfikaty' => 'Certyfikaty',
        'opakowanie' => 'Opakowanie',
        'gwarancja' => 'Gwarancja'
    );

    /**
     * Inicjalizuje atrybuty produktów
     *
     * @since    1.0.1
     */
    public function init_attributes()
    {
        // Sprawdź, czy atrybuty już istnieją
        $existing_attributes = wc_get_attribute_taxonomies();
        $existing_slugs = array();

        foreach ($existing_attributes as $attribute) {
            $existing_slugs[] = $attribute->attribute_name;
        }

        // Utwórz brakujące atrybuty
        foreach ($this->standard_attributes as $slug => $name) {
            if (!in_array($slug, $existing_slugs)) {
                $this->create_attribute($slug, $name);
            }
        }
    }

    /**
     * Tworzy nowy atrybut produktu
     *
     * @since    1.0.1
     * @param    string    $slug    Slug atrybutu
     * @param    string    $name    Nazwa atrybutu
     * @return   int|false          ID atrybutu lub false w przypadku błędu
     */
    private function create_attribute($slug, $name)
    {
        $args = array(
            'name' => $name,
            'slug' => $slug,
            'type' => 'select',
            'order_by' => 'menu_order',
            'has_archives' => false
        );

        $result = wc_create_attribute($args);

        if (is_wp_error($result)) {
            error_log('Błąd tworzenia atrybutu: ' . $result->get_error_message());
            return false;
        }

        // Zarejestruj taksonomię
        $taxonomy_name = wc_attribute_taxonomy_name($slug);
        register_taxonomy(
            $taxonomy_name,
            'product',
            array(
                'labels' => array(
                    'name' => $name,
                    'singular_name' => $name,
                    'search_items' => sprintf('Szukaj %s', $name),
                    'all_items' => sprintf('Wszystkie %s', $name),
                    'edit_item' => sprintf('Edytuj %s', $name),
                    'update_item' => sprintf('Aktualizuj %s', $name),
                    'add_new_item' => sprintf('Dodaj nowy %s', $name),
                    'new_item_name' => sprintf('Nowa nazwa %s', $name),
                    'menu_name' => $name,
                ),
                'hierarchical' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array('slug' => $slug),
            )
        );

        return $result;
    }

    /**
     * Mapuje atrybuty z hurtowni na standardowe atrybuty
     *
     * @since    1.0.1
     * @param    string    $hurtownia_id       ID hurtowni
     * @param    array     $product_data       Dane produktu
     * @param    array     $attribute_mapping  Mapowanie atrybutów
     * @return   array                         Zmapowane atrybuty
     */
    public function map_attributes($hurtownia_id, $product_data, $attribute_mapping = array())
    {
        $mapped_attributes = array();

        // Domyślne mapowanie dla każdej hurtowni
        $default_mappings = array(
            'hurtownia1' => array(
                'kolor' => 'color',
                'material' => 'material',
                'rozmiar' => 'size',
                'waga' => 'weight',
                'pojemnosc' => 'capacity',
                'wymiary' => 'dimensions'
            ),
            'hurtownia2' => array(
                'kolor' => 'color',
                'material' => 'material',
                'rozmiar' => 'size',
                'waga' => 'weight',
                'pojemnosc' => 'capacity',
                'wymiary' => 'dimensions'
            ),
            'hurtownia3' => array(
                'kolor' => 'color',
                'material' => 'material',
                'rozmiar' => 'size',
                'waga' => 'weight',
                'pojemnosc' => 'capacity',
                'wymiary' => 'dimensions'
            ),
            'hurtownia4' => array(
                'kolor' => 'color',
                'material' => 'material',
                'rozmiar' => 'size',
                'waga' => 'weight',
                'pojemnosc' => 'capacity',
                'wymiary' => 'dimensions'
            ),
            'hurtownia5' => array(
                'kolor' => 'color',
                'material' => 'material',
                'rozmiar' => 'size',
                'waga' => 'weight',
                'pojemnosc' => 'capacity',
                'wymiary' => 'dimensions'
            )
        );

        // Użyj niestandardowego mapowania, jeśli podano
        $mapping = !empty($attribute_mapping) ? $attribute_mapping : $default_mappings[$hurtownia_id];

        // Mapuj atrybuty
        foreach ($mapping as $standard_attr => $hurtownia_attr) {
            if (isset($product_data[$hurtownia_attr]) && !empty($product_data[$hurtownia_attr])) {
                $mapped_attributes[$standard_attr] = $product_data[$hurtownia_attr];
            }
        }

        return $mapped_attributes;
    }

    /**
     * Dodaje atrybuty do produktu
     *
     * @since    1.0.1
     * @param    int      $product_id     ID produktu
     * @param    array    $attributes     Atrybuty do dodania
     */
    public function add_attributes_to_product($product_id, $attributes)
    {
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }

        $product_attributes = array();

        foreach ($attributes as $slug => $value) {
            if (empty($value)) {
                continue;
            }

            $taxonomy = wc_attribute_taxonomy_name($slug);

            // Sprawdź, czy taksonomia istnieje
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }

            // Dodaj termin, jeśli nie istnieje
            $term = get_term_by('name', $value, $taxonomy);
            if (!$term) {
                $term = wp_insert_term($value, $taxonomy);
                if (is_wp_error($term)) {
                    continue;
                }
                $term_id = $term['term_id'];
            } else {
                $term_id = $term->term_id;
            }

            // Przypisz termin do produktu
            wp_set_object_terms($product_id, $term_id, $taxonomy, true);

            // Dodaj atrybut do listy atrybutów produktu
            $product_attributes[$taxonomy] = array(
                'name' => $taxonomy,
                'value' => '',
                'position' => count($product_attributes),
                'is_visible' => 1,
                'is_variation' => 0,
                'is_taxonomy' => 1
            );
        }

        // Zapisz atrybuty produktu
        update_post_meta($product_id, '_product_attributes', $product_attributes);
    }
}