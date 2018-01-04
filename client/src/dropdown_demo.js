"use strict";

import Dropdown from "FilterableDropdownModal";
import timezones from "timezone-array";

document.addEventListener("DOMContentLoaded", init);

function init() {
	let data = timezones.map(d=>[d, d]);
	let dropdown = new Dropdown(data);
	dropdown.render("#modalexample");
}