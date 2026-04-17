const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js').vue()
    .sass('resources/sass/app.scss', 'public/css')
    .webpackConfig(webpack => {
        return {
            plugins: [
                new webpack.DefinePlugin({
                    __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: JSON.stringify(false),
                }),
            ],
        };
    });
