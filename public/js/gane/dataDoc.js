
/*

START: Field / Column Documentation 
These are functions used for documenting and describing different fields / columns

*/

var baseURI = "http://issues.opencontext.org/plugin.php";
var GRprojectID = "";
var numberColumns;
var userID;
var numberRequiredFields;

function getGrProjectID(){
    GRprojectID = document.getElementById("GRprojectID").value; //project id in hidden input element
    numberColumns = document.getElementById("numColumns").value; //number columns in hidden input element
    userID = document.getElementById("userID").value; //User-id columns in hidden input element
}

function addNote(noteElementID, colIndex){
    
    getGrProjectID();
    
    var textNote = document.getElementById(noteElementID).value;
    var myAjax = new Ajax.Request(baseURI,
        {method: 'post',
        parameters:
        {
            page: "DataRefine/data-doc-update-note",
            colIndex: colIndex,
            grProject: GRprojectID,
            note: textNote 
        },
        onComplete: addNoteDone }
    );
    
    
}

function addNoteDone(response){
    
  
    var respData = JSON.parse(response.responseText);
    var colIndex = respData.colIndex;

    var addedNote = document.getElementById((colIndex + "_col_note"));
    addedNote.setAttribute("class", "dataDocTextAreaUp") ;
    
    var docRemaining = document.getElementById("docRemaining");
    var oldRemaining = docRemaining.innerHTML;
    docRemaining.innerHTML = oldRemaining - 1;
    
    var noteCreator = document.getElementById((colIndex + "_col_noteCreator"));
    noteCreator.innerHTML = "Note created by: " + userID + " (you!)";
    
    var skipHide = document.getElementById((colIndex + "_no_note_needed"));
    skipHide.innerHTML = "";
    
    //jump focus
    var nextNoteIndex = colIndex + 1;
    if(nextNoteIndex < numberColumns){
        var jumpElementID = nextNoteIndex + "_col_note";
        var nextElement = document.getElementById(jumpElementID);
    }
    else{
        var nextElement = document.getElementById("0_col_note");
    }
    
    nextElement.focus();
}


function skipNote(colIndex, colName){
    
    getGrProjectID();
    
    var myAjax = new Ajax.Request(baseURI,
        {method: 'post',
        parameters:
        {
            page: "DataRefine/data-doc-update-skip-note",
            colIndex: colIndex,
            grProject: GRprojectID
        },
        onComplete: skipNoteDone }
    );
    
    
}

function skipNoteDone(response){
    
    var respData = JSON.parse(response.responseText);
    var colIndex = respData.colIndex;

    var skipNote = document.getElementById((colIndex + "_col_note"));
    skipNote.setAttribute("class", "dataDocTextAreaSkip") ;
    skipNote.value = "[Skipped]";
    
    var docRemaining = document.getElementById("docRemaining");
    var oldRemaining = docRemaining.innerHTML;
    docRemaining.innerHTML = oldRemaining - 1;
    
    var skipControl = document.getElementById((colIndex + "_no_note_needed"));
    skipControl.innerHTML = "";
    
    var noteCreator = document.getElementById((colIndex + "_col_noteCreator"));
    noteCreator.innerHTML = "Skip column documentation approved by: " + userID;
    
    //jump focus
    var nextNoteIndex = colIndex + 1;
    if(nextNoteIndex < numberColumns){
        var jumpElementID = nextNoteIndex + "_col_note";
        var nextElement = document.getElementById(jumpElementID);
    }
    else{
        var nextElement = document.getElementById("0_col_note");
    }
    
    nextElement.focus();
}


//AJAX request to get sample values for a field.
function getSampleValues(colIndex, colName){
    
    getGrProjectID();
    
    var myAjax = new Ajax.Request(baseURI,
        {method: 'get',
        parameters:
        {
            page: "DataRefine/data-doc-get-sample-values",
            colIndex: colIndex,
            grProject: GRprojectID,
            colName: colName 
        },
        onComplete: addSampleValues }
    );
    
}


function addSampleValues(response){
    
    //alert("Here I am!");
    
    var respData = JSON.parse(response.responseText);
    var colIndex = respData.colIndex;
    var valDiv = document.getElementById((colIndex + "_sample_val"));
    
    var controlA = document.getElementById((colIndex + "_sample_val_cont"));
    controlA.innerHTML = "";
    
    var numCount = respData.colNumeric.facets[0].numericCount;
    var textCount = respData.colNumeric.facets[0].nonNumericCount;
    
    var outputHTML = "";
    
    if(textCount < numCount){
        outputHTML = outputHTML + "<strong>Minimum: " + respData.colNumeric.facets[0].min + "</strong>";
        outputHTML = outputHTML + "<br/><strong>Maximum: " + respData.colNumeric.facets[0].max + "</strong><br/>";
    }
    
    
    var showText = true;
    if(showText){
        var fChoices = respData.colText.facets[0].choices;
        var arrayLen = fChoices.length;
        if(arrayLen > 5){
            arrayLen = 5;
        }
        
        for(var ii=0; ii < arrayLen; ii++) {
            var actText = fChoices[ii].v.l;
            var actTextCount = fChoices[ii].c;
            
            if(ii>0){
                outputHTML = outputHTML + ", ";
            }
            
            outputHTML = outputHTML + "<em>" + actText + "</em> (" + actTextCount + ")";
        }
    }
    
    
    outputHTML = outputHTML + "<br/><br/>[Numeric: " + numCount + "]";
    outputHTML = outputHTML + " [Text: " + textCount + "]";
    outputHTML = outputHTML + " [Blanks: " + respData.colNumeric.facets[0].blankCount + "]";
    
    valDiv.innerHTML = outputHTML;
}



