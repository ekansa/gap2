
/*

START: Edit feature functions

*/

function showRomanizedEdit(itemID){
    
    var paneArea = document.getElementById("paneArea");
    var pFloatingPane = document.createElement('div');
    pFloatingPane.setAttribute('id', 'editPane');
    paneArea.appendChild(pFloatingPane);
        
    var divA = document.createElement('div');
    pFloatingPane.appendChild(divA);
    
    var instructP = document.createElement('p');
    if(itemID >0 ){
        var originalValID = "r-" + itemID;
        var orignalVal = document.getElementById(originalValID).innerHTML;
        instructP.innerHTML = "Edit Romanized Name <em>" + orignalVal + "</em>";
    }
    else{
        instructP.innerHTML = "Add a Romanized Name";
    }
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
    var tabCellB = document.createElement('th');
    tabCellB.innerHTML = "Romanized Name";
    tabRowA.appendChild(tabCellB);
    var tabCellC = document.createElement('th');
    tabCellC.innerHTML = "Commit Link";
    tabRowA.appendChild(tabCellC);
    
    if(itemID > 0){
        //UPDATES
        var tabRowA = document.createElement('tr');
        editTab.appendChild(tabRowA);
        var tabCellA = document.createElement('td');
        tabCellA.innerHTML = "Update name";
        tabRowA.appendChild(tabCellA);
        
        var tabCellB = document.createElement('td');
        tabRowA.appendChild(tabCellB);
        var newValInput = document.createElement('input');
        newValInput.setAttribute("type", "text") ;
        newValInput.setAttribute("id", ("textval-" + itemID) ) ;
        newValInput.value = orignalVal;
        tabCellB.appendChild(newValInput);
        
        //now the action link
        var tabCellC = document.createElement('td');
        tabCellC.innerHTML = "<a href=\"javascript:updateDetail('romanized', " + itemID + ")\">Update Romanized Name</a>";
        tabRowA.appendChild(tabCellC);
        
        //DELETES
        var tabRowA = document.createElement('tr');
        editTab.appendChild(tabRowA);
        var tabCellA = document.createElement('td');
        tabCellA.innerHTML = "Delete name";
        tabRowA.appendChild(tabCellA);
        
        var tabCellB = document.createElement('td');
        tabCellB.innerHTML = orignalVal + ": <em>Error, redundant, etc.</em>";
        tabRowA.appendChild(tabCellB);
        
        //now the action link
        var tabCellC = document.createElement('td');
        tabCellC.innerHTML = "<a href=\"javascript:deleteDetail('romanized', " + itemID + ")\">Delete Romanized Name</a>";
        tabRowA.appendChild(tabCellC);
    }
    
    //Addition
    var tabRowA = document.createElement('tr');
    editTab.appendChild(tabRowA);
    var tabCellA = document.createElement('td');
    tabCellA.innerHTML = "Add name";
    tabRowA.appendChild(tabCellA);
    
    var tabCellB = document.createElement('td');
    tabRowA.appendChild(tabCellB);
    var newValInput = document.createElement('input');
    newValInput.setAttribute("type", "text") ;
    newValInput.setAttribute("id", "textval-0") ;
    tabCellB.appendChild(newValInput);
    
    //now the action link
    var tabCellC = document.createElement('td');
    tabCellC.innerHTML = "<a href=\"javascript:addDetail('romanized')\">Add Romanized Name</a>";
    tabRowA.appendChild(tabCellC);
    
    var dlgTop = cursorY + 25;
    var dlgLeft = cursorX - 250;
    
    pFloatingPane = new dojox.layout.FloatingPane({
            title: ("Edit Romanized Names"),
            resizable: true,
            dockable: false,
            style: "top:" + dlgTop + "px; left:" + dlgLeft + "px;",
            id: "editPane"
        },
        dojo.byId("editPane"));
    
    pFloatingPane.startup();
    pFloatingPane.show();
}


