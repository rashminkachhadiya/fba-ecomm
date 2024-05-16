@props(['parent_id', 'child_id', 'title'])

<span id="{{$parent_id}}" style="display: none;">
    <span class="fw-700 me-1">{{$title}}:</span> <span id="{{$child_id}}"></span>
</span>
<span class="mx-2 partition-span" style="display: none;"> |</span>