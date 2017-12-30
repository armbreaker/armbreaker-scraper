"use strict";

document.addEventListener("DOMContentLoaded", init);

var dataset;

// Grouped rendering functions into classes for convenience
// the likes per day view
class PerDayView {
	constructor() {
		this.margin_top = 80;
		this.margin_bottom = 50;
		this.margin_w = 25;
		this.width  = 800 - this.margin_w * 2;
		this.height = 460 - this.margin_top - this.margin_bottom - 10;
		this.binsize = 1;
	}

	bin(newbin) {
		this.binsize = newbin;
		// extract likes per day.
		// first, initialize each day.
		let data = [];
		let bindur = moment.duration(this.binsize, "days");
		let startdate = getDate(this.mintime);
		let enddate = getDate(this.maxtime);
		while (startdate.clone().add(bindur) < enddate) {
			let dstart = startdate.clone();
			let dend   = startdate.clone().add(bindur);
			let s1 = getDateString(dstart);
			let s2 = getDateString(dend);
			data.push({start: dstart, 
						 end: dend,
					 	 count: 0,
					 	 string: getDateRangeString(dstart, dend)});
			startdate.add(bindur);
		}

		data.push({start: startdate, 
					 end: enddate,
				 	 count: 0,
				 	 string: getDateRangeString(startdate, enddate)});

		for (let time of this.alltimes) {
			let day = getDate(time); // conversion puts dates into user timezone.
			for(let bin of data) {
				if (bin.start <= day && day <= bin.end) {
					bin.count += 1;
					break;
				}
			}
		}
		this.data = data;

		// Also create adjusted amounts
		for (let i = 0; i < this.data.length; i++) {
			let d = this.data[i];
			if (i == this.data.length - 1) {
	   			let days = d.end.diff(d.start, "days");
	   			if (days != this.binsize){
		   			d.adj = d.count / days * this.binsize;
		   			d.adj = d.count + (d.adj - d.count) * .5 // discount estimate
		   			continue;
		   		}
			}
			d.adj = d.count;
		}

		// also create sparkline data.
		this.totallikes = arrsum(this.data, d=>d.count);
		this.sparkdata = [];
		let datanum = this.data.length;
		let sum = 0;
		let slope = this.totallikes / parseFloat(datanum);
		for (let i = 0; i < datanum; i++) {
			let date  = this.data[i].string;
			let likes = this.data[i].count;
			// compare expected likes versus actual likes
			// let diff = likes - slope; // likes vs average likes
			sum += likes;
			let diff = sum - (slope * (i+1));
			this.sparkdata.push([date, diff]);
		}
		this.sparkdataMagnitude = Math.abs(arrmax(this.sparkdata, d=>Math.abs(d[1]))[1]);

		// reset axes

		// find max like magnitude, then create scales & axes
		this.maxlikes = arrmax(this.data, d=>d.adj).adj; // adj, to account for estimate

		this.xscale = 
			d3.scaleBand()
			  .domain(this.data.map(d=>d.string))
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
			d3.axisLeft(this.yscale_axis);

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
	}

	setup() {
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
    			if (this.alltimes.length / val >= 1) {
		    		this.bin(this.slider.value());
		    		this.update();
    			}
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
		let sel = this.svg
			.select(".bars")
			.selectAll(".view1bargroup")
			.data(this.data);
		let myself = this;
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
		   		myself.svg.selectAll(".tooltip").style("display", "inherit");
		   		d = myself.data[i];
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
				let line1_w = getTextWidth(d.string, '"Open Sans" 12pt');
				let line2_w = getTextWidth(d.count + " likes", '"Open Sans" 12pt');
				let align_left = line1_w - line2_w;
				myself.svg.select(".tooltipline1").text(getDateString(d.start));
				myself.svg.select(".tooltipline2").text(getDateString(d.end));
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
	}
}

// the likes per chapter per user view, as well as chapter.
class UserView {
	constructor() {
		this.margin_top = 0;
		this.margin_bottom = 100;
		this.margin_w = 25;
		this.width  = 600 - this.margin_w * 2;
		this.height = 550 - this.margin_top - this.margin_bottom;
		this.tolerence = 0;
		this.algoname = "damerau";
		if (this.algoname == "damerau") {
			this.algo = damerau;
		} else {
			this.algo = levenshtein;
		}
		this.innerpadding = 7; // distance between clusters

		this.subgraph_height = 50;
		this.subgraph_margintop = 40;
	}

