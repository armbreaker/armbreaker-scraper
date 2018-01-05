"use strict";

import Popper from "popper.js";
import {fully_in_view, in_view} from "in_view";

export default class FilterableDropdownModal {
	// Should be pairs of [value, text]
	constructor(data) {
		this.data = data.map((d,i)=>[d[0], d[1], i]); // give unique ids
		this.selected = null;
		this.selindex = -1;
		this.hovered = null;
		this.hoveredindex = -1;
		this.visible = false;
		this.callback = null;
	}

	// keep i within [-1, this.data.length)
	boundindex(i) {
		if (i < -1)
			i = -1;
		else if (i >= this.data.length)
			i = this.data.length - 1;
		return i;
	}

	// if -1, return null
	accessData(i) {
		if (i == -1)
			return null;
		return this.data[i];
	}

	renderFromIndex(hovered) {
		let i;
		if (hovered) {
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

	renderselected() {
		d3.select(".filterabledropdown-selected")
		  .text(this.selected===null?"":this.selected[1])
		  ._groups[0][0].focus();
	}

	render(selector) {
		let me = this;
		let props = {};
		let modalpopper, toggle;
		let sel = d3.select(selector);
		sel = sel
			.append("div")
			.classed("filterabledropdown-container filterabledropdown-width", true);

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

		let calcModalWidth = function(el) {
			let w    = calculatedProperty(el, "width", "modal");
			let bw_r = calculatedProperty(el, "border-width").split(" ")[1];
			return removepx(w) - removepx(bw_r) * 2;
		}

		let changeHovered = (key)=>{
			this.hoveredindex = this.boundindex(key);
			this.hovered = this.accessData(key);
			sel.selectAll(".filterabledropdown-hovered")
			   .classed("filterabledropdown-hovered", false);
			let el = sel
				.select(`.filterabledropdown-option[key="${key}"]`)
				.classed("filterabledropdown-hovered", true);
		}

		let keepInView = (key)=>{
			let bounds = sel.select(".filterabledropdown-options")._groups[0][0];
			let option = sel.select(`.filterabledropdown-option[key="${key}"]`)._groups[0][0];
			if (this.hoveredindex != -1) {
				if (!fully_in_view(bounds, option)) {
					let filbox = sel.select(".filterabledropdown-filterbox")._groups[0][0];
					let style = getComputedStyle(filbox);
					let t = parseInt(style.marginTop) + parseInt(style.borderTop);
					let b = parseInt(style.marginBottom) + parseInt(style.borderBottom);
					bounds.scrollTop = option.offsetTop - option.offsetHeight - t - b;
				}
			}
		}

		let changeSelected = (key)=>{
			this.selindex = this.boundindex(key);
			this.selected = this.accessData(key);
			sel.selectAll(".filterabledropdown-prevselected")
			   .classed("filterabledropdown-prevselected", false);
			sel.select(`.filterabledropdown-option[key="${key}"]`)
			   .classed("filterabledropdown-prevselected", true);
			changeHovered(key);
			me.renderselected();
		}

		// selbox contains the dropdown toggle and the selected element
		let toggleModal = function(){
				me.visible = !me.visible;
				d3.select(".filterabledropdown-modal")
				  .classed("filterabledropdown-hidden", !me.visible);
				if (me.visible) {
					d3.select(".filterabledropdown-filterbox")
					  ._groups[0][0].focus();
					modalpopper.scheduleUpdate();
					if (me.selindex != -1)
						keepInView(me.selindex);
				}
				toggle.text(me.visible?"▲":"▼");
		};

		let selbox = sel
			.append("div")
			.classed("filterabledropdown-width", true)
			.on("click", toggleModal)
			.attr("tabindex", 0)
			.on("keydown", function(){
				if (d3.event.key == "Enter")
					toggleModal();
				else if (d3.event.key == "ArrowDown") {
					changeSelected(me.selindex + 1);
					keepInView(me.selindex)
					d3.event.preventDefault();
				} else if (d3.event.key == "ArrowUp") {
					changeSelected(me.selindex - 1);
					keepInView(me.selindex)
					d3.event.preventDefault();
				}
			});
		// selected element
		let valuebox = selbox
			.append("div")
			.classed("filterabledropdown-selected filterabledropdown-padded", true)
			.attr("tabindex", -1)
			.style("height", function(){return calcHeight(this, "selected");})
			.text(this.selected===null?" ":this.selected[1]);
		toggle = selbox
			.append("div")
			.classed("filterabledropdown-toggle", true)
			.text(this.visible?"▲":"▼")
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
			.classed("filterabledropdown-hidden", !this.visible);
		modalpopper = new Popper(selbox._groups[0][0], modal._groups[0][0], {
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
			.style("height", function(){return calcHeight(this, "selected");})
			.on("keydown", function(){
				if (d3.event.key == "Enter") {
					changeSelected(me.hoveredindex)
					toggleModal();
				}
				else if (d3.event.key == "ArrowDown") {
					changeHovered(me.hoveredindex + 1);
					keepInView(me.hoveredindex);
					d3.event.preventDefault();
				} else if (d3.event.key == "ArrowUp") {
					changeHovered(me.hoveredindex - 1);
					keepInView(me.hoveredindex);
					d3.event.preventDefault();
				}
			});
		modal
			.append("div")
			.classed("filterabledropdown-options", true)
			.selectAll(".filterabledropdown-option")
			.data(this.data)
			.enter()
			.append("div")
			.classed("filterabledropdown-option", true)
			.text(d=>d[1])
			.attr("key", d=>d[2])
			.style("height", function(){return calcHeight(this, "option")})
			.on("mouseenter", (d)=>changeHovered(d[2]))
			.on("click", function(d){
				d3.select(".filterabledropdown-modal")
				  .classed("filterabledropdown-hidden", true);
				d3.selectAll(".filterabledropdown-prevselected")
				  .classed("filterabledropdown-prevselected", false);
				d3.select(this)
				  .classed("filterabledropdown-prevselected", true);
				me.selected = d;
				me.renderselected();
				toggleModal();
			});
		modal
			.style("width", function(){return calcModalWidth(this)});
	}
}