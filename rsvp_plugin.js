jQuery(document).ready(function(){
	jQuery.validator.addMethod("customNote", function(value, element) {
    if((jQuery("#additionalRsvp").val() > 0) && (jQuery("#note").val() == "")) {
      return false;
    }

    return true;
  }, "<br />Please enter an email address that we can use to contact you about the extra guest.  We have to keep a pretty close eye on the number of attendees.  Thanks!");
						
	jQuery("#rsvpForm").validate({
		rules: {
			note: "customNote",
			newAttending1LastName:  "required",
			newAttending1FirstName: "required", 
			newAttending2LastName:  "required",
			newAttending2FirstName: "required",
			newAttending3LastName:  "required",
			newAttending3FirstName: "required", 
      attendeeFirstName:      "required", 
      attendeeLastName:       "required"
		},
		messages: {
			note: "<br />If you are adding additional RSVPs please enter your email address in case we have questions",
			newAttending1LastName:  "<br />Please enter a last name",
			newAttending1FirstName: "<br />Please enter a first name", 
			newAttending2LastName:  "<br />Please enter a last name",
			newAttending2FirstName: "<br />Please enter a first name",
			newAttending3LastName:  "<br />Please enter a last name",
			newAttending3FirstName: "<br />Please enter a first name", 
      attendeeFirstName:      "<br />Please enter a first name", 
      attendeeLastName:       "<br />Please enter a last name"
		}
	});
  
  /* First step, where they search for a name */
  jQuery("#rsvp").validate({
    rules: {
      firstName: "required",
      lastName: "required"
    }, 
    messages: {
      firstName: "<br />Please enter your first name", 
      lastName: "<br />Please enter your last name"
    }
  });
  
	jQuery("#addRsvp").click(function() {
		handleAddRsvpClick();
	});
});