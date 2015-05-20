//Afficher le modal de création d'un type de lieu
function creerTypelieu()
{
    $("#modalSaveTypelieu").modal('show');
}

//Afficher le modal de modification d'un type de lieu
function modifTypelieuForm(idTypelieu)
{
    console.log("1");
    console.log("3");
    $('#modalEditTypelieu').children().remove();
    $('#modalEditTypelieu').remove();
        
    $.ajax({
        type: "POST",
        url: Routing.generate('site_carto_afficheEditTypelieu'),
        cache: false,
        data: {"idTypelieu" : idTypelieu},
        success: function(data){
            $('body').append(data);
            $("#modalEditTypelieu").modal('show');
            console.log("2");
        }
    });
}

//Modification d'un type de lieu
function modifTypelieu(idTypelieu)
{
    $.ajax({
        type: "POST",
        url: Routing.generate('site_carto_editTypelieu'),
        data: {"idTypelieu" : idTypelieu},
        cache: false,
        success: function(){
            document.location.href=Routing.generate('site_carto_saveTypelieu');
        }
    });
}

//Afficher le modal de confirmation de suppression d'un type de lieu
function supprTypelieuConfirm(idTypelieu)
{
    console.log("1");
    $('#modalWarningDelete').children().remove();
    $('#modalWarningDelete').remove();
        
    $.ajax({
        type: "POST",
        url: Routing.generate('site_carto_afficheDeleteTypelieu'),
        cache: false,
        data: {"idTypelieu" : idTypelieu},
        success: function(data){
            $('body').append(data);
            $("#modalWarningDelete").modal('show');
            console.log("2");
        }
    });
}

//Suppression d'un type de lieu
function suppressionTypelieu(idTypelieu)
{    
    $.ajax({
        type: "POST",
        url: Routing.generate('site_carto_deleteTypelieu'),
        data: {"idTypelieu" : idTypelieu},
        cache: false,
        success: function(){
            document.location.href=Routing.generate('site_carto_saveTypelieu');
        }
    });
}



//Envoi du formulaire de modification
function envoiFormModif()
{
    var data = $('#editTypelieu').serialize();
    
    $.ajax({
        type: "POST",
        url: Routing.generate('site_carto_editTypelieu'),
        cache: false,
        data: data,
        success: function(){
            document.location.href=Routing.generate('site_carto_typelieu')
        }
    });
}