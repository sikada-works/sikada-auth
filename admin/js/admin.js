/**
 * Example Admin JavaScript
 *
 * @package SikadaWorks\SikadaAuth
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    /**
     * Initialize admin functionality
     */
    $(document).ready(function () {
        console.log('Sikada Authorization admin loaded');
        console.log('API Base:', window.sikadaAuthData.apiBase);

        // Example: Fetch data from REST API
        // fetch(window.sikadaAuthData.apiBase + '/items', {
        // 	headers: {
        // 		'X-WP-Nonce': window.sikadaAuthData.nonce
        // 	}
        // })
        // .then(response => response.json())
        // .then(data => {
        // 	console.log('Items:', data);
        // });
    });

})(jQuery);
