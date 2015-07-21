// handle confirmation alerts
$('.trigger-alert').on('click', function(e, verified){
    if ( !verified ) {
        e.preventDefault();
        var btn = $(this)[0];
        
        $('#alert-modal').modal('show');
        $('#alert-modal .modal-title').text(btn.dataset.alertTitle);
        $('#alert-modal .modal-body').text(btn.dataset.alertMsg);
        
        $('#alert-modal .alert-continue').on('click', function(){
            $(btn).trigger('click',true);
        });
    }
});

/********** AFFILIATES **************************/
// edit affiliates form modal
$('#edit-affiliate-modal').on('show.bs.modal', function (e) {
	$( "#edit-affiliate-modal .modal-body" ).load('affiliates/edit/' + e.relatedTarget.dataset.deckId);
})

/********** CARDS **************************/
// toggle upcoming/released deck fields
function statusToggle(form) {
    if ( form.find('[name=status]').val() == "Upcoming" ) {
        form.find('[name=filename]').closest('.form-group').hide();
        form.find('[name=count]').closest('.row').hide();
        form.find('[name=worth]').closest('.row').hide();
        form.find('[name=masters]').closest('.form-group').hide();
        form.find('[name=puzzle]').closest('.checkbox').hide();
        form.find('[name=masterable]').closest('.checkbox').hide();
    }
    else {
        form.find('div').show();
    }
}
// edit cards form modal
$('#edit-cards-modal').on('show.bs.modal', function (e) {
	$( "#edit-cards-modal .modal-body" ).load('cards/edit' + e.relatedTarget.dataset.deckStatus + '/' + e.relatedTarget.dataset.deckId, function(){
	    // hide/unhide fields
	    var form = $('#edit-cards-form');
	    statusToggle(form);
	    form.find('[name=status]').on('change', function(){
            statusToggle(form);
        })
	});
})

// new deck form - hide/unhide fields
if ( $('#new-cards-form').length > 0 ) {
    var form = $('#new-cards-form');
    statusToggle(form);
    form.find('[name=status]').on('change', function(){
        statusToggle(form);
    })
}

// deck search
var deckList = new List('decks', { valueNames: [ 'deckname' ] });