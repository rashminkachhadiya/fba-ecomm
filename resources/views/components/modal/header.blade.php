@props(['title'])

<div class="modal-header">
    <h3 class="modal-title" id='modelHeading'>{{ ($title) ?? '' }}</h3>
    <!--begin::Close-->
    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal"
        aria-label="Close"><i class="fa fa-times" aria-hidden="true"></i>
        <span class="svg-icon svg-icon-1"></span>
    </div>
    <!--end::Close-->
</div>