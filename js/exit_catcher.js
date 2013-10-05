
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 *   Exit Catcher - non-intrusive script for providing an exit catching
 *
 *   Glen H. Barratt 
 *
 *   Requires jQuery
 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

var ExitCatcher =
{

	status: 0,
	exit_url: "",
	technique: "location_replace",
	exit_location: "exit.php",
	ignore_internal_links: true,
	cancelled: false,
	confirm_message: "Please confirm you want to leave the site.",
	confirm_exit: false,
	use_beforeunload: true,

	init: function(technique, confirm_message)
	{

		ExitCatcher.exit_url = "";
		ExitCatcher.cancelled = false;

		var listen_for_clicks_on_tags = ["a","button","form"];

		if(typeof technique !=="undefined") ExitCatcher.technique = technique;
		if(typeof confirm_message !=="undefined") ExitCatcher.confirm_message = confirm_message;

		// Set up the Trigger/Listeners
		jQuery("a").click(ExitCatcher.setExitURLUsingAnchor);
		jQuery("form").submit(ExitCatcher.setExitURLUsingForm);
		jQuery("button").click(ExitCatcher.setExitURLUsingButton);

		if(ExitCatcher.use_beforeunload)
		{
			window.onbeforeunload = ExitCatcher.catchExit;
		}

		window.onunload = ExitCatcher.catchExit;

		//alert("ExitCatcher engaged!");

	},// init


	setExitURLUsingAnchor: function(obj)
	{
		ExitCatcher.exitURL = jQuery(this).attr("href");
		if(ExitCatcher.ignore_internal_links && ExitCatcher.isURLInternal(ExitCatcher.exitURL)) ExitCatcher.cancelExitCatching();
	},// setExitURLUsingAnchor


	setExitURLUsingForm: function(obj)
	{
		ExitCatcher.exitURL = this.action;
		//alert(ExitCatcher.exitURL);
		if(ExitCatcher.ignore_internal_links && ExitCatcher.isURLInternal(ExitCatcher.exitURL)) ExitCatcher.cancelExitCatching();
	},// setExitURLUsingForm


	cancelExitCatching: function()
	{
		if(typeof window.onbeforeunload!="undefined") window.onbeforeunload = null;
		if(typeof window.onunload!="undefined") window.onunload = null;
		ExitCatcher.cancelled = true; 
	},


	isURLInternal: function(url)
	{

		var internal = true;

		url = url.toLowerCase();
		if(url.indexOf("http://")==-1 && url.indexOf("https://")==-1) internal = true; 
		else
		{
			var comp = new RegExp(location.host);
			if(comp.test(url)) internal = true;
			else internal = false;
		}
		//alert("Is this URL internal? "+internal);
		return internal;
	},

	catchExit: function(e)
	{
		if(ExitCatcher.cancelled) return false;

		e = e || window.event;

		if(ExitCatcher.technique=="location_replace") window.location.replace(ExitCatcher.exit_location);

		// For IE and Firefox prior to version 4
		if(e) e.returnValue = ExitCatcher.confirm_message;

		// For Safari
		return ExitCatcher.confirm_message;

	}// catchExit


}//