	setup() {
		this.svg = d3.select("#userview");
		this.userlikes = {};
		this.usernames = {};
		this.chapterinfo = [];

		// put all users in userdata since need to cmp strings
		for (let post of dataset.posts.posts) {
			let postobj = {};
			postobj.id = post.id;
			postobj.title = post.title;
			postobj.likes = [];
			for (let like of post.likes.likes) {
				let userid = like.user.id;
				postobj.likes.push(String(userid));
				this.usernames[userid] = like.user.name;
				this.userlikes[userid] = "";
			}
			this.chapterinfo.push(postobj);
		}

		// Need to tally likes per user, as well as chapter like sums
		for (let post of this.chapterinfo) {
			for (let userid in this.usernames) {
				if (post.likes.indexOf(userid) >= 0)
					this.userlikes[userid] += "x";
				else
					this.userlikes[userid] += " ";
			}
		}

		// Set tolerence to 10% of chapters or 2, whichever is larger.
		this.tolerence = Math.max(this.chapterinfo.length * 0.1, 5);

		this.usernames_arr = []
		for (let userid in this.usernames) {
			this.usernames_arr.push([userid, this.usernames[userid]]);
		}

		// cluster similar likes, starting from the max "x"s
		this.clustered = [];
		// enumerate object for lufu
		this.user_userlikes = [];
		for (let userid in this.userlikes) {
			this.user_userlikes.push([userid, this.userlikes[userid]]);
		}
		this.clustered = lufu_cluster(this.user_userlikes, this.tolerence, this.algo);

		// sort each cluster. renumber.
		let counter = this.usernames_arr.length - 1;
		for (let cluster of this.clustered) {
			cluster.sort((a, b)=>{
				// start from back
				a = a[1];
				b = b[1];
				for(let i = a.length - 1; 0 <= i; i--) {
					if (a[i] != b[i]) {
						if (a[i] == "x") return -1;
						return 1;
					}
				}
				return 0;
			});

			for (let element of cluster) {
				element[2] = counter--;
			}
		}

		// set scale
		let domain = [];
		for (let j = 0; j < this.chapterinfo.length; j++) {
			domain.push(j);
		}

		this.xscale =
			d3.scaleBand()
			  .domain(domain)
			  //.paddingOuter(10)
			  .range([0, this.width]);
	    this.yscale = 
	    	d3.scaleLinear()
	    	  .domain([0, this.usernames_arr.length])
	    	  .range([0, this.height]);
	    // for subgraph
	    this.likesmax = arrmax(this.chapterinfo, d=>d.likes.length).likes.length;
	    this.sub_yscale = 
	    	d3.scaleLinear()
	    	  .domain([0, this.likesmax])
	    	  .range([this.subgraph_height, 0]);

	    this.xaxis = 
	    	d3.axisBottom(this.xscale);

	    this.subyaxis =
	    	d3.axisLeft(this.sub_yscale)
	    	  .ticks(3);

		this.svg
			.select(".yaxis")
			.call(this.xaxis)
			.attr("transform", `translate(0, ${this.height + 2 + this.innerpadding * this.clustered.length})`)
			.append("text")
			.classed("axislabel", true)
			.attr("x", this.width - this.margin_w)
			.attr("y", 30)
			.text("Chapters");
		this.svg.select(".all")
			.attr("transform", `translate(${this.margin_w}, ${this.margin_top})`)
	}

