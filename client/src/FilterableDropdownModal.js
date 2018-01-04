"use strict";

import * as Popper from "popper.js";

export default class FilterableDropdownModal {
	// Should be pairs of [value, text]
	constructor(data) {
		this.data = data;
		this.selected = null;
	}

	render(selector) {
		let props = {};

		let calculatedProperty = function(me, property, name) {
			let key = `${property}-${type}`;
			if (!(key in props))
				props[key] = window.getComputedStyle(me).getPropertyValue(property);
			return props[key];
		};

		let calcHeight = function(me, name) {
			let height = +calculatedProperty(me, "height", name);
			let lineheight = +calculatedProperty(me, "line-height", name);
			return height * lineheight
		}

		let sel = d3.select(selector);
		sel = sel
			.append("div")
			.classed("filterabledropdown-container", true);
		sel
			.append("div")
			.classed("filterabledropdown-selected", true)
			.text(this.selected===null?" ":this.selected[1])
			.style("height", function(){return calcHeight(this, "selected")});
		sel
			.append("div")
			.classed("filterabledropdown-filterbox", true)
			.style("height", function(){return calcHeight(this, "filterbox")});
		sel
			.append("div")
			.classed("filterabledropdown-options", true)
			.selectAll(".filterabledropdown-option")
			.data(this.data)
			.enter()
			.append("div")
			.classed("filterabledropdown-option", true)
			.text(d=>d[1])
			.style("height", function(){return calcHeight(this, "option")});
	}
}