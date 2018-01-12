"use strict";

import Popper from "popper.js";
import {fully_in_view, in_view} from "in_view";

export default class FilterableDropdownModal {
	// Should be pairs of [value, text]
	constructor(data, selector) {
		this.data = data.map((d,i)=>[d[0], d[1], i]); // give unique ids
		this.filtered = this.data.map(d=>d); // the current list of data shown
		this.selector = selector;
		this.d3sel = null;
		this.selected = null;
		this.selindex = -1;
		this.hovered = null;
		this.hoveredindex = -1; // hover index must go off of displayed elements only
		this.visible = false;
		this.callback = null;
		this.props = {};

		this.modalpopper = null;
		this.toggle = null;

		this.capsensitive = false;
	}

	calculatedProperty(me, property, name, override) {
		let key = `${property}-${name}`;
		if (override || !(key in this.props)){
			let prop = window.getComputedStyle(me).getPropertyValue(property);
			this.props[key] = prop;
		}
		return this.props[key];
	}

	calcModalWidth(el) {
		let w    = this.calculatedProperty(el, "width", "modal");
		let bw_r = this.calculatedProperty(el, "border-width").split(" ")[1];
		return parseInt(w) - parseInt(bw_r) * 2;
	}

	calcHeight(me, name, override) {
		return this.calculatedProperty(me, "line-height", name, override);
	}

	changeHovered(key) {
		this.hoveredindex = this.boundRealIndex(key);
		this.hovered = this.accessData(key, this.filtered);
		this.d3sel.selectAll(".filterabledropdown-hovered")
		   .classed("filterabledropdown-hovered", false);
		let el = this.d3sel
			.select(`.filterabledropdown-option[key="${key}"]`)
			.classed("filterabledropdown-hovered", true);
	}

	changeSelected(key, skipfocus){
		this.selindex = this.boundindex(key);
		this.selected = this.accessData(key, this.data);
		this.d3sel
			.selectAll(".filterabledropdown-prevselected")
			.classed("filterabledropdown-prevselected", false);
		this.d3sel
			.select(`.filterabledropdown-option[key="${key}"]`)
			.classed("filterabledropdown-prevselected", true);
		this.changeHovered(key);
		this.renderselected(skipfocus);
	}

	toggleModal(){
		this.visible = !this.visible;
		this.d3sel
			.select(".filterabledropdown-modal")
			.classed("filterabledropdown-hidden", !this.visible);
		if (this.visible) {
			this.d3sel
				.select(".filterabledropdown-filterbox")
				._groups[0][0].focus();
			this.modalpopper.scheduleUpdate();
			if (this.selindex != -1)
				this.keepInView(this.selindex);
		}
		this.toggle.text(this.visible?"▲":"▼");
	};

	keepInView(key) {
		let bounds = this.d3sel.select(".filterabledropdown-options")._groups[0][0];
		let option = this.d3sel.select(`.filterabledropdown-option[key="${key}"]`)._groups[0][0];
		if (this.hoveredindex != -1) {
			if (!fully_in_view(bounds, option)) {
				let filbox = this.d3sel.select(".filterabledropdown-filterbox")._groups[0][0];
				let style = getComputedStyle(filbox);
				let t = parseInt(style.marginTop) + parseInt(style.borderTop);
				let b = parseInt(style.marginBottom) + parseInt(style.borderBottom);
				bounds.scrollTop = option.offsetTop - option.offsetHeight - t - b;
			}
		}
	}

	// keep i within [-1, this.data.length)
	boundindex(i) {
		if (i < -1)
			i = -1;
		else if (i >= this.data.length)
			i = this.data.length - 1;
		return i;
	}

	boundRealIndex(i) {
		if (i < -1)
			i = -1;
		else if (i >= this.filtered.length)
			i = this.filtered.length - 1;
		return i;
	}

	// if -1, return null
	accessData(i, data) {
		if (i == -1)
			return null;
		return data[i];
	}

	renderFromIndex(hovered) {
		let i;
		if (hovered) {
			throw "Cannot use renderFromIndex for hover anymore"
			i = this.hoveredindex = this.boundindex(this.hoveredindex);
		} else {
			i = this.selindex = this.boundindex(this.selindex);
		}

		if (i == -1)
			this.selected = null
		else
			this.selected = this.data[i];
		this.renderselected();
	}

	renderselected(skipfocus) {
		let s = this.d3sel.select(".filterabledropdown-selected")
		  .text(this.selected===null?"":this.selected[1]);
		if (skipfocus !== true)
			s._groups[0][0].focus();
	}

	// Set the default selected value by data index
	setdefault_index(key) {
		this.changeSelected(key, true);
	}

	// Set the default selected value by matching values. Selects first matching.
	setdefault_value(value) {
		for (let i = 0; i < this.data.length; i++) {
			let datum = this.data[i];
			if (datum[0] == value) {
				this.setdefault_index(i);
				break;
			}
		}
	}

