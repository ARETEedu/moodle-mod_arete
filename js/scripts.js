/////check file for editing is selected start
    function checkFiles(form){
        if( document.getElementById("files").files.length === 0 ){
            alert("Please select at lease one file to update this activity");
            return;
        }else{
            form.submit();
        }
    };
    /////check file for editing is selected end



    /////Custom file selector start
    Array.prototype.forEach.call(
      document.querySelectorAll(".file-upload__button"),
      function(button) {
        const hiddenInput = button.parentElement.querySelector(
          ".file-upload__input"
        );
        const label = button.parentElement.querySelector(".file-upload__label");
        const defaultLabelText = "No file(s) selected";

        // Set default text for label
        label.textContent = defaultLabelText;
        label.title = defaultLabelText;

        button.addEventListener("click", function() {
          hiddenInput.click();
        });

        hiddenInput.addEventListener("change", function() {
          const filenameList = Array.prototype.map.call(hiddenInput.files, function(
            file
          ) {
            return file.name;
          });

          label.textContent = filenameList.join(", ") || defaultLabelText;
          label.title = label.textContent;
        });
      }
    );
    /////Custom file selector end
    
    
    
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
        document.querySelector("#modalImg").addEventListener("click", () => {
           modalEle.style.display = "none";
        });

        document.querySelector("#modal").addEventListener("click", () => {
           modalEle.style.display = "none";
        });
        
        
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