function showRelURIEdit(itemID){
    
    var paneArea = document.getElementById("paneArea");
    var pFloatingPane = document.createElement('div');
    pFloatingPane.setAttribute('id', 'editPane');
    paneArea.appendChild(pFloatingPane);
        
    var divA = document.createElement('div');
    pFloatingPane.appendChild(divA);
    
    var instructP = document.createElement('p');
    if(itemID >0 ){
        var originalValID = "u-" + itemID;
        var originalTitleID = "u-t-" + itemID;
        var originalNoteID = "u-n-" + itemID;
        var orignalVal = document.getElementById(originalValID).innerHTML;
        var originalTitle = document.getElementById(originalTitleID).innerHTML;
        var originalNote = document.getElementById(originalNoteID).innerHTML;
        instructP.innerHTML = "Edit Related URI <em>" + orignalVal + "</em>";
    }
    else{
        instructP.innerHTML = "Add a Related URI";
    }
    
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
    var tabCellB = document.createElement('th');
    tabCellB.innerHTML = "Related URI";
    tabRowA.appendChild(tabCellB);
    var tabCellBb = document.createElement('th');
    tabCellBb.innerHTML = "Title";
    tabRowA.appendChild(tabCellBb);
    var tabCellBc = document.createElement('th');
    tabCellBc.innerHTML = "Note";
    tabRowA.appendChild(tabCellBc);
    var tabCellC = document.createElement('th');
    tabCellC.innerHTML = "Commit Link";
    tabRowA.appendChild(tabCellC);
    
    if(itemID > 0){
        //UPDATES
        var tabRowA = document.createElement('tr');
        editTab.appendChild(tabRowA);
        var tabCellA = document.createElement('td');
        tabCellA.innerHTML = "Update URI";
        tabRowA.appendChild(tabCellA);
        
        var tabCellB = document.createElement('td');
        tabRowA.appendChild(tabCellB);
        var newValInput = document.createElement('input');
        newValInput.setAttribute("type", "text") ;
        newValInput.setAttribute("id", ("textval-" + itemID) ) ;
        newValInput.value = orignalVal;
        tabCellB.appendChild(newValInput);
        
        var tabCellB = document.createElement('td');
        tabRowA.appendChild(tabCellB);
        var newValInput = document.createElement('input');
        newValInput.setAttribute("type", "text") ;
        newValInput.setAttribute("id", ("title-" + itemID) ) ;
        newValInput.value = originalTitle;
        tabCellB.appendChild(newValInput);
        
        var tabCellB = document.createElement('td');
        tabRowA.appendChild(tabCellB);
        var newValInput = document.createElement('textarea');
        newValInput.setAttribute("id", ("note-" + itemID) ) ;
        newValInput.value = originalNote;
        tabCellB.appendChild(newValInput);
        
        
        //now the action link
        var tabCellC = document.createElement('td');
        tabCellC.innerHTML = "<a href=\"javascript:updateDetail('uri', " + itemID + ")\">Update Related URI</a>";
        tabRowA.appendChild(tabCellC);
        
        //DELETES
        var tabRowA = document.createElement('tr');
        editTab.appendChild(tabRowA);
        var tabCellA = document.createElement('td');
        tabCellA.innerHTML = "Delete name";
        tabRowA.appendChild(tabCellA);
        
        var tabCellB = document.createElement('td');
        tabCellB.innerHTML = orignalVal + ": <em>Error, redundant, etc.</em>";
        tabRowA.appendChild(tabCellB);
        
        var tabCellB = document.createElement('td');
        tabCellB.innerHTML = " ";
        tabRowA.appendChild(tabCellB);
        
        var tabCellB = document.createElement('td');
        tabCellB.innerHTML = " ";
        tabRowA.appendChild(tabCellB);
        
        //now the action link
        var tabCellC = document.createElement('td');
        tabCellC.innerHTML = "<a href=\"javascript:deleteDetail('uri', " + itemID + ")\">Delete Related URI</a>";
        tabRowA.appendChild(tabCellC);
    }
    
    //Addition
    var tabRowA = document.createElement('tr');
    editTab.appendChild(tabRowA);
    var tabCellA = document.createElement('td');
    tabCellA.innerHTML = "Add name";
    tabRowA.appendChild(tabCellA);
    
    var tabCellB = document.createElement('td');
    tabRowA.appendChild(tabCellB);
    var newValInput = document.createElement('input');
    newValInput.setAttribute("type", "text") ;
    newValInput.setAttribute("id", "textval-0") ;
    tabCellB.appendChild(newValInput);
    
    var tabCellB = document.createElement('td');
    tabRowA.appendChild(tabCellB);
    var newValInput = document.createElement('input');
    newValInput.setAttribute("type", "text") ;
    newValInput.setAttribute("id", "title-0") ;
    tabCellB.appendChild(newValInput);
    
    var tabCellB = document.createElement('td');
    tabRowA.appendChild(tabCellB);
    var newValInput = document.createElement('textarea');
    newValInput.setAttribute("id", "note-0") ;
    tabCellB.appendChild(newValInput);
    
    //now the action link
    var tabCellC = document.createElement('td');
    tabCellC.innerHTML = "<a href=\"javascript:addDetail('uri')\">Add Related URI</a>";
    tabRowA.appendChild(tabCellC);
    
    var dlgTop = cursorY + 25;
    var dlgLeft = cursorX - 250;
    
    pFloatingPane = new dojox.layout.FloatingPane({
            title: ("Edit Romanized Names"),
            resizable: true,
            dockable: false,
            style: "top:" + dlgTop + "px; left:" + dlgLeft + "px; width: 600px;",
            id: "editPane"
        },
        dojo.byId("editPane"));
    
    pFloatingPane.startup();
    pFloatingPane.show();
}

//creating new from scratch
function addRomanized(){
    showRomanizedEdit(0);
}

//creating new from scratch
function addRelURI(){
    showRelURIEdit(0);
}


