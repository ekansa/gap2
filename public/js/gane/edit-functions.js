
/*

START: Edit functions

*/



var pFloatingPane; //object for the Dojo floating pane widget
var actEditType;
var actElementID;
var actItemID;

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
    
}//end function


function showNameEdit(itemID){
    
    var requestURI = baseURI + "/gane-edit/get-toponymn";
    var myAjax = new Ajax.Request(requestURI,
        {method: 'get',
        parameters:
        {
            id: itemID 
        },
        onComplete: showNameEditDone }
    );
    
}

function showNameEditDone(response){
    
    var respData = JSON.parse(response.responseText);
    var itemID = respData.id;
    var rawTopo = respData.rawTopo;
    var cleanTopo = respData.cleanTopo;
    var useTopo = cleanTopo;
    
    if(cleanTopo.length < 1){
        useTopo = rawTopo;
    }
    
    var paneArea = document.getElementById("paneArea");
    var pFloatingPane = document.createElement('div');
    pFloatingPane.setAttribute('id', 'editPane');
    paneArea.appendChild(pFloatingPane);
        
    var divA = document.createElement('div');
    pFloatingPane.appendChild(divA);
    
    var instructP = document.createElement('p');
    instructP.innerHTML = "Edit the toponymn <em>" + useTopo + "</em> (OCRed as: "+ rawTopo +")";
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
    tabCellB.innerHTML = "New Text";
    tabRowA.appendChild(tabCellB);
    var tabCellC = document.createElement('th');
    tabCellC.innerHTML = "Commit Link";
    tabRowA.appendChild(tabCellC);
    
    //renaming
    var tabRowA = document.createElement('tr');
    editTab.appendChild(tabRowA);
    var tabCellA = document.createElement('td');
    tabCellA.innerHTML = "Update toponymn";
    tabRowA.appendChild(tabCellA);
    
    var tabCellB = document.createElement('td');
    tabRowA.appendChild(tabCellB);
    var newNameInput = document.createElement('input');
    newNameInput.setAttribute("type", "text") ;
    newNameInput.setAttribute("id", ("newTopo-" + itemID) ) ;
    newNameInput.value = useTopo;
    tabCellB.appendChild(newNameInput);
    
    var tabCellC = document.createElement('td');
    tabCellC.innerHTML = "<a href=\"javascript:updateTopo(" + itemID + ")\">Update Name</a>";
    tabRowA.appendChild(tabCellC);
    
    
    
    //Change to Language
    var tabRowA = document.createElement('tr');
    editTab.appendChild(tabRowA);
    var tabCellA = document.createElement('td');
    tabCellA.innerHTML = "Update to Lang.";
    tabRowA.appendChild(tabCellA);
    
    var tabCellB = document.createElement('td');
    tabRowA.appendChild(tabCellB);
    var newNameInput = document.createElement('input');
    newNameInput.setAttribute("type", "text") ;
    var otherID = "topo-lang-" + itemID;
    newNameInput.setAttribute("id", otherID ) ;
    newNameInput.value = useTopo;
    tabCellB.appendChild(newNameInput);
    
    
    var tabCellC = document.createElement('td');
    tabCellC.innerHTML = "<a href=\"javascript:topoToOther(" + itemID + ", 'lang', '" + otherID + "')\">Change to Lang.</a>";
    tabRowA.appendChild(tabCellC);
    
    
    
    //Change to Map
    var tabRowA = document.createElement('tr');
    editTab.appendChild(tabRowA);
    var tabCellA = document.createElement('td');
    tabCellA.innerHTML = "Update to Map";
    tabRowA.appendChild(tabCellA);
    
    var tabCellB = document.createElement('td');
    tabRowA.appendChild(tabCellB);
    var newNameInput = document.createElement('input');
    newNameInput.setAttribute("type", "text") ;
    var otherID = "topo-map-" + itemID;
    newNameInput.setAttribute("id", otherID ) ;
    newNameInput.value = useTopo;
    tabCellB.appendChild(newNameInput);
    
    
    var tabCellC = document.createElement('td');
    tabCellC.innerHTML = "<a href=\"javascript:topoToOther(" + itemID + ", 'maps', '" + otherID + "')\">Change to Map</a>";
    tabRowA.appendChild(tabCellC);
    
    
    
    
    
    //Deleteting
    var tabRowA = document.createElement('tr');
    editTab.appendChild(tabRowA);
    var tabCellA = document.createElement('td');
    tabCellA.innerHTML = "DELETE toponymn";
    tabRowA.appendChild(tabCellA);
    
    var tabCellB = document.createElement('td');
    tabRowA.appendChild(tabCellB);
    tabCellB.innerHTML = "<em>(error, or duplicated)</em>";
    
    
    var tabCellC = document.createElement('td');
    tabCellC.innerHTML = "<a href=\"javascript:deleteTopo(" + itemID + ")\">DELETE Name</a>";
    tabRowA.appendChild(tabCellC);
    
    
    
    
    //Adding
    var tabRowA = document.createElement('tr');
    editTab.appendChild(tabRowA);
    var tabCellA = document.createElement('td');
    tabCellA.innerHTML = "Add new toponymn to line";
    tabRowA.appendChild(tabCellA);
    
    var tabCellB = document.createElement('td');
    tabRowA.appendChild(tabCellB);
    var newNameInput = document.createElement('input');
    newNameInput.setAttribute("type", "text") ;
    newNameInput.setAttribute("id", ("addTopo-" + itemID) ) ;
    newNameInput.value = useTopo;
    tabCellB.appendChild(newNameInput);
    
    
    var tabCellC = document.createElement('td');
    tabCellC.innerHTML = "<a href=\"javascript:addTopo(" + itemID + ")\">ADD Name</a>";
    tabRowA.appendChild(tabCellC);
    
    
    
    
    var dlgTop = cursorY + 25;
    var dlgLeft = cursorX - 250;
    
    pFloatingPane = new dojox.layout.FloatingPane({
            title: ("Edit Toponymn"),
            resizable: true,
            dockable: false,
            style: "top:" + dlgTop + "px; left:" + dlgLeft + "px;",
            id: "editPane"
        },
        dojo.byId("editPane"));
    
    pFloatingPane.startup();
    pFloatingPane.show();
}


