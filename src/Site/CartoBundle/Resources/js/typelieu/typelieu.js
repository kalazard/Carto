//Afficher le modal de confirmation de suppression d'un type de lieu
function creerTypelieu()
{
    $("#modalSaveTypelieu").modal('show');
}

//Afficher le modal de confirmation de suppression d'un type de lieu
function supprTypelieuConfirm()
{
    $("#modalWarningDelete").modal('show');
}

//Suppression d'un type de lieu
function suppressionTypelieu(idTypelieu)
{    
    $.ajax({
        type: "POST",
        url: Routing.generate('site_trail_deleteTypelieu'),
        data: {"idTypelieu" : idTypelieu},
        cache: false,
        success: function(){
            document.location.href=Routing.generate('site_trail_saveTypelieu');
        }
    });
}

//Modification d'un type de lieu
function modifTypelieu(idTypelieu)
{
    $('#modalEditTypelieuForm').children().remove();
    $('#modalEditTypelieuForm').remove();
    
    $.ajax({
        type: "POST",
        url: Routing.generate('site_trail_editTypelieu'),
        data: {"idTypelieu" : idTypelieu},
        cache: false,
        success: function(data){
            $("body").append(data);
            $("#modalEditTypelieuForm").modal('show');
        }
    });
}