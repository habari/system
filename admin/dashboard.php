<?php include( 'header.php' ); ?>
<?php $user= User::identify(); ?>
<div class="container">
  <hr>
  <div class="column span-7 first ">
    <div class="column span-2 first"><?php if ( isset ( $user->info->imageurl ) ) { ?><img class="admin-avatar" src="<?php echo $user->info->imageurl?>" class="usericon"><?php } ?></div>
    <div class="column span-5 last"><?php printf( _t( 'Welcome back, %s!' ), $user->username ); ?></div>
  </div>
  <div class="column prepend-1 span-16 last" id="welcome">
    <?php
      if ( ! isset( $user->info->experience_level ) ) {
      /**
       * @todo Edit and Translate
       **/				 				
    ?> 
    <p><em>Welcome to Habari! This is the first time you've been here, so a quick tour is in order.</em></p>
    <p>In the top right corner of the window you'll find &ldquo;Admin&rdquo;, &ldquo;Publish&rdquo;, and &ldquo;Manage&rdquo;, plus the logout button. (Use that if you're sharing this computer, or paranoid, or just like pushing buttons.)</p>
    <p>Admin has 5 options. Clicking on &ldquo;Admin&rdquo; takes you back here. &ldquo;Options&rdquo; lets you make changes to the entire blog (title, tagline, that sort of thing). &ldquo;Plugins&rdquo; is where you control, well, plugins. There are a few included, and there are dozens more <!-- a href='link to plugin site' -->plugins<!-- a/ --> available. &ldquo;Themes&rdquo; is where you can change how your blog looks to visitors. Again, a few are provided, but there are lots of <!-- a href='link to theme site' -->themes<!-- a/ --> out there. &ldquo;Users&rdquo; is where you control what the registered visitors, authors, and fellow admins can do on the site. Finally &ldquo;Import&rdquo; allows you to bring in your posts from another blogging platform. Just because you're using Habari doesn't mean you have to lose your old work.</p>
    <p>Next is &ldquo;Publish&rdquo;. You can work on posts or pages. Posts are like journal entries and are filed chronologically. Pages are filed seperately and are great for things like telling about the authors on your site.</p>
    <p>Finally, you have the &ldquo;Manage&rdquo; option which includes &ldquo;Content&rdquo; where you can edit and delete posts and pages. You can also choose &ldquo;Comments&rdquo; where you can edit and delete comments. The last option is &ldquo;Spam&rdquo;. Here you can quickly review and destroy the spam that we've trapped.</p>
    <p>Below this message is your &ldquo;Dashboard&rdquo; where you can get a quick overview of what's been happening around <?php Options::out( 'title' ); ?>.</p>
    <p>If this hasn't covered everything you need to know, there is a <a href="<?php Site::out_url( 'system' ); ?>/manual/index.html" onclick="popUp(this.href);return false;" title="The Habari Help Center">Help Center</a> link at the bottom of every page in the admin area. The next time you visit, you'll get a more condensed version of this message.</p>
    <?php
        $user->info->experience_level= 'user';
        $user->info->commit();
      }
      elseif ( $user->info->experience_level == 'user' ) {
    ?>
    <p>This is a quick pointer to help you find things like <a href="<?php Site::out_url( 'system' ); ?>/manual/index.html" onclick="popUp(this.href);return false;" title="The Habari Help Center">Help</a>, <a href="<?php URL::out( 'admin', 'page=themes' )?>" title="Manage your themes">themes</a>, and <a href="<?php URL::out( 'admin', 'page=plugins' )?>" title="Manage your plugins">plugins</a>. Before you go back to creating your masterpiece, you might take a look at what's been happening around <?php Options::out( 'title' ); ?>. When you've done that you can <a href="<?php URL::out( 'admin', 'page=publish&type=entry' ); ?>" title="Post an Entry">post an entry</a> or <a href="<?php URL::out( 'admin', 'page=moderate' )?>" title="Manage Comments">manage your comments</a>.</p>
    <?php
      }
      else {
    ?>
    <p>If you need <a href="<?php Site::out_url( 'system' )?>/help/index.html" onclick="popUp(this.href);return false;" title="The Habari Help Center">Help</a>, it's always available.</p>
    <?php
      }
    ?>
  </div>
  <hr>
  <div class="column span-7 first">
    <div class="column span-7 first" id="system-info">
    <h3><?php _e( 'System Information' ); ?></h3>
      <ul>
        <li><?php printf( _t( 'You are running Habari %s.' ), Version::get_habariversion() ); ?></li>
        <?php
      $updates= Update::check();
      //Utils::debug( $updates );  //Uncomment this line to see what Update:check() returns...
      if ( count( $updates ) > 0 ) {
        foreach ( $updates as $update ) {
          $class= implode( ' ', $update['severity'] );
          if ( in_array( 'critical', $update['severity'] ) ) {
            $updatetext= _t( '<a href="%1s">%2s %3s</a> is a critical update.' );
          }
          elseif ( count( $update['severity'] ) > 1 ) {
            $updatetext= _t( '<a href="%1s">%2s %3s</a> contains bug fixes and additional features.' );
          }
          elseif ( in_array( 'bugfix', $update['severity'] ) ) {
            $updatetext= _t( '<a href="%1s">%2s %3s</a> contains bug fixes.' );
          }
          elseif ( in_array( 'feature', $update['severity'] ) ) {
            $updatetext= _t( '<a href="%1s">%2s %3s</a> contains additional features.' );
          }
          $updatetext= sprintf( $updatetext, $update['url'], $update['name'], $update['latest_version'] );
          echo "<li class=\"{$class}\">&raquo; {$updatetext}</li>";
        }
      }
      else {
        echo '<li>' . _t( 'No updates were found.' ) . '</li>';
      }
        ?>
      </ul>
    </div>
    <div class="column span-7 first last" id="stats">
      <h3><?php _e( 'Site Statistics' ); ?></h3>
      <table id="site-stats" width="100%" cellspacing="0">
        <tr><td><?php _e( 'Total Posts' ); ?></td><td><?php echo Posts::count_total( Post::status( 'all' ) ); ?></td></tr>
        <tr><td><?php _e( 'Number of Your Posts' ); ?></td><td><?php echo Posts::count_by_author( User::identify()->id, Post::status( 'all' ) ); ?></td></tr>
        <tr><td><?php _e( 'Number of Comments' ); ?></td><td><?php echo Comments::count_total(); ?></td></tr>
        <?php
        $stats= array();
        $stats= Plugins::filter( 'statistics_summary', $stats );
        foreach( $stats as $label => $value ) :
        ?>
        <tr><td><?php echo $label; ?></td><td><?php echo $value; ?></td></tr>
        <?php
        endforeach;
        ?>
      </table>
    </div>
  </div>
  <div class="column prepend-1 span-16 last" id="recent-comments">
    <h3><?php _e( 'Recent Comments' ); ?>
      <?php
        if ( Comments::count_total( Comment::STATUS_UNAPPROVED ) ) {
      ?>
      (<a href="<?php URL::out( 'admin', array( 'page'=>'moderate', 'option'=>'comments' ) );?>" title="<?php _e( 'View Comments Awaiting Moderation' ); ?>"><?php echo Comments::count_total( Comment::STATUS_UNAPPROVED ); ?> 
	<?php echo _n( 'comment awaiting moderation', 'comments awaiting moderation', Comments::count_total( Comment::STATUS_UNAPPROVED ) ); ?></a> &raquo;)
      <?php
        }
      ?>
    </h3>
    <?php
      if ( Comments::count_total( Comment::STATUS_APPROVED ) ) {
    ?>
      <table id="comment-data" width="100%" cellspacing="0">
        <thead>
          <tr>
            <th class="span-3"><?php _e( 'Post' ); ?></th>
            <th class="span-3"><?php _e( 'Name' ); ?></th>
            <th class="span-7"><?php _e( 'URL' ); ?></th>
            <th class="last span-3"><?php _e( 'Action' ); ?></th>
          </tr>
        </thead>
        <?php
          foreach ( Comments::get( array( 'status' => Comment::STATUS_APPROVED, 'limit' => 5, 'orderby' => 'date DESC' ) ) as $recent ) {
            $post= Post::get( array( 'id' => $recent->post_id, ) );
        ?>
        <tr>
          <td class="span-3"><?php echo $post->title; ?></td>
          <td class="span-3"><?php echo $recent->name; ?></td>
          <td class="span-7"><?php echo $recent->url; ?></td>
          <td class="last span-3" align="center">
            <a class="view" href="<?php echo $post->permalink; ?>#comment-<?php echo $recent->id; ?>" title="<?php _e( 'View this post' ); ?>">View</a> 
            
          </td>
        </tr>
        <?php } ?>
      </table>
      <?php
      }
      else {
      ?>
        <p><?php _e( 'There are no comments to display.' ); ?></p>
      <?php
      }
      ?>
    
  </div>
  <hr>
  <div class="column span-7 first" id="incoming">
    <h3><?php _e( 'Incoming Links' ); ?> (<a href="http://blogsearch.google.com/?scoring=d&amp;num=10&amp;q=link:<?php Site::out_url( 'hostname' ) ?>" title="<?php _e( 'More incoming links' ); ?>"><?php _e( 'more' ); ?></a> &raquo;)</h3>
    <?php
    // This should be fetched on a pseudo-cron and cached:
    $search= new RemoteRequest( 'http://blogsearch.google.com/blogsearch_feeds?scoring=d&num=10&output=atom&q=link:' . Site::get_url( 'hostname' ) );
    $search->set_timeout( 5 );
    $result= $search->execute();
    if ( $search->executed() ) {
      $xml= new SimpleXMLElement( $search->get_response_body() );
      if ( count( $xml->entry ) == 0 ) {
        echo '<p>' . _t( 'No incoming links were found to this site.' ) . '</p>';
      }
      else {
    ?>
    <ul id="incoming-links">
      <?php foreach( $xml->entry as $entry ) { ?>
      <li>
        <!-- need favicon discovery and caching here: img class="favicon" src="http://skippy.net/blog/favicon.ico" alt="favicon" / -->
        <a href="<?php echo $entry->link['href']; ?>" title="<?php echo $entry->title; ?>"><?php echo $entry->title; ?></a>
      </li>
      <?php } ?>
    </ul>
    <?php
      }
    }
    else {
      echo '<p>' . _e( 'Error fetching links.' ) . '</p>';
    }
    ?>
  </div>
  <div class="column prepend-1 span-16 last" id="logs">
    <h3><?php _e( 'Activity' ); ?> (<a href="<?php URL::out( 'admin', 'page=logs' ); ?>" title="<?php _e( 'More Activity Logs' ); ?>"><?php _e( 'more' ); ?></a> &raquo;)</h3>
    <table id="log-activity" width="100%" cellspacing="0">
      <thead>
        <tr>
		  <th class="span-3" align="left">Type</th>
          <th class="span-3" align="left">Date</th>
          <th class="span-10" align="left">Message</th>
        </tr>
      </thead>
      <?php foreach( eventlog::get( array( 'limit' => 5 ) ) as $log ) { ?>
          <tr>
            <td><?php echo $log->type; ?></td>
		    <td><?php echo Format::nice_date( $log->timestamp, 'F j, Y' ); ?></td>
            <td><?php echo $log->message; ?></td>
          </tr>
          <?php } ?>
    </table>
  </div>
  <hr>
  <div class="column prepend-8 span-16 first" id="drafts">
    <h3>Drafts (<a href="<?php URL::out( 'admin', 'page=content' ); ?>#drafts" title="View Your Drafts">more</a> &raquo;)</h3>
    <?php
      if ( Posts::count_total( Post::status( 'draft' ) ) ) {
    ?>
    <table id="site-drafts" width="100%" cellspacing="0">
    <?php
        foreach ( Posts::by_status( Post::status( 'draft' ) ) as $draft ) {
    ?>
      <tr>
        <td class="span-13"><?php echo $draft->title; ?></td>
        <td class="last span-3">
          <a class="view" href="<?php echo $draft->permalink; ?>" title="View <?php echo $draft->title; ?>">View</a>
          <a class="edit" href="<?php URL::out( 'admin', 'page=publish&slug=' . $draft->slug ); ?>" title="Edit <?php echo $draft->title; ?>">Edit</a>
        </td>
      </tr>
    <?php
        }
    ?>
    </table>
    <?php
      }
      else {
        echo '<p>' . _t( 'There are currently no drafts in process.' ) . '</p>';
      }
    ?>
  </div>
</div>
<?php include( 'footer.php' ); ?>
