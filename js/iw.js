jQuery(function($){
    Sweep_Campaign.onLiked = function(){
        
        $.post(window.location, {
            'after_like'            :1
        }, function(data){
            
            if( $('.entry-mask').length ){
                if( data.html ) $('#enter-tab').html( data.html );
                Sweeps.Campaign.unmask();
                return;
            }
            
            // check to see if we should show the form
            if( data.newbie || Sweep_Campaign.is_facebook_liked ) {
                // this will just display the form
                Sweeps.Campaign.unmask();
                return;
            }
            
            if( data.html ) $('#enter-tab').html( data.html );
            Sweeps.Campaign.unmask();
        });
    };
    
    $('#enter-tab').on('submit', '.reenter-form', function(e){
        
        e.preventDefault();
        
        var btn = $(this).find('input[type=submit]')
          , txt = btn.val()
          ;
          
        btn.attr('disabled', 'disabled').addClass('disabled').val('Entering...');
        
        $.ajax({
            url             :$(this).attr('action'),
            method          :'POST'
        }).done(function(data){
            if( data && data.html ){
                $('#enter-tab').html( data.html );
                $('#enter-tab').trigger('sweeps.update');
            }
        }).always(function(){
            //btn.attr('disabled', false).removeClass('disabled').val(txt);
        });
    });
    
    $('#enter-tab').bind('sweeps.update', function(){
        // do the animation here.
        //setTimeout(function(){
        //$(this).find('.iw-result').fadeIn('slow');
        //},400);
        var result = $(this).find('.iw-result');
        if( !result.length ) return;
        
        result.removeClass('iw-result-start');
        var el = result[0];
        el.addEventListener( 'webkitTransitionEnd', onTransitionEnd, false );
        el.addEventListener( 'transitionend', onTransitionEnd, false );
        el.addEventListener( 'OTransitionEnd', onTransitionEnd, false );
        
        function onTransitionEnd()
        {
            
        }
    });
});