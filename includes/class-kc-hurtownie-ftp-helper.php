<?php

/**
 * Klasa pomocnicza do obsługi połączeń FTP/SFTP
 *
 * @link       https://kemuri.codes
 * @since      1.0.1
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/includes
 */

/**
 * Klasa pomocnicza do obsługi połączeń FTP/SFTP
 *
 * @package    Kc_Hurtownie
 * @subpackage Kc_Hurtownie/includes
 * @author     Marcin Dymek <contact@kemuri.codes>
 */
class Kc_Hurtownie_Ftp_Helper
{
    /**
     * Ustawienia FTP/SFTP
     *
     * @since    1.0.1
     * @access   private
     * @var      array    $settings    Ustawienia FTP/SFTP
     */
    private $settings;

    /**
     * Połączenie FTP/SFTP
     *
     * @since    1.0.1
     * @access   private
     * @var      resource    $connection    Połączenie FTP/SFTP
     */
    private $connection;

    /**
     * Typ połączenia (ftp lub sftp)
     *
     * @since    1.0.1
     * @access   private
     * @var      string    $connection_type    Typ połączenia
     */
    private $connection_type;

    /**
     * Konstruktor
     *
     * @since    1.0.1
     * @param    string    $host       Host FTP/SFTP
     * @param    string    $username   Nazwa użytkownika
     * @param    string    $password   Hasło
     * @param    int       $port       Port (domyślnie 21 dla FTP, 2223 dla AXPOL SFTP)
     * @param    int       $timeout    Timeout (domyślnie 300)
     * @param    bool      $ssl        Czy używać SSL (domyślnie false)
     */
    public function __construct($host, $username, $password, $port = 21, $timeout = 300, $ssl = false)
    {
        // Sprawdź, czy host to AXPOL - jeśli tak, wymuszamy SFTP z odpowiednimi ustawieniami
        $connection_type = 'ftp';
        if (strpos($host, 'axpol.com.pl') !== false) {
            $connection_type = 'sftp';
            $port = 2223; // Port SFTP dla AXPOL
        }

        $this->settings = array(
            'host' => $host,
            'username' => $username,
            'password' => $password,
            'port' => $port,
            'timeout' => $timeout,
            'ssl' => $ssl
        );

        $this->connection_type = $connection_type;
    }

