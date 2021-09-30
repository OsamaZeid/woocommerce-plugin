jQuery(document).ready(function(){
    jQuery("#individual").hide();  
    jQuery("#business").hide();

    jQuery('#indbustype').on('change', function() {
      if ( this.value == 'Individual')
      {
        jQuery("#individual").show();
        jQuery("#business").hide();
      }
      else if ( this.value == 'Business')
      {
        jQuery("#business").show();
        jQuery("#individual").hide();
      }
      else
      {
        jQuery("#individual").hide();  
        jQuery("#business").hide();
      }
    });
});

jQuery(function() {
  jQuery("form[name='accountform']").validate({
    // Specify validation rules
    rules: {
      // The key name on the left side is the name attribute
      // of an input field. Validation rules are defined
      // on the right side
      first_name: "required",
      last_name: "required",
      phone_number: "required",
      indbustype: "required",
      country: "required",
      business_name: "required",
      email: {
        required: true,
        // Specify that email should be validated
        // by the built-in "email" rule
        email: true
      },      
    },
    // Specify validation error messages
    messages: {
      first_name: "Please enter your firstname",
      last_name: "Please enter your lastname",   
      phone_number: "Please enter your phonenumber",   
      email: "Please enter a valid email address",
      indbustype: "Please select Ind Bus Type",
      country: "Please select country",
      business_name: "Please enter your businessname"
    },
    // Make sure the form is submitted to the destination defined
    // in the "action" attribute of the form when valid

    submitHandler: function(form) {
      form.submit();
    }
  });
});


jQuery(document).ready(function($) {
  $('#woocommerce_tz_tazapay_title').attr('required', true);  
  $('.tazapay-multiseller').closest("tr").hide();

  if($('#woocommerce_tz_tazapay_tazapay_seller_type').val() == 'multiseller'){
    $('.tazapay-multiseller').closest("tr").show();
  }

  if($('#woocommerce_tz_tazapay_sandboxmode').val() == 'sandbox'){
    $('.tazapay-production').closest("tr").hide();
  }
  if($('#woocommerce_tz_tazapay_sandboxmode').val() == 'production'){
    $('.tazapay-sandbox').closest("tr").hide();
  }

  $('#woocommerce_tz_tazapay_sandboxmode').change(function () {
    
    if(this.value == 'sandbox'){
      $('.tazapay-sandbox').closest("tr").show();
      $('.tazapay-production').closest("tr").hide();
    }
    if(this.value == 'production'){
      $('.tazapay-sandbox').closest("tr").hide();
      $('.tazapay-production').closest("tr").show();
    }

  });

  $('#woocommerce_tz_tazapay_tazapay_seller_type').change(function () {
    
    if(this.value == 'singleseller'){
      $('.tazapay-multiseller').closest("tr").hide();
    }
    if(this.value == 'multiseller'){
      $('.tazapay-multiseller').closest("tr").show();
    }

  });

});

