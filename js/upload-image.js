// Great documentation: https://github.com/thomasgriffin/New-Media-Image-Uploader/blob/master/js/media.js

jQuery(document).ready(function($) {
        
    // Prepare the variable that holds our custom media manager.
    var formfield;

    jQuery('#jkl_review_cover_button').click(function(e){
        
        // Prevent the default action (which is what?)
        e.preventDefault();

        // If the frame already exists, re-open it
        if( formfield ) {
            formfield.open();
            return;
        }

        // The media frame doesn't exist yet, so let's create it with some options
        formfield = wp.media.frames.formfield = wp.media({

            // 'select' or 'post' are our choices. 'post' is for using the CURRENT post ID - so 'select' is better for general uploads
            frame: 'select',

            // default is true, so let's change it to false
            multiple: false,

            // populate the title with our custom text
            title: jkl_review_cover.title,

            // force the type of media to show to the user - we want images
            library: { type: 'image' },

            // customize the button text - default is 'Select'
            button: { text: jkl_review_cover.button }
        });

        formfield.on('select', function() {
           
            // Grab our attachment selection and construct a JSON representation of the model
            var media_attachment = formfield.state().get('selection').first().toJSON();
            
            // Send the attachment URL to our custom input field via jQuery
            $('#jkl_review_cover').val(media_attachment.url);
       
        });
        
        // Now that everything is set, open the frame
        formfield.open();
 
    });
});
