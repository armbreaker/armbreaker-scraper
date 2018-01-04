"use strict";

import * as d3 from "d3";
import * as moment from "moment";
import * as util from "utility";
import damerau from "damerau-levenshtein";
const levenshtein = require('js-levenshtein');

// the likes per chapter per user view, as well as chapter.
export default class UserView {
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

	setup(dataset) {
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
		this.clustered = util.lufu_cluster(this.user_userlikes, this.tolerence, this.algo);

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
	    this.likesmax = util.arrmax(this.chapterinfo, d=>d.likes.length).likes.length;
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
				let el = util.arrmin(d, d=>this.yscale(d[2]));
				return this.yscale(el[2]);
			})
			.attr("height", (d,i)=>{
				// height is equal to diff between 
				// largest and smallest ypos
				let M = util.arrmax(d, d=>this.yscale(d[2]));
				let m = util.arrmin(d, d=>this.yscale(d[2]));
				return this.yscale(M[2]) - this.yscale(m[2]) + this.yscale(1);
			})
			.on("mouseover", function(d){
				d3.select("#userlistdiv").style("color", undefined);
				// user count
				d3.select("#numusers").text(d.length);
				let p = d.length / parseFloat(me.usernames_arr.length) * 100;
				p = util.n_digits(p, 1);
				d3.select("#percentusers").text(p);
				// like count
				let arr = d.map(el=>{
					return (el[1].match(/x/g) || []).length;
				});
				let average = util.arrsum(arr) / arr.length;
				d3.select("#averagelikes").text(util.n_digits(average, 1));
				p = average / me.chapterinfo.length * 100;
				d3.select("#percentliked").text(util.n_digits(p, 1));
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