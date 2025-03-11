(function ($) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$(function () {
		// Obsługa zakładek w ustawieniach
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

		// Obsługa formularza importu
		$('#kc-hurtownie-import-form').on('submit', function (e) {
			e.preventDefault();

			var hurtownia_id = $('#hurtownia_id').val();

			if (!hurtownia_id) {
				alert('Wybierz hurtownię, z której chcesz zaimportować produkty.');
				return;
			}

			// Pokaż pasek postępu
			$('#kc-hurtownie-import-progress').show();
			$('#kc-hurtownie-import-results').hide();
			$('#kc-hurtownie-import-button').prop('disabled', true).text('Trwa import...');

			// Wykonaj żądanie AJAX
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'kc_hurtownie_import',
					hurtownia_id: hurtownia_id,
					nonce: $('#kc_hurtownie_import_nonce').val()
				},
				success: function (response) {
					// Ukryj pasek postępu
					$('#kc-hurtownie-import-progress').hide();

					if (response.success) {
						// Pokaż wyniki importu
						$('#kc-hurtownie-import-results').show();

						// Sprawdź, czy dane są dostępne
						console.log('Pełna odpowiedź:', response);

						// Obsługa różnych formatów odpowiedzi
						var importData = {};

						if (response.data && typeof response.data === 'object') {
							// Sprawdź, czy dane są w stats czy bezpośrednio w data
							if (response.data.stats && typeof response.data.stats === 'object') {
								importData = response.data.stats;
								console.log('Znaleziono dane w response.data.stats:', importData);
							} else {
								importData = response.data;
								console.log('Znaleziono dane bezpośrednio w response.data:', importData);
							}

							// Aktualizuj statystyki importu
							$('#kc-hurtownie-import-total').text(importData.total || 0);
							$('#kc-hurtownie-import-imported').text(importData.imported || 0);
							$('#kc-hurtownie-import-updated').text(importData.updated || 0);
							$('#kc-hurtownie-import-skipped').text(importData.skipped || 0);
							$('#kc-hurtownie-import-errors').text(importData.errors || 0);
						} else {
							console.error('Brak danych w odpowiedzi:', response);
							alert('Wystąpił błąd podczas importu: Brak danych w odpowiedzi');
						}
					} else {
						// Pokaż komunikat o błędzie
						console.error('Błąd importu:', response);
						var errorMessage = response.data || 'Nieznany błąd';
						alert('Wystąpił błąd podczas importu: ' + errorMessage);
					}

					// Odblokuj przycisk
					$('#kc-hurtownie-import-button').prop('disabled', false).text('Rozpocznij import');
				},
				error: function (xhr, status, error) {
					// Ukryj pasek postępu
					$('#kc-hurtownie-import-progress').hide();

					// Pokaż komunikat o błędzie
					console.error('Błąd AJAX:', xhr, status, error);
					var errorMessage = '';

					try {
						var response = JSON.parse(xhr.responseText);
						errorMessage = response.data || error || 'Nieznany błąd';
					} catch (e) {
						errorMessage = error || 'Nieznany błąd';
					}

					alert('Wystąpił błąd podczas importu: ' + errorMessage);

					// Odblokuj przycisk
					$('#kc-hurtownie-import-button').prop('disabled', false).text('Rozpocznij import');
				}
			});
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
					nonce: $('#kc_hurtownie_test_ftp_nonce').val()
				},
				success: function (response) {
					if (response.success) {
						var data_result = response.data.data_connection;
						var images_result = response.data.images_connection;

						var data_html = '<h5>Połączenie z serwerem danych:</h5>';
						if (data_result.success) {
							data_html += '<p style="color: green;">✓ ' + data_result.message + '</p>';
						} else {
							data_html += '<p style="color: red;">✗ ' + data_result.message + '</p>';
							data_html += '<ul>';
							data_html += '<li>Połączenie z serwerem: ' + (data_result.connection ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>') + '</li>';
							data_html += '<li>Logowanie: ' + (data_result.login ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>') + '</li>';
							data_html += '<li>Ścieżka: ' + (data_result.path ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>') + '</li>';
							data_html += '</ul>';
						}

						var images_html = '<h5>Połączenie z serwerem zdjęć:</h5>';
						if (images_result.success) {
							images_html += '<p style="color: green;">✓ ' + images_result.message + '</p>';
						} else {
							images_html += '<p style="color: red;">✗ ' + images_result.message + '</p>';
							images_html += '<ul>';
							images_html += '<li>Połączenie z serwerem: ' + (images_result.connection ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>') + '</li>';
							images_html += '<li>Logowanie: ' + (images_result.login ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>') + '</li>';
							images_html += '<li>Ścieżka: ' + (images_result.path ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>') + '</li>';
							images_html += '</ul>';
						}

						$('#ftp-test-results').html('<h4>Wyniki testu:</h4><div id="ftp-data-results">' + data_html + '</div><div id="ftp-images-results">' + images_html + '</div>');
					} else {
						$('#ftp-test-results').html('<p style="color: red;">Wystąpił błąd podczas testowania połączenia: ' + response.data + '</p>');
					}
				},
				error: function (xhr, status, error) {
					$('#ftp-test-results').html('<p style="color: red;">Wystąpił błąd podczas testowania połączenia: ' + error + '</p>');
				}
			});
		});

		// Obsługa przycisku testowania połączenia API
		$('#test-api-connection').on('click', function () {
			var hurtownia_id = $(this).data('hurtownia');

			// Pokaż informację o trwającym teście
			$('#api-test-results').show().html('<p>Trwa testowanie połączenia API...</p>');

			// Wykonaj żądanie AJAX
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'kc_hurtownie_test_api',
					hurtownia_id: hurtownia_id,
					nonce: $('#kc_hurtownie_test_api_nonce').val()
				},
				success: function (response) {
					if (response.success) {
						$('#api-test-results').html('<h4>Wyniki testu:</h4><div id="api-results"><p style="color: green;">✓ ' + response.data.message + '</p><p>Format danych: ' + response.data.format.toUpperCase() + '</p></div>');
					} else {
						$('#api-test-results').html('<h4>Wyniki testu:</h4><div id="api-results"><p style="color: red;">✗ ' + response.data + '</p></div>');
					}
				},
				error: function (xhr, status, error) {
					$('#api-test-results').html('<h4>Wyniki testu:</h4><div id="api-results"><p style="color: red;">✗ Wystąpił błąd podczas testowania połączenia: ' + error + '</p></div>');
				}
			});
		});

		// Obsługa przycisku testowania połączenia FTP dla Inspirion
		$('#test-ftp-connection-4').on('click', function () {
			var hurtownia_id = $(this).data('hurtownia');

			// Pokaż informację o trwającym teście
			$('#ftp-test-results-4').show().html('<p>Trwa testowanie połączenia FTP...</p>');

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

						$('#ftp-test-results-4').html('<h4>Wyniki testu:</h4><div id="ftp-results-4"><p style="color: green;">✓ ' + response.data.message + '</p>' + filesHtml + '</div>');
					} else {
						$('#ftp-test-results-4').html('<h4>Wyniki testu:</h4><div id="ftp-results-4"><p style="color: red;">✗ ' + response.data + '</p></div>');
					}
				},
				error: function (xhr, status, error) {
					$('#ftp-test-results-4').html('<h4>Wyniki testu:</h4><div id="ftp-results-4"><p style="color: red;">✗ Wystąpił błąd podczas testowania połączenia: ' + error + '</p></div>');
				}
			});
		});

		// Obsługa przycisku testowania połączenia API dla Macma
		$('#test-api-connection-5').on('click', function () {
			var hurtownia_id = $(this).data('hurtownia');

			// Pokaż informację o trwającym teście
			$('#api-test-results-5').show().html('<p>Trwa testowanie połączenia API...</p>');

			// Wykonaj żądanie AJAX
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'kc_hurtownie_test_api',
					hurtownia_id: hurtownia_id,
					nonce: $('#kc_hurtownie_nonce').val()
				},
				success: function (response) {
					if (response.success) {
						$('#api-test-results-5').html('<h4>Wyniki testu:</h4><div id="api-results-5"><p style="color: green;">✓ ' + response.data.message + '</p><p>Format danych: ' + response.data.format + '</p></div>');
					} else {
						$('#api-test-results-5').html('<h4>Wyniki testu:</h4><div id="api-results-5"><p style="color: red;">✗ ' + response.data + '</p></div>');
					}
				},
				error: function (xhr, status, error) {
					$('#api-test-results-5').html('<h4>Wyniki testu:</h4><div id="api-results-5"><p style="color: red;">✗ Wystąpił błąd podczas testowania połączenia: ' + error + '</p></div>');
				}
			});
		});

		// Obsługa przycisku testowania połączenia FTP dla AXPOL
		$('#test-ftp-connection-2').on('click', function () {
			var hurtownia_id = $(this).data('hurtownia');

			// Pokaż informację o trwającym teście
			$('#ftp-test-results-2').show().html('<p>Trwa testowanie połączenia FTP...</p>');

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

						$('#ftp-test-results-2').html('<h4>Wyniki testu:</h4><div id="ftp-results-2"><p style="color: green;">✓ ' + response.data.message + '</p>' + filesHtml + '</div>');
					} else {
						$('#ftp-test-results-2').html('<h4>Wyniki testu:</h4><div id="ftp-results-2"><p style="color: red;">✗ ' + response.data + '</p></div>');
					}
				},
				error: function (xhr, status, error) {
					$('#ftp-test-results-2').html('<h4>Wyniki testu:</h4><div id="ftp-results-2"><p style="color: red;">✗ Wystąpił błąd podczas testowania połączenia: ' + error + '</p></div>');
				}
			});
		});

		// Obsługa przycisku testowania połączenia API dla PAR
		$('#test-api-connection-3').on('click', function () {
			var hurtownia_id = $(this).data('hurtownia');

			// Pokaż informację o trwającym teście
			$('#api-test-results-3').show().html('<p>Trwa testowanie połączenia API...</p>');

			// Wykonaj żądanie AJAX
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'kc_hurtownie_test_api',
					hurtownia_id: hurtownia_id,
					nonce: $('#kc_hurtownie_nonce').val()
				},
				success: function (response) {
					if (response.success) {
						$('#api-test-results-3').html('<h4>Wyniki testu:</h4><div id="api-results-3"><p style="color: green;">✓ ' + response.data.message + '</p><p>Format danych: ' + response.data.format + '</p></div>');
					} else {
						$('#api-test-results-3').html('<h4>Wyniki testu:</h4><div id="api-results-3"><p style="color: red;">✗ ' + response.data + '</p></div>');
					}
				},
				error: function (xhr, status, error) {
					$('#api-test-results-3').html('<h4>Wyniki testu:</h4><div id="api-results-3"><p style="color: red;">✗ Wystąpił błąd podczas testowania połączenia: ' + error + '</p></div>');
				}
			});
		});

		// Obsługa przycisku pobierania pełnego katalogu PAR
		$('#download-par-products').on('click', function () {
			var hurtownia_id = $(this).data('hurtownia');

			// Pokaż informację o trwającym pobieraniu
			$('#download-results-3').show().html('<p>Trwa pobieranie pełnego katalogu produktów PAR. To może potrwać kilka minut...</p>');

			// Wykonaj żądanie AJAX
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'kc_hurtownie_download_par_products',
					hurtownia_id: hurtownia_id,
					nonce: $('#kc_hurtownie_nonce').val()
				},
				success: function (response) {
					if (response.success) {
						$('#download-results-3').html('<h4>Wyniki:</h4><div><p style="color: green;">✓ ' + response.data.message + '</p></div>');
					} else {
						$('#download-results-3').html('<h4>Wyniki:</h4><div><p style="color: red;">✗ ' + response.data + '</p></div>');
					}
				},
				error: function (xhr, status, error) {
					$('#download-results-3').html('<h4>Wyniki:</h4><div><p style="color: red;">✗ Wystąpił błąd podczas pobierania: ' + error + '</p></div>');
				}
			});
		});

		// Obsługa przycisku testowania połączenia API dla Malfini
		$('#test-api-connection-6').on('click', function () {
			var hurtownia_id = $(this).data('hurtownia');

			// Pokaż informację o trwającym teście
			$('#api-test-results-6').show().html('<p>Trwa testowanie połączenia API Malfini...</p>');

			// Wykonaj żądanie AJAX
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'kc_hurtownie_test_api',
					hurtownia_id: hurtownia_id,
					nonce: $('#kc_hurtownie_nonce').val()
				},
				success: function (response) {
					if (response.success) {
						$('#api-test-results-6').html('<h4>Wyniki testu:</h4><div id="api-results-6"><p style="color: green;">✓ ' + response.data.message + '</p><p>Format danych: ' + response.data.format.toUpperCase() + '</p></div>');
					} else {
						$('#api-test-results-6').html('<h4>Wyniki testu:</h4><div id="api-results-6"><p style="color: red;">✗ ' + response.data + '</p></div>');
					}
				},
				error: function (xhr, status, error) {
					$('#api-test-results-6').html('<h4>Wyniki testu:</h4><div id="api-results-6"><p style="color: red;">✗ Wystąpił błąd podczas testowania połączenia: ' + error + '</p></div>');
				}
			});
		});
	});

})(jQuery);
