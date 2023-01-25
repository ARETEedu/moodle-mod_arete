var data = document.querySelector("#visualEditorContainer script");

// var jsonActivity = JSON.parse(data.getAttribute("activityjson"));
// var jsonWorkplace = JSON.parse(data.getAttribute("workplacejson"));

// console.log(jsonActivity);
// console.log(jsonWorkplace);

console.log(data.getAttribute("name"));

var tableHTML = "";

tableHTML += "<table id='visualEditorTable'>";

var uniqueElements = [];
var uniqueElementIds = [];

//#region header
tableHTML += "<th id='visualEditorTableFirstColumn'></th>"

for(var i = 0; i < jsonActivity.actions.length; i++)
{
    tableHTML += "<th>" + (i + 1) + "</th>";

    jsonActivity.actions[i].enter.activates.forEach((item) => 
    {
        if(item.predicate == "")
        {
            return null;
        }
    
        if(!uniqueElementIds.includes(item.poi))
        {
            uniqueElementIds.push(item.poi);
            uniqueElements.push({id:item.poi, active:[i + 1]});
        }
        else
        {
            uniqueElements.find(obj => 
            {
                return obj.id === item.poi;
            }).active.push(i + 1);
        }
    });
}

tableHTML += "<th> + </th>";
//#endregion

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

        //Singles
        if(uniqueElements[i].active.length == 1)
        {
            if(uniqueElements[i].active[0] == j)
            {
                tableHTML += "<td><div id='elementSingle'></div></td>";
                continue;
            }
            tableHTML += "<td><div></div></td>";
            continue;
        }

        //Multiples
        if(!uniqueElements[i].active.includes(j))
        {
            tableHTML += "<td><div></div></td>";
            continue;
        }

        if(j == 1)
        {
            tableHTML += "<td><div id='elementStart'>";
        }
        else if(j == uniqueElements[i].active.length)
        {
            tableHTML += "<td><div id='elementEnd'>";
        }
        else if(j > 1 && j < uniqueElements[i].active.length)
        {
            tableHTML += "<td><div id='elementMiddle'>";
        }
        tableHTML += "</div></td>";
    }
    tableHTML += "<td></td></tr>";
}

tableHTML += "</table>";
document.getElementById("visualEditorContent").innerHTML += tableHTML;