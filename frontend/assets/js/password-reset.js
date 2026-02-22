( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		initRequestForm();
		initResetForm();
	} );

	function initRequestForm() {
		const forms = document.querySelectorAll(
			'[data-sikada-reset-request-form]'
		);
		forms.forEach( ( form ) => {
			form.addEventListener( 'submit', async ( e ) => {
				e.preventDefault();
				await handleAjaxSubmit( form, 'sikada_auth_reset_request' );
			} );
		} );
	}

	function initResetForm() {
		const forms = document.querySelectorAll( '[data-sikada-reset-form]' );
		forms.forEach( ( form ) => {
			form.addEventListener( 'submit', async ( e ) => {
				e.preventDefault();

				// Password match check
				const p1 = form.querySelector( 'input[name="password"]' ).value;
				const p2 = form.querySelector(
					'input[name="password_confirmation"]'
				).value;
				if ( p1 !== p2 ) {
					showMessage(
						form.querySelector( '.sikada-form-messages' ),
						window.sikadaAuthData?.labels?.mismatch ||
							'Passwords do not match.',
						'error'
					);
					return;
				}

				await handleAjaxSubmit( form, 'sikada_auth_reset_password' );
			} );
		} );
	}

	async function handleAjaxSubmit( form, action ) {
		const submitButton = form.querySelector( 'button[type="submit"]' );
		const messagesContainer = form.querySelector( '.sikada-form-messages' );
		const originalText = submitButton.textContent;

		const formData = new FormData( form );
		formData.append( 'action', action );

		// Append URL params (key/login) if present (needed for reset_password)
		const urlParams = new URLSearchParams( window.location.search );
		if ( urlParams.has( 'key' ) )
			formData.append( 'key', urlParams.get( 'key' ) );
		if ( urlParams.has( 'login' ) )
			formData.append( 'login', urlParams.get( 'login' ) );

		if ( window.sikadaAuthData?.nonce ) {
			formData.append( 'nonce', window.sikadaAuthData.nonce );
		}

		submitButton.disabled = true;
		submitButton.textContent =
			window.sikadaAuthData?.labels?.loading || 'Processing...';
		messagesContainer.innerHTML = '';

		try {
			const response = await fetch( window.sikadaAuthData.ajaxUrl, {
				method: 'POST',
				body: formData,
			} );
			const data = await response.json();

			if ( data.success ) {
				showMessage( messagesContainer, data.data.message, 'success' );
				if ( data.data.redirect_url ) {
					setTimeout(
						() => ( window.location.href = data.data.redirect_url ),
						1500
					);
				} else if ( action === 'sikada_auth_reset_password' ) {
					// Default redirect to login after password reset
					setTimeout(
						() =>
							( window.location.href =
								window.sikadaAuthData.loginUrl ||
								'/wp-login.php' ),
						1500
					);
				}
				form.reset();
			} else {
				showMessage(
					messagesContainer,
					data.data.message || 'Error occurred',
					'error'
				);
			}
		} catch ( err ) {
			showMessage(
				messagesContainer,
				'Server connection failed.',
				'error'
			);
		} finally {
			submitButton.disabled = false;
			submitButton.textContent = originalText;
		}
	}

	function showMessage( container, message, type ) {
		container.innerHTML = `<div class="sikada-message sikada-message-${ type }">${ message }</div>`;
	}
} )();
