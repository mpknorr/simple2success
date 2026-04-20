(function(window, undefined) {
  'use strict';

  /*
  NOTE:
  ------
  PLACE HERE YOUR OWN JAVASCRIPT CODE IF NEEDED
  WE WILL RELEASE FUTURE UPDATES SO IN ORDER TO NOT OVERWRITE YOUR JAVASCRIPT CODE PLEASE CONSIDER WRITING YOUR SCRIPT HERE.  */

  let loginForm = document.getElementById("login-form")
  if (loginForm) {
    loginForm.addEventListener("submit", function(e){
      e.preventDefault()
      var emailField = document.getElementById("email");
      var emailValue = emailField.value.trim();
      if (emailValue === "") {
        alert("Please enter your email address.")
        emailField.focus()
      } else {
        loginForm.submit()
      }
    })
  }

})(window);