/*
BEGIN code for applying measurement standards to a column
*/

var pFloatingPane; //object for the Dojo floating pane widget
var colIndex; //index number of the column
var colName; //name of the column being worked
var unitTypes; //array of measurement unit types, populated via AJAX response
function displayUnitChoices(colIndexV, colNameV){
    
    colIndex = colIndexV;
    colName = colNameV;
    if (typeof pFloatingPane != 'undefined'){
       pFloatingPane.destroyRecursive(); //destroys old floating pane, so no conflict in making new
    }
    
    getMeasurementStandards(); //ajax query to get JSON of different measurement standards
}


function getMeasurementStandards(){
    //gets JSON data of measurement standards available to use
    
    var myAjax = new Ajax.Request(baseURI,
        {method: 'get',
        parameters:
        {
            page: "DataRefine/data-doc-get-measure-standards-json"
        },
        onComplete: createMeasurementTabs }
    );
    
}

function createMeasurementTabs(response){
    
    var respData = JSON.parse(response.responseText); 
    
    //create the floating pane first
    var paneArea = document.getElementById("paneArea");
    var pFloatingPane = document.createElement('div');
    pFloatingPane.setAttribute('id', 'measurementUnitsPane');
    paneArea.appendChild(pFloatingPane);
        
    var beyondTabs = document.createElement('div');
    pFloatingPane.appendChild(beyondTabs);
    
    var instructP = document.createElement('p');
    instructP.innerHTML = "Select a measurement unit for column <strong>" + colName + "</strong> of a type indicated on the tabs below.";
    beyondTabs.appendChild(instructP); 
    
    //create the container for the tabs
    var allTabs = document.createElement('div');
    allTabs.setAttribute('id', 'tabber');
    allTabs.setAttribute('class', 'tabber');
    beyondTabs.appendChild(allTabs);
    
    unitTypes = new Array();
    
    var numTypes = respData.standards.length;
    
    for(var ii=0; ii < numTypes; ii++) {
        
        var actType = respData.standards[ii];
        var typeName = actType.sType;
        unitTypes[ii] = typeName; //save for use in 
        
        var newTab = document.createElement('div');
        newTab.setAttribute('class', 'tabbertab');
        newTab.setAttribute('title', typeName);
        
        var numUnits = actType.units.length;
        
        var unitList = document.createElement('ul');
        unitList.setAttribute('class', 'unitList');
        
        for(var jj= 0; jj < numUnits; jj++){
            var actUnit = actType.units[jj];
            
            var unitInnerHTML = "<strong><a id='"+ii+"_"+jj+"_unitName' title='Indicate column -"+ colName +"- is in " + actUnit.name + " units' href='javascript:addMeasurementUnit(" + ii + ", " + jj + ", " + colIndex + ")' >" + actUnit.name + "</a></strong>";
            unitInnerHTML = unitInnerHTML + " (" + actUnit.abrv + ") ";
            unitInnerHTML = unitInnerHTML + " <br/><em>Defined at: <a id='"+ii+"_"+jj+"_unitURI' href='" + actUnit.uri + "'>" + actUnit.uri + "</a>";
            
            var unitListItem = document.createElement('li');
            unitListItem.setAttribute('class', 'unitListItem');
            unitListItem.innerHTML = unitInnerHTML;
            unitList.appendChild(unitListItem);
        }

        newTab.appendChild(unitList);
        allTabs.appendChild(newTab);
    }

    tabberAutomatic(); //use the tabber.js functions to ready DIV tabs
    
    pFloatingPane = new dojox.layout.FloatingPane({
            title: ("Add Measurement Units to '"+colName+"'"),
            resizable: true,
            dockable: false,
            id: "measurementUnitsPane"
        },
        dojo.byId("measurementUnitsPane"));
    
    pFloatingPane.startup();
    pFloatingPane.show();

}


//AJAX call to add measurement units to a column
function addMeasurementUnit(typeIndex, unitIndex, colIndexVar){
   
     //close the floating pane 
    dijit.byId('measurementUnitsPane').close();
    
    //get basic project data
    getGrProjectID();
   
    var unitNameElement = document.getElementById(typeIndex + "_" + unitIndex + "_unitName");
    var unitName = unitNameElement.innerHTML;
    
    var unitURIElement = document.getElementById(typeIndex + "_" + unitIndex + "_unitURI");
    var unitURI = unitURIElement.innerHTML;
   
    var unitType = unitTypes[typeIndex];
   
    var myAjax = new Ajax.Request(baseURI,
         {method: 'post',
         parameters:
         {
             page: "DataRefine/data-doc-update-measure-unit",
             colIndex: colIndexVar,
             grProject: GRprojectID,
             unitType: unitType,
             unitName: unitName,
             unitURI: unitURI
         },
         onComplete: addMeasurementUnitDone }
     );
    
}


