const path = require('path');

const defaultConfig = require('@wordpress/scripts/config/webpack.config', true);
const { fromProjectRoot } = require('@wordpress/scripts/utils/file', true);

const srcPath = fromProjectRoot('assets-src');
const distPath = fromProjectRoot('assets');

module.exports = {
    ...defaultConfig,
    entry: {
        'wc-checkout': path.join(srcPath, 'wc-checkout.js'),

        'admin/invoice': path.join(srcPath, 'admin/invoice.js')
    },
    output: {
        ...defaultConfig.output,
        path: distPath,
        filename: '[name].js'
    }
};
