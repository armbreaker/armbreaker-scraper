"use strict";

import * as d3 from "d3";
import * as moment from "moment";
import * as util from "utility";
import BinWorker from "./dobinning.worker.js";
import { DateTime } from "luxon";

// the likes per day view
export default class PerDayView {
	constructor() {
		this.margin_top = 80;
		this.margin_bottom = 50;
		this.margin_w = 30;
		this.width  = 800 - this.margin_w * 2;
		this.height = 460 - this.margin_top - this.margin_bottom - 10;
		this.binsize = 1;
		this.binner = new BinWorker();
		this.waitupdate = false;
		this.bindata = {};
	}

	binCallback(data, bin, cached) {
		// convert back to DateTime objects
		if (!cached) {
			this.bindata[bin] = data.map(d=>{
				d.start = util.reTypifyDatetime(d.start);
				d.end = util.reTypifyDatetime(d.end);
				return d
			});	
		}
		data = this.bindata[bin]

		// also create sparkline data.
		this.totallikes = util.arrsum(data, d=>d.count);
		this.sparkdata = [];
		let datanum = data.length;
		let sum = 0;
		let slope = this.totallikes / parseFloat(datanum);
		for (let i = 0; i < datanum; i++) {
			let date  = data[i].string;
			let likes = data[i].count;
			// compare expected likes versus actual likes
			// let diff = likes - slope; // likes vs average likes
			sum += likes;
			let diff = sum - (slope * (i+1));
			this.sparkdata.push([date, diff]);
		}
		this.sparkdataMagnitude = Math.abs(util.arrmax(this.sparkdata, d=>Math.abs(d[1]))[1]);

		// reset axes

		// find max like magnitude, then create scales & axes
		this.maxlikes = util.arrmax(data, d=>d.adj).adj; // adj, to account for estimate

		this.xscale = 
			d3.scaleBand()
			  .domain(data.map(d=>d.string))
			  .paddingOuter(10)
			  .paddingInner(this.binsize == 1 ? 0 : 0.1)
			  .range([0, this.width]);
	    this.yscale = 
			d3.scaleLinear()
			  .domain([0, Math.floor(this.maxlikes * 1.05)])
			  .range([0, this.height]);
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
			d3.axisLeft(this.yscale_axis)
			  .tickFormat(d3.format(".2s"));

		// draw axes
		this.svg.select(".all")
		    .attr("transform", `translate(${this.margin_w}, ${this.margin_top})`)
		this.svg.select(".bars");
		this.svg.select(".xaxis")
			.transition()
		    .call(this.xaxis)
		    .attr("transform", `translate(0, ${this.height})`);
		this.svg.select(".yaxis")
			.transition()
			.call(this.yaxis);

		this.sparkline = 
			d3.line()
			  .x(d=>this.xscale(d[0])+ this.xscale.bandwidth() / 2)
			  .y(d=>this.sparklinescale(d[1]));

		this.waitupdate = false;
	}

	bin(newbin) {
		this.binsize = newbin;
		if (String(newbin) in this.bindata) {
			this.binCallback(this.bindata[newbin], newbin, true);
		} else {
			// extract likes per day.this.svg.select("image")
			this.svg.select(".gear")
				.style("opacity", 0)
				.transition()
				.style("opacity", 1)
			this.waitupdate = true;
			this.binner.onmessage = (event)=>this.binCallback(event.data, newbin, false);
			this.binner.postMessage({type:"bin", binsize: newbin});
		}
	}