//update a toponymn
function updateTopo(itemID){
    var newInputID = "newTopo-" + itemID;
    var newToponymn = document.getElementById(newInputID).value;
    
    var requestURI = baseURI + "/gane-edit/update-toponymn-name";
    var myAjax = new Ajax.Request(requestURI,
        {method: 'post',
        parameters:
        {
            id: itemID,
            newToponymn: newToponymn
        },
        onComplete: updateTopoDone }
    );
    
}

//finish toponymn updates
function updateTopoDone(response){
    
    var respData = JSON.parse(response.responseText);
    var itemID = respData.id;
    var rawTopo = respData.rawTopo;
    var cleanTopo = respData.cleanTopo;
    var useTopo = cleanTopo;
    
    if(cleanTopo.length < 1){
        useTopo = rawTopo;
    }
    
    var displayValue = document.getElementById(actElementID);
    displayValue.innerHTML  = useTopo;
    
    //close the floating pane 
    dijit.byId("editPane").close();

}



//update a toponymn
function topoToOther(itemID, newType, newValueID){
    
    var newValue = document.getElementById(newValueID).value;
    
    var requestURI = baseURI + "/gane-edit/reclass-toponymn";
    var myAjax = new Ajax.Request(requestURI,
        {method: 'post',
        parameters:
        {
            id: itemID,
            newType: newType,
            newValue: newValue
        },
        onComplete: topoToOtherDone }
    );
    
}

//finish toponymn updates
function topoToOtherDone(response){
    
    var respData = JSON.parse(response.responseText);
   
    if(!respData.error){
        var itemID = respData.id;
        var rawLineID = respData.rawLine;
        var newType = respData.newType;
        var newValue = respData.newValue;
        var newID = respData.newID;
        
        var typeDivID = "vals-" + newType.charAt(0) + "-" + rawLineID;
        var typeDiv = document.getElementById(typeDivID);
        var currentHTML = typeDiv.innerHTML;
        
        var newElemID = newType.charAt(0) + "-" + newID;
        var newJavaScript = "javascript:editValue('" + newType + "', " + newID + ", '" + newElemID + "');";
        var newHTML = "<a id=\""+ newElemID + "\" href=\"" + newJavaScript + "\">"+ newValue + "</a>";
        if(currentHTML.length > 4){
            newHTML = ", " + newHTML;
        }
        
        newHTML = currentHTML + newHTML;
        typeDiv.innerHTML = newHTML;
        
        var displayValue = document.getElementById(actElementID);
        displayValue.setAttribute("style", "text-decoration:line-through;") ;
        
    }
    else{
        alert(respData.error);
    }
    
    //close the floating pane 
    dijit.byId("editPane").close();
}











//delete a toponymn
function deleteTopo(itemID){
    
    var requestURI = baseURI + "/gane-edit/delete-toponymn";
    var myAjax = new Ajax.Request(requestURI,
        {method: 'post',
        parameters:
        {
            id: itemID
        },
        onComplete: deleteTopoDone }
    );
    
}

//finish toponymn deletion
function deleteTopoDone(response){
    
    var respData = JSON.parse(response.responseText);
    var itemID = respData.id;
    var rawTopo = respData.rawTopo;
    var cleanTopo = respData.cleanTopo;
    var useTopo = cleanTopo;
    
    if(cleanTopo.length < 1){
        useTopo = rawTopo;
    }
    
    var displayValue = document.getElementById(actElementID);
    displayValue.setAttribute("style", "text-decoration:line-through;") ;
    
    //close the floating pane 
    dijit.byId("editPane").close();

}


//add a toponymn
function addTopo(itemID){
    var newInputID = "addTopo-" + itemID;
    var newToponymn = document.getElementById(newInputID).value;
    var placeID = document.getElementById("edit-place-id").value;
    
    var requestURI = baseURI + "/gane-edit/add-toponymn";
    var myAjax = new Ajax.Request(requestURI,
        {method: 'post',
        parameters:
        {
            id: itemID,
            placeID: placeID,
            newToponymn: newToponymn
        },
        onComplete: addTopoDone }
    );
    
}

//finish toponymn deletion
function addTopoDone(response){
    
    var respData = JSON.parse(response.responseText);
    var itemID = respData.id;
    var lineID = respData.rawLine;
    var rawTopo = respData.rawTopo;
    var cleanTopo = respData.cleanTopo;
    var useTopo = cleanTopo;
    
    if(cleanTopo.length < 1){
        useTopo = rawTopo;
    }
    
    var lineTopoListDivID = "vals-n-" + lineID;
    var lineTopoListDiv = document.getElementById(lineTopoListDivID);
    
    var commaSpan = document.createElement('span');
    commaSpan.innerHTML = ", ";
    lineTopoListDiv.appendChild(commaSpan);
    
    var linkID = "n-" + itemID;
    var javaLink = "javascript:editValue('names', " + itemID+ ", '" + linkID + "');";
    var topoLink = document.createElement('a');
    topoLink.setAttribute('id', linkID);
    topoLink.setAttribute('href', javaLink);
    topoLink.innerHTML = useTopo;
    lineTopoListDiv.appendChild(topoLink);
    
    //close the floating pane 
    dijit.byId("editPane").close();

}