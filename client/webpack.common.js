const path = require('path');
const ExtractTextPlugin = require("extract-text-webpack-plugin");

module.exports = {
  entry: {
    "bundle.js": './src/main.js',
    "dropdowndemo.js": './src/dropdown_demo.js',
    "bundle.css": [
      "./src/FilterableDropdownModal.css",
      "./d3.slider/d3.slider.css",
      "./src/main.css"
    ]
  },
  output: {
    filename: '[name]',
    path: path.resolve(__dirname, 'dist')
  },
  module: {
  	loaders: [
  		{
  			test: /\.js$/,
  			loader: 'babel-loader',
  			exclude: /node_modules/,
  			query: {
  				presets: ['env']
  			}
  		}
  	],
    rules: [
      {
        test: /\.css$/,
        use: ExtractTextPlugin.extract({
          fallback: 'style-loader',
          use: 'css-loader'
        })
      },
      {
        test: /\.worker\.js$/,
        use: { loader: "worker-loader", options:{publicPath: "/js/", name:"[name].js"} }
      }
    ]
  },
  resolve: {
  	modules: [path.resolve(__dirname, "src"), "node_modules"]
  },
  plugins: [
    new ExtractTextPlugin("bundle.css")
  ],
  externals: ["d3", "moment"]
};