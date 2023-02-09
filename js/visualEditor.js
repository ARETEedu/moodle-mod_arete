
var data = document.querySelector("#visualEditorContainer script");
var activityPath = data.getAttribute("activityjson");
var workplacePath = data.getAttribute("workplacejson");
var jsonActivity = null;
var jsonWorkplace = null;

FetchFiles();

function FetchFiles()
{
    fetch(activityPath).then((responseActivity) => 
    responseActivity.json().then((dataActivity) => {
        jsonActivity = dataActivity;
        fetch(workplacePath).then((responseWorkplace) => 
        responseWorkplace.json().then((dataWorkplace) => {
            jsonWorkplace = dataWorkplace;
            SetupEditor();
        }));
    }));
}

function SetupEditor()
{
    var tableHTML = "";

    tableHTML += "<table id='visualEditorTable'>";

    var uniqueElements = [];
    var uniqueElementIds = [];

    //#region header
    tableHTML += "<th id='visualEditorTableFirstColumn'></th>"

    jsonActivity.actions.forEach((element, index) =>{
        tableHTML += `<th>${index + 1}</th>`;
        element.enter.activates.forEach((item) => {
            if(item.type == "tangible")
            {
                var entryID = item.url;
                if(item.predicate == "pickandplace")
                {
                    entryID = "pickandplace";
                }
                if(!uniqueElementIds.includes(entryID))
                {
                    uniqueElementIds.push(entryID);
                    uniqueElements.push({id:entryID, active:[index + 1]});
                }
                else
                {
                    uniqueElements.find(obj => 
                    {
                        return obj.id === entryID;
                    }).active.push(index + 1);
                }
            }
        })
    });
    tableHTML += "<th onclick='cellClicked()'>+</th>";

    for(var i = 0; i < uniqueElements.length; i++)
    {
        tableHTML += "<tr>";
        for(var j = 0; j < jsonActivity.actions.length + 1; j++)
        {
            //ID column
            if(j == 0)
            {
                tableHTML += "<td id='visualEditorTableFirstColumn'><div>" + uniqueElementIds[i] + "</div></td>";
                continue;
            }

            tableHTML += `<td onclick='cellClicked(this, ${j})'>`;

            //Singles
            if(uniqueElements[i].active.length == 1)
            {
                if(uniqueElements[i].active[0] == j)
                {
                    tableHTML += "<div id='elementSingle'></div></td>";
                    continue;
                }
                tableHTML += "<div id='empty'></div></td>";
                continue;
            }

            //Multiples
            if(!uniqueElements[i].active.includes(j))
            {
                tableHTML += "<div id='empty'></div></td>";
                continue;
            }

            if(j == 1)
            {
                tableHTML += "<div id='elementStart'>";
            }
            else if(j == uniqueElements[i].active.length)
            {
                tableHTML += "<div id='elementEnd'>";
            }
            else if(j > 1 && j < uniqueElements[i].active.length)
            {
                tableHTML += "<div id='elementMiddle'>";
            }
            tableHTML += "</div></td>";
        }
        tableHTML += `<td onClick='cellClicked(this, ${jsonActivity.actions.length + 1})'><div></div></td></tr>`;
    }

    tableHTML += "<tr><td>";
    tableHTML += "<input type='file'>";
    tableHTML += "</td></tr>";
    tableHTML += "</table>";

    document.getElementById("visualEditorContent").innerHTML += tableHTML;
}

//How?

function cellClicked(elem, x)
{
    var parent = elem.parentElement;

    var neighborStates = 0;
    
    if(elem.children[0].id != "empty")
    {
        elem.children[0].id = "empty";
        return;
    }

    if(parent.children[x - 1].children[0].id.startsWith("element"))
    {
        neighborStates += 1;
    }

    if(x < (parent.children.length - 1))
    {
        if(parent.children[x + 1].children[0].id.startsWith("element"))
        {
            neighborStates += 2;
        }
    }

    switch(neighborStates)
    {
        case 0: elem.children[0].id = "elementSingle";
            break;
        case 1: elem.children[0].id = "elementEnd";
            break;
        case 2: elem.children[0].id = "elementStart";
            break;
        case 3: elem.children[0].id = "elementMiddle";
            break;
        default:
            break;
    }
}