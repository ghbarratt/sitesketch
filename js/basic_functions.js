	/// VARS ///
	var MF = (navigator.appCodeName.indexOf("Mozilla")>=0 && navigator.vendor && navigator.vendor.indexOf("Firefox")>=0)? true : false;
	var IE = (document.all)? true : false;
	var NS = (parseFloat(navigator.appVersion) >= 5 && navigator.appName.indexOf("Netscape")>=0 )? true: false;

	/// FUNCTIONS ///
	function toggleDisplay(ID)
	{
		var element = document.getElementById(ID);
		
		if(element.style.display=="block")
			element.style.display="none";
		else element.style.display="block";
	}//toggleDisplay


	function changeImage(image_id, image_filename)
	{
		
		image = document.getElementById(image_id);
		if(image) image.src = image_filename;
		else 
		{
			alert("Apparently the id "+image_id+" was not found");
			document[image_id].src = image_filename;	
		}
	}//changeImage


	function changeBackgroundImage(ID, image_filename)
	{
		element = document.getElementById(ID) 
		final_string = "url("+image_filename+")";
		element.style.backgroundImage = final_string;

	}// changeBackgroundImage


	function preloadImage(source, width, height)
	{
	
		if(typeof(width)=="undefined") width = 100;
		if(typeof(height)=="undefined") height = 100;
		
		temp_image = new Image(width, height);
		temp_image.src = source;

	}// preloadImage


	function setOpacity(id, level)
	{
  	element = document.getElementById(id) 
	
  	// If filters exist, then this is IE, so set the Alpha filter
    if(element.filters)
		{
			if(element.filters.alpha) element.filters.alpha.opacity = parseInt(level*100);
 			else element.style.filter="alpha(opacity="+parseInt(level*100)+")";
		}
    // Otherwise use the W3C opacity property
    else element.style.opacity = parseFloat(level);
	

	}// setOpacity
