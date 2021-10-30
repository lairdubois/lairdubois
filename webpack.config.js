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
    .addEntry('user_settings', './assets/user_settings.js')
    .addEntry('message_thread', './assets/message_thread.js')
    .addEntry('event_event', './assets/event_event.js')
    .addEntry('wonder_plan', './assets/wonder_plan.js')
    .addEntry('qa_question', './assets/qa_question.js')
    .addEntry('knowledge_school', './assets/knowledge_school.js')
    .addEntry('workflow_workflow', './assets/workflow_workflow.js')
    .addEntry('workflow_workflow_workspace', './assets/workflow_workflow_workspace.js')
    .addEntry('knowledge_values', './assets/knowledge_values.js')
    // Libs entry points
    .addEntry('lib_blueimp', './assets/lib_blueimp.js')
    .addEntry('lib_leaflet', './assets/lib_leaflet.js')
    .addEntry('lib_tocify', './assets/lib_tocify.js')
    .addEntry('lib_barrating', './assets/lib_barrating.js')
    .addEntry('lib_selectize', './assets/lib_selectize.js')


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