//finished with the adding measurements units, show results
function addMeasurementUnitDone(response){
    var respData = JSON.parse(response.responseText);
    var colIndex = respData.colIndex;
    
    var unitDisplay = document.getElementById(colIndex + "_show_units");
    unitDisplay.setAttribute('class', 'show_units');
    
    var shortURI = respData.addedUnits.unitURI.substr(0,20);
    
    var unitHTML = "<strong>" + respData.addedUnits.unitName + "</strong> [" + respData.addedUnits.unitType + "]<br/>";
    unitHTML = unitHTML + "Defined at: <em><a href=\"" + respData.addedUnits.unitURI + "\">" + shortURI + "...</a></em><br/>";
    unitHTML = unitHTML + "<div id=\"" + colIndex + "_col_unitCreator\" class=\"noteCreator\">Assignment by: " + respData.addedUnits.noteCreator + "</div>";
    
    unitDisplay.innerHTML = unitHTML;
    
    var remUnits = document.getElementById(colIndex + "_rem_units");
    remUnits.innerHTML = "<br/><a href=\"javascript:removeUnitAssignment(" + colIndex + ", '" + colName +"')\">Remove unit of measurement</a>";
    
}


//ajax query to remove measurement units from a column
function removeUnitAssignment(colIndexV, colNameV){
    
    //get basic project data
    getGrProjectID();
    
    colIndex = colIndexV;
    colName = colNameV;
    var unitDisplay = document.getElementById(colIndex + "_show_units");
    unitDisplay.setAttribute('class', 'add_units');
    unitDisplay.innerHTML = "<a href=\"javascript:displayUnitChoices("+colIndex+", '"+ colName +"')\">Units of measurement</a><br/>(if needed)";
    
    var myAjax = new Ajax.Request(baseURI,
         {method: 'post',
         parameters:
         {
             page: "DataRefine/data-doc-update-measure-unit",
             colIndex: colIndexV,
             grProject: GRprojectID,
             unitType: 0,
             unitName: 0,
             unitURI: 0
         },
         onComplete: removeUnitAssignmentDone }
     );
}


//response to removing measurment unit assignment
function removeUnitAssignmentDone(response){
    
    var remUnits = document.getElementById(colIndex + "_rem_units");
    remUnits.innerHTML = "(Unit removed by: " + userID + ")";
    
}

/*
END code for applying measurement standards to a column
*/



//get data about linked data standards.
function displayStandardsChoices(colIndexV, colNameV){
    
    colIndex = colIndexV;
    colName = colNameV;
    if (typeof pFloatingPane != 'undefined'){
       pFloatingPane.destroyRecursive(); //destroys old floating pane, so no conflict in making new
    }
    
    getLinkedDataStandards(); //ajax query to get JSON of different linked-data standards
}


function getLinkedDataStandards(){
    //gets JSON data of measurement standards available to use
    
    var myAjax = new Ajax.Request(baseURI,
        {method: 'get',
        parameters:
        {
            page: "DataRefine/data-doc-get-linked-data-standards-json"
        },
        onComplete: createLinkedDataTabs }
    );
    
}

var vocabData;

function createLinkedDataTabs(response){
    
    var respData = JSON.parse(response.responseText); 
    
    //create the floating pane first
    var paneArea = document.getElementById("paneArea");
    var pFloatingPane = document.createElement('div');
    pFloatingPane.setAttribute('id', 'standardsPane');
    paneArea.appendChild(pFloatingPane);
        
    var beyondTabs = document.createElement('div');
    pFloatingPane.appendChild(beyondTabs);
    
    var instructP = document.createElement('p');
    instructP.innerHTML = "Select a vocabulary for column <strong>" + colName + "</strong> of a type indicated on the tabs below.";
    beyondTabs.appendChild(instructP); 
    
    //create the container for the tabs
    var allTabs = document.createElement('div');
    allTabs.setAttribute('id', 'tabber');
    allTabs.setAttribute('class', 'tabber');
    beyondTabs.appendChild(allTabs);
    
    vocabData = respData.vocabs;
    
    var numTypes = respData.vocabs.length;
    
    for(var ii=0; ii < numTypes; ii++) {
        
        var actType = respData.vocabs[ii];
        var typeName = actType.name;
        
        var newTab = document.createElement('div');
        newTab.setAttribute('class', 'tabbertab');
        newTab.setAttribute('title', typeName);
        
        var numProps = actType.props.length;
        
        var unitList = document.createElement('ul');
        unitList.setAttribute('class', 'unitList');
        
        for(var jj= 0; jj < numProps; jj++){
            
            
            var actProp = actType.props[jj];
            
            //actConName[jj] =  actProp.name;
            //actConURIs[jj] = actProp.uri;
            
            var unitInnerHTML = "<strong><a id='"+ii+"_"+jj+"_unitName' title='Indicate column -"+ colName +"- equates with the " + typeName + "::" + actProp.name + " concept' href='javascript:addLinkedDataStandard(" + ii + ", " + jj + ", " + colIndex + ")' >" +  actProp.name + "</a></strong>";
            unitInnerHTML = unitInnerHTML + " <br/><em>Defined at: <a id='"+ii+"_"+jj+"_unitURI' href='" + actProp.uri + "'>" + actProp.uri + "</a>";
            
            var unitListItem = document.createElement('li');
            unitListItem.setAttribute('class', 'unitListItem');
            unitListItem.innerHTML = unitInnerHTML;
            unitList.appendChild(unitListItem);
            
        }

        newTab.appendChild(unitList);
        allTabs.appendChild(newTab);
    }

    tabberAutomatic(); //use the tabber.js functions to ready DIV tabs
    
    pFloatingPane = new dojox.layout.FloatingPane({
            title: ("Add Linked Data Vocabulary to '"+colName+"'"),
            resizable: true,
            dockable: false,
            id: "standardsPane"
        },
        dojo.byId("standardsPane"));
    
    pFloatingPane.startup();
    pFloatingPane.show();


}