	setup(dataset) {
		let myself = this;
		this.svg = d3.select("#perdayview");

		this.alltimes = [];
		// find time ranges.
		this.mintime = dataset.posts.earliest;
		this.maxtime = dataset.posts.latest;
		for (let post of dataset.posts.posts) {
			for (let like of post.likes.likes) {
				this.alltimes.push(like.time);
			}
		}
		this.binner.postMessage({type:"init", times:this.alltimes, mintime: this.mintime, maxtime:this.maxtime})
		let L = this.alltimes.length;
		if (L < 120)
			this.bin(1);
		else if (L/7 < 120)
			this.bin(7);
		else if (L/14 < 120)
			this.bin(14)
		else if (L/30 < 120)
			this.bin(30)
		else
			this.bin(120);

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
	    	.attr("height", "70")
	    	.attr("x", -50)
	    	.attr("y", -45)
	    	.attr("rx", 5)
	    	.attr("ry", 5);

		tooltip
		    .append("text")
	    	.classed("tooltipline1", true)
	    	.classed("tooltiptext", true)
	    	.attr("y", -25);
	    tooltip
	    	.append("text")
	    	.classed("tooltipline2", true)
	    	.classed("tooltiptext", true)
	    	.attr("y", -5);
	    tooltip
	    	.append("text")
	    	.classed("tooltipline3", true)
	    	.classed("tooltiptext", true)
	    	.attr("y", 15);

	    this.svg.select(".all")
	    	.append("path")
	    	.classed("tooltipguide", true)
	    	.classed("tooltip", true);

		this.binsizes = [1, 7, 14, 30, 120, 365];
		this.sliderticks = [1, 7, 14, 30, 60, 120];
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
			if (d == 1)
				return "1 day"
			if (d < 30)
				return d + " days";
			if (d == 30)
				return "1 month";
			if (d == 120)
				return "4 months";
			if (d == 365)
				return "1 year";
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
	    		this.bin(val);
	    		this.update();
	    	});

	    // Hook up controls.
	    d3.select("#binslider")
    	  .call(this.slider);

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
			.classed("dataline", true);
	}

	update() {
		let myself = this;
   		myself.svg.selectAll(".tooltip").style("display", "none");
		if (this.waitupdate) {
			setTimeout(()=>this.update(), 100);
			return;
		}
		let sel = this.svg
			.select(".bars")
			.selectAll(".view1bargroup")
			.data(this.bindata[this.binsize]);
		sel.exit().remove();
		let enter = sel
			.enter()
	   		.append("g")
	   		.classed("view1bargroup", true)
	   		.attr("transform", d=>`translate(${this.xscale(d.string)}, 0)`);
	   	enter
	   		.append("rect")
	   		.classed("view1barback", true);
	   	enter
	        .append("rect")
	        .classed("view1bar", true);
	    enter
	        .append("rect")
	        .classed("transbars", true)
	        .attr("height", this.height + this.margin_bottom)
		    .on("mouseover", function(d, i){
		    	if (myself.waitupdate)
		    		return;
		   		myself.svg.selectAll(".tooltip").style("display", "inherit");
		   		d = myself.bindata[myself.binsize][i];
		   		let sparkline_y = myself.sparklinescale(myself.sparkdata[i][1]);
				myself.svg.select(".tooltipDot")
					.attr("cx", myself.xscale(d.string) + myself.xscale.bandwidth() / 2)
					.attr("cy", sparkline_y)
					.attr("r" , 3);
				// distance from top of bar to sparkline.
				let y = myself.height - myself.yscale(d.adj);
				let x = myself.xscale(d.string) + myself.xscale.bandwidth() / 2;
				let len = myself.yscale(d.adj) + (sparkline_y - myself.margin_bottom / 2);
				myself.svg.select(".tooltipguide")
					.attr("d", `M${x},${y}L${x},${myself.height + myself.margin_bottom}`);

				// need to find text width to center.
				let line1_w = util.getTextWidth(d.string, '"Open Sans" 12pt');
				let line2_w = util.getTextWidth(d.count + " likes", '"Open Sans" 12pt');
				let align_left = line1_w - line2_w;
				myself.svg.select(".tooltipline1").text(util.getDateString(d.start));
				myself.svg.select(".tooltipline2").text(util.getDateString(d.end));
				myself.svg.select(".tooltipline3").text(d.count + " likes");

				// also move the entire tooltip
				myself.svg.select(".tooltiptext")
					.attr("transform", `translate(${x},${y - 30})`);
		   });

		// time for the update selection
	    sel = sel.merge(enter);

	    // need to manually assign datums due to structure of program
	    sel.each(function(d){
	   			let t = d3.select(this);
	   			t.select(".view1bar")
	   			 .datum(d);
	   			t.select(".view1barback")
	   			 .datum(d);;
	   		})

	    sel.transition()
	   	   .attr("transform", d=>`translate(${this.xscale(d.string)}, 0)`);

		sel.selectAll(".view1barback")
   	       .transition()
   	       .attr("width", d=>this.xscale.bandwidth())
		   .attr("height", d=>this.yscale(d.adj))
		   .attr("y", d=>this.height-this.yscale(d.adj));


	   	sel.selectAll(".view1bar")
	   	   .transition()
		   .attr("width", d=>this.xscale.bandwidth())
		   .attr("height", d=>this.yscale(d.count))
		   .attr("y", d=>this.height-this.yscale(d.count));

	   	sel.selectAll(".transbars")
	        .attr("width", d=>this.xscale.bandwidth());

	    this.svg.select("rect")
	        .on("click", function(){
		   		myself.svg.selectAll(".tooltip").style("display", "none");
	        })

	    this.svg.select(".dataline")
			.datum(this.sparkdata)
			.attr("d", this.sparkline);

		this.svg.select(".gear")
			.style("opacity", 1)
			.transition()
			.style("opacity", 0)
	}
}