@extends('admin.comics.main')
@section('list-title', 'Your Comics')
@section('list-buttons')
    <a href="{{ route('admin.comics.create') }}" class="btn btn-success ms-3">Add comic</a>
    <a href="{{ route('admin.comics.createFromMangadex') }}" class="btn btn-success ms-3">Add from Mangadex</a>
@endsection
@section('list')
    <div class="list">
        @foreach($comics as $comic)
            <div class="item">
                <h5 class="mb-0">
                    <a href="{{ route('admin.comics.show', $comic->slug) }}" class="filter">{{ $comic->name }}</a>
                </h5>
                <span class="small">
                    <a href="{{ route('admin.comics.chapters.create', $comic->slug) }}">Add chapter</a>
                    @if(Auth::user()->hasPermission("manager"))
                        <span class="spacer">|</span>
                        <a href="{{ route('admin.comics.destroy', $comic->id) }}"
                            data-bs-toggle="modal" data-bs-target="#modal-container" data-description="Do you want to delete this comic and its relative chapters?" data-form="destroy-comic-form-{{ $comic->id }}">Delete comic</a>
                        <form id="destroy-comic-form-{{ $comic->id }}" action="{{ route('admin.comics.destroy', $comic->id) }}"
                            method="POST" class="d-none">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endif
                    <span class="spacer">|</span><a href="{{ route('admin.comics.stats', $comic->slug) }}" target="_blank">Stats</a>
                    <span class="spacer">|</span><a href="{{ asset(substr(\App\Models\Comic::getUrl($comic), 1)) }}" target="_blank">Read</a>
                </span>
            </div>
        @endforeach
    </div>
@endsection
