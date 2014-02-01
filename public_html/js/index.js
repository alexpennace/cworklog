$(function() {
		$("a[rel^='prettyPhoto']").prettyPhoto({
              default_width: 300, 
              default_height: 300, 
              social_tools:'', 
              overlay_gallery: false
         });
         
      $('#scrollto_plans').click(function(){
          $.scrollTo( $('#offers'), 800 );
          return false;
      });
      
      
      $('a.login').click(function(){
         if ( $('#quick_login').length > 0){
             $('#quick_login').toggle();
         }else{
             $('li.login').first().append( 
                   $('<div />').attr('id','quick_login').append('<form id="frmQuickLogin" method="post" action="index.php"><label>Username <input type="text" name="username_or_email" tabindex="1" /></label><label>Password <input type="password" name="password" tabindex="2" /></label><a align="right" class="littletext link" href="lostpassword.php" >Lost your password?</a><input type="submit" value="Login" tabindex="3" /></form>')
             );
             $('#quick_login').show();
         }
         return false;
      });
});