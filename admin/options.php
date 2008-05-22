<?php include('header.php');?>


<div class="container navigation">
	<span class="pct40">
		<form>
		<select name="navigationdropdown">
			<option value="core"><?php _e('Core Settings'); ?></option>
			<option value="publication"><?php _e('Publication Settings'); ?></option>
		</select>
		</form>
	</span>
	<span class="or pct20">
		<?php _e('or'); ?>
	</span>
	<span class="pct40">
		<input type="search" placeholder="<?php _e('search settings'); ?>" autosave="habarisettings" results="10"></input>
	</span>
</div>

<form name="form_options" id="form_options" action="<?php URL::out('admin', 'page=options'); ?>" method="post">
	<div class="container settings name-and-tagline">
	
		<h2><?php _e('Name &amp; Tagline'); ?></h2>
		<form name="form_options" id="form_options" action="<?php URL::out('admin', 'page=options'); ?>" method="post">
			<div class="item clear" id="sitename">
				<span class="column span-5">
					<label for="sitename"><?php _e('Site Name'); ?></label>
				</span>
				<span class="column span-14 last">
					<input type="text" name="title" class="border" value="<?php Options::out('title'); ?>"></input>
				</span>
			</div>
	
			<div class="item clear" id="sitetagline">
				<span class="column span-5">
					<label for="sitetagline"><?php _e('Site Tagline'); ?></label>
				</span>
				<span class="column span-14 last">
					<input type="text" name="tagline" class="border" value="<?php Options::out('tagline'); ?>"></input>
				</span>
			</div>	
	</div>
	
	<div class="container settings">
		<h2><?php _e('Publishing'); ?></h2>
		<div class="item clear" id="encoding">
			<span class="column span-5">
				<label for="encoding"><?php _e('Items per Page'); ?></label>
			</span>
			<span class="column span-5 last">
				<input type="text" class="border" id="pagination" name="pagination" value="<?php Options::out('pagination'); ?>">
			</span>
		</div>
		
		<div class="item clear" id="encoding">
			<span class="column span-5">
				<label for="encoding"><?php _e('Send Pingbacks to Links'); ?></label>
			</span>
			<span class="column span-5 last">
				<input type="checkbox" id="pingback_send" name="pingback_send" value="1"<?php echo (Options::get('pingback_send') == 1 ? ' checked' : ''); ?>>			</span>
			</span>
		</div>
		
		<div class="item clear" id="encoding">
			<span class="column span-5">
				<label for="encoding"><?php _e('Require Comment Author Info'); ?></label>
			</span>
			<span class="column span-5 last">
				<input type="checkbox" id="comments_require_id" name="comments_require_id" value="1"<?php echo (Options::get('comments_require_id') == 1 ? ' checked' : ''); ?>>
			</span>
		</div>
	</div>
	
	<a name="presentation"></a>
	<div class="container settings time-and-date">
	
		<h2><?php _e('Time &amp; Date'); ?></h2>
		
		<div class="item clear" id="presets">
			<span class="column span-5">
				<label for="presets"><?php _e('Presets'); ?></label>
			</span>
			<span class="column span-5 last">
				<select><option value="<?php _e('Europe'); ?>"><?php _e('Europe'); ?></option></select>
			</span>
		</div>
	
	
		<div class="item clear" id="timezone">
			<span class="column span-5">
				<label for="timezone"><?php _e('Time Zone'); ?></label>
			</span>
			<span class="column span-5">
				<select name="timezone">
					<option value="<?php _e('Europe'); ?>">GMT +1</option>
				</select>
			</span>
			<span class="column span-9 last helptext">
				<span><?php _e('Adjusts server time to '); ?>21.44</span>
			</span>
		</div>
	
	
		<div class="item clear" id="dateformat">
			<span class="column span-5">
				<label for="dateformat"><?php _e('Date Format'); ?></label>
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
				<label for="timeformat"><?php _e('Time Format'); ?></label>
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
		<h2><?php _e('Presentation'); ?></h2>
		
		<div class="item clear" id="encoding">
			<span class="column span-5">
				<label for="encoding"><?php _e('Encoding'); ?></label>
			</span>
			<span class="column span-5 last">
				<select name="encoding">
					<option value="UTF-8">UTF-8</option>
				</select>
			</span>
		</div>
	
	</div>
	
	<div class="container transparent">
		<input type="submit" value="<?php _e('Apply'); ?>" class="savebutton"></input>
	</div>
</form>

<?php include('footer.php');?>
