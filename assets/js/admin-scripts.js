$('#edit-affiliate-modal').on('show.bs.modal', function (e) {
	$( "#edit-affiliate-modal .modal-body" ).load('affiliates/edit/' + e.relatedTarget.dataset.affiliateId);
})