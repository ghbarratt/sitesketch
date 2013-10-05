	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 *                                                                                             *
	 *  animation.js - a lightweight animation script                                              *
	 *                                                                                             *
	 *  By Glen H. Barratt                                                                         *
	 *  ghbarratt (at) adeptsites.com                                                              *
	 *                                                                                             *
	 *  Get the latest version from adeptsites.com                                                 *
	 *                                                                                             *
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */



	// GLOBALS

	var maximum_concurrent_animations = 10;
	
	var is_alerting_animation_errors = true; //false;
	var is_debugging_animation = false; //true;

	function Animation()
	{

		this.interval_id = null;
		this.element_id = null;
		this.is_animating = false;
		this.what = ""; 
		this.delay = 100; // in milliseconds
		this.step = 0.1;
		this.style = "speedup";
		this.limit = 0.0;
		this.value = 0.0;
		this.completion_function = null;

	}// Animation (Object)



	// LOCAL RUN

	initializeAnimations();			



	// FUNCTIONS

	function getValue(element_id, what)
	{

		var temp_element = document.getElementById(element_id);

		if((typeof(temp_element) == "undefined") && is_alerting_animation_errors) alert("ERROR: No element found with id '"+element_id+"'");

		if 
		(
			what=="height" 
			|| what=="width" 
			|| what=="top" 
			|| what=="left" 
			|| what=="marginTop" 
			|| what=="marginRight" 
			|| what=="marginBottom"
			|| what=="marginLeft"
			|| what=="paddingTop" 
			|| what=="paddingRight" 
			|| what=="paddingBottom"
			|| what=="paddingLeft"
		)
		{				
			// Assumes the format "##px"
			temp_string = temp_element.style[what];				
		
			if(is_debugging_animation) alert("The value is ".temp_string);

			if(typeof(temp_element.style[what])=="undefined" || temp_element.style[what]=="")
			{
				// No (initial) style set?
				if(temp_element[what]) value = temp_element[what];
				else return false;
			}
			else
			{	
				temp_index = temp_string.lastIndexOf("px");				
				value = parseFloat(temp_string.substring(0,temp_index));				
			}
		}
		else if (what=="backgroundposition")
		{				
			// Assumes the format "##px ##px"
			value = temp_element.style.backgroundPosition;				
		}
		else if (what=='opacity')
		{
			if(temp_element.filters) // for screwball IE
			{
				if(temp_element.filters.alpha.opacity) value = parseFloat(temp_element.filters.alpha.opacity/100);
				else value = 1.0;
			}
			else value = parseFloat(temp_element.style.opacity);
			//alert('The opacity value here is '+value);
		}

		else return false;

		return value;

	}// getValue
	

	function setValue(element_id, what, value)
	{

		temp_element = document.getElementById(element_id);

		if 
		(
			what=="height" 
			|| what=="width" 
			|| what=="top" 
			|| what=="left"
			|| what=="marginTop"
			|| what=="marginRight"
			|| what=="marginBottom"
			|| what=="marginLeft"
			|| what=="paddingTop" 
			|| what=="paddingRight" 
			|| what=="paddingBottom"
			|| what=="paddingLeft"
		)
		{
			FinalString = value.toString()+"px";
			temp_element.style[what] = FinalString;
		}					
		else if (what=="backgroundposition") temp_element.style.backgroundPosition = value;
		else if (what=='opacity')
		{
			if(temp_element.filters)
			{
				//if(temp_element_filters.alpha) temp_element.filters.alpha.opacity = parseInt(value*100); // for screwball IE
				temp_element.style.filter="alpha(opacity="+parseInt(value*100)+")";
			}
			temp_element.style.opacity = value;
		}
		else return false;

		return true;

	}// setValue



	function initializeAnimations(maximum_concurrent_animations_in)
	{
		
		if (typeof(maximum_concurrent_animations_in) != "undefined") maximum_concurrent_animations = maximum_concurrent_animations_in;
		if (typeof(Animations) == "undefined") Animations = new Array(maximum_concurrent_animations);

		if(is_debugging_animation) alert("There can be up to "+maximum_concurrent_animations+" concurrent animations");

		for(i=0; i<maximum_concurrent_animations; i++)
		{
			Animations[i] = new Animation();
		}
	}// initializeAnimations


	function getFirstAvailableAnimationIndex()
	{
		if(is_debugging_animation) alert("Going through all the "+maximum_concurrent_animations+" possible animations");
		for(i=0; i<maximum_concurrent_animations; i++)
		{
			if (!Animations[i].is_animating) return i;
		}

		return -1;

	}// getFirstAvailableAnimationIndex


	function determineIndexWithElementID(element_id)
	{
		var result = -1;
		for(i=0; i<maximum_concurrent_animations; i++)
		{
			if (Animations[i].is_animating && element_id==Animations[i].element_id) result = i;
		}
		return result;
	}// determineIndexWithElementID


	function animate(element_id, what, limit, style, delay, step, completion_function)
	{

		if (is_debugging_animation) alert("DEBUG: Animating");
		
	
		// Set optional parameters to defaults if undefined
		if 
		(
		 	typeof(step) != "number"
			&& 
			(
				typeof(step) == "undefined" 
				|| 
				step == "" 
				|| 
				(typeof(step)=="string" && step.toLowerCase() == "default")
			)
		) step = 1;

		if 
		(
		 	typeof(delay) != "number"
			&& 
			(
				typeof(delay) == "undefined" 
				|| 
				delay == "" 
				|| 
				(typeof(delay)=="string" && delay.toLowerCase() == "default")
			)	
		) delay = 50; 
		

		// Format (steralize) inputs
		what = what.toLowerCase().replace(/[' ',_]/g,"");
		style = style.toLowerCase().replace(/[' ',_]/g,"");

		// Replacements
		if(what=='margin-top') what = "marginTop";
		if(what=='margin-right') what = "marginRight";
		if(what=='margin-bottom') what = "marginBottom";
		if(what=='margin-left') what = "marginLeft";
		if(what=='padding-top') what = "paddingTop";
		if(what=='padding-right') what = "paddingRight";
		if(what=='padding-bottom') what = "paddingBottom";
		if(what=='padding-left') what = "paddingLeft";

		var animation_index = determineIndexWithElementID(element_id);
		
		// If not animation is already working on the element then start a new one
		if (animation_index == -1) animation_index = getFirstAvailableAnimationIndex();
		else
		{
			// This means the same thing we want to animate is already in animation			
			if (is_debugging_animation) alert("DEBUG: Trying to animate something that is already animating");
		}			


		stopAnimation(animation_index);
	
		CurrentAnimation = Animations[animation_index];
		
		CurrentAnimation.is_animating = true;
		CurrentAnimation.delay        = delay;
		CurrentAnimation.limit        = parseFloat(limit);
		CurrentAnimation.step         = parseFloat(step);
		CurrentAnimation.element_id   = element_id;
		CurrentAnimation.what         = what;
		CurrentAnimation.style        = style.toLowerCase();
		if(typeof(completion_function)!="undefined") CurrentAnimation.completion_function = completion_function;
		else CurrentAnimation.completion_function = null;

		// Get current animation value from element
		CurrentAnimation.value = getValue(element_id, what);		

		if (is_debugging_animation) alert("The initial value of "+element_id+":"+what+" is: "+CurrentAnimation.value);

		// Check type because 0 which is valid will == false!
		if(typeof(CurrentAnimation.value)=="boolean" && CurrentAnimation.value==false) 
		{
			if (is_alerting_animation_errors) alert("ERROR: Can not figure out how to animate '"+what+"' or unable to determine initial '"+what+"' value. (Hint - set initial value in HTML.)")
			return false;
		}
						

		Call = "updateAnimation('"+animation_index+"')";

		//alert("Starting an animation with '"+Call+"' and index: "+animation_index+".");

		CurrentAnimation.interval_id = setInterval(Call, CurrentAnimation.delay);


		return true;

	}// animate


	function updateAnimation(animation_index)
	{
		//alert("Updating Animation")

		CurrentAnimation = Animations[animation_index];

		original_value = CurrentAnimation.value;

		//if (is_debugging_animation) alert("DEBUG: value: "+CurrentAnimation.value+" and limit: "+CurrentAnimation.limit+" step: "+CurrentAnimation.step);

		completion_function = null;

		if (CurrentAnimation.value < CurrentAnimation.limit)
		{
			CurrentAnimation.value += CurrentAnimation.step;
			if (CurrentAnimation.value >= CurrentAnimation.limit)
			{
				CurrentAnimation.value = CurrentAnimation.limit;
				completion_function = CurrentAnimation.completion_function
				//alert('Completed so run '+CurrentAnimation.completion_function);	
				stopAnimation(animation_index);
				if(completion_function!=null && typeof(yourFunctionName)=='function') this[completion_function]();
			}
		}
		else 
		{
			//alert(' value = '+CurrentAnimation.value+' -= '+CurrentAnimation.step);
			CurrentAnimation.value -= CurrentAnimation.step;
			if (CurrentAnimation.value <= CurrentAnimation.limit)
			{
				//alert('went in here because ca.value= '+CurrentAnimation.value+' and ca.limit='+CurrentAnimation.limit);
				CurrentAnimation.value = CurrentAnimation.limit;
				completion_function = CurrentAnimation.completion_function
				//alert('Completed so run '+CurrentAnimation.completion_function);	
				stopAnimation(animation_index);
				if(completion_function!=null) this[completion_function]();
			}
		}

		applyAnimationStyle(animation_index);
			
		//alert("DEBUG: The animation value is now '"+CurrentAnimation.value+"'");

		if(CurrentAnimation.is_animating && CurrentAnimation.value == original_value)
		{
			if (is_alerting_animation_errors) alert("ERROR: The animation is not working so it will be stopped.");
			stopAnimation(animation_index);
		}
		else if(completion_function==null) setValue(CurrentAnimation.element_id, CurrentAnimation.what, CurrentAnimation.value);

	}// updateAnimation


	function stopAnimation(animation_index)
	{
		if(is_debugging_animation) alert("Stopping animation "+animation_index+"");
		clearInterval(Animations[animation_index].interval_id);			
		Animations[animation_index].is_animating = false;
	}// stopAnimation


	function applyAnimationStyle(animation_index)
	{
		CurrentAnimation = Animations[animation_index];
		if (CurrentAnimation.style == "speedup") 
		{
			if(CurrentAnimation.what=='opacity') CurrentAnimation.step += .1;
			else CurrentAnimation.step += 1;
		}
		else if (CurrentAnimation.style == "fastspeedup") CurrentAnimation.step *= 2;
		else if (CurrentAnimation.style == "slowdown" && CurrentAnimation.step > 1) CurrentAnimation.step -= 1;
	}// applyAnimationstyle 
	
