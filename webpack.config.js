var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('./src/Resources/public')
    .setPublicPath('./')
    .setManifestKeyPrefix('bundles/webelop-album')


    .addEntry('app', './assets/js/app.js')
    .addEntry('admin', './assets/js/admin.js')

    .disableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(false)
    .enableVersioning(false)

    // enables @babel/preset-env polyfills
    .configureBabel(() => {}, {
        useBuiltIns: 'usage',
        corejs: 3
    })

    .enableSassLoader()
    .copyFiles({
        from: './assets/images',
        to: 'images/[path][name].[ext]'
    })

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()

    // uncomment if you use API Platform Admin (composer req api-admin)
    //.enableReactPreset()
    //.addEntry('admin', './assets/js/admin.js')
;

module.exports = Encore.getWebpackConfig();
