import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export default function Edit() {
    const blockProps = useBlockProps();

    return (
        <div {...blockProps}>
            <div className="sikada-auth-reset-placeholder">
                <h3>{__('Password Reset System', 'sikada-auth')}</h3>
                <p>{__('This block dynamically displays the "Reset Request" or "New Password" form based on the URL.', 'sikada-auth')}</p>
                <div style={{ opacity: 0.6, padding: '10px', border: '1px solid #ccc', marginTop: '10px' }}>
                    <div><strong>{__('View: Request Form', 'sikada-auth')}</strong></div>
                    <input type="text" disabled placeholder={__('Username or Email', 'sikada-auth')} style={{ width: '100%', margin: '5px 0' }} />
                    <button disabled>{__('Get New Password', 'sikada-auth')}</button>
                </div>
            </div>
        </div>
    );
}
