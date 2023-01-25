/////check file for editing is selected start
    function checkFiles(form){
            //do what you want here before submit update form
            
            form.submit();
        
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
    
    
    function toggle_validator(){
      $("#validator-modal").toggle();
    }

    function toggle_visual_editor()
    {
      $("#visualEditor").toggle();
    }
    
    
