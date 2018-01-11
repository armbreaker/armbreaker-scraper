import * as util from "utility";
import startOfDay from "date-fns/start_of_day";
import addDays from 'date-fns/add_days';
import isBefore from 'date-fns/is_before';
import differenceInDays from 'date-fns/difference_in_days';


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
	let startdate = util.stripTimezone(mintime);
	startdate = startOfDay(startdate);
	let enddate = util.stripTimezone(maxtime);
	enddate = startOfDay(enddate);

	let nextday = addDays(startdate, binsize);
	while (isBefore(nextday, enddate)) {
		let dstart = startdate;
		let dend   = nextday;
		let s1 = util.getDateString(dstart);
		let s2 = util.getDateString(dend);
		data.push({start: dstart, 
					 end: dend,
				 	 count: 0,
				 	 string: util.getDateRangeString(dstart, dend)});
		nextday = addDays(nextday, binsize);
	}

	data.push({start: startdate, 
				 end: enddate,
			 	 count: 0,
			 	 string: util.getDateRangeString(startdate, enddate)});

	for (let time of timedata) {
		let day = util.stripTimezone(time);
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
   			let days = differenceInDays(d.end, d.start, "days");
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