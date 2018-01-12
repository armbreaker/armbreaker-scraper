import * as util from "utility";


let algos = { 
	"hamming": util.hammingdist
}

self.addEventListener("message", (event)=>{
	let data = event.data.data;
	let tolerence = event.data.tolerence;
	let algo = algos[event.data.algo];
	let result = util.lufu_cluster(data, tolerence, algo);
	self.postMessage(result);
});