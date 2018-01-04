"use strict";

import * as moment from "moment";
import * as d3 from "d3";
import "../d3.slider/d3.slider.js";
import PerDayView from "View-PerDay";
import UserView from "View-User";
import FirstImpressionsView from "View-FirstImpressions";

document.addEventListener("DOMContentLoaded", init);
var dataset;

function init() {
	let url = window.location.href.split("/");
	let ficid = url[url.length - 1];
	d3.json(`../api/fics/${ficid}`, (err, data)=>{
		dataset = data;
		setup();
	});
}

// Initialize everything.
function setup() {
	d3.select("#title h1").text(dataset.name);
	var views = [
		new PerDayView(),
		new UserView(),
		new FirstImpressionsView()
	];
	for (let view of views) {
		view.setup(dataset);
		view.update();
	}
}