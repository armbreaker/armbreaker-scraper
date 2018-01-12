"use strict";

import * as d3 from "d3";
import moment from "moment";
import "moment-timezone";
import * as util from "utility";
import timezones from "timezone-array";
import Dropdown from "FilterableDropdownModal";

// the likes over time for the first 24h view
export default class FirstImpressionsView { 
	constructor() {
		this.margin_top = 30;
		this.margin_bottom = 30;
		this.margin_w = 25;
		this.width  = 800 - this.margin_w;
		this.height = 350 - this.margin_top - this.margin_bottom;
		this.markwidth = 50;
		this.markmargin = 5;
		this.scroll = 0; // amount scrolls
		this.terminalheight = 20; // how much below 48 hours to draw terminal width
	}

	resize() {
		d3.select("#firstimpressionview").attr("width", window.innerWidth * .9);
	}

	// Regenerate shapes and axes
	generateShapes() {
	}

	setWindow(windowsize) {
		this.windowsize = windowsize;
		this.generateShapes();
	}

	// change timezone for axis (default UTC+0)
	setTimezone(timezone) {
		this.timezone = timezone;
		this.generateShapes();
	}

	setup(dataset) {
		this.svg = d3.select("#firstimpressionview");
		// Populate timezone list.
		let timezonedata = timezones.map(d=>{
			let o = [d, null];
			let offset = -moment.tz.zone(d).utcOffset(0);
			let offsethrs = Math.floor(Math.abs(offset) / 60);
			let offsetmin = Math.floor(Math.abs(offset) % 60);
			let hrsstr = String(Math.abs(offsethrs)).padStart(2, 0);
			let minstr = String(Math.abs(offsetmin)).padStart(2, 0);
			if (offset >= 0)
				o[1] = `(UTC+${hrsstr}:${minstr}) ${d}`;
			else
				o[1] = `(UTC–${hrsstr}:${minstr}) ${d}`;
			return o;
		}); 
		timezonedata = util.arrsort(timezonedata, false, d=>-moment.tz.zone(d[0]).utcOffset(0));
		let dropdown = new Dropdown(timezonedata, "#timezones");
		dropdown.setup();
		dropdown.setdefault_value("Europe/Dublin");

		this.windowsize = 24;
		this.timezone = 0;
		this.generateShapes();

		// Create scales and axes
		this.yscale = d3.scaleLinear()
			.domain([0, moment.duration(2, "days").asMilliseconds()])
			.range([0, this.height]);
		let ticks = [];
		for (let i = 0; i < 24; i++) {
			ticks.push(i * moment.duration(2, "hours").asMilliseconds());
		}
		this.yaxis = d3.axisLeft(this.yscale)
			.tickValues(ticks)
			.tickFormat(d=>moment({y:2015, M:1, d:15}).add(d, "ms").format("HH"));
		this.xscale = i=>(this.markwidth + this.markmargin) * i + 0.5 * this.markwidth;

		// Want to extract pertinent information
		let posts = dataset.posts.posts;
		let data = [];
		for (let post of posts) {
			let obj = {};
			obj.time = moment(post.time);
			obj.title = post.title;
			let likes = [];
			for (let like_src of post.likes.likes) {
				let like_dst = {};
				like_dst.userid = like_src.user.id;
				like_dst.username = like_src.user.name;
				like_dst.time = like_src.time;
				likes.push(like_dst);
			}
			util.arrsort(likes, false, d=>d.time);
			for (let like of likes)
				like.time = moment(like.time);
			obj.likes = likes;
			// find only likes that are within 24h
			for (let i = 0; i < obj.likes.length; i++) {
				let like = obj.likes[i];
				if (like.time.diff(obj.time) >= moment.duration(1, "day").asMilliseconds()) {
					obj.cappedlikes = obj.likes.slice(0, i);
					break;
				}
			}
			// make area generator
			obj.markscale = d3.scaleLinear()
				.domain([0, obj.likes.length])
				.range([0, this.markwidth]);

			obj.areagen = d3.area()
				.x0((d,i)=>-0.5 * obj.markscale(i))
				.x1((d,i)=> 0.5 * obj.markscale(i))
				.y0((d,i)=>this.yscale(d.time.clone().diff(obj.time)))
				.y1((d,i)=>this.yscale(d.time.clone().diff(obj.time)));
			data.push(obj);
		}
		this.data = data;

		// Initialize
		let svg = this.svg;
		svg.select(".all")
		   .attr("transform", `translate(${this.margin_w}, ${this.margin_top})`);
		svg.select(".yaxis")
		   .call(this.yaxis);

	    this.drag = d3.drag()
	    	.on("drag", d=>{
	    		this.scroll += d3.event.dx;
	    		this.svg
	    			.select(".marks_drag")
	    			.attr("transform", `translate(${this.scroll}, 0)`);
	    	})

	    svg.select(".drag_hitbox")
	       .call(this.drag);

        // adding slider...
		this.binsizes = [24, 24 * 2, 24 * 3, 24 * 7, 24 * 14];
		this.sliderticks = [24, 24 * 2, 24 * 3, 24 * 7, 24 * 14];
		var sliderFromBin = val=>{
			let i = this.binsizes.indexOf(val);
			return this.sliderticks[i];
		}

		var binFromSlider = val=>{
			let i = this.sliderticks.indexOf(val);
			return this.binsizes[i];
		}

		let slidertickformat = (d)=>{
			d = binFromSlider(d);
			if (d <= 48)
				return d + " hours";
			return d/24 + " days";
		}

    	this.slider = d3
    		.slider()
    		.min(this.sliderticks[0])
    		.max(this.sliderticks[this.sliderticks.length - 1])
    		.tickValues(this.sliderticks)
    		.stepValues(this.sliderticks)
    		.value(sliderFromBin(this.binsize))
    		.tickFormat(slidertickformat)
    		.callback(()=>{
    			let val = this.slider.value();
    			val = binFromSlider(val);
    			if (this.alltimes.length / val >= 1) {
		    		// this.bin(this.slider.value());
		    		// this.update();
    			}
	    	});

	    // Hook up controls.
	    d3.select("#firstimpressions_windowslider")
    	  .call(this.slider);
	}

