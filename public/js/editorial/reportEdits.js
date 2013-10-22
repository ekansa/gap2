

function autoSuggestPaths(){
   
    var filenameDom = document.getElementById('MediaNewFileName');
    var prefixDom = document.getElementById('MediaNewPathPrefix');
    
    var filename = encodeURIComponent(filenameDom.value);
    var prefixPath = prefixDom.value;
    
    var fullDom = document.getElementById('MediaNewFull');
    var previewDom = document.getElementById('MediaNewPreview');
    var thumbDom = document.getElementById('MediaNewThumb');
    
    fullDom.value = prefixPath + "/full/" + filename;
    previewDom.value = prefixPath + "/preview/" + filename;
    thumbDom.value = prefixPath + "/thumbs/" + filename;
}




//checks on the new media files, if they are there
function checkNewFiles(){
    
    var fullDom = document.getElementById('MediaNewFull');
    var previewDom = document.getElementById('MediaNewPreview');
    var thumbDom = document.getElementById('MediaNewThumb');
    
    var fullfile = fullDom.value;
    var preview = previewDom.value;
    var thumb = thumbDom.value;
    
    var checkURI = "../editorial/check-media-files";
    
    var myAjax = new Ajax.Request(checkURI,
        {   method: 'get',
            parameters:
                {fullfile: fullfile,
                preview: preview,
                thumb: thumb
                },
        onComplete: checkFilesDone }
    );
    
}

//displays results on checking on new media
function checkFilesDone(response){
    var respData = JSON.parse(response.responseText);
    var i = 0;
    for (i=0; i< respData.length; i++){
        var fileType = respData[i].filetype;
        var actDomID = fileType + "-newStatus";
        var actDom = document.getElementById(actDomID);
        var bytes = respData[i].bytes;
        var outputMessage = "<button class=\"btn btn-danger btn-mini\">Not Found!</button>";
        if(bytes > 0){
            var outputMessage = "<button class=\"btn btn-success btn-mini\">" + respData[i].human + "</button>";
        }
        actDom.innerHTML = outputMessage;
    }
}    

function updateClass(classLabel){
    var labelDom = document.getElementById('itemClassName');
    var uuidDom = document.getElementById('itemClassUUID');
    labelDom.innerHTML = classLabel;
    var selectedClassUUID = getCheckedRadio("itemClass");
    uuidDom.value = selectedClassUUID;
}



//validates a document's XHTML
//checks on the new media files, if they are there
function checkXHTML(){
    
    var textDom = document.getElementById('DocNewContent');
    var XHTMLstring = textDom.value;
    
    var checkURI = "../editorial/validate-xhtml";
    
    var myAjax = new Ajax.Request(checkURI,
        {   method: 'post',
            parameters:
                {xhtml: XHTMLstring
                },
        onComplete: checkXHTMLDone }
    );
    
}


//displays results on checking XHTML validity
function checkXHTMLDone(response){
    var respData = JSON.parse(response.responseText);
    var XHTMLstatusDom = document.getElementById('docXHTMLval');
    if(respData.valid){
        var outputMessage = "<button class=\"btn btn-success btn-mini\">Valid</button>";
    }
    else{
        var outputMessage = "<button class=\"btn btn-danger btn-mini\">Invalid!</button>";
    }
    XHTMLstatusDom.innerHTML = outputMessage;
}   



//validates a document's XHTML
//checks on the new media files, if they are there
function getParentData(){
    
    var parentDom = document.getElementById('SubjectNewParUUID');
    var parentUUID = parentDom.value;
    
    if(parentUUID != "none"){
        var checkURI = "../xml/space";
        var myAjax = new Ajax.Request(checkURI,
            {   method: 'get',
                parameters:
                    {id: parentUUID
                    },
            onComplete: getParentDataDone }
        );
    }
}


//displays results on checking a parentUUID
function getParentDataDone(response){
    var respData = JSON.parse(response.responseText);
    var parentDataDom = document.getElementById('SubjectParData');
    var outputMessage = "<p><small>";
    outputMessage += respData.label + " (" + respData.className + ")";
    outputMessage += "</small></p>";
    parentDataDom.innerHTML = outputMessage;
} 





