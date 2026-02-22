( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		const forms = document.querySelectorAll( '[data-sikada-login-form]' );

		forms.forEach( ( form ) => {
			form.addEventListener( 'submit', handleLogin );

			// Update password reset link from localized data
			const resetLink = form.querySelector(
				'.sikada-lost-password-link'
			);
			if (
				resetLink &&
				window.sikadaAuthData &&
				window.sikadaAuthData.passwordResetUrl
			) {
				resetLink.href = window.sikadaAuthData.passwordResetUrl;
			}
		} );
	} );

	async function handleLogin( e ) {
		e.preventDefault();

		const form = e.target;
		const submitButton = form.querySelector( 'button[type="submit"]' );
		const messagesContainer = form.querySelector( '.sikada-form-messages' );

		// Get form data
		const formData = new FormData( form );
		formData.append( 'action', 'sikada_auth_login' );

		if ( window.sikadaAuthData && window.sikadaAuthData.nonce ) {
			formData.append( 'nonce', window.sikadaAuthData.nonce );
		}

		// Show loading state
		submitButton.disabled = true;
		const originalBtnText = submitButton.textContent;
		submitButton.textContent =
			window.sikadaAuthData &&
			window.sikadaAuthData.labels &&
			window.sikadaAuthData.labels.loading
				? window.sikadaAuthData.labels.loading
				: 'Logging in...';

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

				// Redirect after 1 second
				setTimeout( () => {
					window.location.href = data.data.redirect_url;
				}, 1000 );
			} else {
				// Show error message
				showMessage(
					messagesContainer,
					data.data.message || 'Login failed',
					'error'
				);
				submitButton.disabled = false;
				submitButton.textContent = originalBtnText; // Restore original text
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Login error:', error );
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
