const path = require('path');
const ExtractTextPlugin = require("extract-text-webpack-plugin");

module.exports = {
  entry: {
    "js/bundle.js": './src/main.js',
    "js/dropdowndemo.js": './src/dropdown_demo.js',
    "css/bundle.css": [
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
      }
    ]
  },
  resolve: {
  	modules: [path.resolve(__dirname, "src"), "node_modules"]
  },
  plugins: [
    new ExtractTextPlugin("css/bundle.css")
  ],
  externals: ["d3", "moment"]
};