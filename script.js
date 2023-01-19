jQuery(document).ready(function () {
console.log("loaded");
 
  var show_hide =  jQuery("input[name='show_hide']").val().trim().replace(/ /g,'');
  console.log('sh = '+show_hide);
  if (show_hide == 'hide') {
  jQuery("#cgv-show-hide").text('Show').change();
  jQuery("input[name='show_hide']").val('hide');  
  jQuery("#cgv-variable-description").hide();
  };
 var display_vars_checked = jQuery("input[name='display_vars']").is(':checked');
 if ( ! display_vars_checked ) {
   jQuery("div[id='vars-display']").hide('slow');
          } else {
   jQuery("div[id='vars-display']").show('slow');
          }      
  
jQuery("input[name='display_vars']").on('click',function (){
 display_vars_checked = jQuery("input[name='display_vars']").is(':checked');
  console.log('dvc = '+display_vars_checked);
 if ( ! display_vars_checked ) {
   jQuery("div[id='vars-display']").hide('slow');
          } else {
  jQuery("div[id='vars-display']").show('slow');
          }      
});
  
jQuery("#cgv-show-hide").on('click',function (){
console.log('clicked');
  var show_hide = jQuery(this).text().trim().replace(/ /g,'');
console.log('sh = '+show_hide);
  if (show_hide == 'Hide') {
  jQuery(this).text('Show').change();
  jQuery("input[name='show_hide']").val('hide');  
  jQuery("#cgv-variable-description").hide("slow");
  } else {
    jQuery(this).text('Hide').change();
    jQuery("input[name='show_hide']").val('show');  
  jQuery("#cgv-variable-description").show("slow");
  }
  
});  
    jQuery("input[name*='name']").blur(function() {
      console.log('about to validate '+jQuery(this).attr('name'));
      var element = jQuery(this);
      validate(element);
    });

 function    validate (element) {
      var name = element.attr('name');
      var matches =   name.match(/(\d+)/);
      var number = 0;
      if (matches) {  
        number = matches[0];
      }
      console.log('number = '+number);
      var msg_element = jQuery("span[id*='msg-name-"+number+"']");
      console.log('msg id = '+msg_element.attr('id') );
      var val = element.val();
      console.log('name = '+name);
      if (name.includes('name')) {
        console.log('found a name');
        let pattern = /^[a-z0-9\_]+$/i;
        var isValid = pattern.test(val);
        console.log('isvalid = '+isValid);
        var isUnique = unique_name(val);
        console.log('isUnique = '+isUnique);        
        var uniqueError ='Names must be unique.';
        var vaildError = 'Required. Only letters, numbers, or underscores allowed';
        var currentError = '';
          if (! isUnique ) {
            console.log('do unique error');
            jQuery("input.button-primary").attr("disabled", true);
         jQuery(element).addClass('invalid');
          jQuery(element).focus();
         jQuery(msg_element).text(uniqueError).change(); 
            currentError = uniqueError;
        } else {
            jQuery("input.button-primary").attr("disabled", false);
                   jQuery(element).removeClass('invalid').change();
         jQuery(msg_element).text('').change();
 				 }// isUnique     
        if (! isValid ) {
          var currenterror =  jQuery(msg_element).text();
            jQuery("input.button-primary").attr("disabled", true);
         jQuery(element).addClass('invalid');
          jQuery(element).focus();
         jQuery(msg_element).text(currentError+' '+vaildError).change(); 
        } else {
          if (isUnique) {
            jQuery("input.button-primary").attr("disabled", false);
                   jQuery(element).removeClass('invalid').change();
         jQuery(msg_element).text('').change();
          }
 				 }// isValid
      
      } //name  
   return isValid;
    };
  
  function unique_name (val) {
    var count =  jQuery("input[name*='name'][value='"+val+"']").length;
    console.log ('val = '+val);    
    console.log ('count = '+count);
     if ( count > 0 ) {
      return false;
    } else {
      return true;
    }
  };
 
  console.log("validating");
});




// Deletes a variable when its trigger is clicked.
jQuery('#custom-global-variables-table-definitions .delete').on('click', function() {

	jQuery(this).parent().parent().fadeTo(500, 0, function() {

		jQuery(this).remove();
	});

	return false;
});
