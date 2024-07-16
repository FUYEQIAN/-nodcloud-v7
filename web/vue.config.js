module.exports = {
    parallel: false,
    assetsDir: 'static',
    filenameHashing: false,
    productionSourceMap: false,
    configureWebpack: {
        resolve: {
            alias: {
                '#': require('path').join(__dirname, 'public')
            }
        }
    },
    chainWebpack: (config) => {
        config.plugins.delete('prefetch');
        config.optimization.splitChunks({
            automaticNameDelimiter: '.'
        });
    },
    css: {
        sourceMap: false
    }
};
