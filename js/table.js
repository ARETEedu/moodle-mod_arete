///ARLEM Table page scripts


//Do On Start
$(document).ready(function() {
    
    //change the color of the header which the table is sorted by
    const params = new URLSearchParams(window.location.search);
    change_sorted_header_color(params);
    
    //set the scroll bar back to saved play if it was saved
    if (sessionStorage.scrollTop !== "undefined") {
        $(window).scrollTop(sessionStorage.scrollTop);
    }

    //initiate rating objects
    initRatingObject();
    
    //editmode button pressed
    $("#editModeButton").click(function(){
        edit_mode_toggle(true, window.location.href.includes("&editing=on"));
    });

    //disable save button  at the start (enabled by checkbox)
    $("#saveButton").prop('disabled', true);
    $("#confirmchk").change(function(){
        
        //toggle savebutton activition
        $("#saveButton").prop('disabled', function(i, v) { return !v; });
    });
    
});


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

//Gets from PHP 
var domain, userviewmode, editmodebutton_on_text, editmodebutton_off_text, viewsTitle, playTitle, downloadTitle,
        editTitle, qrTitle, publicTitle, deleteTitle, assignTitle, ratingTitle, scoreTitle, voteTitle , voteRegisteredTitle = "";


//get the data from PHP
function init(userViewMode, editmodebutton_on_text , editmodebutton_off_text ,viewsTitle, playTitle, downloadTitle, editTitle, qrTitle, publicTitle, deleteTitle, assignTitle, ratingTitle, scoreTitle, voteTitle, voteRegisteredTitle){
     this.userviewmode = userViewMode;
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
     this.voteRegisteredTitle = voteRegisteredTitle;
     
     edit_mode_toggle(false , window.location.href.includes("&editing=on"));
     
     const params = new URLSearchParams(window.location.search);
     if(!params.has('sort')){
        params.append('sort', 'timecreated');
        change_sorted_header_color(params);
     }

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
    //add  editing parameter from url
    //if editig=on does not exist in the URL add it
    if(window.location.href.includes("&editing=off")){
        var url = window.location.href.replace("&editing=off", "&editing=on");
        window.history.replaceState(null, null, url);  
    }
    else if(!window.location.href.includes("&editing=on")){
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


var starRatingControl = new StarRating('.star-rating',{
    maxStars: 5,
    tooltip: false
});


var rating_objects = $('[id^="star_rating_"]');
var onStart = 1; //changes to 0 after all rating objects are initiated

// read ratings from db and add the click event too
function initRatingObject(){

     //for all rating objects in the page
    for (i = 0; i <= rating_objects.length; i++) {

        var full_ID = "#" + $(rating_objects[i]).attr('id');
        
        if(!full_ID){
            continue;
        }       
   
        var split_id = full_ID.split("_"),
        itemid = split_id[2],
        score_text_id = full_ID.replace("star_rating_" ,"ratingtext_");

        if(!itemid){
            continue;
        }

        //get rating from DB 
        getRating(full_ID, itemid, score_text_id);
        
        //set value to DB on select stars
        setRating(full_ID, itemid, score_text_id);

    }

    setTimeout(function(){  onStart = 0; }, 2000);
   
}



function setRating(id, itemid, score_text_id){
    
    $(id).on('change', function() {

        $.ajax({
            url: 'classes/insertRating.php?' + Math.random(),
            cache: false,
            type: 'post',
            data: {
                itemid: itemid,
                userid: window.userid,
                rating: $(id).find(":selected").text(),
                onstart: onStart
            },
            dataType: 'text',
            success: function (data) {
                //only onLoading page update the ratings text not when the user vote
                if(onStart === 1)
                    $(score_text_id).html(scoreTitle + ':' + $(id).find(":selected").text() + ' (' + voteTitle + ':' + data + ')');  
                else{
                    $(score_text_id).html(voteRegisteredTitle);
                }
            }
        });
            
    });

}


function  getRating(id, itemid, score_text_id){
    
    var value = "0";
    $.ajax({
        url: 'classes/getRating.php?' + Math.random(),
        cache: false,
        type: 'post',
        data: {
            itemid: itemid
        },
        dataType: 'json',
        success: function (data) {
                value = Math.floor(data['avrage']);
                var vote = data['votes'];
                
                
                if(value > 0){
                    $(id).val(value.toString()).change();
                    $(score_text_id).html(scoreTitle + ':' + value + ' (' + voteTitle + ':' + vote + ')');  
                }else{
                    $(score_text_id).html(scoreTitle + ':0 (' + voteTitle + ':0)');  
                }
        },
        complete: function() {
            starRatingControl.rebuild();
        }
    });
    
}


/**
 * save scroll bar position
 */
$(window).scroll(function() {
  sessionStorage.scrollTop = $(this).scrollTop();
});



 //change the color of the header which the table is sorted by
function change_sorted_header_color(params){
    
    $('.headers').each(function(i, obj) {
        if(params.get('sort') === $(obj).attr('id')){
           $('#'+ $(obj).attr('id')+ ' a').css('color', 'orange');
        }        
    });

}



function reverse_sorting(sorting){
    
const params = new URLSearchParams(window.location.search);

    if(params.has('order')){
        if(params.get('order') === "DESC"){  
            params.set('order' , 'ASC');
        }else if(params.get('order') === "ASC"){
            params.set('order' , 'DESC');
        }
    }else{
        params.append('order' , 'ASC');
    }

    if(!params.has('sort')){
        params.append('sort' , sorting);
    }else{
        params.set('sort' , sorting);
    }

    window.location = 'view.php?' + params;
}