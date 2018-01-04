const path = require('path');

module.exports = {
  entry: './src/main.js',
  output: {
    filename: 'bundle.js',
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
  	]
  },
  resolve: {
  	modules: [path.resolve(__dirname, "src"), "node_modules"]
  },
  externals: ["d3", "moment"]
};