<?php

/**
 * Klasa pomocnicza do obsługi połączeń FTP
 *
 * @link       https://kemuri.codes
 * @since      1.0.0
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/includes
 */

/**
 * Klasa pomocnicza do obsługi połączeń FTP
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/includes
 * @author     Marcin Dymek <contact@kemuri.codes>
 */
class Kc_Hurtownie_Ftp_Helper
{
    /**
     * Ustawienia FTP
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $settings    Ustawienia FTP
     */
    private $settings;

    /**
     * Połączenie FTP
     *
     * @since    1.0.0
     * @access   private
     * @var      FTP\Connection|resource    $ftp    Połączenie FTP
     */
    private $ftp;

    /**
     * Konstruktor
     *
     * @since    1.0.0
     * @param    string    $host       Host FTP
     * @param    string    $username   Nazwa użytkownika
     * @param    string    $password   Hasło
     * @param    int       $port       Port (domyślnie 21)
     * @param    int       $timeout    Timeout (domyślnie 90)
     * @param    bool      $ssl        Czy używać SSL (domyślnie false)
     */
    public function __construct($host, $username, $password, $port = 21, $timeout = 300, $ssl = false)
    {
        $this->settings = array(
            'host' => $host,
            'username' => $username,
            'password' => $password,
            'port' => $port,
            'timeout' => $timeout,
            'ssl' => $ssl
        );
    }

    /**
     * Nawiązuje połączenie z serwerem FTP
     *
     * @since    1.0.0
     * @return   bool    Czy połączenie zostało nawiązane
     */
    public function connect()
    {
        try {
            // Inicjalizuj połączenie FTP
            $this->ftp = @ftp_connect($this->settings['host'], $this->settings['port'], $this->settings['timeout']);

            if (!$this->ftp) {
                error_log('Nie można połączyć się z serwerem FTP: ' . $this->settings['host']);
                return false;
            }

            // Zaloguj się
            if (!@ftp_login($this->ftp, $this->settings['username'], $this->settings['password'])) {
                error_log('Nie można zalogować się na serwer FTP: ' . $this->settings['host']);
                return false;
            }

            // Ustaw tryb pasywny
            @ftp_pasv($this->ftp, true);

            return true;
        } catch (Exception $e) {
            error_log('Błąd połączenia FTP: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Pobiera listę plików z serwera FTP
     *
     * @since    1.0.0
     * @param    string    $remote_dir    Katalog zdalny
     * @return   array                    Lista plików
     */
    public function list_files($remote_dir = '/')
    {
        if (!$this->ftp) {
            return false;
        }

        try {
            $files = ftp_nlist($this->ftp, $remote_dir);
            return $files;
        } catch (Exception $e) {
            error_log('Błąd pobierania listy plików FTP: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Pobiera plik z serwera FTP
     *
     * @since    1.0.0
     * @param    string    $local_file     Ścieżka lokalna
     * @param    string    $remote_file    Ścieżka zdalna
     * @return   bool                      Czy plik został pobrany
     */
    public function get_file($local_file, $remote_file)
    {
        if (!$this->ftp) {
            return false;
        }

        try {
            // Upewnij się, że katalog lokalny istnieje
            $local_dir = dirname($local_file);
            if (!file_exists($local_dir)) {
                wp_mkdir_p($local_dir);
            }

            // Pobierz plik
            return ftp_get($this->ftp, $local_file, $remote_file, FTP_BINARY);
        } catch (Exception $e) {
            error_log('Błąd pobierania pliku FTP: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Zamyka połączenie FTP
     *
     * @since    1.0.0
     */
    public function close()
    {
        if ($this->ftp) {
            ftp_close($this->ftp);
        }
    }

    /**
     * Destruktor
     *
     * @since    1.0.0
     */
    public function __destruct()
    {
        $this->close();
    }
}