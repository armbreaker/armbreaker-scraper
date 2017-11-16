"use strict";

document.addEventListener("DOMContentLoaded", init);

var dataset;

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

// Grouped rendering functions into classes for convenience
// the likes per day view
class PerDayView {
	constructor() {
		this.margin_top = 60;
		this.margin_bottom = 50;
		this.margin_w = 25;
		this.width  = 800 - this.margin_w * 2;
		this.height = 430 - this.margin_top - this.margin_bottom;
	}

	setup() {
		this.svg = d3.select("#perdayview");

		let alltimes = [];
		// find time ranges.
		this.mintime = "9999-08-25T06:27:00+00:00";
		this.maxtime = "0000-01-01T00:00:00+00:00";
		for (let postid in dataset.posts) {
			let post = dataset.posts[postid];
			for (let key in post.likes) {
				let time = post.likes[key];
				if (this.mintime > time) {
					this.mintime = time;
				} else if (this.maxtime < time) {
					this.maxtime = time;
				}
				alltimes.push(time);
			}
		}

		// extract likes per day.
		// first, initialize each day.
		let data = {};
		let oneday = moment.duration(1, "days");
		let startdate = getDate(this.mintime);
		let enddate = getDate(this.maxtime);
		while (startdate.clone().add(oneday) < enddate) {
			data[getDateString(startdate)] = 0;
			startdate.add(oneday);
		}
		data[getDateString(enddate)] = 0;

		for (let time of alltimes) {
			let day = getDate(time); // conversion puts dates into user timezone.
			let datestring = getDateString(day);
			data[datestring] += 1;
		}
		// flatten to ensure order.
		this.data = [];
		for (let date in data) {
			this.data.push([date, data[date]]);
		}
		this.data = this.data.sort((a, b)=>{
			if (a > b) return 1;
			if (a < b) return -1;
			return 0;
		});

		// also create sparkline data.
		this.totallikes = arrsum(this.data, d=>d[1]);
		this.sparkdata = [];
		let datanum = this.data.length;
		let sum = 0;
		let slope = this.totallikes / parseFloat(datanum);
		for (let i = 0; i < this.data.length; i++) {
			let date  = this.data[i][0];
			let likes = this.data[i][1];
			// compare expected likes versus actual likes
			// let diff = likes - slope; // likes vs average likes
			sum += likes;
			let diff = sum - (slope * (i+1))
			this.sparkdata.push([date, diff]);
		}
		this.sparkdataMagnitude = Math.abs(arrmax(this.sparkdata, d=>Math.abs(d[1]))[1]);

		// find new min and max times.
		this.mintime = this.data[0][0];
		this.maxtime = this.data[this.data.length - 1][0];

		// find max like magnitude, then create scales & axes
		this.maxlikes = arrmax(this.data, d=>d[1])[1];

		this.xscale = 
			d3.scaleBand()
			  .domain(this.data.map(d=>d[0]))
			  .paddingOuter(10)
			  .range([0, this.width]);
	    this.yscale = 
			d3.scaleLinear()
			  .domain([0, Math.floor(this.maxlikes * 1.05)])
			  .range([1, this.height]);
	    // have to invert scale for y axis
	    this.yscale_axis = this.yscale
	    	.copy()
	        .range([this.height, 1]);
        // scale for trend height.
		this.sparklinescale =
			d3.scaleLinear()
			  .domain([-this.sparkdataMagnitude, this.sparkdataMagnitude])
			  .range([this.margin_bottom / 2, -this.margin_bottom / 2]);

		this.xaxis =
			d3.axisBottom(this.xscale)
		this.yaxis =
			d3.axisLeft(this.yscale_axis);

		// draw axes
		this.svg.select(".all")
		    .attr("transform", `translate(${this.margin_w}, ${this.margin_top})`)
		this.svg.select(".bars");
		this.svg.select(".xaxis")
		    .call(this.xaxis)
		    .attr("transform", `translate(0, ${this.height})`);
		this.svg.select(".yaxis")
			.call(this.yaxis);

		this.sparkline = 
			d3.line()
			  .x(d=>this.xscale(d[0])+ this.xscale.bandwidth() / 2)
			  .y(d=>this.sparklinescale(d[1]));

	    // Create tooltip.
	    this.svg.select(".sparkline")
	    	.append("circle") // the dot on the sparkline
	    	.classed("tooltipDot", true)
	    	.classed("tooltip", true);
	    let tooltip = this.svg.select(".tooltiptext");
	    tooltip.style("display", "none");
	    tooltip
	    	.append("rect")
	    	.attr("width" , "100")
	    	.attr("height", "49")
	    	.attr("x", -50)
	    	.attr("y", -25)
	    	.attr("rx", 5)
	    	.attr("ry", 5);

		tooltip
		    .append("text")
	    	.classed("tooltipline1", true)
	    	.attr("y", -5);
	    tooltip
	    	.append("text")
	    	.classed("tooltipline2", true)
	    	.attr("y", 15);

	    this.svg.select(".all")
	    	.append("path")
	    	.classed("tooltipguide", true)
	    	.classed("tooltip", true);
	}

