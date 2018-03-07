<?php get_header();
    
    $user_bookmarks = get_user_meta(get_current_user_id(), 'user_bookmarks', true); ?> 

    <div class="wrap">
        <h2>My bookmarks</h2>
        <ul id="bookmarks-list"><?php
        if(!empty($user_bookmarks)) {
            foreach($user_bookmarks as $postID) { ?>
                <li><a href="<?php the_permalink($postID); ?>"><?php echo get_the_title($postID); ?></a></li><?php
            }
        } ?>
        </ul>
    </div>

<?php get_footer(); ?>