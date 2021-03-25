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
