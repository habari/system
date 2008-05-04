<?php include('header.php');?>


<div class="container navigation">
	<span class="pct40">
		<form>
		<select name="navigationdropdown">
			<option value="core">Core Settings</option>
			<option value="publication">Publication Settings</option>
		</select>
		</form>
	</span>
	<span class="or pct20">
		or
	</span>
	<span class="pct40">
		<input type="search" placeholder="search settings" autosave="habarisettings" results="10"></input>
	</span>
</div>


<div class="container settings name-and-tagline">

	<h2>Name &amp; Tagline</h2>
	
		<div class="item clear" id="sitename">
			<span class="column span-5">
				<label for="sitename">Site Name</label>
			</span>
			<span class="column span-14 last">
				<input type="text" name="sitename" class="border"></input>
			</span>
		</div>

		<div class="item clear" id="sitetagline">
			<span class="column span-5">
				<label for="sitetagline">Site Name</label>
			</span>
			<span class="column span-14 last">
				<input type="text" name="sitetagline" class="border"></input>
			</span>
		</div>		
</div>

<a name="presentation"></a>
<div class="container settings time-and-date">

	<h2>Time &amp; Date</h2>
	
	<div class="item clear" id="presets">
		<span class="column span-5">
			<label for="presets">Presets</label>
		</span>
		<span class="column span-5 last">
			<select><option value="Europe">Europe</option></select>
		</span>
	</div>


	<div class="item clear" id="timezone">
		<span class="column span-5">
			<label for="timezone">Time Zone</label>
		</span>
		<span class="column span-5">
			<select name="timezone">
				<option value="Europe">GMT +1</option>
			</select>
		</span>
		<span class="column span-9 last helptext">
			<span>Adjusts server time to 21.44</span>
		</span>
	</div>


	<div class="item clear" id="dateformat">
		<span class="column span-5">
			<label for="dateformat">Date Format</label>
		</span>
		<span class="column span-5">
			<input type="text" name="dateformat" class="border"></input>
		</span>
		<span class="column span-9 last helptext">
			<span>Tuesday, Jan 15th, 2008</span>
		</span>
	</div>


	<div class="item clear" id="timeformat">
		<span class="column span-5">
			<label for="timeformat">Time Format</label>
		</span>
		<span class="column span-5">
			<input type="text" name="timeformat" class="border"></input>
		</span>
		<span class="column span-9 last helptext">
			<span>21:44</span>
		</span>
	</div>
</div>


<div class="container settings">
	<h2>Presentation</h2>
	
	<div class="item clear" id="encoding">
		<span class="column span-5">
			<label for="encoding">Encoding</label>
		</span>
		<span class="column span-5 last">
			<select name="encoding">
				<option value="UTF-8">UTF-8</option>
			</select>
		</span>
	</div>

</div>

<div class="container transparent">
	<input type="button" value="Apply" class="savebutton"></input>
</div>


<?php include('footer.php');?>