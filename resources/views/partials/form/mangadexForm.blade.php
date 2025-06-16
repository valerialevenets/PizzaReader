@extends('layouts.admin')
@section('content')
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-12">
                    <h3 class="mt-1 float-start">@yield('card-title')</h3>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form action="@yield('form-action')" method="POST" enctype="multipart/form-data">
{{--                @csrf--}}
{{--                @yield('method')--}}
{{--                <input {{ isset($required) && $required ? 'required' : '' }} type="text" maxlength="{{ $max ?? '191' }}"--}}
{{--                       class="form-control @error($field) is-invalid @enderror {{ (isset($disabled) && $disabled) || (isset($clear) && $clear) ? 'col-sm-10' : 'col-sm-12' }}"--}}
{{--                       name="{{ $field }}" id="{{ $field }}" placeholder="{{ $label }}" pattern="{{ $pattern ?? '.*' }}"--}}
{{--                       value="@yield($field)" {{ isset($disabled) && $disabled ? 'disabled' : '' }}>--}}
                <div class="d-grid gap-2 max-auto">
                    <button type="submit" id="submit" class="btn btn-lg btn-success">Save</button>
                </div>
            </form>
        </div>
    </div>
@endsection