	update(selector) {
		let me = this;
		// update filter
		let filter = this.d3sel.select("input").property("value");
		let filterfunc;
		if (!this.capsensitive) {
			filter = filter.toLowerCase();
			filterfunc = d=>d[1].toLowerCase().indexOf(filter) > -1;
		} else {
			filterfunc = d=>d[1].indexOf(filter) > -1;
		}
		if (filter == "") {
			this.filtered = this.data.map(d=>d);
		} else {
			this.filtered = this.data.filter(filterfunc);
		}
		// update DOM
		let sel = d3.select(this.selector)
			.select(".filterabledropdown-options")
			.selectAll(".filterabledropdown-option")
			.data(this.filtered, d=>d[2]);
		sel.exit().remove();
		sel
			.enter()
			.append("div")
			.classed("filterabledropdown-option", true)
			.text(d=>d[1])
			.attr("key", d=>d[2])
			.style("height", function(){return me.calcHeight(this, "option")})
			.on("mouseenter", (d)=>me.changeHovered(d[2]))
			.on("click", function(d, i){
				me.d3sel.select(".filterabledropdown-filterbox")
					.property("value", "");
				me.update();
				me.d3sel
					.select(".filterabledropdown-modal")
					.classed("filterabledropdown-hidden", true);
				me.d3sel
					.selectAll(".filterabledropdown-prevselected")
					.classed("filterabledropdown-prevselected", false);
				d3
					.select(this)
					.classed("filterabledropdown-prevselected", true);
				me.selected = d;
				me.renderselected();
				me.toggleModal();
			});
		this.d3sel
			.select(".filterabledropdown-modal")
			.style("width", function(){return me.calcModalWidth(this)});
	}

	setup() {
		let me = this;
		let sel = d3.select(this.selector);
		this.d3sel = sel;
		sel = sel
			.append("div")
			.classed("filterabledropdown-container filterabledropdown-width", true);

		// selbox contains the dropdown toggle and the selected element
		let selbox = sel
			.append("div")
			.classed("filterabledropdown-width", true)
			.on("click", ()=>me.toggleModal())
			.attr("tabindex", 0)
			.on("keydown", function(){
				if (d3.event.key == "Enter")
					me.toggleModal();
				else if (d3.event.key == "ArrowDown") {
					me.changeSelected(me.selindex + 1);
					me.keepInView(me.selindex)
					d3.event.preventDefault();
				} else if (d3.event.key == "ArrowUp") {
					me.changeSelected(me.selindex - 1);
					me.keepInView(me.selindex)
					d3.event.preventDefault();
				}
			});
		// selected element
		let valuebox = selbox
			.append("div")
			.classed("filterabledropdown-selected filterabledropdown-padded", true)
			.attr("tabindex", -1)
			.style("height", function(){
				let v = me.calcHeight(this, "selected");
				if (Number.isNaN(parseInt(v))) {
					selbox.select(".filterabledropdown-selected").style("line-height", "1.5");
					v = me.calcHeight(this, "selected", true);
				}
				return v;
			})
			.text(this.selected===null?" ":this.selected[1]);

		this.toggle = selbox
			.append("div")
			.classed("filterabledropdown-toggle", true)
			.text(this.visible?"▲":"▼")
			.style("height", this.props["line-height-selected"])
			.style("width" , this.props["line-height-selected"]);
		let togglepopper = new Popper(selbox._groups[0][0], this.toggle._groups[0][0], {
			placement: "right",
			modifiers: {
				flip: {
					enabled: false
				},
				offset: {
					offset: `0px,-${parseInt(me.props["line-height-selected"])+1}px`
				}
			}
		}); 
		//// modal 
		let modal = sel
			.append("div")
			.classed("filterabledropdown-modal filterabledropdown-width", true)
			.classed("filterabledropdown-hidden", !this.visible);
		this.modalpopper = new Popper(selbox._groups[0][0], modal._groups[0][0], {
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
			.style("height", function(){return me.calcHeight(this, "selected");})
			.style("font-size", function(){return me.calculatedProperty(this.parentNode, "font-size", "filterbox")})
			.on("keydown", function(){
				if (d3.event.key == "Enter") {
					me.changeSelected(me.hoveredindex)
					me.toggleModal();
				}
				else if (d3.event.key == "ArrowDown") {
					me.changeHovered(me.hoveredindex + 1);
					me.keepInView(me.hoveredindex);
					d3.event.preventDefault();
				} else if (d3.event.key == "ArrowUp") {
					me.changeHovered(me.hoveredindex - 1);
					me.keepInView(me.hoveredindex);
					d3.event.preventDefault();
				} else {
					// Do the filtering.
					me.update();
				}
			});
		modal
			.append("div")
			.classed("filterabledropdown-options", true);
		this.update();
	}
}