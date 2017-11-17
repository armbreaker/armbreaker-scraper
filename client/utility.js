// keyfunc is applied to each object before comparing.
function arrmin(arr, keyfunc) {
	if (keyfunc == undefined) {
		keyfunc = d=>d;
	}
	return arr.reduce((a, b)=>{
		// return the smaller object
		return keyfunc(a) > keyfunc(b) ? b : a;
	})
}

// keyfunc is applied to each object before comparing.
function arrmax(arr, keyfunc) {
	if (keyfunc == undefined) {
		keyfunc = d=>d;
	}
	return arr.reduce((a, b)=>{
		// return the larger object
		return keyfunc(a) > keyfunc(b) ? a : b;
	})
}

function arrsum(arr, keyfunc) {
	if (keyfunc == undefined) {
		keyfunc = d=>d;
	}
	return arr.reduce((accum, curr)=>{
		// return the larger object
		return accum + keyfunc(curr);
	}, 0)
}

// truncate so there are only n digits
function n_digits(x, n) {
	let e = Math.pow(10, n);
	return Math.floor(e * x) / e;
}

function getDate(timestr) {
	return moment(timestr).hour(0).minute(0).second(0).millisecond(0);
}

// accept a Moment obj, return YYYY-MM-DD
function getDateString(moment) {
	return moment.format("YYYY-MM-DD");
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
function getTextWidth (text, font) {
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
function findTextWidth(text, font, target) {
	var size = 16;
	var textsize = target * 10;
	while (textsize > target) {
		size -= 1;
		textsize = getTextWidth(text, size + "px " + font);
	}
	return size
}

var TOLERENCE = 1;
function levdist(a, b) {
	if (a == b) return 0;
	let dist = levenshtein(a, b);
	if (dist == 0) return 0;
	if (dist <= TOLERENCE) {
		// return normal comparison if under tolerence
		if (a > b) return 1;
		if (a < b) return -1;
		return 0;
	}
	// If the difference is too large, the one with the
	// greater significant digit is returned as first.
	for (let i = a.length - 1; 0 <= i; i--) {
		// keep looping until digit isn't same
		if (a[i] != b[i]) {
			if (a[i] == "x") return 1;
			return -1; // case of b[i] == "x"
		}
	}
	// shouldn't get here
	throw("levdist somehow reached end of function.");
}