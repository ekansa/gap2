
/*

START: Edit feature functions

*/

function showMapEdit(itemID){
    
    var linkID = "m-" + itemID;
    var mapVal = document.getElementById(linkID).innerHTML;
    
    var paneArea = document.getElementById("paneArea");
    var pFloatingPane = document.createElement('div');
    pFloatingPane.setAttribute('id', 'editPane');
    paneArea.appendChild(pFloatingPane);
        
    var divA = document.createElement('div');
    pFloatingPane.appendChild(divA);
    
    var instructP = document.createElement('p');
    instructP.innerHTML = "Edit the map code <em>" + mapVal + "</em>";
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
    tabCellB.innerHTML = "Letter";
    tabRowA.appendChild(tabCellB);
    var tabCellBb = document.createElement('th');
    tabCellBb.innerHTML = "Roman";
    tabRowA.appendChild(tabCellBb);
    var tabCellBc = document.createElement('th');
    tabCellBc.innerHTML = "Number";
    tabRowA.appendChild(tabCellBc);
    var tabCellC = document.createElement('th');
    tabCellC.innerHTML = "Commit Link";
    tabRowA.appendChild(tabCellC);
    
    //update
    var tabRowA = document.createElement('tr');
    editTab.appendChild(tabRowA);
    var tabCellA = document.createElement('td');
    tabCellA.innerHTML = "Delete map";
    tabRowA.appendChild(tabCellA);
    
    var tabCellB = document.createElement('td');
    tabRowA.appendChild(tabCellB);
    tabCellB.innerHTML = "<em>(error, or duplicated)</em>";
    /*
    var newNameInput = document.createElement('input');
    newNameInput.setAttribute("type", "text") ;
    newNameInput.setAttribute("id", ("upFeature-" + itemID) ) ;
    newNameInput.value = useTopo;
    tabCellB.appendChild(newNameInput);
    */
    
    var tabCellC = document.createElement('td');
    tabCellC.innerHTML = "<a href=\"javascript:deleteRecord('maps', " + itemID + ")\">Delete Map</a>";
    tabRowA.appendChild(tabCellC);
    
    
    var dlgTop = cursorY + 25;
    var dlgLeft = cursorX - 250;
    
    pFloatingPane = new dojox.layout.FloatingPane({
            title: ("Edit Map Reference"),
            resizable: true,
            dockable: false,
            style: "top:" + dlgTop + "px; left:" + dlgLeft + "px;",
            id: "editPane"
        },
        dojo.byId("editPane"));
    
    pFloatingPane.startup();
    pFloatingPane.show();
}
