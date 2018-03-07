jQuery(document).ready(function() {

    jQuery('#wp-admin-bar-bthis-button').click(function(e) {
        e.preventDefault();
        var this2 = jQuery(this);
        
        jQuery.ajax({
            type: "POST",
            url: bthis.ajax_url,
            data: {
                action: 'save_bookmark',
            },
            success: function (output) {
                if(output == 'saved') {
                    if(this2.children('a').text() == 'Bookmark This')
                        this2.children('a').text('Remove Bookmark');
                    else if(this2.children('a').text() == 'Remove Bookmark')
                        this2.children('a').text('Bookmark This');
                }
            }
        });
        
        return false;
        
    });
    
});
