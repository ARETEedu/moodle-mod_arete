///ARLEM Table scripts


//thumbnail display
 var modalEle = document.querySelector("#modal");
    var modalImage = document.querySelector(".modalImage");
    var modalTitle = document.querySelector("#modalTitle");
    Array.from(document.querySelectorAll(".ImgThumbnail")).forEach(item => {
       item.addEventListener("click", event => {

        const pathArray = event.target.src.split("/");
        const lastIndex = pathArray.length - 1;

        //dont show no-thumbnail
        if(pathArray[lastIndex] !== "no-thumbnail.jpg"){
             modalEle.style.display = "block";
             modalImage.src = event.target.src;
             modalTitle.innerHTML = event.target.alt;
        }

       });
    });

    if(document.querySelector("#modalImg")){
       document.querySelector("#modalImg").addEventListener("click", () => {
       modalEle.style.display = "none";
       });
    }


    if(document.querySelector("#modal")){
         document.querySelector("#modal").addEventListener("click", () => {
        modalEle.style.display = "none";
           });

    }


//ARLEM delete confirm
 function confirmSubmit(form)
 {
     var checked = document.querySelectorAll('input.deleteCheckbox:checked');

     if (checked.length === 0) {

             form.submit();
     } else {

 if (confirm("Are you sure you want to delete these files?")) {
      form.submit();
             }
     }
 }


var userviewmode, editmodebutton_on_text, editmodebutton_off_text, viewsTitle, playTitle, downloadTitle,
        editTitle, qrTitle, publicTitle, deleteTitle, assignTitle, ratingTitle, scoreTitle, voteTitle = "";


//get the data from PHP
function init($userViewMode, editmodebutton_on_text , editmodebutton_off_text ,viewsTitle, playTitle, downloadTitle, editTitle, qrTitle, publicTitle, deleteTitle, assignTitle, ratingTitle, scoreTitle, voteTitle){
     this.userviewmode = $userViewMode;
     this.editmodebutton_on_text = editmodebutton_on_text;
     this.editmodebutton_off_text = editmodebutton_off_text;
     this.viewsTitle = viewsTitle;
     this.playTitle = playTitle;
     this.downloadTitle = downloadTitle;
     this.editTitle = editTitle;
     this.qrTitle = qrTitle;
     this.publicTitle = publicTitle;
     this.deleteTitle = deleteTitle;
     this.assignTitle = assignTitle; 
     this.ratingTitle = ratingTitle; 
     this.scoreTitle = scoreTitle;
     this.voteTitle = voteTitle;
     
     edit_mode_toggle(false , window.location.href.includes("&editing=on"));
}


//toggle editmode
function edit_mode_toggle(ButtonCallBack ,editmode){
   
   //if the fuction is called from button
   if(ButtonCallBack){
       
       //toggle edit mode by button
       if(editmode){
           edit_mode_off(ButtonCallBack);
           
       }else{
           edit_mode_on(ButtonCallBack);
       }
       
        window.open(window.location.href, "_self");
       
   }else{
       
       //if at the start editing=on founded in URL enable edit mode
       if(editmode){
           edit_mode_on();
       }else{
           edit_mode_off();
       }
   }
   
}


//disable edit mode
function edit_mode_off(ButtonCallBack){
    $("#saveButton").hide();
    $("#editModeButton").attr("value",editmodebutton_off_text);

    //remove editing parameter from url
    var url = window.location.href.replace("&editing=on", "");
    window.history.replaceState(null, null, url ); 


    $("#arlemTable").find("th").each(function() {
        var header = $(this).text();
        var index =  $(this).index() + 1;
        //only on page refreshing manage the columns (not on button pressing)
        if(!ButtonCallBack){
            if(header === editTitle || header === publicTitle || header === deleteTitle || header === assignTitle){
                    $('#arlemTable td:nth-child(' + index + '),#arlemTable th:nth-child(' + index + ')').hide();
            }

            if(header === playTitle || header === downloadTitle || header === qrTitle || header === ratingTitle || header === viewsTitle){
                $('#arlemTable td:nth-child(' + index + '),#arlemTable th:nth-child(' + index + ')').show();
            }
        }

        //assign radio button should not be displayed for the students at all
        if(userviewmode === 1 && header === assignTitle){
            $('#arlemTable td:nth-child(' + index + '),#arlemTable th:nth-child(' + index + ')').hide();
        }
    });

}


