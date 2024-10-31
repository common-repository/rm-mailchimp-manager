jQuery(document).ready(function($) {
    'use strict';
    jQuery('.mcmanager-form .error').hide();
    jQuery(".mcmanager-form input[type='submit']").click(function(e) {
        e.preventDefault();

        var _this = jQuery(this).closest('.mcmanager-form');
        var mc_form_id = _this.find('.mc_form_id').val();
        var mc_list_id = _this.find('.mc_list_id').val();
        var mc_double_opt_in = _this.find('.mc_double_opt_in').val();

        var mc_first_name = _this.find("input[name='first_name']").val();
        var mc_last_name = _this.find("input[name='last_name']").val();

        var mc_email = _this.find("input[type='email']").val();
        var mc_success_redirect = _this.find('.mc_success_redirect').val();

        var check_required = false;

        if((_this.find("input[name='first_name']").hasClass( "required" ))) {
          if((_this.find("input[name='first_name']").val().length < 2)) {
            _this.find('.required-field-missing').show();
            //_this.find('.required-field-missing').delay(3000).fadeOut('slow');
            check_required = true;
          }
        }

        if((_this.find("input[name='last_name']").hasClass( "required" ))) {
          if((_this.find("input[name='last_name']").val().length < 2)) {
            _this.find('.required-field-missing').show();
            //_this.find('.required-field-missing').delay(3000).fadeOut('slow');
            check_required = true;
          }
        }

        if((_this.find("input[type='email']").hasClass( "required" ))) {
          if((_this.find("input[type='email']").val().length < 2)) {
            _this.find('.required-field-missing').show();
            //_this.find('.required-field-missing').delay(3000).fadeOut('slow');
            check_required = true;
          }
        }

        if(_this.find("input[type='checkbox']").val()){
          var terms_value = _this.find("input[type='checkbox']:checked").val();
          if(terms_value != 1){
            _this.find('.required-field-missing').show();
            //_this.find('.required-field-missing').delay(3000).fadeOut('slow');
            check_required = true;
          }
        }

        if(check_required){
          _this.find('.required-field-missing').delay(3000).fadeOut('slow');
          return false;
        }

        var loading_img = '<img src="'+ mcm_subs_ajax_data.loadingimg +'" alt="Operating...." />';
        _this.find('.mcmanager-form-response').html(loading_img);

        var dataContainer = {
            security: mcm_subs_ajax_data.nonce,
            form_id: mc_form_id,
            list_id: mc_list_id,
            double_opt_in: mc_double_opt_in,
            success_redirect: mc_success_redirect,
            first_name: mc_first_name,
            last_name: mc_last_name,
            email: mc_email,
            action: mcm_subs_ajax_data.action,
            timeout: 180000
        };

        jQuery.post(mcm_subs_ajax_data.subs_ajaxurl, dataContainer, function(response, status) {
            //Check reqeust status
            if(status == 'success'){
                //alert(response.data['form_id']);
                if(response.data['is_success']){
                  var message = response.data['message'];
                  var success_redirect_url = response.data['success_redirect'];
                  var message_class = response.data['message_class'];
                  _this.find('.mcmanager-form-response').html('<p class="'+ message_class +'">'+ message +'</p>');
                  if(success_redirect_url !== '0'){
										window.location = success_redirect_url;
									}
                }else{
                  var message = response.data['message'];
                  var message_class = response.data['message_class'];
                  _this.find('.mcmanager-form-response').html('<p class="'+ message_class +'">'+ message +'</p>');
                }

            }else{
                //ajax request error...
            }

        });
        return false;
    });
});
