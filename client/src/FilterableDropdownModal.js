"use strict";

import * as Popper from "popper.js";

export default class FilterableDropdownModal {
	// Should be pairs of [value, text]
	constructor(data) {
		this.data = data;
		this.selected = null;
		this.hovered = null;
	}

	renderselected() {
		d3.select(".filterabledropdown-selected")
		  .text(this.selected[1]);
	}

	render(selector) {
		let me = this;
		let props = {};
		let calculatedProperty = function(me, property, name) {
			let key = `${property}-${name}`;
			if (!(key in props)){
				let prop = window.getComputedStyle(me).getPropertyValue(property);
				props[key] = prop;
			}
			return props[key];
		};

		let calcHeight = function(me, name) {
			return calculatedProperty(me, "line-height", name);
		}

		let sel = d3.select(selector);
		sel = sel
			.append("div")
			.classed("filterabledropdown-container", true);
		sel
			.append("div")
			.classed("filterabledropdown-selected", true)
			.style("height", function(){return calcHeight(this, "selected");})
			.text(this.selected===null?" ":this.selected[1]);
		sel = sel
			.append("div")
			.classed("filterabledropdown-modal", true);
		sel
			.append("input")
			.classed("filterabledropdown-filterbox", true)
			.attr("type", "text")
			.style("height", function(){return calcHeight(this, "selected");});
		sel
			.append("div")
			.classed("filterabledropdown-options", true)
			.on("keydown", function(){
				console.log(d3.event);
			})
			.selectAll(".filterabledropdown-option")
			.data(this.data)
			.enter()
			.append("div")
			.classed("filterabledropdown-option", true)
			.text(d=>d[1])
			.style("height", function(){return calcHeight(this, "option")})
			.on("mouseenter", function(d) {
				me.hovered = d;
				d3.selectAll(".filterabledropdown-hovered")
				  .classed("filterabledropdown-hovered", false);
				d3.select(this).classed("filterabledropdown-hovered", true);
			})
			.on("click", function(d){
				d3.select(".filterabledropdown-modal")
				  .classed("filterabledropdown-hidden", true);
				d3.selectAll(".filterabledropdown-prevselected")
				  .classed("filterabledropdown-prevselected", false);
				d3.select(this)
				  .classed("filterabledropdown-prevselected", true);
				me.selected = d;
				me.renderselected();
			});
	}
}