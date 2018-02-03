function getXmlDoc() {
    let xmlDoc;
    if (window.XMLHttpRequest) {
        xmlDoc = new XMLHttpRequest();
    }
    else {
        xmlDoc = new ActiveXObject("Microsoft.XMLHTTP");
    }
    return xmlDoc;
}

function Post(url, data, callback) {
    const xmlDoc = getXmlDoc();
    xmlDoc.open('POST', url, true);
    xmlDoc.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlDoc.onreadystatechange = function () {
        if (xmlDoc.readyState === 4 && xmlDoc.status === 200) {
            callback(xmlDoc);
        }
    };
    xmlDoc.send(data);
}