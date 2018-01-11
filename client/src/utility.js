"use strict";
import format from 'date-fns/format';

// keyfunc is applied to each object before comparing.
export function arrmin(arr, keyfunc) {
	if (keyfunc == undefined) {
		keyfunc = d=>d;
	}
	return arr.reduce((a, b)=>{
		// return the smaller object
		return keyfunc(a) > keyfunc(b) ? b : a;
	})
}

// keyfunc is applied to each object before comparing.
export function arrmax(arr, keyfunc) {
	if (keyfunc == undefined) {
		keyfunc = d=>d;
	}
	return arr.reduce((a, b)=>{
		// return the larger object
		return keyfunc(a) > keyfunc(b) ? a : b;
	})
}

export function arrsum(arr, keyfunc) {
	if (keyfunc == undefined) {
		keyfunc = d=>d;
	}
	return arr.reduce((accum, curr)=>{
		// return the larger object
		return accum + keyfunc(curr);
	}, 0)
}

export function arrsort(arr, reverse, keyfunc) {
	if (keyfunc == undefined) {
		keyfunc = d=>d;
	}
	if (reverse) {
		return arr.sort((a, b)=>{
			a = keyfunc(a);
			b = keyfunc(b);
			if (a > b) return -1;
			if (a < b) return 1;
			return 0;
		});
	}
	return arr.sort((a, b)=>{
		a = keyfunc(a);
		b = keyfunc(b);
		if (a > b) return 1;
		if (a < b) return -1;
		return 0;
	});
}

// As indexOf, but accepts an accessor function
export function indexOfKey(value, array, accessor) {
	if (accessor == undefined)
		accessor = x=>x;
	for (let i = 0; i < array.length; i++) {
		if (accessor(array[i]) == value)
			return i;
	}
	return -1;
}

// truncate so there are only n digits
export function n_digits(x, n) {
	let e = Math.pow(10, n);
	return Math.floor(e * x) / e;
}

// for a "2017-07-23T22:23:52+00:00" format timestamp,
// strip TZ and insert into Date obj. Because
// Date sucks.
export function stripTimezone(timestr) {
	let s = timestr.split("+")[0];
	return new Date(s);
}

// accept a Date obj, return YYYY-MM-DD
export function getDateString(date) {
	return format(date, "YYYY-MM-DD");
}

export function getDateRangeString(datestart, dateend) {
	return `${format(datestart, "YYYY-MM-DD")}\n${format(dateend, "YYYY-MM-DD")}`;
}

// set all times to same date
export function makeTimeOnlyMoment(timestr) {
	let m = moment(timestr);
	m.year(2015).month(0).date(15).day("Thursday");
	return m;
}

// good ol' text width estimator. i seriously use this everywhere
// Taken from https://github.com/Skyyrunner/JeevesCoursePlanner/blob/master/client/typescript/utility.ts
/**
 * Uses canvas.measureText to compute and return the width of the given text of given font in pixels.
 *
 * @param text The text to be rendered.
 * @param font The css font descriptor that text is to be rendered with (e.g. "bold 14px verdana").
 * @returns The estimated width of the text in pixels.
 *
 * @see http://stackoverflow.com/questions/118241/calculate-text-width-with-javascript/21015393#21015393
 */
export function getTextWidth (text, font) {
	// re-use canvas object for better performance
	var canvas = getTextWidth.prototype.canvas || (getTextWidth.prototype.canvas = document.createElement("canvas"));
	var context = canvas.getContext("2d");
	context.font = font;
	var metrics = context.measureText(text);
	return metrics.width;
};

/**
 * Uses `getTextWidth()` and a trial-and-error approach to fitting a given
 * string into a given area.
 * @param text The text to be rendered.
 * @param font The name of the font-family to render in.
 * @param target The target width.
 * @returns The appropriate font size so that the text is at most `target` pixels wide.
 */
export function findTextWidth(text, font, target) {
	var size = 16;
	var textsize = target * 10;
	while (textsize > target) {
		size -= 1;
		textsize = getTextWidth(text, size + "px " + font);
	}
	return size
}

export function hammingdist(s1, s2) {
	let L = s1.length;
	if (L != s2.length)
		throw "Hamming distance requires strings of same length.";
	let d = 0;
	for (let i = L - 1; 0 <= i; i--) {
		if (s1[i] != s2[i])
			d++;
	}
	return d;
}

export function getKeys(o) {
	let out = [];
	for (let key in o) {
		out.push(key);
	}
	return out;
}

export function symbolcount(string, symbol) {
	let re = new RegExp(symbol, "g");
	return (string[1].match(re) || []).length;
}

// Put elements in first cluster that is under threshold
export function greedy_cluster(data, threshold, algo) {
	arrsort(data, d=>symbolcount(d[1], "x"));
	let clusters = [];
	clusters.push([[...data[0], 0]]);
	for(let el of data.slice(1)) {
		let succeeded = false;
		for (let cluster of clusters) {
			if (algo(el[1], cluster[0][1]) <= threshold) {
				cluster.push([...el, 0]);
				succeeded = true;
				break;
			}
		}
		// new cluster
		if (!succeeded) {			
			clusters.push([[...el, 0]]);
		}
	}
	return clusters;
}

export function lufu_cluster(data, threshold, algo) {
	arrsort(data, d=>symbolcount(d[1], "x"));
	let clusters = [];
	clusters.push([[...data[0], 0]]);
	for(let el of data.slice(1)) {
		let temp = [];
		for (let cluster of clusters) {
			// find min score for this cluster
			let min = Infinity;
			for (let el2 of cluster) {
				let score = algo(el[1], el2[1]);
				if (score < min)
					min = score;
			}
			temp.push([cluster, min]);
		}
		// find smallest
		let min = arrmin(temp, d=>d[1]);
		if (min[1] <= threshold)
			min[0].push([...el, 0]);
		else
			clusters.push([[...el, 0]]);
	}
	return clusters;
}
