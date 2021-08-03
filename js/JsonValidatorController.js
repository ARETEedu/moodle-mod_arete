var originalSessionID ='';
var originalActivityString = '';
var originalWorkplaceString = '';

$( document ).ready(function() {
    setTimeout(function(){ 
        var originalActivityOBJ = JSON.parse(activityEditor.getText());
        originalSessionID = originalActivityOBJ.id;
        
        originalActivityString = activityEditor.getText();
        originalWorkplaceString = workplaceEditor.getText();
    }, 500);


});


function On_Save_JSON_Pressed(){
    
    if (typeof activityEditor === 'undefined' || typeof workplaceEditor === 'undefined') {
      alert("Something is wrong with the JSON editor. Please close this page and try open it again form editing page.");
      return;
    }
    
    var activityOBJ = JSON.parse(activityEditor.getText());
    var workplaceOBJ = JSON.parse(workplaceEditor.getText());
    
    var sessionID = activityOBJ.id;
    
    //reset activity id
    if(sessionID !== originalSessionID){
        activityOBJ.id = originalSessionID;
    }
    
    //reset workplace id
    if(workplaceOBJ.id != sessionID + "-workplace.json" ){
        workplaceOBJ.id = sessionID + "-workplace.json";
    }

    Apply_New_JSON_To_Activity(JSON.stringify(activityOBJ), JSON.stringify(workplaceOBJ));
}


function Apply_New_JSON_To_Activity(activityJson, workplaceJSON){
    
        $.ajax("classes/JSONValidatorFileManager.php" , { 
        type: "POST", 
        data: {  activityJson: activityJson , workplaceJSON: workplaceJSON}, 
        dataType: "text",

        success: function (data) {
            if(confirm(data)){
                toggle_validator();
                if(originalActivityString != activityJson || originalWorkplaceString != workplaceJSON)
                {
                   $(".saving-warning").css("display" , "block");
                   $("#edit_page_save_button").css("background-color", "red");
                 }else{
                   $(".saving-warning").css("display" , "none");
                 }
            }
        }
      }); 
}
