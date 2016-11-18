const elixir = require('laravel-elixir');


/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for our application, as well as publishing vendor resources.
 |
 */


elixir(mix => {
	mix.sass('app.scss');

    // mix.styles([
    //     'resources/assets/css/sb-admin.css',
    //     'public/css/app.css'
    // ], 'public/css/main.css', './');

    //mix.version('public/css/main.css');

    mix.webpack('app.js');
});
