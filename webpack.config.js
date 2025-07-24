const path = require('path');
const glob = require('glob');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const fs = require('fs');

function getEntryPoints() {
    const entries = {};

    // Helper function to check if a file is not empty
    const isNotEmptyFile = (filePath) => fs.statSync(filePath).size > 0;

    // JavaScript and TypeScript files
    const jsFiles = glob.sync('./assets/js/*.js');
    const tsFiles = glob.sync('./assets/ts/*.ts');
    const cssFiles = glob.sync('./assets/css/*.css');

    [...jsFiles, ...tsFiles].forEach((file) => {
        if (isNotEmptyFile(file) && !file.includes('.min.')) {
            const entryName = path.basename(file, path.extname(file));
            entries[entryName] = path.resolve(__dirname, file);
        }
    });

    cssFiles.forEach((file) => {
        if (isNotEmptyFile(file) && !file.includes('.min.css')) {
            const entryName = path.basename(file, '.css');
            entries[entryName] = path.resolve(__dirname, file);
        }
    });

    return entries;
}

module.exports = {
    entry: getEntryPoints(),
    output: {
        path: path.resolve(__dirname, 'dist'),
        filename: '[name].js',
    },
    mode: 'production',
    module: {
        rules: [
            {
                test: /\.(js|ts)$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env', '@babel/preset-typescript'],
                    },
                },
            },
            {
                test: /\.css$/,
                exclude: /node_modules/,
                use: [MiniCssExtractPlugin.loader, 'css-loader'],
            },
        ],
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: '[name].css',
        }),
    ],
    optimization: {
        minimize: true,
        minimizer: [new TerserPlugin(), new CssMinimizerPlugin()],
    },
    resolve: {
        extensions: ['.ts', '.js', '.css'],
    },
};
