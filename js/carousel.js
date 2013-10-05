/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 *   Carousel - non-instrusive script for providing a carousel
 *
 *   Glen H. Barratt from AdeptSites ghbarratt [at] adeptsites [dot] com
 *   http://adeptsites.com
 *
 *   Requires jQuery
 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


var Carousel = 
{
	container: "#carousel_wrapper",
	change_technique: "fade",
	current_slide: 0,
	advance_delay: 4000,
	slides: false,
	rotating: false,
	interval_id: false,
	timeout_id: false,
	advance_method: "advance",

	init: function(slides, advance_delay, change_technique, current_slide, container)
	{
		if(typeof(slides)!="undefined" && slides) Carousel.slides = slides;
		if(typeof(advance_delay)!="undefined" && advance_delay) Carousel.advance_delay = advance_delay;
		if(typeof(change_technique)!="undefined" && change_technique) Carousel.change_technique = change_technique;
		if(typeof(current_slide)!="undefined" && current_slide) Carousel.current_slide = current_slide;
		if(typeof(container)!="undefined" && container) Carousel.container = container;
		if(!Carousel.rotating) Carousel.startRotation(Carousel.advance_delay);
	},
	startRotation: function(advance_delay)
	{
		if(typeof(advance_delay)!="undefined" || !advance_delay) advance_delay = Carousel.advance_delay;
		if(!Carousel.rotating) 
		{
			Carousel.rotating = true;
			// Just in case
			window.clearInterval(Carousel.interval_id);
			Carousel.interval_id = window.setInterval("Carousel."+Carousel.advance_method+"();", advance_delay);
		}
	},
	advance: function()
	{
		Carousel.current_slide++;
		if(Carousel.current_slide>=Carousel.slides.length) Carousel.current_slide = 0;

		Carousel.transitionTo(Carousel.current_slide);
	},
	transitionTo: function(slide_index, speed, method)
	{

		if(typeof(slide_index)=="undefined" || slide_index===false) slide_index = Carousel.current_slide;
		if(typeof(speed)=="undefined" || speed===false) speed = "slow";

		//alert("Would attempt to transition to slide "+slide_index);
		if(Carousel.change_technique=="fade")
		{
			// Set background to foreground image
			jQuery(Carousel.container).css("background-image", "url("+jQuery(Carousel.container+"_image").attr("src")+")");
			// Hide foreground image
			jQuery(Carousel.container+"_image").hide();
			// Change foreground image to new slide image
			jQuery(Carousel.container+"_image").attr("src", Carousel.slides[slide_index].image);
			// Fade the new foreground image in
			jQuery(Carousel.container+"_image").fadeIn(speed);
			// We now update the Carousel current slide
			Carousel.current_slide = slide_index;
		}
		if(typeof(Carousel.slides[slide_index].eval)!="undefined" && Carousel.slides[slide_index].eval) eval(Carousel.slides[slide_index].eval);
	},
	overTrigger: function(slide_index, speed)
	{
		Carousel.transitionTo(slide_index, speed);
		Carousel.rotating = false;
		clearInterval(Carousel.interval_id);
		clearTimeout(Carousel.timeout_id);
	},
	outTrigger: function()
	{
		if(!Carousel.rotating)
		{
			Carousel.timeout_id = window.setTimeout("Carousel.startRotation();", 5000);
		}
	},
	Slide: function(image, content, eval)
	{
		this.image = image;
		this.content = content;
		this.eval = eval;
	}

}

