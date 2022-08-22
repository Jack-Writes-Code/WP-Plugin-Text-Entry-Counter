(function($){
	$(document).ready(function(){
		
        
        jQuery("#tec_submit").click(function(){
            feedback_field = jQuery("#tec_feedback");
            feedback_field.html("");
            feedback_field.addClass("tec_loader");
            jQuery.get("/wp-json/passwords/v1/check", {value: jQuery("#tec_entry").val()}, function(data, status) {
                if (data["status"] == "success")
                {
                    count = data["data"]["calls"];
                    if (count > 0)
                    {
                        feedback_field.html("Password found " + count + " times.");
                    }
                    else
                    {
                        feedback_field.html("Your password wasnt found. However it has now been added to the database.");
                    }
                }
                else
                {
                    feedback_field.html("Please provide a password in the text field.");
                }
                feedback_field.removeClass("tec_loader");
            });
        }); 
		
        
	});
})(jQuery);