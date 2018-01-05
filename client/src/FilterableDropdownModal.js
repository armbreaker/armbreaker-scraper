"use strict";

import Popper from "popper.js";

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

		let removepx = str=>+str.slice(0, str.length - 2);

		let calcModalWidth = function(me) {
			let w    = calculatedProperty(me, "width", "modal");
			let bw_r = calculatedProperty(me, "border-width").split(" ")[1];
			return removepx(w) - removepx(bw_r) * 2;
		}

		let sel = d3.select(selector);
		sel = sel
			.append("div")
			.classed("filterabledropdown-container filterabledropdown-width", true);
		// selbox contains the dropdown toggle and the selected element
		let selbox = sel
			.append("div")
			.classed("filterabledropdown-width", true);
		// selected element
		let valuebox = selbox
			.append("div")
			.classed("filterabledropdown-selected filterabledropdown-padded", true)
			.style("height", function(){return calcHeight(this, "selected");})
			.text(this.selected===null?" ":this.selected[1]);
		let toggle = selbox
			.append("div")
			.classed("filterabledropdown-toggle", true)
			.text("▼")
			.style("height", props["line-height-selected"])
			.style("width" , props["line-height-selected"]);
		let togglepopper = new Popper(selbox._groups[0][0], toggle._groups[0][0], {
			placement: "right",
			modifiers: {
				flip: {
					enabled: false
				},
				offset: {
					offset: `0px,-${removepx(props["line-height-selected"])+1}px`
				}
			}
		}); 
		//// modal 
		let modal = sel
			.append("div")
			.classed("filterabledropdown-modal filterabledropdown-width", true)
		let popper = new Popper(selbox._groups[0][0], modal._groups[0][0], {
			placement: "bottom",
			modifiers: {
				flip: {
					enabled: false
				}
			}
		});
		modal
			.append("input")
			.classed("filterabledropdown-filterbox filterabledropdown-padded", true)
			.attr("type", "text")
			.style("height", function(){return calcHeight(this, "selected");});
		modal
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
		modal
			.style("width", function(){return calcModalWidth(this)});
	}
}