	update() {
		let sel = this.svg
			.select(".bars")
			.selectAll(".view1bars")
			.data(this.data);
		let myself = this;
		sel.enter()
		   .append("rect")
		   .classed("view1bars", true)
		   .attr("width", d=>this.xscale.bandwidth())
		   .attr("height", d=>this.yscale(d[1]))
		   .attr("x", d=>this.xscale(d[0]))
		   .attr("y", d=>this.height - this.yscale(d[1]))
		   .on("mouseover", function(d, i){
		   		myself.svg.selectAll(".tooltip").style("display", "inherit");

		   		let sparkline_y = myself.sparklinescale(myself.sparkdata[i][1]);
				myself.svg.select(".tooltipDot")
					.attr("cx", myself.xscale(d[0]) + myself.xscale.bandwidth() / 2)
					.attr("cy", sparkline_y)
					.attr("r" , 3);
				// distance from top of bar to sparkline.
				let y = myself.height - myself.yscale(d[1]);
				let x = myself.xscale(d[0]) + myself.xscale.bandwidth() / 2;
				let len = myself.yscale(d[1]) + (sparkline_y - myself.margin_bottom / 2);
				myself.svg.select(".tooltipguide")
					.attr("d", `M${x},${y}L${x},${myself.height + myself.margin_bottom}`);

				// need to find text width to center.
				let line1_w = getTextWidth(d[0], '"Open Sans" 12pt');
				let line2_w = getTextWidth(d[1] + " likes", '"Open Sans" 12pt');
				let align_left = line1_w - line2_w;
				myself.svg.select(".tooltipline1").text(d[0]);
				myself.svg.select(".tooltipline2").text(d[1] + " likes");

				// also move the entire tooltip
				myself.svg.select(".tooltiptext")
					.attr("transform", `translate(${x},${y - 30})`);
		   });
	    this.svg.select("rect")
	        .on("click", function(){
		   		myself.svg.selectAll(".tooltip").style("display", "none");
	        })
		// also add sparkline
		let sparkline = this.svg.select(".sparkline")
		    .attr("transform", `translate(0, ${this.height + this.margin_bottom / 2})`);
		sparkline
		    .append("line")
			.classed("normalline", true)
		    .attr("x1", 0)
		    .attr("x2", this.width)
		    .attr("y1", 0)
		    .attr("y2", 0);

		sparkline
			.append("path")
			.classed("dataline", true)
			.datum(this.sparkdata)
			.attr("d", this.sparkline);
	}
}

// the likes per chapter per user view
class UserView {
	constructor() {

	}

	setup() {

	}

	update() {

	}
}

// the likes over time for the first 24h view
class FirstImpressionsView {
	constructor() {

	}

	setup() {

	}

	update() {

	}
}

// the likes per chapter view
class ChapterLikesView {
	constructor() {

	}

	setup() {

	}

	update() {

	}
}

var views = [
	new PerDayView(),
	new UserView(),
	new FirstImpressionsView(),
	new ChapterLikesView()
];

// Initialize everything.
function setup() {
	d3.select("#title h1").text(dataset.info.title);
	for (let view of views) {
		view.setup();
		view.update();
	}
}

function init() {
	d3.json("testdata.json", (err, data)=>{
		dataset = data;
		setup();
	});
}
