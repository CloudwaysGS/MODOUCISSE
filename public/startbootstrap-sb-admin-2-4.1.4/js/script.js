console.log('ok')
alert('ok')
function exportData() {
    // Récupérer les données du formulaire
    const form = document.getElementById('my-form');
    const formData = new FormData(form);
    const data = [];
    for (let pair of formData.entries()) {
        data.push(pair);
    }

    // Extraire les noms de clé
    const keys = Object.keys(data[0]);

    // Créer un tableau qui contient les valeurs de chaque clé pour chaque paire de données
    const rows = data.map(row => Object.values(row));

    // Insérer la ligne d'en-tête au début du tableau
    rows.unshift(keys);

    // Créer un fichier CSV
    const csvContent = "data:text/csv;charset=utf-8," + rows.map(row => row.join(",")).join("\n");
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "data.csv");
    document.body.appendChild(link); // Required for FF

    // Cliquez sur le lien pour télécharger le fichier
    link.click();
}

function creationLignes(name,id,tr,ligneMontantHT,type,typezone) {
    var td = document.createElement("td");
    var input = document.createElement("input");
    input.setAttribute("type",typezone);
    input.setAttribute("name",name);
    input.setAttribute("id",id);
    if (type!=undefined){
        input.setAttribute("class",type);
        if (type=="element") input.addEventListener("change", produit, false);
    }
    td.appendChild(input);
    tr.appendChild(td);
    var parent = ligneMontantHT.parentNode;
    parent.insertBefore(tr,ligneMontantHT);
}

function ajoutLignes() {
    var lignes = document.getElementsByClassName("ligne")
    var numero = lignes.length+1;
    var ligneMontantHT = document.getElementById("montantHT");
    var tr = document.createElement("tr");
    tr.setAttribute("class","ligne");
    tr.setAttribute("id","ligne"+numero);
    creationLignes("designation[]","designation"+numero,tr,ligneMontantHT,"text");
    creationLignes("quantite[]","quantite"+numero,tr,ligneMontantHT,"element","text");
    creationLignes("prix[]","prix"+numero,tr,ligneMontantHT,"element","text");
    creationLignes("montant[]","montant"+numero,tr,ligneMontantHT,"montant");
    creationLignes("sup[]","sup"+numero,tr,ligneMontantHT,"sup","checkbox");
}

function supLignes() {
    var sups= document.getElementsByClassName("sup");
    var j = 0;
    var lignes = Array();
    for (var i = 0; i < sups.length; i++){
        if (sups[i].checked){
            var id = sups[i].getAttribute("id");
            var numero = id.substring(id.length-1, id.length);
            lignes[j] = "ligne"+numero;
            j++;
        }
    }
    for (var i = 0; i < lignes.length; i++){
        var ligne = document.getElementById(lignes[i]);
        var parent = ligne.parentNode;
        parent.removeChild(ligne);
    }
    totalGeneral();
}

function produit() {
    var id = this.getAttribute("id")
    var numero = id.substring(id.length-1, id.length);
    var quantite1 = document.getElementById("quantite"+numero).value;
    var prix1 = document.getElementById("prix"+numero).value;
    var montant1 = document.getElementById("montant"+numero);
    var produit = parseInt(quantite1)*parseInt(prix1);
    if (!isNaN(produit)) montant1.value = produit;
    totalGeneral();
}

function totalGeneral() {
    var montantTotal=0;
    var montants = document.getElementsByClassName("montant")
    for (var i=0; i<montants.length; i++){
        if (!isNaN(parseInt(montants[i].value))) montantTotal = montantTotal+parseInt(montants[i].value);
    }
    var montantHT = document.getElementById("montantHT1" );
    montantHT.value = montantTotal;
}


var elements = document.getElementsByClassName("element");
for (var i =0; i < elements.length; i++){
    elements[i].addEventListener("change", produit, false);
    elements[i].addEventListener("change", produit, false);
}

var plus = document.getElementById("plus");
plus.addEventListener('click', ajoutLignes, false);

var moins = document.getElementById("moins");
moins.addEventListener('click', supLignes, false);