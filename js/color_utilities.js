/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 *
 *   Color Utilities - non-intrusive script for color (parsing, converting, etc.)
 *
 *   Glen H. Barratt from AdeptSites ghbarratt [at] adeptsites [dot] com
 *   http://adeptsites.com
 *
 *   Get the updated version from adeptsites.com
 *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


var ColorUtilities =
{
	convertBase: function(number_in, old_base, new_base) 
	{
		if(new_base==10) return parseInt(number_in, 16);
		if(new_base==16) return parseInt(number_in).toString(16);
			
		number_in = number_in + "";
		number_in = number_in.toUpperCase();
			
		var valid_chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		var dec = 0;
		for(var i=0; i<=number_in.length; i++) 
		{
			dec += (valid_chars.indexOf(number_in.charAt(i))) * (Math.pow(old_base, (number_in.length - i - 1)));
		}

		number_out = "";
		var magnitude = Math.floor((Math.log(dec)) / (Math.log(new_base)));
		for(var i=magnitude; i>=0; i--)
		{
			var amount = Math.floor(dec / Math.pow(new_base, i));
			number_out = number_out + valid_chars.charAt(amount);
			dec -= amount * (Math.pow(new_base, i));
		}
			
		if(number_out.length==0) number_out = 0;
		if(!number_out) number_out = 0;
			
		return number_out;
	},

	getHSVUsingRGBCode: function(rgb_code) 
	{
		if(typeof(rgb_code)=="undefined") return false;
		if(rgb_code.charAt(0)==="#") rgb_code = rgb_code.slice(1);
		//rgb_code = rgb_code.replace('#', '');
		red = parseInt(ColorUtilities.convertBase(rgb_code.substr(0, 2), 16, 10));
		green = parseInt(ColorUtilities.convertBase(rgb_code.substr(2, 2), 16, 10));
		blue = parseInt(ColorUtilities.convertBase(rgb_code.substr(4, 2), 16, 10));
		if(red==0 && green==0 && blue==0) 
		{
			var output_array = {};
			output_array.hue = 0;
			output_array.saturation = 0;
			output_array.brightness = 0;
			return output_array;
		}
		red = red / 255;
		green = green / 255;
		blue = blue / 255;
		max_value = Math.max(red, green, blue);
		min_value = Math.min(red, green, blue);
		var hue = 0;
		if(max_value==min_value)
		{
			hue = 0;
			saturation = 0;
		} 
		else 
		{
			if(red==max_value) hue = (green - blue) / (max_value - min_value) / 1;
			else if (green==max_value) hue = 2 + (blue - red) / 1 / (max_value - min_value) / 1;
			else if (blue==max_value) hue = 4 + (red - green) / (max_value - min_value) / 1;
			saturation = (max_value - min_value) / max_value;
		}
		hue = hue * 60;
		brightness_value = max_value;
		if (hue < 0) hue += 360;
		var output_array = {};
		output_array.hue = hue;
		output_array.saturation = saturation;
		output_array.brightness = brightness_value;
		return output_array;
	},

	getContrastColorUsingRGB: function(rgb_code) 
	{
		var hsv = ColorUtilities.getHSVUsingRGBCode(rgb_code);
		hsv.hue += 180;
		if (hsv.hue >= 360) hsv.hue -= 360;
		return ColorUtilities.getRGBCodeUsingHSV(hsv.hue, hsv.saturation, hsv.brightness);
	},

	getTriadeColorsUsingRGB: function(rgb_code) 
	{
		var hsv = ColorUtilities.getHSVUsingRGBCode(rgb_code);
		var colors = new Array();
		for (var no=120; no<360; no+=120) 
		{
			colors[colors.length] = ColorUtilities.getRGBCodeUsingHSV(hsv.hue + no, hsv.saturation, hsv.brightness)
		}
		return colors;
	},

	getTetradeColorsUsingRGB: function(rgb_code) 
	{
		var hsv = ColorUtilities.getHSVUsingRGBCode(rgb_code);
		var colors = new Array();
		for (var no=90; no<360; no+=90)
		{
			colors[colors.length] = ColorUtilities.getRGBCodeUsingHSV(hsv.hue + no, hsv.saturation, hsv.brightness)
		}
		return colors;
	},

	getAnalogicColors: function(rgb_code, degrees) 
	{
		degrees = degrees + '';
		if (!degrees) degrees = 25;
		if (!degrees.match(/^[0-9]{2}$/)) degrees = 25;
		if (degrees<15) degrees = 15;
		if (degrees>30) degrees = 30;
		degrees /= 1;
		var hsv = ColorUtilities.getHSVUsingRGBCode(rgb_code);
		var colors = new Array();
		for (var no=1; no<=2; no++) 
		{
			colors[colors.length] = ColorUtilities.getRGBCodeUsingHSV(hsv.hue + (no * degrees), hsv.saturation, hsv.brightness)
		}
		for (var no=-1; no>=-2; no--)
		{
			colors[colors.length] = ColorUtilities.getRGBCodeUsingHSV(hsv.hue + (no * degrees), hsv.saturation, hsv.brightness)
		}
			return colors;
	},

	getRGBCodeUsingRGBColors: function(red, green, blue) 
	{
		red = ColorUtilities.convertBase(red, 10, 16);
		green = ColorUtilities.convertBase(green, 10, 16);
		blue = ColorUtilities.convertBase(blue, 10, 16);
		red = red + "";
		green = green + "";
		blue = blue + "";
		while (red.length<2) red = "0" + red;
		while (green.length<2) green = "0" + green;
		while (blue.length<2) blue = "0" + "" + blue;
		rbg_code = red + "" + green + "" + blue;
		return rbg_code.toUpperCase();
	},

	getRGBColorsUsingRGBCode: function(rgb_code) 
	{
		var output_array = {};
		output_array.red = ColorUtilities.convertBase(rgb_code.substr(0, 2), 16, 10);
		output_array.green = ColorUtilities.convertBase(rgb_code.substr(2, 2), 16, 10);
		output_array.blue = ColorUtilities.convertBase(rgb_code.substr(4, 2), 16, 10);
		return output_array;
	},
	
	getRGBColorsUsingHSV: function(hue, saturation, brightness_value) 
	{
		Hi = Math.floor(hue / 60);
		if (hue == 360) hue = 0;
		f = hue / 60 - Hi;
		if (saturation > 1) saturation /= 100;
		if (brightness_value > 1) brightness_value /= 100;
		p = (brightness_value * (1 - saturation));
		q = (brightness_value * (1 - (f * saturation)));
		t = (brightness_value * (1 - ((1 - f) * saturation)));
		switch (Hi) 
		{
			case 0:
				red = brightness_value;
				green = t;
				blue = p;
			break;
			case 1:
				red = q;
				green = brightness_value;
				blue = p;
			break;
			case 2:
				red = p;
				green = brightness_value;
				blue = t;
			break;
			case 3:
				red = p;
				green = q;
				blue = brightness_value;
			break;
			case 4:
				red = t;
				green = p;
				blue = brightness_value;
			break;
			default:
				red = brightness_value;
				green = p;
				blue = q;
			break;
		}
		if (saturation==0)
		{
			red = brightness_value;
			green = brightness_value;
			blue = brightness_value;
		}
		red *= 255;
		green *= 255;
		blue *= 255;
		red = Math.round(red);
		green = Math.round(green);
		blue = Math.round(blue);
	
		var output_array = 
		{
			red: red,
			green: green,
			blue: blue
		}

		return output_array;

	},

	getRGBCodeUsingHSV: function(hue, saturation, brightness_value) 
	{
		while (hue>=360) hue -= 360;
		var colors = ColorUtilities.getRGBColorsUsingHSV(hue, saturation, brightness_value);
		return ColorUtilities.getRGBCodeUsingRGBColors(colors.red, colors.green, colors.blue);
	}

}// ColorUtilities
