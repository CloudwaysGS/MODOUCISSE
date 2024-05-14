$.ajax({
    url: '/facture/add',
    type: 'POST',
    dataType: 'json',
    data: {
        'quantite': quantite, // Remplacez quantite par la quantité que vous voulez ajouter
        'produit': produit // Remplacez produit par l'ID du produit que vous voulez ajouter
    },
    success: function(response) {
        console.log(response); // La réponse du serveur sera affichée dans la console
    },
    error: function(jqXHR, textStatus, errorThrown) {
        console.log(textStatus, errorThrown); // Si une erreur se produit, elle sera affichée dans la console
    }
});
