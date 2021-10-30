const Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or sub-directory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app_header', './assets/app_header.js')
    .addEntry('app', './assets/app.js')
    .addEntry('thread', './assets/thread.js')
    .addEntry('event', './assets/event.js')
    .addEntry('plan', './assets/plan.js')
    .addEntry('qa', './assets/qa.js')
    .addEntry('school', './assets/school.js')
    .addEntry('settings', './assets/settings.js')
    .addEntry('workflow', './assets/workflow.js')
    .addEntry('workflow_workspace', './assets/workflow_workspace.js')
    .addEntry('ladb_values', './assets/ladb_values.js')
    // Vendors entry points
    .addEntry('blueimp', './assets/blueimp.js')
    .addEntry('leaflet', './assets/leaflet.js')
    .addEntry('tocify', './assets/tocify.js')
    .addEntry('barrating', './assets/barrating.js')
    .addEntry('selectize', './assets/selectize.js')


    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    .enableStimulusBridge('./assets/controllers.json')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    // .enableVersioning(Encore.isProduction())
    .enableVersioning(true)

    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

    // enables Sass/SCSS support
    .enableSassLoader()
    .enableLessLoader()

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use React
    //.enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    .autoProvidejQuery()

    // Add icons
    .copyFiles([
        {from: './assets/ladb/fonts/', to: 'fonts/[path][name].[ext]', pattern: /\.(eot|svg|ttf|woff)$/}
    ])

    // Add pdfjs
    .copyFiles([
        {from: './assets/ladb/pdfjs/', to: 'pdfjs/[path][name].[ext]'}
    ])

    // Add images
    .copyFiles([
        {from: './assets/ladb/images/', to: 'images/[path][name].[ext]', pattern: /\.(jpg|png|gif|jpeg|svg)$/}
    ])
;

module.exports = Encore.getWebpackConfig();
