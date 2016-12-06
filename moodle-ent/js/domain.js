$( function(){

    // Change the domain of the hrefs
    $( "body a" ).each( function( ){
      var new_domain = document.domain;
      var old_href   = $( this ).attr( "href" );
      if(old_href !== undefined ){
          var new_href   = old_href.replace( "lycees\.netocentre\.fr", new_domain );
      }

      $( this ).attr( "href", new_href );
    });

});