	update() {
		let me = this;
		let clusters = this.svg
			.select(".bars")
			.selectAll(".cluster")
			.data(this.clustered);
		let clusterenter = clusters
			.enter()
			.append("g")
			.classed("cluster", true);		

		clusters = clusters.merge(clusterenter);

		clusters
			.attr("data-index", (d,i)=>i)
			.classed("odd", (d,i)=>i % 2 == 1)
			.classed("even", (d,i)=>i % 2 == 0);

		let bars = clusters
			.selectAll(".userbar")
			.data(d=>d);

		bars = bars.enter()
			.append("g")
			.classed("userbar", true)
			.merge(bars);

		bars.attr("transform", function(d){
			let i = +this.parentNode.getAttribute("data-index");
			return `translate(0, ${me.yscale(d[2]) + (me.clustered.length - i) * me.innerpadding})`
		});

		let sqrs = bars
			.selectAll(".barsquare", true)
			.data(d=>d[1]);
		sqrs = sqrs.enter()
			.append("rect")
			.classed("barsquare", true)
			.attr("width", this.xscale.bandwidth())
			.attr("height", this.yscale(1))
			.merge(sqrs);
		sqrs.attr("opacity", d=>d == "x" ? 1 : 0)
			.attr("x", (d, i)=>this.xscale(i));

		// add the rectangles to the rectangle div.
		let clusterboxes = 
			this.svg.select(".boxes")
				.selectAll(".clusterbox")
				.data(this.clustered);
		clusterboxes
			.enter()
			.append("rect")
			.classed("clusterbox", true)
			.classed("odd", (d,i)=>i % 2 == 1)
			.classed("even", (d,i)=>i % 2 == 0)
			.attr("transform", (d,i)=>`translate(0, ${(this.clustered.length - i)  * me.innerpadding})`)
			.attr("width", this.width)
			.attr("y", (d, i)=>{
				// y pos is equal to smallest y pos
				let el = arrmin(d, d=>this.yscale(d[2]));
				return this.yscale(el[2]);
			})
			.attr("height", (d,i)=>{
				// height is equal to diff between 
				// largest and smallest ypos
				let M = arrmax(d, d=>this.yscale(d[2]));
				let m = arrmin(d, d=>this.yscale(d[2]));
				return this.yscale(M[2]) - this.yscale(m[2]) + this.yscale(1);
			})
			.on("mouseover", function(d){
				d3.select("#userlistdiv").style("color", undefined);
				// user count
				d3.select("#numusers").text(d.length);
				let p = d.length / parseFloat(me.usernames_arr.length) * 100;
				p = n_digits(p, 1);
				d3.select("#percentusers").text(p);
				// like count
				let arr = d.map(el=>{
					return (el[1].match(/x/g) || []).length;
				});
				let average = arrsum(arr) / arr.length;
				d3.select("#averagelikes").text(n_digits(average, 1));
				p = average / me.chapterinfo.length * 100;
				d3.select("#percentliked").text(n_digits(p, 1));
				// append user list
				let usernames = 
					d.map(d=>me.usernames[d[0]])
					 .sort((a, b)=>{
					 	a = a.toLowerCase();
					 	b = b.toLowerCase();
					 	if (a > b) return 1;
					 	if (a < b) return -1;
					 	return 0;
					 });

				d3.select("#userlist")
				  .html("")
				  .selectAll(".username")
				  .data(usernames)
				  .enter()
				  .append("span")
				  .classed("username", true)
				  .text(d=>d);
			});

		// populate like sub-graph 
		let band = this.xscale.bandwidth();
		let likeline = 
			d3.line()
			  .x((d,i)=>this.xscale(i) + band/2)
			  .y(d=>this.sub_yscale(d.likes.length));

		let likes = this.svg.select(".likegraph");
		let ypos = this.height + this.innerpadding * this.clustered.length + this.subgraph_margintop;
		likes.attr("transform", `translate(0, ${ypos})`)
		likes.append("path")
			.classed("likeline", true)
			.datum(this.chapterinfo)
			.attr("d", likeline);
		likes.append("line")
			.classed("bottomline", true)
			.attr("x1", this.xscale(0) + band/2)
			.attr("x2", this.xscale(this.chapterinfo.length - 1) + band/2)
			.attr("y1", this.sub_yscale(0) + 1)
			.attr("y2", this.sub_yscale(0) + 1);
		let subxaxis = 
			likes.append("g")
				.classed("subxaxis", true)
				.call(this.subyaxis)
				.attr("transform", "translate(12, 0)");
		subxaxis
			.append("text")
			.classed("label", true)
			.attr("text-anchor", "middle")
			.attr("x", this.width / 2 - 12)
			.attr("y", this.subgraph_height + 13)
			.text("Likes per chapter")
	}
}

// the likes over time for the first 24h view
class FirstImpressionsView {
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

	setup() {
		this.svg = d3.select("#firstimpressionview");

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
			arrsort(likes, false, d=>d.time);
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
	}

	// take in chapter obj, return area path for longtail. 
	drawLongtail(datum) {
		// Areagen mirrors right side to left.
		let a = d3.area()
			.x0(d=>-d[0])
			.x1(d=>d[0])
			.y0(d=>d[1])
			.y1(d=>d[1]);
		let numlikes = datum.cappedlikes.length;
		let lastlike = datum.cappedlikes[numlikes - 1];
		let tail_start = this.yscale(lastlike.time.clone().diff(datum.time));
		let time_above_cap = makeTimeOnlyMoment(datum.time).diff(moment("2015-01-15"));
		let tail_end = this.yscale(moment.duration(2, "day").asMilliseconds() - time_above_cap);
		let half_width = datum.markscale(numlikes) * 0.5;
		let half_final = 0.5 * this.markwidth;

		return a([
			[half_width, tail_start],
			[half_final, tail_end],
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
		   		let dur = makeTimeOnlyMoment(d.time).diff(moment("2015-01-15"));
		   		return `translate(${this.xscale(i)},${this.yscale(dur)+4})`;
			})
		   .each(function(d, i) {
		   		if (i == 0) {
		   			console.log(d);
		   		}

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

var views = [
	new PerDayView(),
	new UserView(),
	new FirstImpressionsView()
];

// Initialize everything.
function setup() {
	d3.select("#title h1").text(dataset.name);
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
