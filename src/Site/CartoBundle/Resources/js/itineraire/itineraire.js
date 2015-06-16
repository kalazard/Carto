function noterIti(idIti)
{
    $('#modalAddNoteForm').remove();
    
    $.ajax({
        type: "POST",
        url: Routing.generate('site_carto_noteItineraireForm'),
        cache: false,
        data: {"idIti" : idIti},
        success: function(data){
            $('body').append(data);
            $("#modalAddNoteForm").modal('show');
           // document.location.href=Routing.generate('site_trail_getByIditineraire', {'id' : idIti});
        }
    });
}