/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 *   Image Rollovers - non-intrusive script for providing image rollovers
 *
 *   Glen H. Barratt from AdeptSites ghbarratt [at] adeptsites [dot] com
 *   http://adeptsites.com
 *
 *   Requires jQuery
 *   Requires URLUtilities
 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


var ImageRollovers = 
{
	filename_postfix: "_hover",
	trigger_class_prefix: "trigger-rollover-for-",
	init: function()
	{
		var rollover_images = jQuery(".image-with-rollover");
		for(var i=0; i<rollover_images.length; i++)
		{
			if(typeof(jQuery(rollover_images[i]).attr("id"))=="undefined") jQuery(rollover_images[i]).attr("id", "image_with_rollover_"+i);
			jQuery(rollover_images[i]).bind("mouseover", ImageRollovers.mouseoverListener);
			jQuery(rollover_images[i]).bind("mouseout", ImageRollovers.mouseoutListener);
			jQuery(rollover_images[i]).bind("click", ImageRollovers.mouseoutListener);
			//alert("trigger-rollover-for-"+jQuery(rollover_images[i]).attr("id"));
			jQuery("."+ImageRollovers.trigger_class_prefix+jQuery(rollover_images[i]).attr("id")).bind("mouseover", ImageRollovers.mouseoverTriggerListener);
			jQuery("."+ImageRollovers.trigger_class_prefix+jQuery(rollover_images[i]).attr("id")).bind("mouseout", ImageRollovers.mouseoutTriggerListener);
		}
	},
	getImageWithClass: function(classname)
	{
		var id = classname.substr(ImageRollovers.trigger_class_prefix.length);
		//alert(id);
		return (document.getElementById(id));
	},
	mouseoverListener: function(event)
	{
		ImageRollovers.swapToRollover(this);
	},
	mouseoutListener: function(event)
	{
		ImageRollovers.swapToNormal(this);
	},
	mouseoverTriggerListener: function(event)
	{
		var image = ImageRollovers.getImageWithClass(jQuery(this).attr("class"));
		ImageRollovers.swapToRollover(image);
	},
	mouseoutTriggerListener: function(event)
	{
		//alert("Mouse Out");
		var image = ImageRollovers.getImageWithClass(jQuery(this).attr("class"));
		ImageRollovers.swapToNormal(image);
	},
	swapToRollover: function(image)
	{
		current_src = jQuery(image).attr("src");
		current_src = URLUtilities.getRelativeURL(current_src);
		ext = URLUtilities.getExtension();
		filepath_base = URLUtilities.getFilepathBase();
		if(filepath_base.indexOf(ImageRollovers.filename_postfix)<0) filepath_base = filepath_base+ImageRollovers.filename_postfix;
	
		new_src = filepath_base+"."+ext+URLUtilities.query_string+URLUtilities.getAnchor();
		//alert("New src: "+new_src);
		jQuery(image).attr("src",new_src);
	},
	swapToNormal: function(image)
	{
		current_src = jQuery(image).attr("src");
		current_src = URLUtilities.getRelativeURL(current_src);
		ext = URLUtilities.getExtension();
		filepath_base = URLUtilities.getFilepathBase();
		if(filepath_base.indexOf(ImageRollovers.filename_postfix)>0) 
		{
			//alert("At this point the filepath_base: "+filepath_base);
			filepath_base = filepath_base.substr(0,filepath_base.lastIndexOf(ImageRollovers.filename_postfix));
			//alert("After cutting off the postfix, the filepath_base: "+filepath_base);
		}
		new_src = filepath_base+"."+ext+URLUtilities.query_string+URLUtilities.getAnchor();
		//alert("New src: "+new_src);
		jQuery(image).attr("src",new_src);
	},
}
