function dirname (path) {
  // http://kevin.vanzonneveld.net
  // +   original by: Ozh
  // +   improved by: XoraX (http://www.xorax.info)
  // *     example 1: dirname('/etc/passwd');
  // *     returns 1: '/etc'
  // *     example 2: dirname('c:/Temp/x');
  // *     returns 2: 'c:/Temp'
  // *     example 3: dirname('/dir/test/');
  // *     returns 3: '/dir'

  return path.replace(/\\/g, '/').replace(/\/[^\/]*\/?$/, '');
}

$(function(){

var editor = ace.edit("divaceconfigfile");
var textarea = $('#config_inc_file_gen').hide();
editor.getSession().setValue(textarea.val());
editor.getSession().on('change', function(){
  textarea.val(editor.getSession().getValue());
});
editor.setTheme("ace/theme/monokai");
editor.getSession().setMode("ace/mode/php");
editor.setTheme("ace/theme/twilight");

var session = editor.getSession();
session.setUseWrapMode(true);
session.setWrapLimitRange();

glbGeneratePHPDefine = function(key, value){
    return 'define('+JSON.stringify(key)+', '+JSON.stringify(value)+');';
}

glbGenerateConfigFileFromForm = function(){
   var php = "<?PHP\n"+
"//CWorkLog generated config file (by install helper)\n"
"//create user 'USER'@'localhost' IDENTIFIED BY 'PASSWORD';\n"+
"define('CFG_DB_HOST', 'localhost'); \n";

   $.each($('form').serializeArray(), function(){
   	    var val = this.value;
   	    if (val == '1'){
   	    	val = true;
	    }
   		php += glbGeneratePHPDefine(this.name, val) +"\n";
   });

   $('form :checkbox:not(:checked)').each(function(){
		php += glbGeneratePHPDefine(this.name, false) + "\n";
   });

   editor.getSession().setValue(php);
}


//TODO: for radio buttons, we gotta figure something else out
$('input[type=text], input[type=password], input[type=checkbox]').on('change', function(){
	glbGenerateConfigFileFromForm();
});

//setup before functions
var typingTimer;                //timer identifier
var doneTypingInterval = 750;  //time in ms, 5 second for example

//on keyup, start the countdown
$('input').keyup(function(){
    clearTimeout(typingTimer);
    typingTimer = setTimeout(doneTyping, doneTypingInterval);
});

//on keydown, clear the countdown 
$('input').keydown(function(){
    clearTimeout(typingTimer);
});

//user is "finished typing," do something
function doneTyping () {
    glbGenerateConfigFileFromForm();
}


var curbase = $('#CFG_BASE_URL').val();

if (curbase == ''){
	$('#CFG_BASE_URL').val(dirname(dirname(document.URL))+'/');
}

glbGenerateConfigFileFromForm();

});
