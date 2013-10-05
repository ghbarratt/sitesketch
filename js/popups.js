/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 *   Popups - non-intrusive script for providing popups
 *
 *   Glen H. Barratt from AdeptSites ghbarratt [at] adeptsites [dot] com
 *   http://adeptsites.com
 *
 *   Requires jQuery
 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

var Popups =
{

	default_button_image: "/images/button_close_popup.png",
	add_button: true,
	darkener_id: "popup_darkener",
	popup_class: "popup",
	opener_class: "popup-opener",
	closer_class: "popup-closer",
	margin_top: 30,
	controlling_class_prefix: "controlling-popup-",
	current_id: "",
	status: 0,


	init: function(add_button, z_index)
	{

		if(typeof(add_button)=="undefined") add_button = Popups.default_button_image; // Add a close popup button to every popup?
		Popups.add_button = add_button;
		if(typeof(z_index)=="undefined") z_index = 10; // We want to be fairly sure it ends up on top

		if(!$("#"+Popups.darkener_id).length) $("body").append("<div id='"+Popups.darkener_id+"'></div>");
		$("#"+Popups.darkener_id).css
		(
			{
				"position": "absolute",
				"top": "0",
				"left": "0",
				"width": "100%",
				"background-color": "black",
				"z-index": z_index
			}
		);
	
		$("."+Popups.popup_class).css
		(
			{
				"z-index": (z_index+1),
				"display": "none"
			}
		);
	
		if(Popups.add_button)
		{
			$("."+Popups.popup_class).prepend('<div class="popup-close-button"><a href="javascript: void(0);" onclick="Popups.clickedCloser()"><img src="'+Popups.add_button+'" alt="x" /></a></div>');
		}

		// Set up the Trigger/Listeners
		$("."+Popups.opener_class).bind("click", Popups.clickedOpener);

		$("."+Popups.closer_class).bind("click", Popups.clickedCloser);

		$("#"+Popups.darkener_id).click(function(){Popups.close();});

		$(document).keypress
		(
			function(e)
			{
				if(e.keyCode==27 && Popups.status==1)
				{
					Popups.close();
				}
			}
		);

	}, // init


	determinePopupToControl: function(element)
	{
		var class_strings = element.className.split(/\s+/);
		for(i=0; i<class_strings.length; i++)
		{
			if(class_strings[i].indexOf(Popups.controlling_class_prefix)===0)
			{
				to_control_id = class_strings[i].substr(Popups.controlling_class_prefix.length);
				return to_control_id;
			}
		}

	},

	
	clickedOpener: function(event)
	{
		
		if(typeof(event)=="string") id = event;
		else 
		{
			id = Popups.determinePopupToControl(event.target);
    }

		if(Popups.current_id)
		{
			$("#"+Popups.current_id).fadeOut("slow");
			Popups.status = 0;
		}

		Popups.center(id); 
		Popups.open(id);

	}, // clickedOpener

	clickedCloser: function()
	{
		
		Popups.close();

	}, // clickedCloser

	open: function(id)
	{
		//alert("Attempting to open the popup: "+id);
		// opens popup only if it is closed
		if(Popups.status==0)
		{
			$("#"+Popups.darkener_id).css({"opacity": "0.7"});
			$("#"+Popups.darkener_id).fadeIn("slow");
			$("#"+id).fadeIn("slow");
			Popups.status = 1;
			Popups.current_id = id;
		}
	}, // open


	close: function()
	{
		// Closes popup only if it is enabled
		if(Popups.status==1)
		{
			$("#"+Popups.darkener_id).fadeOut("slow");
			$("#"+Popups.current_id).fadeOut("slow");
			Popups.status = 0;
			Popups.current_id = "";
		}
	}, // close


	center: function(id)
	{

		if(typeof(id)=="undefined") id = Popups.current_id;

		// Request data for centering
		var window_width = document.documentElement.clientWidth;
		var window_height = document.documentElement.clientHeight;
		var popup_height = $("#"+id).height();
		var popup_width = $("#"+id).width();
		var page_height = $(document).height();


		//var new_top = window_height/2-popup_height/2;
		//alert("Made it here with: "+id+" new_top: "+new_top);
	
		$("#"+id).css
		(
			{
				"position": "absolute",
				"top": $(document).scrollTop()+Popups.margin_top, //(window_height/2.25-popup_height/2+$(document).scrollTop()),
				"left": (window_width/2-popup_width/2)
			}
		);
	
		// We want the darkener to be as tall as the page, not just the window
		$("#"+Popups.darkener_id).css({"height": page_height});
	
	}// center
	
}//

