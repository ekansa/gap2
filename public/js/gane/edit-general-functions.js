
/*

START: Generl functions for all types of data

*/



var pFloatingPane; //object for the Dojo floating pane widget
var actEditType;
var actElementID;
var actItemID;

function updatePageRef(rawLine){
    var inputID = "tavoPageRef";
    var valA = document.getElementById(inputID).value;
    
    var requestURI = baseURI + "/gane-edit/update-page-ref";
    var myAjax = new Ajax.Request(requestURI,
        {method: 'post',
        parameters:
        {
            rawLine: rawLine,
            pdfpage: valA
        },
        onComplete: updatePageRefDone }
    );
    
}//end function

function updatePageRefDone(response){
    
    var respData = JSON.parse(response.responseText);
    alert("Updated to book page: " + respData.pdfpage);

}//update page ref done


function addNewValueToLine(editType, lineID){
        
    var paneArea = document.getElementById("paneArea");
    var pFloatingPane = document.createElement('div');
    pFloatingPane.setAttribute('id', 'editPane');
    paneArea.appendChild(pFloatingPane);
        
    var divA = document.createElement('div');
    pFloatingPane.appendChild(divA);
    
    var instructP = document.createElement('p');
    instructP.innerHTML = "Add a new <em>" + editType + "</em> to line "+ lineID +")";
    divA.appendChild(instructP); 
    
    var editTab = document.createElement('table');
    editTab.setAttribute('class', 'editOps');
    divA.appendChild(editTab);
    
    
    //headers
    var tabRowA = document.createElement('tr');
    editTab.appendChild(tabRowA);
    var tabCellA = document.createElement('th');
    tabCellA.innerHTML = "Edit Function";
    tabRowA.appendChild(tabCellA);
    
    if(editType == "geo"){
        var tabCellB = document.createElement('th');
        tabCellB.innerHTML = "Lat";
        tabRowA.appendChild(tabCellB);
        var tabCellBb = document.createElement('th');
        tabCellBb.innerHTML = "Lon";
        tabRowA.appendChild(tabCellBb);
    }
    else if(editType == "maps"){
        var tabCellB = document.createElement('th');
        tabCellB.innerHTML = "Letter";
        tabRowA.appendChild(tabCellB);
        var tabCellBb = document.createElement('th');
        tabCellBb.innerHTML = "Roman";
        tabRowA.appendChild(tabCellBb);
        var tabCellBc = document.createElement('th');
        tabCellBc.innerHTML = "Number";
        tabRowA.appendChild(tabCellBc);
    }
    else{
        var tabCellB = document.createElement('th');
        tabCellB.innerHTML = "New Text";
        tabRowA.appendChild(tabCellB);
    }
    
    var tabCellC = document.createElement('th');
    tabCellC.innerHTML = "Commit Link";
    tabRowA.appendChild(tabCellC);
    
        //Adding
    var tabRowA = document.createElement('tr');
    editTab.appendChild(tabRowA);
    var tabCellA = document.createElement('td');
    tabCellA.innerHTML = "Add new " + editType + " to line";
    tabRowA.appendChild(tabCellA);
    
    var tabCellB = document.createElement('td');
    tabRowA.appendChild(tabCellB);
    var newNameInput = document.createElement('input');
    newNameInput.setAttribute("type", "text") ;
    newNameInput.setAttribute("id", ("addVal-" + lineID) ) ;
    tabCellB.appendChild(newNameInput);
    
    
    var override = "";
    if(editType == "geo"){
        var tabCellBb = document.createElement('td');
        tabRowA.appendChild(tabCellBb);
        var newNameInputb = document.createElement('input');
        newNameInput.setAttribute("class", "short-input");
        newNameInputb.setAttribute("class", "short-input");
        newNameInputb.setAttribute("type", "text") ;
        newNameInputb.setAttribute("id", ("addVal-b-" + lineID) ) ;
        tabCellBb.appendChild(newNameInputb);
    }
    
    if(editType == "maps"){
        var tabCellBb = document.createElement('td');
        tabRowA.appendChild(tabCellBb);
        var newNameInputb = document.createElement('input');
        newNameInputb.setAttribute("type", "text") ;
        newNameInputb.setAttribute("id", ("addVal-b-" + lineID) ) ;
        newNameInput.setAttribute("class", "short-input");
        newNameInputb.setAttribute("class", "short-input");
        
        tabCellBb.appendChild(newNameInputb);
        
        var tabCellBc = document.createElement('td');
        tabRowA.appendChild(tabCellBc);
        var newNameInputc = document.createElement('input');
        newNameInputc.setAttribute("class", "short-input");
        newNameInputc.setAttribute("type", "text") ;
        newNameInputc.setAttribute("id", ("addVal-c-" + lineID) ) ;
        tabCellBc.appendChild(newNameInputc);
        
        override = "<br/><br/><a href=\"javascript:NOVALaddRecordToRawLine('" + editType + "', " + lineID + ")\">ADD Record (No Validation)</a>";
    }
    
    
    
    var tabCellC = document.createElement('td');
    tabCellC.innerHTML = "<a href=\"javascript:addRecordToRawLine('" + editType + "', " + lineID + ")\">ADD Record</a>" + override;
    tabRowA.appendChild(tabCellC);
    
    var dlgTop = cursorY + 25;
    var dlgLeft = cursorX - 250;
    
    pFloatingPane = new dojox.layout.FloatingPane({
            title: ("Add " + editType + " record"),
            resizable: true,
            dockable: false,
            style: "top:" + dlgTop + "px; left:" + dlgLeft + "px;",
            id: "editPane"
        },
        dojo.byId("editPane"));
    
    pFloatingPane.startup();
    pFloatingPane.show();
}


