/*
Predefined functions for adding controls and handlers to page

Version 1 - Add event handlers

*/

/*
An static UI Service

- ResourceAssociations[resourceName]
      : [associativeCollectionName]
            : [foreignResourceName] = foreignFieldName, primaryFieldName
  @example:
                facility,
*/
class PWH_UIService{


  static API_URL = 'http://localhost:8888/api';
  static SITE_URL = 'http://localhost:8888';

  /*
    Map of Resource Associations, keyed by resourceName (i.e. storagefacility)
    Data is referenced in one place to avoid reprinting in loops
  */
  static ResourceConfig = {

    /* Dictionary of ResourceConfigItems - "resourceName" : ResourceConfigItem
      ResourceConfig.User.;
    */
    Items : {},

    Add(resourceName){

      this.Items[resourceName] = new ResourceConfigItem(resourceName);

    }

  };

  //------- HANDLERS -------
  static AddHandler(classname, str){

    $(`.${classname}`).click(function (){alert('hello there now ' + str);});
  }
  /*
      // TODO: Code is currently in the WarehouseBaseType, must be moved here
  */
  static Associate(){

  }
  /*
  http://localhost:8888/api/storagefacility/2/disassociate/facilityowners/user?userid=3
  */
  static Disassociate(elementID, apiURL, associativeCollectionName,
              foreignResourceName,
              foreignResourceIndexFieldname,
              primaryRecordID,
              foreignResourceID)
  {

    var url = `${apiURL}/${primaryRecordID}/disassociate/${associativeCollectionName}/${foreignResourceName}?${foreignResourceIndexFieldname}=${foreignResourceID}`;
    //// TODO: ADD CONFIRM DIALOGS FOR SENSITIVE TXNs (and a key for this txn)
    var data = {
      callbackDataItems: {


      }
    };
    CloudService.DELETE(`${elementID}LIST`, url, null, `CloudServiceResponseHandlers.disassociate('${elementID}', ${foreignResourceID})`);


  }
  /*
    Authenticate and Login a user
  */
  static loginUser(apiURL, $callbackMethodName){

        //alert($callbackMethodName);
        // TODO: Move this to utility class

        //Call cloud service with username/password login data

        var u = $("#username").val();
        var p = $("#password").val();
        //  CloudServiceResponseHandler will be called using data returned
        //     when called from the LOGIN wrapper :
        //       update the page URL on success or update textbox failure message
        CloudService.LOGIN('loginStatusMessage',
                                  `${apiURL}`,
                                  [u,p],
                                  `${$callbackMethodName}`,
          );
      }
      /*
      * TODO: validate form data
      */
      static validateFormData(){
        return true;
      }
  /*
    Call CloudService to enroll a user
  */
  static signupUser(){


    if (!PWH_UIService.validateFormData()){

    }
    else{
      var e     = $("#emailaddress").val();
      var u     = $("#username").val();
      var p     = $("#password").val();
      var fn    = $("#firstname").val();
      var ln    = $("#lastname").val();
      var city  = $("#city").val();
      var state = $("#signup-state :selected").val();
      var zip   = $("#zipcode").val();

      //map the requests into an array
      // TODO: THIS IS NOT WORKING, it is returning entire element
      var userTypeRequests = $('[name="user-type-request"]:checked').map(function (){
        return $(this).val();

        });

      var requestDataObject = {
          EmailAddress: e,
          Username: u,
          Password: p,
          Firstname: fn,
          Lastname: ln,
          City: city,
          State: state,
          Zipcode: zip,
          UserTypeRequests : {}
        };

      //CloudServiceResponseHandlers will:
      //       update the page URL on success or update textbox failure message
      CloudService.SIGNUP( 'loginStatusMessage',
                            PWH_UIService.API_URL + '/Signup',
                            requestDataObject,
                            'CloudServiceResponseHandlers.signup'
                          );
      }
    }

    /*
    Ensure passwords are same and any other validation
    */
    static validatePassword(){
      if ($("#password").val() != $("#vpassword").val()){
        $("#password").addClass("is-invalid");
        $("#vpassword").addClass("is-invalid");
        $("#submit").attr("disabled", true);
        return false;
      }
      else{
        $("#password").removeClass("is-invalid");
        $("#vpassword").removeClass("is-invalid");
        $("#password").addClass("is-valid");
        $("#vpassword").addClass("is-valid");
        $("#submit").attr("disabled", false);
        return true;
      }
    }
    /*
    Ensure emailaddress are same and any other validation
    */
    static validateEmailAddressesMatch(){
      if (  $("#emailaddress").val() != $("#emailaddressv").val() ||
            $("#emailaddress").val()==''
          ) {
            $("#emailaddress").addClass("is-invalid");
            $("#emailaddressv").addClass("is-invalid");
            $("#submit").attr("disabled", true);
        return false;
      }
      else{
        $("#emailaddress").removeClass("is-invalid");
        $("#emailaddressv").removeClass("is-invalid");
        $("#emailaddressv").addClass("is-valid");
        $("#emailaddressv").addClass("is-valid");
        $("#submit").attr("disabled", false);
        return true;
      }
    }

    /*
    Request a password reset link be sent to an emailaddress
    */
    static SendPasswordResetLink(authCode){

    var emailaddress = $("#emailaddress").val();
    var resetStep = $("#resetStep").val();

    console.log(`Reset step is ${resetStep}`);

    CloudService.SEND_PASSWORD_RESET_LINK(  'passwordResetStatusMessage',
                                            PWH_UIService.API_URL + '/Reset',
                                            [emailaddress, authCode, resetStep],
                                            'CloudServiceResponseHandlers.SendPasswordResetLink'
                                          );





  }
  /*
    Send a request to the CloudService to change a user's password
  */
  static ChangePassword(accessToken){

    var userID          = $("#userID").val();
    var password_raw    = $("#password").val();
    var resetStep       = $("#resetStep").val();

    console.log(`Sending ChangePassword request to the cloud for userID ${userID} with resetStep ${resetStep}`);

    CloudService.RESET_PASSWORD(  'passwordResetStatusMessage',
                                  PWH_UIService.API_URL + '/Reset',
                                  [userID, password_raw, accessToken, resetStep],
                                  'CloudServiceResponseHandlers.ChangePassword'
                                );
  }
}