function updateLinkedItemType(linkedItemSource){
    actLinkedItemSource = linkedItemSource;
    if(actLinkedItemSource == "newSubject"){
        var linkedTypeDom = document.getElementById('SubjectNewLinkedItemType');
        var itemType = getCheckedRadio("newSubjectLinkedType");
        linkedTypeDom.value = itemType;
    }
    else if(actLinkedItemSource == "newMedia"){
        var linkedTypeDom = document.getElementById('MediaNewLinkedItemType');
        var itemType = getCheckedRadio("newMediaLinkedType");
        linkedTypeDom.value = itemType;
    }
    else if(actLinkedItemSource == "newDoc"){
        var linkedTypeDom = document.getElementById('DocNewLinkedItemType');
        var itemType = getCheckedRadio("newDocLinkedType");
        linkedTypeDom.value = itemType;
    }
    else if(actLinkedItemSource == "newPerson"){
        var linkedTypeDom = document.getElementById('PersonNewLinkedItemType');
        var itemType = getCheckedRadio("newPersonLinkedType");
        linkedTypeDom.value = itemType;
    }
    else if(actLinkedItemSource == "newLink"){
        var linkedTypeDom = document.getElementById('LinkNewLinkedItemType');
        var itemType = getCheckedRadio("newLinkLinkedType");
        linkedTypeDom.value = itemType;
    }
    getLinkingItemData(actLinkedItemSource);
}




var actLinkItemType;
var actLinkedItemSource;
//prepare a AJAX call to get data about the item used in a linking relationship
function getLinkingItemData(linkedItemSource){
    actLinkedItemSource = linkedItemSource;
    if(actLinkedItemSource == "newSubject"){
        var uuidDom = document.getElementById('SubjectNewLinkedUUID');
        var linkedItemUUID = uuidDom.value;
        var itemType = getCheckedRadio("newSubjectLinkedType");
    }
    else if(actLinkedItemSource == "newMedia"){
        var uuidDom = document.getElementById('MediaNewLinkedUUID');
        var linkedItemUUID = uuidDom.value;
        var itemType = getCheckedRadio("newMediaLinkedType");
    }
    else if(actLinkedItemSource == "newDoc"){
        var uuidDom = document.getElementById('DocNewLinkedUUID');
        var linkedItemUUID = uuidDom.value;
        var itemType = getCheckedRadio("newDocLinkedType");
    }
    else if(actLinkedItemSource == "newPerson"){
        var uuidDom = document.getElementById('PersonNewLinkedUUID');
        var linkedItemUUID = uuidDom.value;
        var itemType = getCheckedRadio("newPersonLinkedType");
    }
    else if(actLinkedItemSource == "newLink"){
        var uuidDom = document.getElementById('LinkNewLinkedUUID');
        var linkedItemUUID = uuidDom.value;
        var itemType = getCheckedRadio("newLinkLinkedType");
    }
    AJAXgetLinkingItemData(itemType, linkedItemUUID);
}


//gets some information about an item do describe it for linking
function AJAXgetLinkingItemData(itemType, linkedItemUUID){
    actLinkItemType = itemType;
    if(itemType == "subject"){
        var checkURI = "../xml/space";
    }
    else if(itemType == "media"){
        var checkURI = "../xml/media";
    }
    else if(itemType == "document"){
        var checkURI = "../xml/document";
    }
    else if(itemType == "project"){
        var checkURI = "../xml/project";
    }
    else if(itemType == "person"){
        var checkURI = "../xml/person";
    }
    
    var myAjax = new Ajax.Request(checkURI,
            {   method: 'get',
                parameters:
                    {id: linkedItemUUID
                    },
            onComplete: getLinkingItemDone }
        );
}

//displays results of getting some information about the item being linked
function getLinkingItemDone(response){
    var respData = JSON.parse(response.responseText);
    
    var outputMessage = "<p><small>";
    if(actLinkItemType == "subject"){
        outputMessage += respData.label + " (" + respData.className + ")";
    }
    else if(actLinkItemType == "media"){
        outputMessage += respData.label + "<br/><img src=\"" + respData.thumbURI + "\" />";
    }
    else if(actLinkItemType == "document"){
        outputMessage += respData.label;
    }
    else if(actLinkItemType == "project"){
        outputMessage += respData.projectName;
    }
    else if(actLinkItemType == "person"){
        outputMessage += respData.label;
    }
    
    outputMessage += "</small></p>";
    //alert(actLinkedItemSource + outputMessage);
    var displayDataDomID = actLinkedItemSource + "LinkedItemData";
    var displayDataDom = document.getElementById(displayDataDomID);
    displayDataDom.innerHTML = outputMessage;

}




//select a classUUID for a new item
function updateNewClass(classLabel){
    var labelDom = document.getElementById('SubjectNewClassName');
    var uuidDom = document.getElementById('SubjectNewClassUUID');
    labelDom.innerHTML = classLabel;
    var selectedClassUUID = getCheckedRadio("newItemClass");
    uuidDom.value = selectedClassUUID;
}




function getCheckedRadio(radioName) {
    var radios = document.getElementsByName(radioName);
    var radioValue = false;
    for(var i = 0; i < radios.length; i++){
        if(radios[i].checked){
            radioValue = radios[i].value;
        }
    }
    return radioValue;
}







