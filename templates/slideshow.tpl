<script type="text/javascript">
	
	slideshow_~alias~_slide = 0;

	function SlideshowImage()
	{

		this.src = null;
		this.original = null;
		this.width = null;
		this.height = null;
		//this.alt = null;

	}// SlideshowImage (Object)

	var slideshow_~alias~_interval_id;
	var is_playing_slideshow_~alias~ = false;
	var slideshow_~alias~_images = new Array(~image_count~);
	~images{
	slideshow_~slideshow_alias~_images[~index~] = new SlideshowImage();
	slideshow_~slideshow_alias~_images[~index~].src = '~web_path~/viewport/~eval(addslashes("~filename~"))eval~';
	slideshow_~slideshow_alias~_images[~index~].original = '~web_path~/~eval(addslashes("~filename~"))eval~';
	slideshow_~slideshow_alias~_images[~index~].width = ~width~;
	slideshow_~slideshow_alias~_images[~index~].height = ~height~;
	}images~

	//var preload_images;	
	//preload~alias~SlideshowImages();


	function preload~alias~SlideshowImages()
	{

		if(typeof(preload_images)=="undefined")
		{
			pi_count = 0;
			preload_images = new Array();
		}
		else pi_count = preload_images.length; 

		for(pi=0; pi<slideshow_~alias~_images.length; pi++)
		{
			//preload_images[pi+pi_count] = new Image();
			//preload_images[pi+pi_count].src = slideshow_~alias~_images[pi].src;
			changeBackgroundImage('slideshow_~alias~_below_image', slideshow_~alias~_images[pi].src);
			//alert("Preloaded "+slideshow_~alias~_images[pi].src+" as image "+pi);
		}

	}// preload~alias~SlideshowImages


	function clickAdvance~alias~Slide(step)
	{

		should_restart_slideshow = false;

		if(is_playing_slideshow_~alias~) 
		{	
			should_restart_slideshow = true;
			stopPlayingSlideshow~alias~();
		}
		
		advance~alias~Slide(step);

		if(should_restart_slideshow) startPlayingSlideshow~alias~(false);
		
	}// clickAdvance~alias~Slide


	function advance~alias~Slide(step)
	{
		var previous_slide = slideshow_~alias~_slide;
	
		slideshow_~alias~_slide += step;
		
		if(slideshow_~alias~_slide>=~image_count~) slideshow_~alias~_slide -= ~image_count~;
		else if(slideshow_~alias~_slide<0) slideshow_~alias~_slide += ~image_count~;
		
		
		if(slideshow_~alias~_slide+step>-1 && slideshow_~alias~_slide+step<~image_count~) changeBackgroundImage('slideshow_~alias~_guess_next_image', slideshow_~alias~_images[slideshow_~alias~_slide+step].src);				

		setOpacity('slideshow_~alias~_above_image', 0);
		changeBackgroundImage('slideshow_~alias~_above_image', slideshow_~alias~_images[previous_slide].src);
		setOpacity('slideshow_~alias~_above_image', 1.0);
		changeBackgroundImage('slideshow_~alias~_below_image', slideshow_~alias~_images[slideshow_~alias~_slide].src);
		//alert("The above image should be at 100% now");
		animate('slideshow_~alias~_above_image', 'opacity', 0.0, 'normal', 75, 0.1, "resetSlideshow~alias~AboveImage");
		setSlideshow~alias~Progress(parseInt(slideshow_~alias~_slide+1)+" of "+~image_count~);
	}// advance~alias~Slide


	function setSlideshow~alias~Progress(content)
	{
		element = document.getElementById("slideshow_~alias~_progress");
		if(element) element.innerHTML = content;
	}// setSlideshow~alias~Progress


	function resetSlideshow~alias~AboveImage()
	{
		// set the image to the current image and set the opacity back to 1

		setOpacity('slideshow_~alias~_above_image', 0);
		changeBackgroundImage('slideshow_~alias~_above_image', slideshow_~alias~_images[slideshow_~alias~_slide].src);				
		
		//alert('To set opacity');

	}// resetSlideshow~alias~AboveImage


	function togglePlayingSlideshow~alias~()
	{
		if(is_playing_slideshow_~alias~) stopPlayingSlideshow~alias~();
		else startPlayingSlideshow~alias~();
	}// togglePlayingSlideshow~alias~


	function startPlayingSlideshow~alias~(advance_on_start)
	{
		if(typeof(advance_on_start)=="undefined") advance_on_start = true;

		//alert("Type is currently "+typeof(slideshow_~alias~_interval_id)); 
		if(!is_playing_slideshow_~alias~)
		{
			if(typeof(slideshow_~alias~_interval_id)!="undefined") stopPlayingSlideshow~alias~();
			is_playing_slideshow_~alias~ = true;
			if(advance_on_start) advance~alias~Slide(1);
			slideshow_~alias~_interval_id = window.setInterval("advance~alias~Slide(1)", 8000);
		}
	}// startPlayingSlideshow~alias~


	function stopPlayingSlideshow~alias~()
	{
		//alert("Interval_id = "+slideshow_~alias~_interval_id);
		if(typeof(slideshow_~alias~_interval_id)!="undefined")
		{
			window.clearInterval(slideshow_~alias~_interval_id);
		}
		is_playing_slideshow_~alias~ = false;

	}// stopPlayingSlideshow~alias~


	function clickSlideshow~alias~Photo()
	{
		window.location = slideshow_~alias~_images[slideshow_~alias~_slide].original;
	}


	// RUN the slideshow!

	setTimeout('startPlayingSlideshow~alias~()', 8000);

</script>

<div id="slideshow_~alias~" class="slideshow">
	<table cellpadding=0 cellspacing=0 border=0>
		<tr>
			<td class="slideshow-top-left"></td>
			<td class="slideshow-top-center"></td>
			<td class="slideshow-top-right"></td>
		</tr>
		<tr>
			<td class="slideshow-middle-left"></td>
			<td class="slideshow-middle-center">
				<div id="slideshow_~alias~_viewport" class="slideshow-viewport" style="overflow: hidden; text-align: center; position: relative; width:~viewport_width~px; height: ~viewport_height~px;">
					<div 
						id="slideshow_~alias~_guess_next_image" 
						class="slideshow-below-image"
						style="width: ~viewport_width~px; height: ~viewport_height~px; background: black url(~initial_image_web_filepath~) no-repeat 50% 50%; position: absolute; top: 0; left: 0; z-index: 1; ~image_style~" 
					><br/>
					</div>
					<div 
						id="slideshow_~alias~_below_image" 
						class="slideshow-below-image"
						style="width: ~viewport_width~px; height: ~viewport_height~px; background: black url(~initial_image_web_filepath~) no-repeat 50% 50%; position: absolute; top: 0; left: 0; z-index: 1; ~image_style~" 
					><br/>
					</div>
					<div 							 
						id="slideshow_~alias~_above_image" 
						class="slideshow-above-image"
						style="opacity: 0.0; filter: alpha(opacity=0); cursor: pointer; width: ~viewport_width~px; height: ~viewport_height~px; background: transparent url(~initial_image_web_filepath~) no-repeat 50% 50%; position: absolute; top: 0; left: 0; z-index: 2; ~image_style~" 
						onclick="clickSlideshow~alias~Photo();"
					><br/>
					</div>
				</div>
			</td>
			<td class="slideshow-middle-right"></td>
		</tr>
		<tr class="slideshow-bottom">
			<td class="slideshow-bottom-left"></td>
			<td class="slideshow-bottom-center"></td>
			<td class="slideshow-bottom-right"></td>
		</tr>
	</table>
	~slideshow_controls~
</div>

