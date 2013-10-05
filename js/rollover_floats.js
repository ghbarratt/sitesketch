/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 *   Rollover Floats - non-intrusive script for providing rollover floats
 *
 *   Glen H. Barratt from AdeptSites ghbarratt [at] adeptsites [dot] com
 *   http://adeptsites.com
 *
 *   Requires jQuery
 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


var RolloverFloats = 
{
	mouseover_activator: false,
	mouseover_float: false,
	going_to_check: false,
	activator_postfix: "-activator",
	init: function()
	{
		var rollover_floats = $j(".rollover-float");
		for(var i=0; i<rollover_floats.length; i++)
		{
			id = $j(rollover_floats[i]).attr("id");
			activators = $j("."+id+RolloverFloats.activator_postfix);
			if(activators.length) 
			{
				//alert("FOUND element with class: ."+id+"\n activator count: "+activators.length);
				$j("."+id+RolloverFloats.activator_postfix).mouseover(function() {RolloverFloats.mouseoverActivator(this);});
				$j("."+id+RolloverFloats.activator_postfix).mouseout(function() {RolloverFloats.mouseoutActivator(this);});
				$j("."+id+RolloverFloats.activator_postfix).click(function() {RolloverFloats.mouseoutActivator(this);});
				$j("#"+id).mouseover(function() {RolloverFloats.mouseoverFloat(this);});
				$j("#"+id).mouseout(function() {RolloverFloats.mouseoutFloat(this);});
			}
		}
	},
	mouseoverActivator: function(activator)
	{
		//alert("Moused over an activator ");
		float_id = RolloverFloats.getFloatIdWithActivator(activator);
		RolloverFloats.mouseover_activator = float_id;
		$j("#"+float_id).show();
	},
	mouseoutActivator: function(activator)
	{
		float_id = RolloverFloats.getFloatIdWithActivator(activator);
		RolloverFloats.mouseover_activator = false;
		if(RolloverFloats.going_to_check) return false;
		RolloverFloats.going_to_check = setTimeout("RolloverFloats.checkAndHideFloat(\""+float_id+"\")", 10);
	},
	mouseoutFloat: function(float_element)
	{
		//alert("Entered MouseoutFloat "+float_id);
		float_id = $j(float_element).attr("id");
		RolloverFloats.mouseover_float = false;
		if(RolloverFloats.going_to_check) return false;
		RolloverFloats.going_to_check = setTimeout("RolloverFloats.checkAndHideFloat(\""+float_id+"\")", 10);
	},
	mouseoverFloat: function(float_element)
	{
		float_id = $j(float_element).attr("id");
		RolloverFloats.mouseover_float = float_id;
	},
	getFloatIdWithActivator: function(activator)
	{
		//alert("Activator_id: "+activator_id);
		activator_class_string = $j(activator).attr("class");
		activator_classes = activator_class_string.split(" ");	
		rollover_float_id = false;
		for(c_i in activator_classes)
		{
			var classname = activator_classes[c_i];
			if((pos = classname.indexOf(RolloverFloats.activator_postfix))>=0)
			{
				rollover_float_id = classname.substr(0, pos);
			}
		}
		return rollover_float_id;
	},
	checkAndHideFloat: function(float_id)
	{
		RolloverFloats.going_to_check = false;
		if(typeof(float_id)=="undefined") alert("Float id is undefined???");
		//alert("Check and Hide Float "+float_id+" = "+RolloverFloats.mouseover_float);
		if(float_id == RolloverFloats.mouseover_float) return false;
		if(float_id == RolloverFloats.mouseover_activator) return false;
		else RolloverFloats.hideFloat(float_id);
	},
	hideFloat: function(float_id)
	{
		$j("#"+float_id).hide();
		//alert("Should have faded out");
	}
}
