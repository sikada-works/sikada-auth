( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		const forms = document.querySelectorAll( '[data-sikada-reset-form]' );

		forms.forEach( ( form ) => {
			form.addEventListener( 'submit', handleResetPassword );
		} );
	} );

	async function handleResetPassword( e ) {
		e.preventDefault();

		const form = e.target;
		const messagesContainer = form.querySelector( '.sikada-form-messages' );
		const password = form.querySelector( 'input[name="password"]' ).value;
		const confirmation = form.querySelector(
			'input[name="password_confirmation"]'
		).value;

		// Basic client-side validation
		if ( password !== confirmation ) {
			showMessage(
				messagesContainer,
				window.sikadaAuthData &&
					window.sikadaAuthData.labels &&
					window.sikadaAuthData.labels.mismatch
					? window.sikadaAuthData.labels.mismatch
					: 'Passwords do not match.',
				'error'
			);
			return;
		}

		// Get URL parameters (key and login)
		const urlParams = new URLSearchParams( window.location.search );
		const key = urlParams.get( 'key' );
		const login = urlParams.get( 'login' );

		if ( ! key || ! login ) {
			showMessage(
				messagesContainer,
				'Invalid reset link. Please request a new one.',
				'error'
			);
			return;
		}

		const submitButton = form.querySelector( 'button[type="submit"]' );

		// Get form data
		const formData = new FormData( form );
		formData.append( 'action', 'sikada_auth_reset_password' );
		formData.append( 'key', key );
		formData.append( 'login', login );

		if ( window.sikadaAuthData && window.sikadaAuthData.nonce ) {
			formData.append( 'nonce', window.sikadaAuthData.nonce );
		}

		// Show loading state
		submitButton.disabled = true;
		const originalBtnText = submitButton.textContent;
		submitButton.textContent =
			window.sikadaAuthData &&
			window.sikadaAuthData.labels &&
			window.sikadaAuthData.labels.saving
				? window.sikadaAuthData.labels.saving
				: 'Saving...';

		messagesContainer.innerHTML = '';

		try {
			const ajaxUrl =
				window.sikadaAuthData && window.sikadaAuthData.ajaxUrl
					? window.sikadaAuthData.ajaxUrl
					: '/wp-admin/admin-ajax.php';

			const response = await fetch( ajaxUrl, {
				method: 'POST',
				body: formData,
			} );

			const data = await response.json();

			if ( data.success ) {
				// Show success message
				showMessage( messagesContainer, data.data.message, 'success' );

				// Redirect after 1 second (usually to login page)
				setTimeout( () => {
					// Try to redirect to login URL provided in data or fallback
					if ( data.data.redirect_url ) {
						window.location.href = data.data.redirect_url;
					} else if (
						window.sikadaAuthData &&
						window.sikadaAuthData.loginUrl
					) {
						window.location.href = window.sikadaAuthData.loginUrl;
					} else {
						window.location.href = '/wp-login.php';
					}
				}, 1500 );
			} else {
				// Show error message
				showMessage(
					messagesContainer,
					data.data.message || 'Reset failed',
					'error'
				);
				submitButton.disabled = false;
				submitButton.textContent = originalBtnText; // Restore original text
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Reset password error:', error );
			showMessage(
				messagesContainer,
				'An error occurred. Please try again.',
				'error'
			);
			submitButton.disabled = false;
			submitButton.textContent = originalBtnText;
		}
	}

	function showMessage( container, message, type ) {
		container.innerHTML = `<div class="sikada-message sikada-message-${ type }">${ message }</div>`;
	}
} )();
