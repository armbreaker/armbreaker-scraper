import * as util from "utility";
import { DateTime } from "luxon";

let timedata = null;
let mintime, maxtime;

function initdata(d) {
	timedata = d.times;
	mintime = d.mintime;
	maxtime = d.maxtime;
}

function bin(binsize) {
	// Poll for data.
	if (!timedata)
		setTimeout(()=>bin(binsize), 100);
	// first, initialize each day.
	let data = [];
	let startdate = DateTime.fromISO(mintime, {setZone: true});
	startdate = startdate.startOf("day");
	let enddate = DateTime.fromISO(maxtime, {setZone: true});
	enddate = enddate.startOf("day");

	let nextday = startdate.plus({days: binsize});
	while (nextday < enddate) {
		let dstart = startdate;
		let dend   = nextday;
		data.push({start: dstart, 
					 end: dend,
				 	 count: 0,
				 	 string: util.getDateRangeString(dstart, dend)});
		startdate = nextday
		nextday = nextday.plus({days: binsize});
	}

	data.push({start: startdate, 
				 end: enddate,
			 	 count: 0,
			 	 string: util.getDateRangeString(startdate, enddate)});

	for (let time of timedata) {
		let day = DateTime.fromISO(time, {setZone: true});
		for(let bin of data) {
			if (bin.start <= day && day <= bin.end) {
				bin.count += 1;
				break;
			}
		}
	}

	// Also create adjusted amounts
	for (let i = 0; i < data.length; i++) {
		let d = data[i];
		if (i == data.length - 1) {
   			let days = d.end.diff(d.start, "days").days;
   			if (days != binsize){
	   			d.adj = d.count / days * binsize;
	   			d.adj = d.count + (d.adj - d.count) * .5 // discount estimate
	   			continue;
	   		}
		}
		d.adj = d.count;
	}

	self.postMessage(data);
}

let algos = { 
	"hamming": util.hammingdist
}

self.addEventListener("message", (event)=>{
	let type = event.data.type;
	if (type == "init")
		initdata(event.data)
	else if (type == "bin")
		bin(event.data.binsize);
});