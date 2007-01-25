<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="en"/>
    <meta name="robots" content="no index,no follow" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="stylesheet" type="text/css" media="all" href="templates/install/style.css" />
  </head>
  <body>
    <div id="container">
      <div id="header">
        <h1>Configure <em>fwriteful</em></h1>
      </div>
      <div id="page">
        <form action="" method="post">
          <h2>Your Information</h2>
          <div class="row">
            <label for="display_name">Your Name</label>
            <input type="textbox" name="display_name" size="40" value="{$DisplayName}" maxlength="50" />
            {include file="install/form.error.tpl" Id="display_name"}
          </div>
          <div class="row">
            <label for="email">Email Address</label>
            <input type="textbox" name="email" size="40" value="{$Email}" maxlength="80" />
            {include file="install/form.error.tpl" Id="email"}
          </div>
          <div class="row">
            <label for="password">Password</label>
            <input type="password" name="password" size="20" value="{$Password}" maxlength="32" />
            {include file="install/form.error.tpl" Id="password"}
          </div>
          <div class="row">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" value ="{$ConfirmPassword}" size="20" maxlength="32" />
            {include file="install/form.error.tpl" Id="confirm_password"}
          </div>
          <h2>Blog Information</h2>
          <div class="row">
            <label for="blog_title">Title of Your Blog</label>
            <input type="textbox" name="blog_title" value="{$BlogTitle}" size="60" maxlength="150" />
            {include file="install/form.error.tpl" Id="blog_title"}
          </div>
          <div class="row">
            <label for="blog_url">URL of Your Blog</label>
            <input type="textbox" name="blog_url" value="{$BlogUrl}" size="60" maxlength="150" />
            {include file="install/form.error.tpl" Id="blog_url"}
          </div>
          <div class="row">
            <input type="submit" value="Continue" />
          </div>
        </form>
      </div>
    </div>
  </body>
</html>

