/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 *   URL Utilities - non-intrusive script for manipulating and parsing urls
 *
 *   Glen H. Barratt from AdeptSites ghbarratt [at] adeptsites [dot] com
 *   http://adeptsites.com
 *
 *   INCOMPLETE OR IN DEVELOPMENT! Get the updated version from adeptsites.com
 *
 *   Requires jQuery
 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


var URLUtilities = 
{
	protocol: window.location.protocol,
	host: window.location.host,
	path: window.location.pathname,
	query_string: "",
	parameteres: new Array(),
	anchor: "",
	init: function (url)
	{
		URLUtilities.reset_url();
	},
	reset_url: function()
	{
		URLUtilities.protocol = window.location.protocol;
		URLUtilities.host = window.location.host;
		URLUtilities.path = window.location.pathname;
		URLUtilities.query_string = "";
		URLUtilities.parameters = Array();
		URLUtilities.anchor = "";
	},
	getRelativeURL: function(url)
	{
		if(typeof(url)!="undefined")URLUtilities.parseURL(url);
		//alert(URLUtilities.protocol+" \n"+URLUtilities.path);
		return URLUtilities.path+URLUtilities.query_string+URLUtilities.getAnchor();
	},
	getAnchor: function(url)
	{
		if(typeof(url)!="undefined")URLUtilities.parseURL(url);
		
		return (URLUtilities.anchor.length ? "#"+URLUtilities.anchor : "");
	},
	parseURL: function(url)
	{
		if(url==undefined) url = window.location+"";
		//alert("Parsing the url: "+url);
		
		var pos;
		if((pos=url.indexOf(":")) >= 0)
		{
			URLUtilities.protocol = url.substring(0, pos);
			var url_n_p = url.substring(pos+1);
		}
		else var url_n_p = url;

		//alert("url_n_p: "+url_n_p);

		if((pos = url_n_p.indexOf("#")) >= 0)
		{
			URLUtilities.anchor = url_n_p.substring(pos+1);
			url_n_pa = url_n_p.substring(0, pos);
		}
		else 
		{
			url_n_pa = url_n_p;
			URLUtilities.anchor = "";
		}

		if((pos = url_n_pa.indexOf("?")) >= 0)
		{
			URLUtilities.query_string = url_n_pa.substring(pos);
			query_string = "&"+URLUtilities.query_string.substr(1);
			//alert(query_string);
			path = url_n_pa.substring(0, pos);
			while((pos = query_string.indexOf("&")) >= 0)
			{
				var parameter_string = query_string.substring(0, pos);
				query_string = query_string.substr(pos+1);

				if(parameter_string.length)
				{
					var equ_pos = parameter_string.indexOf("=");
					if(equ_pos > 0) URLUtilities.parameters[parameter_string] = '';
					else URLUtilities.parameters[parameter_string.substring(0, equ_pos)] = decodeURIComponent(parameter_string.substring(equ_pos + 1));
				}
			}
		}
		else 
		{
			path = url_n_pa;	
			URLUtilities.query_string = "";
		}

		if(path.indexOf("//") == 0) // absolute
		{
			path = path.substring(2);
			if((pos = path.indexOf("/")) >= 0)
			{
				URLUtilities.host = path.substring(0, pos);
				URLUtilities.path = path.substring(pos);
			}
			else
			{
				URLUtilities.host = path;
				URLUrilities.path = '/';
			}
		}
		else if(path.indexOf("/") == 0) // relative to host
		{
			//alert("Path is relative to host\nSetting path to: "+path);
			URLUtilities.path = path;
		}
		else // relative to directory
		{
			var p = URLUtilities.path.lastIndexOf('/');
			if(p<0) URLUtilities.path = '/';
			else if(p < URLUtilities.path.length - 1) URLUtilities.path = URLUtilities.path.substring(0, p+1);
			
			while(path.indexOf("../") == 0)
			{
				var p = URLUtilities.path.lastIndexOf('/', URLUtilities.path.lastIndexOf('/') - 1);
				if(p >= 0) URLUtilities.path = URLUtilities.path.substring(0, p + 1);
				path = path.substring(3); // removing '../' from begining
			}
			URLUtilities.path = URLUtilities.path + path;
		}
	},
	getURL: function()
	{
		var url = URLUtilities.protocol + '://' + URLUtilities.host + URLUtilities.path;
		var div = '?';
		for(var key in URLUtilities.parameters)
		{
			url += div + key + '=' + encodeURIComponent(URLUtilities.parameters[key]);
			div = '&';
		}
		return url;
	},
	getExtension: function(url)
	{
		if(typeof(url)!="undefined") URLUtilities.parseURL(url);	

		pos = URLUtilities.path.lastIndexOf('.');
		return URLUtilities.path.substr(pos+1);
	},
	getFilenameBase: function(url)
	{
		if(typeof(url)!="undefined") URLUtilities.parseURL(url);	

		start_pos = URLUtilities.path.lastIndexOf('/')+1;
		end_pos = URLUtilities.path.lastIndexOf('.');
		return URLUtilities.path.substring(start_pos, end_pos);	
	},
	getFilepathBase: function(url)
	{
		if(typeof(url)!="undefined") URLUtilities.parseURL(url);	

		pos = URLUtilities.path.lastIndexOf('.');
		if(pos==-1) pos = URLUtilities.path.length;
		return URLUtilities.path.substring(0, pos);	
	}
}