//enable edit mode
function edit_mode_on(ButtonCallBack){
    
    $("#saveButton").show();
    $("#editModeButton").attr("value",editmodebutton_on_text);
    //add  editing parameter from url
    
    //if editig=on does not exist in the URL add it
    if(!window.location.href.includes("&editing=on")){
        var url = window.location.href + "&editing=on";
        window.history.replaceState(null, null, url);  
    }

    //remove # from url
    var url = window.location.href.replace("#", "");
    window.history.replaceState(null, null, url ); 

    $("#arlemTable").find("th").each(function() {

        var header = $(this).text();
        var index =  $(this).index() + 1;

        //only on page refreshing manage the columns (not on button pressing)
        if(!ButtonCallBack){

            if(header === editTitle || header === publicTitle || header === deleteTitle || header === assignTitle){
                $('#arlemTable td:nth-child(' + index + '),#arlemTable th:nth-child(' + index + ')').show();
            }

            if(header === playTitle || header === downloadTitle || header === qrTitle || header === ratingTitle || header === viewsTitle){
                $('#arlemTable td:nth-child(' + index + '),#arlemTable th:nth-child(' + index + ')').hide();
            }

        }

        //assign radio button should not be displayed for the students at all
        if(userviewmode === 1 && header === assignTitle){
            $('#arlemTable td:nth-child(' + index + '),#arlemTable th:nth-child(' + index + ')').hide();
        }
    });
   
}



//download a file from url
function forceDownload(href) {
	var anchor = document.createElement('a');
	anchor.href = href;
	anchor.download = href;
	document.body.appendChild(anchor);
	anchor.click();
}



//Get all ratings select objects by id (startswith star_rating_) and assgin the correct score from DB
var rating_objects = $('[id^="star_rating_"]');
var ids = '';
 //for all rating objects in the page
for (i = 0; i <= rating_objects.length; i++) {
     initRatingObject("#" + $(rating_objects[i]).attr('id'));
}

/*TODO: hm... if we remove one odf these for loops bellow, the rating will not be displayed on assigned table for the students
and if we remove both, only some of ratings object will be displayed on teacher view. the error is barrating not defined.
Having these loop affects the performance about 500ms
*/
for (i = 0; i <= rating_objects.length; i++) {
     initRatingObject("#" + $(rating_objects[i]).attr('id'));
}
for (i = 0; i <= rating_objects.length; i++) {
     initRatingObject("#" + $(rating_objects[i]).attr('id'));
}
//

// read ratings from db and add the click event too
function initRatingObject(id){
    var full_ID = id,

    split_id = full_ID.split("_"),
    itemid = split_id[2],
    score_text_id = full_ID.replace("star_rating_" ,"ratingtext_");

    $.ajax({
        url: 'classes/getRating.php?' + Math.random(),
        cache: false,
        type: 'post',
        data: {
            itemid: itemid
        },
        dataType: 'json',
        success: function (data) {
            var value = Math.floor(data['avrage']),
                vote = data['votes'];
            try{
                $(full_ID).barrating(
                    {
                        theme: 'fontawesome-stars',
                        allowEmpty : null,
                        onSelect: function (selectedValue, text, event) {

                            if (typeof (event) !== 'undefined') {

                                $.ajax({
                                    url: 'classes/insertRating.php?' + Math.random(),
                                    cache: false,
                                    type: 'post',
                                    data: {
                                        itemid: itemid,
                                        userid: window.userid,
                                        rating: selectedValue
                                    },
                                    dataType: 'json',
                                    success: function (data) {
                                    }
                                });
                            }
                        }
                    });
                $(full_ID).barrating('set', value);
            }catch(err) {
            }
            $(score_text_id).html(scoreTitle + ':' + value + ' (' + voteTitle + ':' + vote + ')'); 
        }
    });
}


/**
 * save and store scroll bar position
 */
$(window).scroll(function() {
  sessionStorage.scrollTop = $(this).scrollTop();
});
$(document).ready(function() {
  if (sessionStorage.scrollTop !== "undefined") {
    $(window).scrollTop(sessionStorage.scrollTop);
  }
});
    