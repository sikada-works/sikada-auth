const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        'blocks/login-form': path.resolve(__dirname, 'blocks/login-form/index.js'),
        'blocks/password-reset': path.resolve(__dirname, 'blocks/password-reset/index.js'),
    },
    output: {
        path: path.resolve(__dirname, 'build'),
        filename: '[name].js',
    },
};