//formulate AJAX call to add concept link to a field
function addLinkedDataStandard(vocabIndex, conceptIndex, colIndexVar){
    
     //close the floating pane 
    dijit.byId('standardsPane').close();
    
    //get basic project data
    getGrProjectID();
   
    var actVocab = vocabData[vocabIndex];
    var actProp = actVocab.props[conceptIndex];
   
    var myAjax = new Ajax.Request(baseURI,
         {method: 'post',
         parameters:
         {
             page: "DataRefine/data-doc-update-field-concept",
             colIndex: colIndexVar,
             grProject: GRprojectID,
             vocabName: actVocab.name,
             vocabURI: actVocab.uri,
             conceptName: actProp.name,
             conceptURI: actProp.uri
         },
         onComplete: addLinkedDataStandardDone }
     );
    
}



function addLinkedDataStandardDone(response){

    var respData = JSON.parse(response.responseText);
    var colIndex = respData.colIndex;
    
    var conceptDisplay = document.getElementById(colIndex + "_show_concepts");
    conceptDisplay.setAttribute('class', 'show_concepts');
    
    var shortConceptURI = respData.addedConcept.conceptURI.substr(0,20);
    
    var conHTML = "<strong>" + respData.addedConcept.conceptName + "</strong> [" + respData.addedConcept.vocabName + "]<br/>";
    conHTML = conHTML + "Defined at: <em><a href=\"" + respData.addedConcept.conceptURI + "\">" + shortConceptURI + "...</a></em><br/>";
    conHTML = conHTML + "<div id=\"" + colIndex + "_col_conceptCreator\" class=\"noteCreator\">Assignment by: " + respData.addedConcept.noteCreator + "</div>";
    
    conceptDisplay.innerHTML = conHTML;
    
    var remCon = document.getElementById(colIndex + "_rem_concepts");
    remCon.innerHTML = "<br/><a href=\"javascript:removeConceptAssignment(" + colIndex + ", '" + colName +"')\">Remove concept assignment</a>";
    
}//end function




function removeConceptAssignment(colIndexV, colNameV){
    
    //get basic project data
    getGrProjectID();
    
    colIndex = colIndexV;
    colName = colNameV;
    var conceptDisplay = document.getElementById(colIndex + "_show_concepts");
    conceptDisplay.setAttribute('class', 'add_units');
    conceptDisplay.innerHTML = "<a href=\"javascript:displayStandardsChoices("+colIndex+", '"+ colName +"')\">Linked Data Standards</a><br/>(if needed)";
    
    var myAjax = new Ajax.Request(baseURI,
         {method: 'post',
         parameters:
         {
             page: "DataRefine/data-doc-update-field-concept",
             colIndex: colIndexV,
             grProject: GRprojectID,
             vocabName: false,
             vocabURI: false,
             conceptName: false,
             conceptURI: false
         },
         onComplete: removeConceptAssignmentDone }
     );
}

function removeConceptAssignmentDone(response){
    
    var remCon = document.getElementById(colIndex + "_rem_concepts");
    remCon.innerHTML = "(Concept removed by: " + userID + ")";
    
}

/*

END: Field / Column Documentation 
We're done adding functions used for documenting and describing different fields / columns

*/





/*

START: Adding General Descriptive Metadata 
These functions give users funcitonality for adding, editing, and approving general (dataset-level) descriptive metadata

*/

function meta_getGrProjectID(){
    GRprojectID = document.getElementById("GRprojectID").value; //project id in hidden input element
    numberRequiredFields = document.getElementById("reqFieldsTotal").value; //number Reequired metadata fields in hidden input element
    userID = document.getElementById("userID").value; //User-id columns in hidden input element
}

var lastUpdatedInput;
function addFieldValue(noteElementID, fieldKey, valIndex){
    
    // Adds user submitted data to a table descriptive metadata field
    lastUpdatedInput = noteElementID;
    meta_getGrProjectID();
    var textValue = document.getElementById(noteElementID).value;
    var myAjax = new Ajax.Request(baseURI,
        {method: 'post',
        parameters:
        {
            page: "DataRefine/data-doc-update-tab-field",
            fieldKey: fieldKey,
            valIndex: valIndex,
            grProject: GRprojectID,
            value: textValue
        },
        onComplete: addFieldValueDone }
    );
    
    
}


function addFieldValueDone(response){
      
    // Finish adding user submitted data to a table descriptive metadata field
    var respData = JSON.parse(response.responseText);
    var valIndex = respData.valIndex;
    var fieldKey = respData.fieldKey;

    var addedValue = document.getElementById(lastUpdatedInput);
    addedValue.setAttribute("class", (respData.type + "_up")); //change class to reflect it's updated
    
    //add note about who made the update    
    var noteCreatorDOMid = "field_noteCreator_"+ fieldKey + "_" + valIndex;
    var noteCreator = document.getElementById(noteCreatorDOMid);
    noteCreator.innerHTML = "Documented by: " + userID + " (you!)";

    //add control for removing (deleting) the update
    var valComDOMid = "field_val_com_" + fieldKey + "_" + valIndex;
    var comDivNode = document.getElementById(valComDOMid);
    comDivNode.innerHTML = "";
    var remJavaScript = "javascript:remMultiValue(this.id, '" + fieldKey + "', " + valIndex + ")";
    var remDOMid = "val_multiRemove_" + fieldKey + "_" + valIndex;
    var remNode = document.createElement('a');
    remNode.setAttribute("id", remDOMid);
    remNode.setAttribute("class", "rem_multivalue");
    remNode.setAttribute("href", remJavaScript);
    remNode.innerHTML = "Remove documentation";
    comDivNode.appendChild(remNode);
    
    //show number of completed required fields
    var docDone = document.getElementById("show_reqFieldsCompleted");
    docDone.innerHTML = respData.reqCompleted;
    
}

