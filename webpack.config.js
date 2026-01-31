const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const fs = require('fs');
const CopyPlugin = require('copy-webpack-plugin');

// Helper to find block entries in frontend/blocks
const getBlockEntries = () => {
    const blocksDir = path.resolve(__dirname, 'frontend/blocks');
    if (!fs.existsSync(blocksDir)) {
        return {};
    }
    return fs.readdirSync(blocksDir).reduce((acc, dir) => {
        const fullPath = path.join(blocksDir, dir);
        // Check for block.json and index.js
        if (
            fs.statSync(fullPath).isDirectory() &&
            fs.existsSync(path.join(fullPath, 'block.json')) &&
            fs.existsSync(path.join(fullPath, 'index.js'))
        ) {
            acc[`blocks/${dir}/index`] = path.join(fullPath, 'index.js');
        }
        return acc;
    }, {});
};

const blockEntries = getBlockEntries();
const appEntry = fs.existsSync(path.resolve(__dirname, 'frontend/app/index.js'))
    ? { 'index': path.resolve(__dirname, 'frontend/app/index.js') }
    : {};

module.exports = {
    ...defaultConfig,
    entry: {
        ...blockEntries,
        ...appEntry,
    },
    output: {
        path: path.resolve(__dirname, 'build'),
        filename: '[name].js',
    },
    resolve: {
        ...defaultConfig.resolve,
        alias: {
            ...defaultConfig.resolve.alias,
            '@': path.resolve(__dirname, 'frontend/app'),
        },
    },
    plugins: [
        ...defaultConfig.plugins,
        // Copy blocks that are NOT built (optional, if using static blocks)
        // Or copy PHP files from blocks if they exist
        new CopyPlugin({
            patterns: [
                {
                    from: 'frontend/blocks',
                    to: 'blocks',
                    globOptions: {
                        ignore: ['**/*.js', '**/*.jsx', '**/*.scss', '**/*.css'], // Ignore source files, copy PHP/JSON/Assets
                    },
                    noErrorOnMissing: true,
                },
                {
                    from: 'frontend/assets',
                    to: 'assets',
                    noErrorOnMissing: true,
                },
            ],
        }),
    ],
};
