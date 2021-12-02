jQuery(document).ready(function () {
  jQuery("#individual").hide();
  jQuery("#business").hide();

  jQuery("#indbustype").on("change", function () {
    if (this.value == "Individual") {
      jQuery("#individual").show();
      jQuery("#business").hide();
    } else if (this.value == "Business") {
      jQuery("#business").show();
      jQuery("#individual").hide();
    } else {
      jQuery("#individual").hide();
      jQuery("#business").hide();
    }
  });
});

jQuery(function () {
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
        email: true,
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
      business_name: "Please enter your businessname",
    },
    // Make sure the form is submitted to the destination defined
    // in the "action" attribute of the form when valid
    submitHandler: function (form) {
      form.submit();
    },
  });
});

jQuery(function () {
  var inputs = document.getElementsByTagName("INPUT");
  for (var i = 0; i < inputs.length; i++) {
    inputs[i].oninvalid = function (e) {
      e.target.setCustomValidity("");
      if (!e.target.validity.valid) {
        e.target.setCustomValidity(e.target.getAttribute("data-error"));
      }
    };
  }
});

jQuery(document).ready(function ($) {
  $(".step_one").closest("tr").addClass("tzstep tz-stepone active");
  $(".step_two").closest("tr").addClass("tzstep tz-steptwo");

  var stepone = $(".step_one").closest("tr");
  var steptwo = $(".step_two").closest("tr");

  $(".step_one_section").closest("tr").show();
  $(".step_two_section").closest("tr").hide();

  $(stepone).click(function () {
    $(".step_one_section").closest("tr").show();
    $(".step_two_section").closest("tr").hide();

    $(".step_one").closest("tr").addClass("active");
    $(".step_two").closest("tr").removeClass("active");

    if ($("#woocommerce_tz_tazapay_sandboxmode").val() == "sandbox") {
      $(".tazapay-production").closest("tr").hide();
    }
    if ($("#woocommerce_tz_tazapay_sandboxmode").val() == "production") {
      $(".tazapay-sandbox").closest("tr").hide();
    }

    $("#woocommerce_tz_tazapay_seller_id").attr("required", false);
    $("#woocommerce_tz_tazapay_seller_id").attr("data-error", "");

  });

  $(steptwo).click(function () {

    var woocommerce_tz_tazapay_title                  = $('#woocommerce_tz_tazapay_title').val();
    var woocommerce_tz_tazapay_sandboxmode            = $('#woocommerce_tz_tazapay_sandboxmode').val();
    var woocommerce_tz_tazapay_sandbox_api_key        = $('#woocommerce_tz_tazapay_sandbox_api_key').val();
    var woocommerce_tz_tazapay_sandbox_api_secret_key = $('#woocommerce_tz_tazapay_sandbox_api_secret_key').val();
    var woocommerce_tz_tazapay_live_api_key           = $('#woocommerce_tz_tazapay_live_api_key').val();
    var woocommerce_tz_tazapay_live_api_secret_key    = $('#woocommerce_tz_tazapay_live_api_secret_key').val();
    var woocommerce_tz_tazapay_seller_email           = $('#woocommerce_tz_tazapay_seller_email').val();
    
    if(woocommerce_tz_tazapay_sandboxmode == "sandbox"){
      if( woocommerce_tz_tazapay_title != "" && woocommerce_tz_tazapay_sandbox_api_key != "" && woocommerce_tz_tazapay_sandbox_api_secret_key != "" && woocommerce_tz_tazapay_seller_email != "" ){
        $(".step_one_section").closest("tr").hide();
        $(".step_two_section").closest("tr").show();
        $(".step_one").closest("tr").removeClass("active");
        $(".step_two").closest("tr").addClass("active");
        
        if ( $("#woocommerce_tz_tazapay_tazapay_seller_type").val() == "singleseller" ) {
          $(".tazapay-multiseller").closest("tr").hide();          
        }
        if (
          $("#woocommerce_tz_tazapay_tazapay_seller_type").val() == "multiseller"
        ) {
          $(".tazapay-multiseller").closest("tr").show();
        }
      }else{
        return false;
      }
    }
    if(woocommerce_tz_tazapay_sandboxmode == "production"){
      if( woocommerce_tz_tazapay_title != "" && woocommerce_tz_tazapay_live_api_key != "" && woocommerce_tz_tazapay_live_api_secret_key != "" && woocommerce_tz_tazapay_seller_email != "" ){
        $(".step_one_section").closest("tr").hide();
        $(".step_two_section").closest("tr").show();
        $(".step_one").closest("tr").removeClass("active");
        $(".step_two").closest("tr").addClass("active");
        if ( $( "#woocommerce_tz_tazapay_tazapay_seller_type" ).val() == "singleseller" ) {
          $(".tazapay-multiseller").closest("tr").hide();
        }
        if ( $("#woocommerce_tz_tazapay_tazapay_seller_type").val() == "multiseller" ) {
          $(".tazapay-multiseller").closest("tr").show();
        }
        }else{
          return false;
        }
      }

      if($("#woocommerce_tz_tazapay_seller_id").val() == ""){
        $("#woocommerce_tz_tazapay_seller_id").attr("required", true);
        $("#woocommerce_tz_tazapay_seller_id").attr(
          "data-error",
          "Please add your Platform Seller ID"
        );
        $("#woocommerce_tz_tazapay_enabled").prop( "disabled", true );
      }else{
        $("#woocommerce_tz_tazapay_seller_id").attr("required", false);
        $("#woocommerce_tz_tazapay_seller_id").attr("data-error", "");
        $("#woocommerce_tz_tazapay_enabled").prop( "disabled", false );
      }
  });

  $("#woocommerce_tz_tazapay_title").attr("required", true);
  $("#woocommerce_tz_tazapay_title").attr(
    "data-error",
    "Please add title"
  );
  $("#woocommerce_tz_tazapay_seller_email").attr("required", true);
  $("#woocommerce_tz_tazapay_seller_email").attr(
    "data-error",
    "Please input the platform's email id"
  );
  if ($("#woocommerce_tz_tazapay_sandboxmode").val() == "sandbox") {
    $(".tazapay-production").closest("tr").hide();

    $("#woocommerce_tz_tazapay_sandbox_api_key").attr("required", true);
    $("#woocommerce_tz_tazapay_sandbox_api_key").attr(
      "data-error",
      "Please add Sandbox API"
    );
    $("#woocommerce_tz_tazapay_sandbox_api_secret_key").attr("required", true);
    $("#woocommerce_tz_tazapay_sandbox_api_secret_key").attr(
      "data-error",
      "Please add Sandbox API Secret Key"
    );
  }
  if ($("#woocommerce_tz_tazapay_sandboxmode").val() == "production") {
    $(".tazapay-sandbox").closest("tr").hide();

    $("#woocommerce_tz_tazapay_live_api_key").attr("required", true);
    $("#woocommerce_tz_tazapay_live_api_key").attr(
      "data-error",
      "Please add Production API"
    );
    $("#woocommerce_tz_tazapay_live_api_secret_key").attr("required", true);
    $("#woocommerce_tz_tazapay_live_api_secret_key").attr(
      "data-error",
      "Please add Production API Secret Key"
    );
  }

  $("#woocommerce_tz_tazapay_sandboxmode").change(function () {
    if (this.value == "sandbox") {
      $(".tazapay-sandbox").closest("tr").show();
      $(".tazapay-production").closest("tr").hide();

      $("#woocommerce_tz_tazapay_sandbox_api_key").attr("required", true);
      $("#woocommerce_tz_tazapay_sandbox_api_key").attr(
        "data-error",
        "Please add Sandbox API"
      );
      $("#woocommerce_tz_tazapay_sandbox_api_secret_key").attr(
        "required",
        true
      );
      $("#woocommerce_tz_tazapay_sandbox_api_secret_key").attr(
        "data-error",
        "Please add Sandbox API Secret Key"
      );

      $("#woocommerce_tz_tazapay_live_api_key").attr("required", false);
      $("#woocommerce_tz_tazapay_live_api_key").attr("data-error", "");
      $("#woocommerce_tz_tazapay_live_api_secret_key").attr("required", false);
      $("#woocommerce_tz_tazapay_live_api_secret_key").attr("data-error", "");
    }
    if (this.value == "production") {
      $(".tazapay-sandbox").closest("tr").hide();
      $(".tazapay-production").closest("tr").show();

      $("#woocommerce_tz_tazapay_live_api_key").attr("required", true);
      $("#woocommerce_tz_tazapay_live_api_key").attr(
        "data-error",
        "Please add Production API"
      );
      $("#woocommerce_tz_tazapay_live_api_secret_key").attr("required", true);
      $("#woocommerce_tz_tazapay_live_api_secret_key").attr(
        "data-error",
        "Please add Production API Secret Key"
      );

      $("#woocommerce_tz_tazapay_sandbox_api_key").attr("required", false);
      $("#woocommerce_tz_tazapay_sandbox_api_key").attr("data-error", "");
      $("#woocommerce_tz_tazapay_sandbox_api_secret_key").attr(
        "required",
        false
      );
      $("#woocommerce_tz_tazapay_sandbox_api_secret_key").attr(
        "data-error",
        ""
      );
    }
  });

  $("#woocommerce_tz_tazapay_tazapay_seller_type").change(function () {
    if (this.value == "singleseller") {
      $(".tazapay-multiseller").closest("tr").hide();

      $("#woocommerce_tz_tazapay_seller_id").attr("required", true);
      $("#woocommerce_tz_tazapay_seller_id").attr(
        "data-error",
        "Please add your Platform Seller ID"
      );
    }
    if (this.value == "multiseller") {
      $(".tazapay-multiseller").closest("tr").show();

      $("#woocommerce_tz_tazapay_seller_id").attr("required", false);
      $("#woocommerce_tz_tazapay_seller_id").attr("data-error", "");
    }
  });

  $("#nextconfigurationstep").on("click", function () {
    
    var woocommerce_tz_tazapay_title                  = $('#woocommerce_tz_tazapay_title').val();
    var woocommerce_tz_tazapay_sandboxmode            = $('#woocommerce_tz_tazapay_sandboxmode').val();
    var woocommerce_tz_tazapay_sandbox_api_key        = $('#woocommerce_tz_tazapay_sandbox_api_key').val();
    var woocommerce_tz_tazapay_sandbox_api_secret_key = $('#woocommerce_tz_tazapay_sandbox_api_secret_key').val();
    var woocommerce_tz_tazapay_live_api_key           = $('#woocommerce_tz_tazapay_live_api_key').val();
    var woocommerce_tz_tazapay_live_api_secret_key    = $('#woocommerce_tz_tazapay_live_api_secret_key').val();
    var woocommerce_tz_tazapay_seller_email           = $('#woocommerce_tz_tazapay_seller_email').val();
    
    if(woocommerce_tz_tazapay_sandboxmode == "sandbox"){
      if( woocommerce_tz_tazapay_title != "" && woocommerce_tz_tazapay_sandbox_api_key != "" && woocommerce_tz_tazapay_sandbox_api_secret_key != "" && woocommerce_tz_tazapay_seller_email != "" ){
        $(".tz-steptwo").trigger('click');
        $(".tz-steptwo").css('pointer-events', 'inherit');
        var success = 1;
      }else{
        $(".tz-steptwo").css('pointer-events', 'none');
      }
    }
    if(woocommerce_tz_tazapay_sandboxmode == "production"){
      if( woocommerce_tz_tazapay_title != "" && woocommerce_tz_tazapay_live_api_key != "" && woocommerce_tz_tazapay_live_api_secret_key != "" && woocommerce_tz_tazapay_seller_email != "" ){
        $(".tz-steptwo").trigger('click');
        $(".tz-steptwo").css('pointer-events', 'inherit');        
        var success = 1;
      }else{
        $(".tz-steptwo").css('pointer-events', 'none');
      }
    }
    if(success == 1){

      var data = {
        'action': 'setting_optionsave',
        'woocommerce_tz_tazapay_title': woocommerce_tz_tazapay_title,
        'woocommerce_tz_tazapay_sandboxmode': woocommerce_tz_tazapay_sandboxmode,
        'woocommerce_tz_tazapay_sandbox_api_key': woocommerce_tz_tazapay_sandbox_api_key,
        'woocommerce_tz_tazapay_sandbox_api_secret_key': woocommerce_tz_tazapay_sandbox_api_secret_key,
        'woocommerce_tz_tazapay_live_api_key': woocommerce_tz_tazapay_live_api_key,
        'woocommerce_tz_tazapay_live_api_secret_key': woocommerce_tz_tazapay_live_api_secret_key,
        'woocommerce_tz_tazapay_seller_email': woocommerce_tz_tazapay_seller_email,
      };
      $.post(ajaxurl, data, function(response) {
        var objJSON = JSON.parse(response);
          setTimeout(function() {
            if(objJSON.account_id != ''){
              $("#woocommerce_tz_tazapay_seller_id").attr("required", false);
              $("#woocommerce_tz_tazapay_seller_id").attr("data-error", "");
              $("#woocommerce_tz_tazapay_seller_id").val(objJSON.account_id);
              $("#woocommerce_tz_tazapay_enabled").prop( "disabled", false );
            }else{
              $("#woocommerce_tz_tazapay_seller_id").val('');
              $("#woocommerce_tz_tazapay_seller_id").attr("required", true);
              $("#woocommerce_tz_tazapay_seller_id").attr(
                "data-error",
                "Please add your Platform Seller ID"
              );
              $("#woocommerce_tz_tazapay_enabled").prop( "disabled", true );
            }
          }, 100);
      });
    }
  });

});