function remMultiValue(noteElementID, fieldKey, valIndex){
    
    // Adds user submitted data to a table descriptive metadata field
    meta_getGrProjectID();
    
    var myAjax = new Ajax.Request(baseURI,
        {method: 'post',
        parameters:
        {
            page: "DataRefine/data-doc-update-tab-field",
            fieldKey: fieldKey,
            valIndex: valIndex,
            grProject: GRprojectID,
            value: ""
        },
        onComplete: remMultiValueDone }
    );
       
}

function remMultiValueDone(response){
      
    // Finish adding user submitted data to a table descriptive metadata field
    var respData = JSON.parse(response.responseText);
    var valIndex = respData.valIndex;
    var fieldKey = respData.fieldKey;

    var remLinkDOMid = "val_multiRemove_"+ fieldKey + "_" + valIndex;
    var remLinkDom = document.getElementById(remLinkDOMid);
    remLinkDom.innerHTML = "";
    
    if(valIndex == 0){
        var textAreaDOMid = "field_val_"+ fieldKey + "_" + valIndex;
        var remValue = document.getElementById(textAreaDOMid);
        remValue.setAttribute("class", (respData.type)); //change class to reflect it's updated
        remValue.value = "";
        
        var noteCreatorDOMid = "field_noteCreator_"+ fieldKey + "_" + valIndex;
        var noteCreator = document.getElementById(noteCreatorDOMid);
        noteCreator.innerHTML = "Documentation deleted by: " + userID + " (you!)";
        
        //delete remove control
        var valComDOMid = "field_val_com_" + fieldKey + "_" + valIndex;
        var comDivNode = document.getElementById(valComDOMid);
        comDivNode.innerHTML = "";
    }
    else{
        var fieldDivTab = document.getElementById("field_tab_" + fieldKey);
        
        var oldDivRowDOMid = "field_DivRow_" + fieldKey + "_" + valIndex;
        var oldDivRow = document.getElementById(oldDivRowDOMid);
        oldDivRow.innerHTML = "";
        fieldDivTab.removeChild(oldDivRow);
    }
    
    var docDone = document.getElementById("show_reqFieldsCompleted");
    docDone.innerHTML = respData.reqCompleted;
}



function addMultiValue(cmdElementID, fieldType, fieldKey, valIndex){
    
    
    var existingText = document.getElementById("field_val_" + fieldKey + "_" + valIndex);
    if(existingText.value.length > 0){
        
        var fieldDivTab = document.getElementById("field_tab_" + fieldKey);
        
        valIndex = valIndex + 1;
        
        //make a div "row" for the next field value
        var newDivRowDOMid = "field_DivRow_" + fieldKey + "_" + valIndex;
        var newDivRow = document.createElement('div');
        newDivRow.setAttribute("id", newDivRowDOMid);
        newDivRow.setAttribute("class", "div_val_row");
        fieldDivTab.appendChild(newDivRow);
        
        //make a div "table cell" for the next field value input area
        var newDivValCell = document.createElement('div');
        newDivValCell.setAttribute("class", "div_val_cell");
        newDivRow.appendChild(newDivValCell);
        
        //make a new text input area
        var inputJavaScript = "addFieldValue(this.id, '" + fieldKey + "', " + valIndex + ")";
        var newTextInputDOMid = "field_val_" + fieldKey + "_" + valIndex;
        var newTextInput = document.createElement('textarea');
        newTextInput.setAttribute("id", newTextInputDOMid);
        newTextInput.setAttribute("class", fieldType);
        newTextInput.setAttribute("onChange", inputJavaScript);
        newDivValCell.appendChild(newTextInput);
        
        //add spacer
        var newSpacer = document.createElement('br');
        newDivValCell.appendChild(newSpacer);
        
        //add note creator div
        var noteCreatorDOMid = "field_noteCreator_" + fieldKey + "_" + valIndex;
        var newNoteCreatorDiv = document.createElement('div');
        newNoteCreatorDiv.setAttribute("id", noteCreatorDOMid);
        newNoteCreatorDiv.setAttribute("class", "noteCreator");
        newDivValCell.appendChild(newNoteCreatorDiv);
        
        //make a div "table cell" for the next field value commands / control area
        var newDivValContCell = document.createElement('div');
        newDivValContCell.setAttribute("class", "div_val_cont_cell");
        newDivRow.appendChild(newDivValContCell);
        
        //update the add new link with the new higher index number
        var addNewJavascript = "javascript:addMultiValue(this.id, '" + fieldType + "', '" + fieldKey + "', " + valIndex + ")";
        var newAddAnchor = document.getElementById("addMulti_" + fieldKey);
        newAddAnchor.setAttribute("href", addNewJavascript);
        
    }
    
}//end function




