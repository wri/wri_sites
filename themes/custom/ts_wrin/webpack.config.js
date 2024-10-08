const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const path = require("path");
const BrowserSyncPlugin = require("browser-sync-webpack-plugin");

module.exports = (env, argv) => {
  const isDevMode = argv.mode === "development";
  return {
    mode: isDevMode ? "development" : "production",
    devtool: isDevMode ? "source-map" : false,
    entry: {
      main: ["./js/main.js", "./sass/style.scss"],
      ckeditor5: ["./sass/ckeditor.scss"]
    },
    module: {
      rules: [
        {
          test: /\.scss$/,
          use: [
            {
              loader: MiniCssExtractPlugin.loader
            },
            {
              loader: "css-loader",
              options: {
                sourceMap: true
              }
            },
            {
              loader: "postcss-loader",
              options: {
                sourceMap: true
              }
            },
            {
              loader: "sass-loader",
              options: {
                implementation: require("sass"),
                sourceMap: true
              }
            }
          ]
        },
        {
          test: /\.js$/,
          exclude: /(node_modules|bower_components)/,
          use: {
            loader: "babel-loader",
            options: {
              presets: [["@babel/preset-env", { modules: false }]]
            }
          }
        },
        {
          test: /\.svg/,
          type: "asset/inline"
        },
        {
          test: /\.(jpg|png|gif)$/,
          type: "asset/resource"
        }
      ]
    },
    output: {
      path: isDevMode
        ? path.resolve(__dirname, "dist_dev")
        : path.resolve(__dirname, "dist"),
      filename: "[name].min.js",
      publicPath: "/libraries/ts_wrin/"
    },
    plugins: [
      new MiniCssExtractPlugin(),
      new BrowserSyncPlugin({
        host: "localhost",
        port: 3000,
        // Replace proxy url with your local.
        proxy: "http://web.wriflagship.localhost/"
      })
    ],
    optimization: {
      usedExports: true
    },
    externals: {
      // require("jquery") is external and available
      //  on the global var jQuery
      jquery: "jQuery"
    }
  };
};