	// take in chapter obj, return area path for longtail. 
	drawLongtail(datum) {
		// Areagen mirrors right sided to left.
		let a = d3.area()
			.x0(d=>-d[0])
			.x1(d=>d[0])
			.y0(d=>d[1])
			.y1(d=>d[1])
			.curve(d3.curveBasis);
		let numlikes = datum.cappedlikes.length;
		let lastlike = datum.cappedlikes[numlikes - 1];
		let tail_start = this.yscale(lastlike.time.clone().diff(datum.time));
		let time_above_cap = util.makeTimeOnlyMoment(datum.time).diff(moment("2015-01-15"));
		let tail_end = this.yscale(moment.duration(2, "day").asMilliseconds() - time_above_cap);
		let half_width = datum.markscale(numlikes) * 0.5;
		let half_final = 0.5 * this.markwidth;

		return a([
			[half_width, tail_start],
			[half_width * 0.8 + half_final * 0.2, tail_end * 0.7 + tail_start * 0.3],
			[half_final, tail_end + this.terminalheight]
		]);
	}

	update() {
		let me = this;
		let sel = this.svg.select(".marks")
			.selectAll(".mark")
			.data(this.data);
		sel.enter()
		   .append("g")
		   .classed("mark", true)
		   .attr("transform", (d,i)=>{
		   		let dur = util.makeTimeOnlyMoment(d.time).diff(moment("2015-01-15"));
		   		return `translate(${this.xscale(i)},${this.yscale(dur)+4})`;
			})
		   .each(function(d, i) {
		   		let sel = d3.select(this);
		   		// Draw the long tail
		   		sel.append("path")
		   		   .classed("longtail", true)
		   		   // four corners, starting top-left
		   		   .attr("d", me.drawLongtail(d));

		   		// append the main mark
		   		sel.append("path")
		   		   .classed("markpath", true)
		   		   .attr("d", d.areagen(d.cappedlikes));
	   		    // append the start time
	   		    sel.append("circle")
	   		       .classed("markcap", true)
	   		       .attr("cx", 0)
	   		       .attr("cy", 0)
	   		       .attr("r", 2.5);
		   });
	}
}