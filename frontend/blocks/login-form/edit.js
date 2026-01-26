import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @return {WPElement} Element to render.
 */
export default function Edit() {
    const blockProps = useBlockProps();

    return (
        <div {...blockProps}>
            <div className="sikada-auth-login-form-placeholder">
                <h3>{__('Login Form', 'sikada-auth')}</h3>
                <p>{__('This block will display the user login form on the frontend.', 'sikada-auth')}</p>
                <div className="sikada-login-form-preview">
                    {/* Preview of visual structure */}
                    <div style={{ opacity: 0.6, marginTop: '10px' }}>
                        <input type="text" placeholder={__('Username', 'sikada-auth')} disabled style={{ width: '100%', marginBottom: '10px' }} />
                        <input type="password" placeholder={__('Password', 'sikada-auth')} disabled style={{ width: '100%', marginBottom: '10px' }} />
                        <button disabled style={{ width: '100%' }}>{__('Log In', 'sikada-auth')}</button>
                    </div>
                </div>
            </div>
        </div>
    );
}
