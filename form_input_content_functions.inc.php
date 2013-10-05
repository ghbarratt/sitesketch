<?php

	/// FORM INPUT CONTENT FUNCTIONS ///

	function getSelectInputContent($alias, $values, $labels=false, $default=false, $extra_attributes='')
	{
		global $line_prefix;

		if(!is_array($values))return false;
		if(!is_array($labels))$labels = $values;

		//echo "HI! Default:".$default."\nHI! Labels";
		//print_r($labels);
		//echo "HI! Choices";
		//print_r($values);

		$content = '';
		$content .= $line_prefix."<select id=\"".$alias."\" name=\"".$alias."\" ".$extra_attributes.">\n";
		for($i=0; $i<count($values); $i++)
		{
			$temp_values = $values[$i];
			$temp_label = $labels[$i];

			$content .= $line_prefix."\t<option value=\"".$temp_values."\"";
			if(($temp_values==$default)||($temp_label==$default))$content .= " selected";
			$content .= ">".$temp_label."</option>\n";
		}
		$content .= $line_prefix."</select>\n";

		return $content;
	}// getSelectInputContent

	function getSelectContent($alias, $values, $labels=false, $default=false, $extra_attributes='')
	{
		return getSelectInputContent($alias, $values, $labels, $default, $extra_attributes);
	}// getSelectContent (alias)

	function getDropDownInputContent($alias, $values, $labels=false, $default=false, $extra_attributes='')
	{
		return getSelectInputContent($alias, $values, $labels, $default, $extra_attributes);
	}// getDropDownInputContent (alias)


	function getRadioInputContent($alias, $values, $labels=false, $default=false, $extra_attributes='')
	{
		global $line_prefix;

		if(!is_array($values)) return false;
		if(!is_array($labels)) $labels = $values;

		//echo "DEBUG Default:".$default."\nHI! Labels";
		//print_r($labels);
		//echo "DEBUG Choices";
		//print_r($values);

		$content = '';
		for($i=0; $i<count($values); $i++)
		{
			$temp_values = $values[$i];
			$temp_label = $labels[$i];

			$content .= $line_prefix."\t<input type=\"radio\" name=\"".$alias."\" value=\"".$temp_values."\"";
			if(($temp_values==$default)||($temp_label==$default)) $content .= ' checked';
			$content .= ">".$temp_label."\n";
		}

		return $content;

	}// getRadioInputContent

	
	function getDateInputsContent($alias=false, $default_date=false, $min_year=false, $max_year=false, $format=false, $place_base_input='hidden')
	{

		global $line_prefix;

		if (!$line_prefix) $line_prefix = "\t\t\t\t";
		

		if(isset($_POST[$alias]) && $_POST[$alias]) $default_date = $_POST[$alias];

		//if(!$default_date) $default_date = time();


		if(is_numeric($default_date))
		{
			if(strlen($default_date)==8)
			{
				// Eightdate
				$default_year = intval(substr($default_date,0,4));
				$default_month = intval(substr($default_date,4,2));
				$default_day = intval(substr($default_date,6,2));
			}
			else if(strlen($default_date)==6) 
			{
				// Sixdate
				$default_year = 2000+intval(substr($default_date,0,2));
				$default_month = intval(substr($default_date,2,2));
				$default_day = intval(substr($default_date,4,2));
			}
			else 
			{
				// Unix timestamp
				$default_year = intval(date('Y',$default_date));
				$default_month = intval(date('m',$default_date));
				$default_day = intval(date('d',$default_date));
			}
		}
		else if(strlen($default_date>7))
		{
			$dash_parts = split('-',$default_date); // THIS ONE IS THE FORMAT THAT IS CURRENTLY OUTPUT
			$slash_parts = split('/',$default_date);
			if (count($dash_parts)==3)
			{
				$default_year = $dash_parts[0];
				$default_month = $dash_parts[1];
				$default_day = $dash_parts[2];
			}
			else if(count($slash_parts)==3)
			{
				$default_year = $slash_parts[0];
				$default_month = $slash_parts[1];
				$default_day = $slash_parts[2];
			}
			else if(strlen($default_date)==10) 
			{
				// YYYYXMMXDD
				$default_year = intval(substr($default_date,0,4));
				$default_month = intval(substr($default_date,5,2));
				$default_day = intval(substr($default_date,8,2));
			}
			else if(strlen($default_date)==8)
			{
				// YYXMMXDD
				$default_year = intval(substr($default_date,0,2));
				$default_month = intval(substr($default_date,3,2));
				$default_day = intval(substr($default_date,7,2));
			}
			else
			{
				$default_date = strtotime($default_date);
				$default_year = intval(date('Y',$default_date));
				$default_month = intval(date('m',$default_date));
				$default_day = intval(date('d',$default_date));
			}
		}

		if(is_numeric($min_year))
		{
			if(strlen($min_year)==4) $min_year = intval(substr($min_year,0,4));
			else if(strlen($min_year)==2) $min_year = 2000+intval($min_year);
			else $min_year = intval(date('Y',$min_year));
		}
		else $min_year = intval(date('Y'))-10;
		
		if(is_numeric($max_year))
		{
			if(strlen($max_year)==4) $max_year = intval(substr($max_year,0,4));
			else if(strlen($max_year)==2) $max_year = 2000+intval($max_year);
			else $max_year = intval(date('Y',$max_year));
		}
		else $max_year = intval(date('Y'))+10;

		if($default_year)
		{
			if($min_year>$default_year) $min_year = $default_year;
			if($max_year<$default_year) $max_year = $default_year;
		}

		// Build the onchange which should just update the "hidden field" when one of the select drop downs get changed
		if(stripos($format, 'timestamp')!==false)
		{
			$on_change = 'onchange='.
			'"'.
			'{'.
			"year=this.form['".$alias."[year]'].value;".
			"month=this.form['".$alias."[month]'].value;".
			"day=this.form['".$alias."[day]'].value;".
			'd=new Date();'.
			'd.setFullYear(year,parseInt(month)-1,day);'.
			"this.form['".$alias."'].value=parseInt(d.getTime()/1000)+".(-(mktime()-mktime(0,0,0))).';'.
			'}'.
			'"';		
		}
		else
		{
			$on_change = 'onchange='.
			'"'.
			"this.form['".$alias."'].value = this.form['".$alias."[year]'].value+'-'+this.form['".$alias."[month]'].value+'-'+this.form['".$alias."[day]'].value;".
			'"';	
		}

		$content = ''; //"<!-- DATE INPUTS START HERE -->\n";

		$years = array();
		$year_labels[] = 'year';
		$year_values[] = '';
		for($temp_year=$min_year; $temp_year<=$max_year; $temp_year++)
		{
			$year_labels[] = $temp_year;
			$year_values[] = $temp_year;
		}
		$year_input_content = getSelectInputContent($alias.'[year]', $year_values, $year_labels, $default_year, $on_change);

		$months = array();
		$month_labels[] = 'month';
		$month_values[] = '';
		for($temp_month=1; $temp_month<=12; $temp_month++)
		{
			$month_labels[] = date('M',mktime(0,0,0,$temp_month, 1));
			$month_values[] = $temp_month;
		}
		$month_input_content = getSelectInputContent($alias.'[month]', $month_values, $month_labels, $default_month, $on_change);

		$days = array();
		$day_labels[] = 'day';
		$day_values[] = '';
		for($temp_day=1; $temp_day<=31; $temp_day++)
		{
			$day_labels[] = $temp_day; 
			$day_values[] = $temp_day; 
		}
		$day_input_content = getSelectInputContent($alias.'[day]', $day_values, $day_labels, $default_day, $on_change);


		if
		(
			stripos($format, 'mdy')!==false
			||
			stripos($format, 'md,y')!==false
		)
		{
			// This means to put the inputs in month day year order
			$content .= $month_input_content.$day_input_content;
			if(stripos($format, 'md,y')!==false) $content .= ',';
			$content .= $year_input_content;
		}
		else if((stripos($format, 'my')!==false || stripos($format, 'ym')!==false) && stripos($format, 'd')===false)
		{
			// This means that there is no day
			if(stripos($format, 'ym')!==false) $content = $year_input_content.$month_input_content;
			else $content .= $month_input_content.$year_input_content;
			// Add a hidden input for the day?
			$content .= '<input name="'.$alias.'[day]" type="hidden" value="1" />';
		}
		else
		{
			// Otherwise put them in logical order = ymd
			$content .= $year_input_content.$month_input_content.$day_input_content;
		}



		if(stripos($format, 'timestamp')!==false) $formatted_default_date = $default_date;
		else $formatted_default_date = $default_year.'-'.$default_month.'-'.$default_day;

		if($place_base_input) $content .= $line_prefix.'<input type="'.$place_base_input.'" name="'.$alias.'" value="'.$formatted_default_date."\">\n";

		//$content .= "<!-- DATE INPUTS END HERE -->\n";

		return $content;

	}// getDateInputsContent


	function getDOBInputsContent($min_age=0, $max_age=110)
	{

		global $line_prefix;

		if (!$line_prefix) $line_prefix = "\t\t\t\t";

		$content = "<!-- DOB INPUTS START HERE -->\n";

		$default_year = intval(date('Y'))-(int)(($min_age+$max_age)/2);
		if($_POST['dob_year']) $default_year = $_POST['dob_year'];
		$years = array();
		for($temp_year=intval(date('Y'))-$max_age; $temp_year<=intval(date('Y'))-($min_age-1); $temp_year++)
		$years[] = $temp_year;

		$content .= getSelectInputContent('dob_year',$years, false,$default_year);

		$default_month = false;
		if($_POST['dob_month']) $default_month = $_POST['dob_month'];
		$months = array();
		for($temp_month=1; $temp_month<=12; $temp_month++)
		{
			$month_labels[] = date('M',mktime(0,0,0,$temp_month, 1));
			$months[] = $temp_month;
		}

		$content .= getSelectInputContent('dob_month',$months, $month_labels,$default_month);

		$default_day = false;
		if($_POST['dob_day']) $default_day = $_POST['dob_day'];
		$days = array();
		for($temp_day=1; $temp_day<=31; $temp_day++)
		{
			$days[] = $temp_day; //date('d',mktime(0,0,0,1,$temp_day));
			$day_labels[] = $temp_day;
		}

		$content .= getSelectInputContent('dob_day',$days,$day_labels,$default_day);

		$content .= "<!-- DOB INPUTS END HERE -->\n";

		return $content;
	}// getDOBInputsContent
	
?>