//execute ajax to add an record to a line
function addRecordToRawLine(editType, lineID){
    
    var newInputID = "addVal-" + lineID;
    var valA = document.getElementById(newInputID).value;
    if(editType == "geo"){
        var newInputIDb = "addVal-b-" + lineID;
        var valB = document.getElementById(newInputIDb).value;
        var valC = false;
    }
    else if(editType == "maps"){
        var newInputIDb = "addVal-b-" + lineID;
        var valB = document.getElementById(newInputIDb).value;
        var newInputIDc = "addVal-c-" + lineID;
        var valC = document.getElementById(newInputIDc).value;
    }
    else{
        var valB = false;
        var valC = false;
    }
   
    var requestURI = baseURI + "/gane-edit/add-record";
    var myAjax = new Ajax.Request(requestURI,
        {method: 'post',
        parameters:
        {
            lineID: lineID,
            editType: editType,
            valA: valA,
            valB: valB,
            valC: valC
        },
        onComplete: addRecordToRawLineDone }
    );
    
}


//execute ajax to add an record to a line
function NOVALaddRecordToRawLine(editType, lineID){
    
    var newInputID = "addVal-" + lineID;
    var valA = document.getElementById(newInputID).value;
    if(editType == "geo"){
        var newInputIDb = "addVal-b-" + lineID;
        var valB = document.getElementById(newInputIDb).value;
        var valC = false;
    }
    else if(editType == "maps"){
        var newInputIDb = "addVal-b-" + lineID;
        var valB = document.getElementById(newInputIDb).value;
        var newInputIDc = "addVal-c-" + lineID;
        var valC = document.getElementById(newInputIDc).value;
    }
    else{
        var valB = false;
        var valC = false;
    }
   
    var requestURI = baseURI + "/gane-edit/add-record";
    var myAjax = new Ajax.Request(requestURI,
        {method: 'post',
        parameters:
        {
            lineID: lineID,
            editType: editType,
            valA: valA,
            valB: valB,
            valC: valC,
            override: true
        },
        onComplete: addRecordToRawLineDone }
    );
    
}