/*

End: Adding General Descriptive Metadata 
These functions give users funcitonality for adding, editing, and approving general (dataset-level) descriptive metadata

*/





/*

START: Adding new Google Refine Projects (datasets) the Issue Tracker 
These functions give users control over adding new Google Refine projects to the Issue Tracker

*/


//for the main project page

function trackNewProject(GRprojectIDv){
    //gets JSON data of measurement standards available to use
    
    GRprojectID = GRprojectIDv; //set global var for later use
    var myAjax = new Ajax.Request(baseURI,
        {method: 'post',
        parameters:
        {
            page: "DataRefine/data-doc-track-project",
            grProject: GRprojectIDv
        },
        onComplete: trackNewProjectDone }
    );
    
}

function trackNewProjectDone(response){

    var respData = JSON.parse(response.responseText);
    
    if(respData.success == true){
        var listHTML = "<a href=\"../plugin.php?page=DataRefine/data-doc-summary&grProject=" + respData.grProject + "\"><strong>" + respData.grData.name + "</strong></a>";
    
        var oldList = document.getElementById( "grProjects");
        var oldID =  document.getElementById( respData.grProject + "_ut");
        oldList.removeChild(oldID);
    
        var addToList =  document.getElementById("trackedProjects");
        var addListItem = document.createElement('li');
        addListItem.innerHTML = listHTML;
        addToList.appendChild(addListItem);
        
        var oldCountNode = document.getElementById("grNotTrackCount");
        var oldCount = oldCountNode.innerHTML;
        oldCountNode.innerHTML = oldCount - 1;
    }
    else{
        if(respData.noCurProject){
            alert("Select a project in the Issue Tracker first, so this Google Refine dataset can be included with other project issues.");
        }
        else{
            alert("Something when wrong!" + response.responseText);   
        }
    }
    
}


/*
END: Adding new Google Refine Projects (datasets) the Issue Tracker 
*/






/*

START: Entity Reconciliation

*/

var legacyParams; //global to store legacy parameters, just in case
var reconKey;
var testing = false; //set to "test" if you want to just go through the motions
function openReconDialogue(reconKeySel){
    
    testing = document.getElementById( "reconTesting").value; //set global var for later use
    reconKey = reconKeySel;
    if (typeof pFloatingPane != 'undefined'){
       pFloatingPane.destroyRecursive(); //destroys old floating pane, so no conflict in making new
    }
    
    getColData();
}

function getColData(){
    
    legacyParams = false;
    GRprojectID = document.getElementById( "GRprojectID").value; //set global var for later use
    var myAjax = new Ajax.Request(baseURI,
        {method: 'get',
        parameters:
        {
            page: "DataRefine/data-doc-col-reconcile-cols",
            reconKey: reconKey,
            grProject: GRprojectID
        },
        onComplete: showColsToRecon }
    );
    
}



var reconName;
function showColsToRecon(response){
    
    var respData = JSON.parse(response.responseText);
    
    if(!respData.oldProcessParams){ //no pending processes
        //create the floating pane first
        var paneArea = document.getElementById("paneArea");
        var pFloatingPane = document.createElement('div');
        pFloatingPane.setAttribute('id', 'reconColsPane');
        paneArea.appendChild(pFloatingPane);
            
        var instuctArea = document.createElement('div');
        pFloatingPane.appendChild(instuctArea);
        
        var instructP = document.createElement('p');
  
        if(!testing){
            instructP.innerHTML = "Select a column below to reconcile with: <strong>" + respData.service.title + "</strong>";
        }
        else{
            instructP.innerHTML = "Select a column below to reconcile with: <strong>" + respData.service.title + " [Test / Demo Only]</strong>";
        }
        
        instuctArea.appendChild(instructP);        
        reconName = respData.service.title ;
        
        var colArea = document.createElement('div');
        pFloatingPane.appendChild(colArea);
        
        var colP = document.createElement('p');
        var colList = "";
        
        var cols = respData.cols;
        var numCols = cols.length;
        for(var ii=0; ii < numCols; ii++) {
            
            var newColLink = "<a href=\"javascript:ConfirmReconStart('" + cols[ii].name + "', '" + cols[ii].cellIndex + "');\">" + cols[ii].name + "</a>";
            
            if(ii > 0){
                colList = colList + ", " + newColLink;    
            }
            else{
                colList = newColLink;
            }
        
        }
        
        colP.innerHTML = colList;
        colArea.appendChild(colP);
    }
    else{
        //give some options for dealing with old processes
        displayOldProcessOptions(respData.oldProcessParams);
    }
    
    pFloatingPane = new dojox.layout.FloatingPane({
            title: ("Reconcile a Column with " + respData.service.title),
            resizable: true,
            dockable: false,
            id: "reconColsPane",
            style: "width:40%; position:absolute; top:45%; left:25%;"
        },
        dojo.byId("reconColsPane"));
    pFloatingPane.startup();
    pFloatingPane.show();

}


function ConfirmReconStart(colNameSet, colIndexSet){
    
    colName = colNameSet;
    colIndex = colIndexSet;
    
    //var answer = confirm("Reconcile '" + colName + "' using '" + reconName + "' ? \r\n(Choose carefully, the reconcilation process can take some time.)");
    //if(anwser){
    if(confirm("Reconcile '" + colName + "' using '" + reconName + "' ? \r\n(Choose carefully, the reconcilation process can take some time.)")){
        dijit.byId('reconColsPane').close();
        ReconStartPrep();
    }
    else{
        //do nothing;
    }
    
}


