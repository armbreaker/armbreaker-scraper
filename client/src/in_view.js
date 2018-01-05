"use strict";

export function fully_in_view(bound, el) {
	let b_top = bound.scrollTop + bound.offsetTop;
	let b_bot = b_top + bound.offsetHeight;
	let e_top = el.offsetTop;
	let e_bot = e_top + el.offsetHeight;
	return (e_bot <= b_bot) && (b_top <= e_top)
}

export function in_view(bound, el) {
	let b_top = bound.scrollTop + bound.offsetTop;
	let b_bot = b_top + bound.offsetHeight;
	let e_top = el.offsetTop;
	let e_bot = e_top + el.offsetHeight;
	return (e_bot <= b_bot) || (b_top <= e_top)
}