    /**
     * Nawiązuje połączenie z serwerem FTP/SFTP
     *
     * @since    1.0.1
     * @return   bool    Czy połączenie zostało nawiązane
     */
    public function connect()
    {
        try {
            if ($this->connection_type === 'sftp') {
                return $this->connect_sftp();
            } else {
                return $this->connect_ftp();
            }
        } catch (Exception $e) {
            error_log('Błąd połączenia ' . strtoupper($this->connection_type) . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Nawiązuje połączenie z serwerem FTP
     *
     * @since    1.0.1
     * @return   bool    Czy połączenie zostało nawiązane
     */
    private function connect_ftp()
    {
        // Inicjalizuj połączenie FTP
        $this->connection = @ftp_connect($this->settings['host'], $this->settings['port'], $this->settings['timeout']);

        if (!$this->connection) {
            error_log('Nie można połączyć się z serwerem FTP: ' . $this->settings['host']);
            return false;
        }

        // Zaloguj się
        if (!@ftp_login($this->connection, $this->settings['username'], $this->settings['password'])) {
            error_log('Nie można zalogować się na serwer FTP: ' . $this->settings['host']);
            return false;
        }

        // Ustaw tryb pasywny
        @ftp_pasv($this->connection, true);

        return true;
    }

    /**
     * Nawiązuje połączenie z serwerem SFTP (tylko dla AXPOL)
     *
     * @since    1.0.1
     * @return   bool    Czy połączenie zostało nawiązane
     */
    private function connect_sftp()
    {
        // Sprawdź, czy SSH2 jest dostępny
        if (!function_exists('ssh2_connect')) {
            error_log('Funkcja ssh2_connect nie jest dostępna. Zainstaluj rozszerzenie SSH2 dla PHP.');
            return false;
        }

        // Inicjalizuj połączenie SSH2
        $this->connection = @ssh2_connect($this->settings['host'], $this->settings['port']);

        if (!$this->connection) {
            error_log('Nie można połączyć się z serwerem SFTP: ' . $this->settings['host'] . ':' . $this->settings['port']);
            return false;
        }

        // Zaakceptuj klucz serwera (fingerprint)
        // W produkcji należy zweryfikować klucz serwera
        if (!@ssh2_auth_password($this->connection, $this->settings['username'], $this->settings['password'])) {
            error_log('Nie można zalogować się na serwer SFTP: ' . $this->settings['host']);
            return false;
        }

        // Inicjalizuj SFTP
        $this->connection = @ssh2_sftp($this->connection);

        if (!$this->connection) {
            error_log('Nie można zainicjować podsystemu SFTP: ' . $this->settings['host']);
            return false;
        }

        return true;
    }

    /**
     * Pobiera listę plików z serwera FTP/SFTP
     *
     * @since    1.0.1
     * @param    string    $remote_dir    Katalog zdalny
     * @return   array                    Lista plików
     */
    public function list_files($remote_dir = '/')
    {
        if (!$this->connection) {
            return false;
        }

        try {
            if ($this->connection_type === 'sftp') {
                return $this->list_files_sftp($remote_dir);
            } else {
                return $this->list_files_ftp($remote_dir);
            }
        } catch (Exception $e) {
            error_log('Błąd pobierania listy plików ' . strtoupper($this->connection_type) . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Pobiera listę plików z serwera FTP
     *
     * @since    1.0.1
     * @param    string    $remote_dir    Katalog zdalny
     * @return   array                    Lista plików
     */
    private function list_files_ftp($remote_dir)
    {
        return ftp_nlist($this->connection, $remote_dir);
    }

    /**
     * Pobiera listę plików z serwera SFTP
     *
     * @since    1.0.1
     * @param    string    $remote_dir    Katalog zdalny
     * @return   array                    Lista plików
     */
    private function list_files_sftp($remote_dir)
    {
        $handle = @opendir('ssh2.sftp://' . $this->connection . $remote_dir);

        if (!$handle) {
            error_log('Nie można otworzyć katalogu SFTP: ' . $remote_dir);
            return false;
        }

        $files = array();

        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $files[] = $file;
            }
        }

        closedir($handle);

        return $files;
    }

    /**
     * Pobiera plik z serwera FTP/SFTP
     *
     * @since    1.0.1
     * @param    string    $local_file     Ścieżka lokalna
     * @param    string    $remote_file    Ścieżka zdalna
     * @return   bool                      Czy plik został pobrany
     */
    public function get_file($local_file, $remote_file)
    {
        if (!$this->connection) {
            return false;
        }

        try {
            // Upewnij się, że katalog lokalny istnieje
            $local_dir = dirname($local_file);
            if (!file_exists($local_dir)) {
                wp_mkdir_p($local_dir);
            }

            if ($this->connection_type === 'sftp') {
                return $this->get_file_sftp($local_file, $remote_file);
            } else {
                return $this->get_file_ftp($local_file, $remote_file);
            }
        } catch (Exception $e) {
            error_log('Błąd pobierania pliku ' . strtoupper($this->connection_type) . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Pobiera plik z serwera FTP
     *
     * @since    1.0.1
     * @param    string    $local_file     Ścieżka lokalna
     * @param    string    $remote_file    Ścieżka zdalna
     * @return   bool                      Czy plik został pobrany
     */
    private function get_file_ftp($local_file, $remote_file)
    {
        return ftp_get($this->connection, $local_file, $remote_file, FTP_BINARY);
    }

    /**
     * Pobiera plik z serwera SFTP
     *
     * @since    1.0.1
     * @param    string    $local_file     Ścieżka lokalna
     * @param    string    $remote_file    Ścieżka zdalna
     * @return   bool                      Czy plik został pobrany
     */
    private function get_file_sftp($local_file, $remote_file)
    {
        $sftp_stream = @fopen('ssh2.sftp://' . $this->connection . $remote_file, 'r');

        if (!$sftp_stream) {
            error_log('Nie można otworzyć pliku SFTP: ' . $remote_file);
            return false;
        }

        $local_stream = @fopen($local_file, 'w');

        if (!$local_stream) {
            error_log('Nie można otworzyć pliku lokalnego: ' . $local_file);
            fclose($sftp_stream);
            return false;
        }

        $result = stream_copy_to_stream($sftp_stream, $local_stream);

        fclose($local_stream);
        fclose($sftp_stream);

        return ($result !== false);
    }

    /**
     * Zamyka połączenie FTP/SFTP
     *
     * @since    1.0.1
     */
    public function close()
    {
        if ($this->connection && $this->connection_type === 'ftp') {
            ftp_close($this->connection);
        }
        // Dla SFTP nie trzeba zamykać połączenia, PHP zrobi to automatycznie
    }

    /**
     * Destruktor
     *
     * @since    1.0.1
     */
    public function __destruct()
    {
        $this->close();
    }
}