function ReconStartPrep(){
    
    if (typeof pFloatingPane != 'undefined'){
        pFloatingPane.destroyRecursive(); //destroys old floating pane, so no conflict in making new
    }
    //alert('You chose.... poorly, again.');
    ReconStart(); //start reconciling
}


//start Google Refine on the reconcilation path
function ReconStart(){
    
    GRprojectID = document.getElementById( "GRprojectID").value; //set global var for later use
    var myAjax = new Ajax.Request(baseURI,
        {method: 'post',
        parameters:
        {
            page: "DataRefine/data-doc-col-reconcile-start",
            reconKey: reconKey,
            baseColumnName: colName,
            columnInsertIndex: colIndex,
            test: testing,
            grProject: GRprojectID
        },
        onComplete: ReconStartShowProgress }
    );
    
}


var reconProgress;
var finishIndex; //variable for the index of the column to create in Google Refine from raw data returned from recon service
var cancelProcess;
function ReconStartShowProgress(response){

    finishIndex = 1;
    reconProgress = 0;
    cancelProcess = false;
    var respData = JSON.parse(response.responseText);
    
    if(respData.refineOK){
        
        //create the floating pane first
        var paneArea = document.getElementById("paneArea");
        var pFloatingPane = document.createElement('div');
        pFloatingPane.setAttribute('id', 'reconProgressPane');
        paneArea.appendChild(pFloatingPane);
            
        var instuctArea = document.createElement('div');
        pFloatingPane.appendChild(instuctArea);
        
        var instructP = document.createElement('p');
        instructP.innerHTML = "Progress toward reconciling column '" + colName + "' with: <strong>" + reconName + "</strong>";
        instuctArea.appendChild(instructP);
    
        
        var progArea = document.createElement('div');
        progArea.setAttribute('class', 'sum-div-cell-bar');
        pFloatingPane.appendChild(progArea);
        
        var progBar = document.createElement('div');
        progBar.setAttribute('id', 'reconProgBar');
        progArea.appendChild(progBar);
    
        var bottomArea = document.createElement('div');
        bottomArea.setAttribute('style', 'padding-top:5px; padding-bottom:5px;');
        pFloatingPane.appendChild(bottomArea);
        
        var messageArea = document.createElement('p');
        messageArea.setAttribute('id', 'reconProgMessage');
        messageArea.innerHTML = "[<a href=\"javascript:ReconCancelConfirm();\">CANCEL this process</a>]";
        bottomArea.appendChild(messageArea);
    
    
        pFloatingPane = new dojox.layout.FloatingPane({
                title: ("Progress Reconciling '" + colName + "' with " + reconName),
                resizable: true,
                dockable: false,
                id: "reconProgressPane",
                style: "width:40%; position:absolute; top:50%; left:30%;"
            },
            dojo.byId("reconProgressPane"));
        pFloatingPane.startup();
        pFloatingPane.show();
        reconCheckProgress();
    }
    else{
        alert(respData.error);
    }

}



function reconCheckProgress(){

    if(reconProgress >= 100){
        reconProgress = 100;
    }
    
    var progBar = document.getElementById( "reconProgBar");
    var maxWidth = 490;
    var currentWidth = Math.round( maxWidth * (reconProgress / 100 )  );
    
    if(currentWidth < 1){
        currentWidth = 1;
    }
    
    
    var barStyle = "margin-left:5px; margin-right:5px; height: 26px; text-align:center; padding-top:12px; background-color: #DCDCDC; width: " + currentWidth + "px;"
    progBar.setAttribute('style', barStyle);
    progBar.innerHTML = reconProgress + "% ";
    
    if(reconProgress < 100 || finishIndex > 0){
        //try checking the status and finalizing the entity recon by asking Google Refine to parse raw results from the service 
       
        if(finishIndex > 1){
            var messageArea = document.getElementById( "reconProgMessage");
            messageArea.innerHTML = "DONE ! Making result column (" + finishIndex + ")....";
        }
       
        if(!cancelProcess){
            ReconCheckFinish();
        }
    }
    else{
        //do the final cleanup
        var messageArea = document.getElementById( "reconProgMessage");
        messageArea.innerHTML = "DONE ! Clean-up pending....";
        ReconCheckCleanUp();
    }
    
}