function addDetail(detailType){
    
    var placeID = document.getElementById("edit-place-id").value;
    var textval = document.getElementById("textval-0").value;
    if(detailType == "uri"){
        var title = document.getElementById("title-0").value;
        var note = document.getElementById("note-0").value;
    }
    else{
        var title = false;
        var note = false;
    }
    
    var requestURI = baseURI + "/gane-edit/add-detail";
    var myAjax = new Ajax.Request(requestURI,
        {method: 'post',
        parameters:
        {
            placeID: placeID,
            detailType: detailType,
            textval: textval,
            title: title,
            note: note
        },
        onComplete: addDetailDone }
    );
    
}

function addDetailDone(response){
    
    var respData = JSON.parse(response.responseText);
    var itemID = respData.id;
    
    var detailType = respData.detailType;
    if(detailType == "romanized"){
        var tabDOMid = "romanized";
        var linkID = "r-" + itemID;
        var javaLink = "javascript:editValue('romanized', " + itemID + ", '" + linkID + "');";
    }
    else{
        var tabDOMid = "relURIs";
        var linkID = "u-" + itemID;
        var javaLink = "javascript:editValue('uri', " + itemID + ", '" + linkID + "');";
    }
    
    var tabDOM = document.getElementById(tabDOMid);
    var tabRowA = document.createElement('div');
    tabRowA.setAttribute("class", "tRow");
    tabDOM.appendChild(tabRowA);
    
    //add the textval data with correct id and href attributes to DOM
    var tabValCell = document.createElement('div');
    tabValCell.setAttribute("class", "tCell");
    var valLink = document.createElement('a');
    valLink.innerHTML = respData.textval;
    valLink.setAttribute("id", linkID);
    valLink.setAttribute("href", javaLink);
    tabValCell.appendChild(valLink);
    tabRowA.appendChild(tabValCell);
    
    
    
    if(detailType == "uri"){
        var tabTitleCell = document.createElement('div');
        tabTitleCell.setAttribute("class", "tCell");
        tabTitleCell.setAttribute("id", ("u-t-" + itemID));
        tabTitleCell.innerHTML = respData.title;
        tabRowA.appendChild(tabTitleCell);
        
        var tabNoteCell = document.createElement('div');
        tabNoteCell.setAttribute("class", "tCell");
        tabNoteCell.setAttribute("id", ("u-n-" + itemID));
        tabNoteCell.innerHTML = respData.note;
        tabRowA.appendChild(tabNoteCell);
    }
    
    dijit.byId("editPane").close();
}


function deleteDetail(detailType, itemID){
    
    var requestURI = baseURI + "/gane-edit/delete-detail";
    var myAjax = new Ajax.Request(requestURI,
        {method: 'post',
        parameters:
        {
            id: itemID,
            detailType: detailType
        },
        onComplete: deleteDetailDone }
    );
    
}

function deleteDetailDone(response){
    
    var respData = JSON.parse(response.responseText);
    var itemID = respData.deleteID;
    var detailType = respData.detailType;
    if(detailType == "romanized"){
        var linkID = "r-" + itemID;
    }
    else{
        var linkID = "u-" + itemID;
        var titleID = "u-t-" + itemID;
        var noteID = "u-n-" + itemID;
        var displayTitle = document.getElementById(titleID);
        displayTitle.setAttribute("style", "text-decoration:line-through;") ;
        var displayNote = document.getElementById(noteID);
        displayNote.setAttribute("style", "text-decoration:line-through;") ;
    }
    
    var displayValue = document.getElementById(linkID);
    displayValue.setAttribute("style", "text-decoration:line-through;") ;
    displayValue.setAttribute("href", " ") ;
    
    dijit.byId("editPane").close();
}


function updateDetail(detailType, itemID){
    
    var textval = document.getElementById("textval-" + itemID).value;
    if(detailType == "uri"){
        var title = document.getElementById("title-" + itemID).value;
        var note = document.getElementById("note-" + itemID).value;
    }
    else{
        var title = false;
        var note = false;
    }
    
    var requestURI = baseURI + "/gane-edit/update-detail";
    var myAjax = new Ajax.Request(requestURI,
        {method: 'post',
        parameters:
        {
            id: itemID,
            detailType: detailType,
            textval: textval,
            title: title,
            note: note
        },
        onComplete: updateDetailDone }
    );
    
}

function updateDetailDone(response){
    
    var respData = JSON.parse(response.responseText);
    var itemID = respData.id;
    var detailType = respData.detailType;
    if(detailType == "romanized"){
        var tabDOMid = "romanized";
        var linkID = "r-" + itemID;
    }
    else{
        var tabDOMid = "relURIs";
        var linkID = "u-" + itemID;
        var titleID = "u-t-" + itemID;
        var noteID = "u-n-" + itemID;
        var displayTitle = document.getElementById(titleID);
        displayTitle.innerHTML = respData.title;
        var displayNote = document.getElementById(noteID);
        displayNote.innerHTML = respData.note;
    }
    
    var displayValue = document.getElementById(linkID);
    displayValue.innerHTML = respData.textval;
    
    dijit.byId("editPane").close();
}




