/**
 * onTypeSomeKeys jquery plugin
 * @example
 *  $('input').onTypeSomeKeys(function(){
 *	 		$.ajax(...)
 *	 	}, { doneTypingInterval:2500 } ); 
 *
 *  thanks to http://stackoverflow.com/questions/4220126/run-javascript-function-when-user-finishes-typing-instead-of-on-key-up
 */
(function ($) {
    jQuery.fn.onTypeSomeKeys = function(callback, opts){ 
    	if (typeof(opts) == 'number'){
    		opts = {doneTypingInterval: opts};
    	}
		if (!opts){ opts = {}; }

		opts.doneTyping = callback;
		opts.doneTypingInterval = opts.doneTypingInterval || 2000; 
		opts.typingTimer = null;

		$(this).keyup(function(){
		    clearTimeout(typingTimer);
		    opts.typingTimer = setTimeout(opts.doneTyping, opts.doneTypingInterval);
		});

		//on keydown, clear the countdown 
		$(this).keydown(function(){
		    clearTimeout(opts.typingTimer);
		});
    }
});
