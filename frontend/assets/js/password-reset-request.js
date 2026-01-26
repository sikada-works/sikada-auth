(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const forms = document.querySelectorAll('[data-sikada-reset-request-form]');

        forms.forEach(form => {
            form.addEventListener('submit', handleResetRequest);

            // Update login link from localized data
            const loginLink = form.querySelector('.sikada-login-link');
            if (loginLink && window.sikadaAuthData && window.sikadaAuthData.loginUrl) {
                loginLink.href = window.sikadaAuthData.loginUrl;
            }
        });
    });

    async function handleResetRequest(e) {
        e.preventDefault();

        const form = e.target;
        const submitButton = form.querySelector('button[type="submit"]');
        const messagesContainer = form.querySelector('.sikada-form-messages');

        // Get form data
        const formData = new FormData(form);
        formData.append('action', 'sikada_auth_reset_request');

        if (window.sikadaAuthData && window.sikadaAuthData.nonce) {
            formData.append('nonce', window.sikadaAuthData.nonce);
        }

        // Show loading state
        submitButton.disabled = true;
        const originalBtnText = submitButton.textContent;
        submitButton.textContent = (window.sikadaAuthData && window.sikadaAuthData.labels && window.sikadaAuthData.labels.sending)
            ? window.sikadaAuthData.labels.sending
            : 'Sending...';

        messagesContainer.innerHTML = '';

        try {
            const ajaxUrl = (window.sikadaAuthData && window.sikadaAuthData.ajaxUrl)
                ? window.sikadaAuthData.ajaxUrl
                : '/wp-admin/admin-ajax.php';

            const response = await fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Show success message
                showMessage(messagesContainer, data.data.message, 'success');
                submitButton.textContent = originalBtnText;
                form.reset();
            } else {
                // Show error message
                showMessage(messagesContainer, data.data.message || 'Request failed', 'error');
                submitButton.disabled = false;
                submitButton.textContent = originalBtnText; // Restore original text
            }
        } catch (error) {
            console.error('Reset request error:', error);
            showMessage(messagesContainer, 'An error occurred. Please try again.', 'error');
            submitButton.disabled = false;
            submitButton.textContent = originalBtnText;
        }
    }

    function showMessage(container, message, type) {
        container.innerHTML = `<div class="sikada-message sikada-message-${type}">${message}</div>`;
    }
})();