function displayOldProcessOptions(oldProcessParams){
    
    legacyParams = oldProcessParams;
    
    var paneArea = document.getElementById("paneArea");
    var pFloatingPane = document.createElement('div');
    pFloatingPane.setAttribute('id', 'reconColsPane');
    paneArea.appendChild(pFloatingPane);
        
    var instuctArea = document.createElement('div');
    pFloatingPane.appendChild(instuctArea);
    
    var instructP = document.createElement('p');
    var oldNote =  "There was an incomplete attempt to reconcile the column: <strong>" + oldProcessParams.baseColumnName + "</strong>";
    instructP.innerHTML = oldNote;
    instuctArea.appendChild(instructP);
    
    var opsList = document.createElement('ul');
    opsList.setAttribute('id', 'legacyOps');
    var opA = document.createElement('li');
    opA.innerHTML = "[<a href=\"javascript:ReconCancelConfirm();\">CANCEL this process</a>]";
    opsList.appendChild(opA);
    
    var opB = document.createElement('li');
    opB.innerHTML = "[<a href=\"javascript:AttemptReconFinish();\">Attempt to COMPLETE this process</a>] (Note: may not work)";
    opsList.appendChild(opB);
    
    instuctArea.appendChild(opsList);
    
    var progArea = document.createElement('div');
    progArea.setAttribute('class', 'sum-div-cell-bar');
    pFloatingPane.appendChild(progArea);
    
    var progBar = document.createElement('div');
    progBar.setAttribute('id', 'reconProgBar');
    progArea.appendChild(progBar);

    var bottomArea = document.createElement('div');
    bottomArea.setAttribute('style', 'padding-top:5px; padding-bottom:5px;');
    pFloatingPane.appendChild(bottomArea);
    
    var messageArea = document.createElement('p');
    messageArea.setAttribute('id', 'reconProgMessage');
    bottomArea.appendChild(messageArea);


    pFloatingPane = new dojox.layout.FloatingPane({
            title: ("Progress Reconciling '" + colName + "' with " + reconName),
            resizable: true,
            dockable: false,
            id: "reconProgressPane",
            style: "width:40%; position:absolute; top:50%; left:30%;"
        },
        dojo.byId("reconProgressPane"));
    pFloatingPane.startup();
    pFloatingPane.show();
    
}


function AttemptReconFinish(){
    
    //use legacyParams to set key parameters
    reconKey = legacyParams.reconKey;
    colName = legacyParams.baseColumnName;
    colIndex = legacyParams.baseColumnIndex;
    reconProgress = 0;
    finishIndex = 1;
    
    var opsList = document.getElementById( "legacyOps");
    opsList.setAttribute('style', 'display:none;'); //hide the options
    
    var messageArea = document.getElementById( "reconProgMessage");
    messageArea.innerHTML = "Attempting to complete, [<a href=\"javascript:ReconCancelConfirm();\">CANCEL this process</a>]"; //add a cancel button
    
    ReconCheckFinish(); //try finishing!
    
}


//AJAX call to check progress, and if finished, have Google Refine generate a new column by parsing raw results of the entity recon. service
function ReconCheckFinish(){
    
    GRprojectID = document.getElementById( "GRprojectID").value; //set global var for later use
    var myAjax = new Ajax.Request(baseURI,
        {method: 'post',
        parameters:
        {
            page: "DataRefine/data-doc-col-reconcile-finish",
            reconKey: reconKey,
            baseColumnName: colName,
            baseColumnIndex: colIndex,
            test: testing,
            progress: reconProgress,
            finishIndex: finishIndex,
            grProject: GRprojectID
        },
        onComplete: ReconCheckFinishProgress }
    );
    
}

//AJAX response from checking on / finishing the entity reconcilation
function ReconCheckFinishProgress(response){

    var respData = JSON.parse(response.responseText);
    
    if(!respData.refineOK){
        alert("Problem: ".$respData.error);
    }
    else{
        reconProgress = respData.progress;
        finishIndex = respData.nextFinishIndex;
        reconCheckProgress();
    }

}



//AJAX call to clean-up the results, asks Google Refine to remove the column with raw data from the entity recon. service
function ReconCheckCleanUp(){
    
    GRprojectID = document.getElementById( "GRprojectID").value; //set global var for later use
    var myAjax = new Ajax.Request(baseURI,
        {method: 'post',
        parameters:
        {
            page: "DataRefine/data-doc-col-reconcile-cleanup",
            reconKey: reconKey,
            baseColumnName: colName,
            test: testing,
            grProject: GRprojectID
        },
        onComplete: ReconCleanUpFinish }
    );
    
}



function ReconCleanUpFinish(response){

    var respData = JSON.parse(response.responseText);
    
    if(!respData.refineOK){
        alert("Problem: ".$respData.error);
    }
    else{
        legacyParams = false;
        var messageArea = document.getElementById( "reconProgMessage");
        var doneLink = document.getElementById("RefineProjURL").value;
        var doneMessage = "DONE. Clean-up complete. [<a href=\"javascript:window.open('"+ doneLink + "')\" title=\"Review and edit with Google Refine\" >View Results</a>] ";
        doneMessage = doneMessage + " Tracked Here [<a href=\"../view.php?id=" + respData.newIssue + " \">Issue: " + respData.newIssue + " </a>]";
        messageArea.innerHTML = doneMessage;
    }

}


function ReconCancelConfirm(){
    
    if(confirm("Cancel ongoing reconcilation process?")){
        
        legacyParams = false;
        reconProgress = 100;
        finishIndex = 0;
        cancelProcess = true;
        
        GRprojectID = document.getElementById( "GRprojectID").value; //set global var for later use
        var myAjax = new Ajax.Request(baseURI,
        {method: 'post',
        parameters:
        {
            page: "DataRefine/data-doc-col-reconcile-cancel",
            grProject: GRprojectID
        },
        onComplete: ReconCancelFinish }
        ); 
    }
    else{
        //do nothing;
    }
     
}


function ReconCancelFinish(response){
    
    var respData = JSON.parse(response.responseText);
    
    if(!respData.refineOK){
        alert("Problem: ".$respData.error);
    }
    else{
        var messageArea = document.getElementById( "reconProgMessage");
        messageArea.innerHTML = "Reconciliation process CANCELED by user.";
    }
    
}




/*

END: Entity Reconciliation

*/