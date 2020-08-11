/*
Handler Requests to a backend service

// TODO: use or don't use parameter-data
*/



class CloudService {

  static GET(elementID, callEndpoint, parameterData){
    CloudService.Call("get",elementID, callEndpoint, parameterData);
  }
  static POST(elementID, callEndpoint, parameterData){
    CloudService.Call("post",elementID, callEndpoint, parameterData);
  }
  static PUT(elementID, callEndpoint, callbackMethodName, callbackOrderedData){
    var callbackMethod = CloudService.constructCallbackURL(callbackMethodName, callbackOrderedData);

    CloudService.Call("put",elementID, callEndpoint, null, callbackMethod);
  }
  static constructCallbackURL(callbackMethodName, callbackOrderedData, resultDataText=null){
    var callbackMethod = `${callbackMethodName}(`;

    callbackOrderedData.forEach( (parameterValue, i) => {
      var adjustedValue = parameterValue;
      if (typeof(parameterValue) === "string"){
        adjustedValue = `'${parameterValue}'`;
      }
      if (i>0){
        callbackMethod += ', ';
      }
      callbackMethod += adjustedValue;

    });
    if (null!=resultDataText){
      callbackMethod += `, ${resultData}`;
    }
    callbackMethod += ')';

    //console.log(`CallbackMethod is : ${callbackMethod}`);

    return callbackMethod;
  }
  static DELETE(elementID, callEndpoint, parameterData, callbackMethod){
    CloudService.Call("delete",elementID, callEndpoint, parameterData, callbackMethod);
  }
  /*
  Send a signup request POST
  @param: data - SignupData named array: data.Firstname, data.LastName, ...
  */
  static SIGNUP(elementID, callEndpoint, signupRequestObject, callbackMethodName){
    //CloudService.Call("signup", elementID, callEndpoint, data, callbackMethodName);
    //alert('enrolling ' + callEndpoint);
    console.log(`calling endpoint: ${callEndpoint}`);

    $.post(`${callEndpoint}`, signupRequestObject,
      function (result){
        //Expected reply is csv string: "userID, authCode"

        //supports returning result as string or int
        var userID = isNaN(result.trim()) ? parseInt(result.trim()) : result;
        var callbackOrderedData = [elementID, userID];

        //Attach the result
        var callbackMethod = `${callbackMethodName}('${elementID}', ${userID})`;
        // CloudService.constructCallbackURL(callbackMethodName, callbackOrderedData);
        //console.log(`executing callback  ${callbackMethod}`);
        eval(callbackMethod);
      }
    );
  }
  /*
    Authenticate a user
    @param: data - ORDERED array username, password
  */
  static AUTHENTICATE(elementID, callEndpoint, data, callbackMethodName){

    $.post(`${callEndpoint}`, { username: data[0], password: data[1]},
      function (result){
        //Expected reply is csv string: "userID, authCode"

        console.log(result);
        //supports returning result as string or int
        var userID = isNaN(result.trim()) ? parseInt(result.trim()) : result;
        var callbackOrderedData = [elementID, userID];

        //Attach the result
        var callbackMethod = `${callbackMethodName}('${elementID}', ${userID})`;
        // CloudService.constructCallbackURL(callbackMethodName, callbackOrderedData);
        //console.log(`executing ${callbackMethod}`);
        eval(callbackMethod);
      }
    );
  }
  /*
    Login a user
  */
  static LOGIN(elementID, callEndpoint, data, callbackMethodName){

    console.log(`posting to ${callEndpoint}`);

    $.post(`${callEndpoint}`, { username: data[0], password: data[1]},
      function (result){
        //Expected reply is csv string: "userID, authCode"
        //supports returning result as string or int
        var replyData = result.split(',');
        console.log('userid was ' + replyData[0]);
        var userID = (isNaN(replyData[0].trim()) || replyData[0].trim()==='') ?
                          0 : replyData[0];
        var authCode = userID>0 ? replyData[1].trim() : '0';
        if (authCode==='0'){
          console.log(`AuthCode was empty.  Error was ${replyData[1]}`);
        //  console.log(replyData);
        }
        var callbackOrderedData = [elementID, userID, authCode];

        //Attach the result
        var callbackMethod = `${callbackMethodName}('${elementID}', ${userID}, '${authCode}')`;
        // CloudService.constructCallbackURL(callbackMethodName, callbackOrderedData);
        console.log(`executing login callback ${callbackMethod}`);
        eval(callbackMethod);
      }
    );
  }

  /**/
  static SEND_PASSWORD_RESET_LINK(elementID, callEndpoint, data, callbackMethodName ){

    console.log(`posting to ${callEndpoint}`);

    var emailaddress = data[0];
    $.post(`${callEndpoint}`, { emailaddress: data[0], authCode: data[1], resetStep: data[2]},
      function (result){
        //Expected reply is csv string: "userID, authCode"
        //supports returning result as string or int
        var replyData = result.split(',');
        console.log('userid was ' + replyData[0]);
        var userID = (isNaN(replyData[0].trim()) || replyData[0].trim()==='') ?
                          0 : replyData[0];
        var authCode = userID>0 ? replyData[1].trim() : '0';
        if (authCode==='0'){
          console.log(`AuthCode was empty.  Error was ${replyData[1]}`);
        //  console.log(replyData);
        }

        //Attach the result
        var callbackMethod = `${callbackMethodName}('${elementID}', '${emailaddress}')`;
        // CloudService.constructCallbackURL(callbackMethodName, callbackOrderedData);
        console.log(`executing password request callback ${callbackMethod}`);
        eval(callbackMethod);
      }
    );



  }

  /*
    Call API to change password
    @param: data array[userid;password_raw,accesstoken]
  */
  static RESET_PASSWORD(elementID, callEndpoint, data, callbackMethodName)
  {
    console.log(`posting to ${callEndpoint}`);

    var resetStep = data[3];
    console.log(`RESET STEP ${resetStep}`);

    $.post(`${callEndpoint}`, { userid: data[0], password_raw: data[1], accessToken: data[2], resetStep: data[3]},
      function (result){
        //Expected reply is BOOL
        console.log('changepassword reply was ' + result);

        result =  (undefined===result) ? false : result;

        //Attach the result
        var callbackMethod = `${callbackMethodName}('${elementID}', ${result})`;
        // CloudService.constructCallbackURL(callbackMethodName, callbackOrderedData);
        console.log(`executing password reset callback ${callbackMethod}`);
        eval(callbackMethod);
      }
    );
  }

  /*
    Call requested endpoint and pass results to callbackMethod

  */
  static Call(method, elementID, callEndpoint, parameterData, callbackMethod){
    // TODO: parameterData not used yet
    //console.log(`${elementID} is calling service ${method} : ${callEndpoint} using selected data ${parameterData}`);
    $.ajax({
      method: `${method}`,
      url: `${callEndpoint}`,
      data: null,
      fail: CloudService.HandleErrorReply,
      success: (result) => {
                        CloudService.HandleSuccessReply(result,elementID);
                        //console.log('calling ' + callbackMethod);
                        eval(callbackMethod);//// TODO: Careful using eval!!
                      },
                      statusCode: {
                        401: function(){
                          alert("Auth failure");
                        }
                      }
        });
  }
  /*
  Generic handler for success replies
  */
  static HandleSuccessReply(result, elementID){
    //console.log(`Success for ${elementID}`);

  }
  /*
  Generic handler for error replies
  */
  static HandleErrorReply(error){
      var msg = "An error occured with this call: " + error.status + " - " + error.statusText;
      console.log(msg);
      //alert(msg);
  }
}
