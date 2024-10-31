jQuery(document).ready(function($) {
    $("#mc-form-editor").McEnableInsertAtCaret();
    $(document).on('click', '.form-builder-table .add-name-field', function(e){
      var nameFieldContent = '<p><label>First Name: </label><input type="text" name="first_name" class="required" placeholder="First Name" /></p><p><label>Last Name: </label><input type="text" name="last_name" class="required" placeholder="Last Name" /></p>';
      McInsertAtCaret(nameFieldContent);
      return false;
    });
    $(document).on('click', '.form-builder-table .add-email-field', function(e){
      var emailFieldContent = '<p><label>Email address: </label><input type="email" name="email" class="required" placeholder="Your email address" /></p>';
      McInsertAtCaret(emailFieldContent);
      return false;
    });
    $(document).on('click', '.form-builder-table .add-signup-field', function(e){
      var signupFieldContent = '<p><input type="submit" value="Sign up" /></p>';
      McInsertAtCaret(signupFieldContent);
      return false;
    });
    $(document).on('click', '.form-builder-table .add-terms-field', function(e){
      var termsFieldContent = '<p><label><input name="agree_to_terms" type="checkbox" value="1">I have read and agree to the <a href="#">terms & conditions</a></label></p>';
      McInsertAtCaret(termsFieldContent);
      return false;
    });
});


//http://www.iminfo.in/post/how-to-insert-text-at-cursor-position-jquery
(function ( $ ) {
  "use strict";
  $.fn.McEnableInsertAtCaret = function() {
    $(this).on("focus", function() {
        $(".mcinsertatcaretactive").removeClass("mcinsertatcaretactive");
        $(this).addClass("mcinsertatcaretactive");
    });
  };
}( jQuery ));

function McInsertAtCaret(myValue) {
    return jQuery(".mcinsertatcaretactive").each(function(i) {
        if (document.selection) {
            //For browsers like Internet Explorer
            this.focus();
            sel = document.selection.createRange();
            sel.text = myValue;
            this.focus();
        } else if (this.selectionStart || this.selectionStart == '0') {
            //For browsers like Firefox and Webkit based
            var startPos = this.selectionStart;
            var endPos = this.selectionEnd;
            var scrollTop = this.scrollTop;
            this.value = this.value.substring(0, startPos) + myValue + this.value.substring(endPos, this.value.length);
            this.focus();
            this.selectionStart = startPos + myValue.length;
            this.selectionEnd = startPos + myValue.length;
            this.scrollTop = scrollTop;
        } else {
            this.value += myValue;
            this.focus();
        }
    })
}