//update the webpage to show the additional record
function addRecordToRawLineDone(response){
    
    var respData = JSON.parse(response.responseText);
    
    if(!respData.errors){
        var newValue = respData.newValue;
        var itemID = respData.id;
        var editType = respData.editType;
        var typePrefix = respData.typePrefix;
        var lineID = respData.rawLine; 
    
        var lineTypeListDivID = "vals-" + typePrefix + "-" + lineID;
        var lineTypeListDiv = document.getElementById(lineTypeListDivID);
        
        var commaSpan = document.createElement('span');
        commaSpan.innerHTML = ", ";
        lineTypeListDiv.appendChild(commaSpan);
        
        var linkID = typePrefix + "-" + itemID;
        var javaLink = "javascript:editValue('" + editType + "', " + itemID+ ", '" + linkID + "');";
        var newLink = document.createElement('a');
        newLink.setAttribute('id', linkID);
        newLink.setAttribute('href', javaLink);
        newLink.innerHTML = newValue;
        lineTypeListDiv.appendChild(newLink);
    }
    else{
        alert("D'oh! Somthing went wrong! " + respData.errors);
    }

    //close the floating pane 
    dijit.byId("editPane").close();
}


//execute ajax to delete a record from a line
function deleteRecord(editType, id){
    
    var requestURI = baseURI + "/gane-edit/delete-record";
    var myAjax = new Ajax.Request(requestURI,
        {method: 'post',
        parameters:
        {
            id: id,
            editType: editType
        },
        onComplete: deleteRecordDone }
    );
    
}


//finish record deletion
function deleteRecordDone(response){
    
    var respData = JSON.parse(response.responseText);
    var itemID = respData.deleteID;
    var typePrefix = respData.typePrefix;
    var linkID = typePrefix + "-" + itemID;
    
    var displayValue = document.getElementById(linkID);
    displayValue.setAttribute("style", "text-decoration:line-through;") ;
    displayValue.setAttribute("href", " ") ;
    
    //close the floating pane 
    dijit.byId("editPane").close();

}



function editValue(editType, itemID, elementID){
    
    if (typeof pFloatingPane != 'undefined'){
       pFloatingPane.destroyRecursive(); //destroys old floating pane, so no conflict in making new
    }
    
    actEditType = editType;
    actElementID = elementID;
    actItemID = itemID;
    
    if(actEditType == "names"){
        showNameEdit(itemID);
    }
    else if(actEditType == "features"){
        showFeatureEdit(itemID);
    }
    else if(actEditType == "geo"){
        showGeoEdit(itemID);
    }
    else if(actEditType == "maps"){
        showMapEdit(itemID);
    }
    else if(actEditType == "lang"){
        showLangEdit(itemID);
    }
    else if(actEditType == "romanized"){
        showRomanizedEdit(itemID);
    }
    else if(actEditType == "uri"){
        showRelURIEdit(itemID);
    }
    
}//end function


//add a new line to an existing line
function addNewLine(rawLine){
    //actPlaceID
    var conf = confirm("Do you really want to add a text line just after " + rawLine + "? (Can't undo!)");
    if (conf){
        var requestURI = baseURI + "/gane-edit/add-line";
        post_to_url(requestURI, {'placeID':actPlaceID, 'rawLine':rawLine})
    }
    else{
        alert("Canceled adding a text line.");
    }
}



function post_to_url(path, params) {
    var method = "post"; // Set method to post by default, if not specified.

    // The rest of this code assumes you are not using a library.
    // It can be made less wordy if you use one.
    var form = document.createElement("form");
    form.setAttribute("method", method);
    form.setAttribute("action", path);

    for(var key in params) {
        if(params.hasOwnProperty(key)) {
            var hiddenField = document.createElement("input");
            hiddenField.setAttribute("type", "hidden");
            hiddenField.setAttribute("name", key);
            hiddenField.setAttribute("value", params[key]);

            form.appendChild(hiddenField);
         }
    }

    document.body.appendChild(form);
    form.submit();
}

