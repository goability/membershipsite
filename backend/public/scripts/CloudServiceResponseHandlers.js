class CloudServiceResponseHandlers {

  /*
    Response handler for a successful record association
     -- Add the item to the list
     @param:
  */
  static associate(elementID, primaryResourceName, primaryResourceID, associativeCollectionName, foreignResourceName, foreignResourceIndexFieldname, foreignResourceID) {
      console.log(`in the callback ${elementID} with ${foreignResourceID} `);
    var associatedText = $(`#${elementID}SELECT :selected`).text();
    var $associatedID = $(`#${elementID}SELECT :selected`).val();
//id='$elementID-item-$resource->ID'
    var listItemHTML = `<li id=${elementID}-item-${foreignResourceID} class='list-group-item' aria-hidden='true' style='margin:0; padding:1;'>`;
    var itemHTML = `<i id='nav-record-item-disassociate-${foreignResourceID}'
                      class='fa fa-trash nav-record-item-disassociate'

                onclick =\"
                      PWH_UIService.Disassociate('${elementID}', '${PWH_UIService.API_URL}/${primaryResourceName}',
                            '${associativeCollectionName}',
                            '${foreignResourceName}',
                            '${foreignResourceIndexFieldname}',
                            '${primaryResourceID}',
                            '${foreignResourceID}');
                          \";></i>${associatedText}`;

    $(`#${elementID}LIST`).append(listItemHTML + itemHTML+ "</li>");
  }
  /*
    Response handler after clicking Disassociate on a foreign resource
    Disassociate a foreign resource on the UI by removing the LI
         @param:
  */
  static disassociate(elementID, resourceID) {

    var elementName = `${elementID}-item-${resourceID}`;
    $(`#${elementID}-item-${resourceID}`).remove();
  }
  /*
    Response handler for authentication login
  */

  static authenticate(elementID, userID){

     if (userID < 1){

        $('#loginStatusMessage').text('Error Logging in');
      }
      else{
        //$('#loginStatusMessage').style('color:Blue');
        $('#loginStatusMessage').text('Authenticated !');
        //DO NOTHING
        //window.location = `${PWH_UIService.SITE_URL}/Dashboard`;
      }
  }
  /*
    Handle results of a login

     -- Success - Redirect with AuthCode to /Login
  */
  static login(elementID, userID, authCode){

     if (userID < 1){
        $('#loginStatusMessage').text('Error Logging in');
      }
      else{
        //$('#loginStatusMessage').style('color:Blue');
        $('#loginStatusMessage').text('Logged in!');

        console.log(`Logged in user ${userID} with ${authCode}`);
        //Forward back to Login with authCode.  If matches with what was
        //  written by the API Login transcation, then a session will be created
        //  design:  this authCode has a very short lifespan and is removed from DB after validation
        window.location = `${PWH_UIService.SITE_URL}/Login?authCode=${authCode}&userID=${userID}`;
      }
  }
  /*
    Handle results of server sending a password reset link to an emailaddress
      It is not know if the email address was valid OR if the email was sent

  */
  static SendPasswordResetLink(elementID, emailaddress){

    //var msg = `Please check your email.  A password reset link has been sent to ${emailaddress} which will allow you to reset your password.  The link will expire in 3 minutes.`;

    alert(`Please check email at ${emailaddress} `);

    window.location = `${PWH_UIService.SITE_URL}/Login`;

  }

  /*
  * Handle results of a change password request
  */
  static ChangePassword(elementID, result){

    console.log(`in callback - result was ${result}`);
    // TODO: Need a general update method for status/text type components

    var msg = (result) ? "Success" : "Failed";

    $(`#${elementID}`).text($msg);
  }

  /*
    Handle signup result results
  */
  static signup(elementID, userID){

    if (userID < 1){
       $('#signupStatusMessage').replaceWith('Error Signing up, please try again.');
     }
     else{
       var link = $("<a href='/Login'>Login</a>")
       $('#signupStatusMessage').add("span").html(`Welcome ! You can now <a href="${PWH_UIService.SITE_URL}/Login">Login</a>`);
       //After two seconds, forward to login screen
       //setTimeout( () => {window.location = `${PWH_UIService.SITE_URL}/Login`}, 2000);
     }

  }
}
