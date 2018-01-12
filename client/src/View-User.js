"use strict";

import * as d3 from "d3";
import * as moment from "moment";
import * as util from "utility";
import ClusterWorker from "./docluster.worker.js";

// the likes per chapter per user view, as well as chapter.
export default class UserView {
	constructor() {
		this.margin_top = 0;
		this.margin_bottom = 0;
		this.margin_w = 25;
		this.width  = 600 - this.margin_w * 2;
		this.height = 800 - this.margin_top - this.margin_bottom;
		this.tolerence = 0;
		this.algo = "hamming";

		this.subgraph_height = 50;
		this.subgraph_margintop = 40;

		this.style = {
			square: "#4c568c"
		}
		this.clusterer = new ClusterWorker();
		this.waitupdate = false;
	}

	setup(dataset) {
		this.svgtop = d3.select("#userview_top");
		this.svgbot = d3.select("#userview_bot");
		this.userlikes = {};
		this.usernames = dataset.users;
		this.chapterinfo = [];

		// put all users in userdata since need to cmp strings
		for (let post of dataset.posts.posts) {
			let postobj = {};
			postobj.id = post.id;
			postobj.title = post.title;
			postobj.likes = [];
			for (let like of post.likes.likes) {
				let userid = like.user;
				postobj.likes.push(String(userid));
				this.userlikes[userid] = "";
			}
			this.chapterinfo.push(postobj);
		}

		// Need to tally likes per user, as well as chapter like sums
		this.chapterinfo.forEach((post, index) => {
			for (let like of post.likes) {
				this.userlikes[like] += "x";
			}
			for (let userid in this.usernames) {
				if (this.userlikes[userid].length  == index) {
					this.userlikes[userid] += " ";
				}
			}
		})

		// Set tolerence to X% of chapters or Y, whichever is larger.
		this.tolerence = Math.max(this.chapterinfo.length * 0.1, 4);

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

		this.clusterer.onmessage = (event)=>{
			// put outliers in one group
			let clusters = [];
			let outliers = [];
			event.data.forEach(d=>{
				if (d.length != 1)
					clusters.push(d);
				else
					outliers.push(d[0]);
			})
			this.clustered = clusters;

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
			// sort clusters from large to small
			this.clustered = util.arrsort(this.clustered, true, d=>d.length)

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

		    this.xaxis_bot = 
		    	d3.axisBottom(this.xscale);
		    this.xaxis_top = 
		    	d3.axisTop(this.xscale);

			this.svgbot
				.select(".yaxis")
				.call(this.xaxis_bot)
				.append("text")
				.classed("axislabel", true)
				.attr("x", this.width - this.margin_w)
				.attr("y", 20)
				.text("Chapters");

			this.svgtop
				.select(".yaxis")
				.call(this.xaxis_top)
				.attr("transform", `translate(0, 49)`)
				.append("text")
				.classed("axislabel", true)
				.attr("x", this.width - this.margin_w)
				.attr("y", -15)
				.text("Chapters");

			this.waitupdate = false;
		}
		this.waitupdate = true;
		this.clusterer.postMessage({data: this.user_userlikes, algo:"hamming", tolerence:this.tolerence})
	}

	update() {
		let me = this;
		if (this.waitupdate) {
			setTimeout(()=>this.update(), 100);
			return;
		}
		// populate like sub-graph 
		let band = this.xscale.bandwidth();
		let likeline = 
			d3.line()
			  .x((d,i)=>this.xscale(i) + band/2)
			  .y(d=>this.sub_yscale(d.likes.length));

		let likes = this.svgbot.select(".likegraph");
		let ypos = this.subgraph_margintop;
		likes.attr("transform", `translate(0, ${ypos})`)
		likes.append("path")
			.classed("likeline", true)
			.datum(this.chapterinfo)
			.attr("d", likeline);

		function makedot(i) {
			let d = me.chapterinfo[i]
			console.log(d)
			let startdot = likes
				.append("g")
				.attr("transform", `translate(${me.xscale(i) + band/2}, ${me.sub_yscale(d.likes.length)})`);
			startdot
				.append("circle")
				.attr("r", 2)
				.classed("likelinedot", true)
			startdot
				.append("text")
				.classed("likelinetext", true)
				.attr("y", -2)
				.text(d.likes.length)
		}
		// Add 2 dots, for start and end
		makedot(0);
		makedot(this.chapterinfo.length - 1);


		likes.append("line")
			.classed("bottomline", true)
			.attr("x1", this.xscale(0) + band/2)
			.attr("x2", this.xscale(this.chapterinfo.length - 1) + band/2)
			.attr("y1", this.sub_yscale(0) + 1)
			.attr("y2", this.sub_yscale(0) + 1);
		let subxaxis = 
			likes.append("g")
				.classed("subxaxis", true)
				.attr("transform", "translate(12, 0)");
		subxaxis
			.append("text")
			.classed("label", true)
			.classed("label_likesperchapter", true)
			.attr("text-anchor", "middle")
			.attr("x", this.width / 2 - 12)
			.attr("y", this.subgraph_height + 13)
			.text("Likes per chapter")

		// Time to use divs instead of clusters
		let clusters = d3.select("#canvases")
			.selectAll("canvas")
			.data(this.clustered)
			.enter()
			.append("canvas")
			.classed("canvascluster", true)
			.attr("width", this.width)
			.attr("height", d=>Math.ceil(this.yscale(1)) * d.length);

		clusters
			.each(function(d, i) {
				let L = d[0][1].length; // num chapters
				let ctx = this.getContext('2d');
				let rect_w = Math.ceil(me.xscale.bandwidth());
				let rect_h = Math.ceil(me.yscale(1));
				ctx.fillStyle = me.style.square;
				d.forEach((user, userindex)=>{
					let likestring = user[1];
					for (let chapter = 0; chapter < L; chapter++) {
						if (likestring[chapter] == "x")
							ctx.fillRect(chapter * rect_w, userindex * rect_h, rect_w, rect_h);
					}
				})
			})
			.on("mouseenter", function(d) {
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
		d3.select("#userviewgear")
	      .style("display", "none")
	}
}
